import Foundation
import Security
import SwiftUI

enum AppVersion {
    static func isNewer(_ candidate: String, than current: String) -> Bool {
        func components(_ value: String) -> [Int]? {
            let parts = value.split(separator: ".", omittingEmptySubsequences: false)
            guard !parts.isEmpty, parts.count <= 4 else { return nil }
            let numbers = parts.compactMap { part -> Int? in
                guard !part.isEmpty, part.allSatisfy({ $0.isASCII && $0.isNumber }) else { return nil }
                return Int(part)
            }
            return numbers.count == parts.count ? numbers : nil
        }
        guard let next = components(candidate), let installed = components(current) else { return false }
        for index in 0..<max(next.count, installed.count) {
            let left = index < next.count ? next[index] : 0
            let right = index < installed.count ? installed[index] : 0
            if left != right { return left > right }
        }
        return false
    }
}

// The teacher tools API intentionally returns server-defined forms and fields.
// A lossless JSON value keeps those permission-dependent schemas intact.
indirect enum JSON: Codable, Hashable, Identifiable {
    case object([String: JSON]), array([JSON]), string(String), number(Double), bool(Bool), null
    init(from decoder: Decoder) throws {
        let c = try decoder.singleValueContainer()
        if c.decodeNil() { self = .null }
        else if let v = try? c.decode(Bool.self) { self = .bool(v) }
        else if let v = try? c.decode(Double.self) { self = .number(v) }
        else if let v = try? c.decode(String.self) { self = .string(v) }
        else if let v = try? c.decode([JSON].self) { self = .array(v) }
        else { self = .object(try c.decode([String: JSON].self)) }
    }
    func encode(to encoder: Encoder) throws {
        var c = encoder.singleValueContainer()
        switch self {
        case .object(let v): try c.encode(v)
        case .array(let v): try c.encode(v)
        case .string(let v): try c.encode(v)
        case .number(let v): try c.encode(v)
        case .bool(let v): try c.encode(v)
        case .null: try c.encodeNil()
        }
    }
    subscript(_ key: String) -> JSON { if case .object(let v) = self { return v[key] ?? .null }; return .null }
    var object: [String: JSON] { if case .object(let v) = self { return v }; return [:] }
    var array: [JSON] { if case .array(let v) = self { return v }; return [] }
    var text: String {
        switch self { case .string(let s): return s; case .number(let n): return n == n.rounded() ? String(format: "%.0f", n) : String(n); case .bool(let b): return b ? "Yes" : "No"; default: return "" }
    }
    var flag: Bool { self == .bool(true) }
    var id: String { self["id"].text.isEmpty ? text : self["id"].text }
    var rows: [JSON] { if case .array = self { return array }; return self["data"].array }
    static func strings(_ values: [String: String]) -> JSON { .object(values.mapValues(JSON.string)) }
}
struct Envelope: Decodable { let data: JSON; let meta: JSON? }
struct APIError: LocalizedError {
    let status: Int
    let message: String
    var errorDescription: String? { message }
}
extension Notification.Name { static let edlinkSessionExpired = Notification.Name("edlinkSessionExpired") }
struct API {
    static let base = URL(string: Bundle.main.object(forInfoDictionaryKey: "EdlinkAPIURL") as? String ?? "https://edlink.space/api/v1")!
    var token: String?
    var baseURL: URL = Self.base
    static let secureTransport: URLSession = {
        let configuration = URLSessionConfiguration.ephemeral
        configuration.urlCache = nil
        configuration.httpCookieStorage = nil
        return URLSession(configuration: configuration)
    }()
    var transport: URLSession = Self.secureTransport
    func request(_ path: String, method: String = "GET", body: JSON? = nil) async throws -> Envelope {
        let (data, response) = try await send(path, method: method, body: body)
        guard (200..<300).contains(response.statusCode) else { throw Self.error(data, response.statusCode) }
        return try JSONDecoder().decode(Envelope.self, from: data)
    }
    func send(_ path: String, method: String, body: JSON?) async throws -> (Data, HTTPURLResponse) {
        guard let url = URL(string: baseURL.absoluteString + path) else { throw APIError(status: 0, message: "Invalid request address.") }
        var request = URLRequest(url: url, cachePolicy: .reloadIgnoringLocalCacheData, timeoutInterval: 25)
        request.httpMethod = method
        request.setValue("application/json", forHTTPHeaderField: "Accept")
        if let token { request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization") }
        if let body { request.httpBody = try JSONEncoder().encode(body); request.setValue("application/json", forHTTPHeaderField: "Content-Type") }
        let (data, response) = try await transport.data(for: request)
        guard let response = response as? HTTPURLResponse else { throw APIError(status: 0, message: "Invalid server response.") }
        if response.statusCode == 401, let token { NotificationCenter.default.post(name: .edlinkSessionExpired, object: token) }
        return (data, response)
    }
    func download(_ path: String, filename: String) async throws -> URL {
        let (data, response) = try await send(path, method: "GET", body: nil)
        guard (200..<300).contains(response.statusCode) else { throw Self.error(data, response.statusCode) }
        let directory = FileManager.default.temporaryDirectory.appendingPathComponent("edlink-downloads").appendingPathComponent(UUID().uuidString)
        try FileManager.default.createDirectory(at: directory, withIntermediateDirectories: true)
        let url = directory.appendingPathComponent(URL(fileURLWithPath: filename).lastPathComponent)
        try data.write(to: url, options: .atomic)
        return url
    }
    static func error(_ data: Data, _ status: Int) -> APIError {
        let json = (try? JSONDecoder().decode(JSON.self, from: data)) ?? .null
        let validation = json["errors"].object.values.flatMap(\.array).first?.text
        let message = validation ?? (json["message"].text.isEmpty ? (status == 401 ? "Your session has expired. Please sign in again." : "The request failed (\(status)). Please try again.") : json["message"].text)
        return APIError(status: status, message: status == 409 ? message + " Refresh this screen before making further changes." : message)
    }
}

enum TokenStore {
    static let query: [String: Any] = [kSecClass as String: kSecClassGenericPassword, kSecAttrService as String: "space.edlink.native", kSecAttrAccount as String: "session"]
    static func read() -> String? {
        var q = query; q[kSecReturnData as String] = true; q[kSecMatchLimit as String] = kSecMatchLimitOne
        var result: CFTypeRef?
        guard SecItemCopyMatching(q as CFDictionary, &result) == errSecSuccess, let data = result as? Data else { return nil }
        return String(data: data, encoding: .utf8)
    }
    static func save(_ token: String) throws {
        let attributes: [String: Any] = [kSecValueData as String: Data(token.utf8), kSecAttrAccessible as String: kSecAttrAccessibleWhenUnlockedThisDeviceOnly]
        let status = SecItemUpdate(query as CFDictionary, attributes as CFDictionary)
        if status == errSecItemNotFound {
            guard SecItemAdd(query.merging(attributes) { _, new in new } as CFDictionary, nil) == errSecSuccess else { throw APIError(status: 0, message: "Unable to securely save this session.") }; return
        }
        guard status == errSecSuccess else { throw APIError(status: 0, message: "Unable to securely save this session.") }
    }
    static func clear() { SecItemDelete(query as CFDictionary) }
}

@MainActor final class Session: ObservableObject {
    @Published var user: JSON = .null
    @Published var token: String?
    @Published var locked = UserDefaults.standard.bool(forKey: "edlink.requireUnlock")
    @Published var restoring = true
    @Published var restoreError: String?
    @Published var children: [JSON] = []
    @Published var studentID = ""
    var api: API { API(token: token) }
    var role: String { user["role"].text }
    var isTeacher: Bool { role == "teacher" }
    func scoped(_ path: String) -> String { role == "parent" && !studentID.isEmpty ? path + (path.contains("?") ? "&" : "?") + "student_id=\(studentID)" : path }
    func restore() async {
        restoring = true; restoreError = nil
        guard let saved = TokenStore.read() else { restoring = false; return }
        token = saved
        do { user = try await api.request("/auth/me").data; try await loadChildren() }
        catch let error as APIError where error.status == 401 || error.status == 403 { clear() }
        catch { restoreError = error.localizedDescription }
        restoring = false
    }
    func accept(_ data: JSON) async throws {
        let newToken = data["token"].text
        guard !newToken.isEmpty else { throw APIError(status: 0, message: "The server did not return a session.") }
        try TokenStore.save(newToken); token = newToken; user = data["user"]
        try await loadChildren()
    }
    func loadChildren() async throws {
        if role == "parent" { children = try await api.request("/children").data.rows; studentID = children.first?.id ?? "" }
    }
    func clear() { TokenStore.clear(); try? FileManager.default.removeItem(at: FileManager.default.temporaryDirectory.appendingPathComponent("edlink-downloads")); token = nil; user = .null; children = []; studentID = ""; restoreError = nil }
    func logout() async throws { _ = try await api.request("/auth/logout", method: "POST"); clear() }
}

func query(_ path: String, _ values: [String: String]) -> String {
    var parts = URLComponents(); parts.queryItems = values.filter { !$0.value.isEmpty }.sorted { $0.key < $1.key }.map { URLQueryItem(name: $0.key, value: $0.value) }
    return path + (path.contains("?") ? "&" : "?") + (parts.percentEncodedQuery ?? "").replacingOccurrences(of: "+", with: "%2B")
}
func dayString(_ date: Date) -> String { let formatter = DateFormatter(); formatter.locale = Locale(identifier: "en_US_POSIX"); formatter.dateFormat = "yyyy-MM-dd"; return formatter.string(from: date) }
