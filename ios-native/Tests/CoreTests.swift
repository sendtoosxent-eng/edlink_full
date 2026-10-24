import XCTest
@testable import EdlinkCore

final class MockTransport: URLProtocol {
    static var handler: ((URLRequest) throws -> (Int, Data))?
    override class func canInit(with request: URLRequest) -> Bool { true }
    override class func canonicalRequest(for request: URLRequest) -> URLRequest { request }
    override func startLoading() {
        do {
            let (status, data) = try Self.handler!(request)
            let response = HTTPURLResponse(url: request.url!, statusCode: status, httpVersion: nil, headerFields: ["Content-Type": "application/json"])!
            client?.urlProtocol(self, didReceive: response, cacheStoragePolicy: .notAllowed)
            client?.urlProtocol(self, didLoad: data)
            client?.urlProtocolDidFinishLoading(self)
        } catch { client?.urlProtocol(self, didFailWithError: error) }
    }
    override func stopLoading() {}
}
final class CoreTests: XCTestCase {
    func testEnvelopesPreservePaginationNullsAndConflictVersion() throws {
        let response = try JSONDecoder().decode(Envelope.self, from: Data(#"{"data":{"data":[{"id":42,"score":null,"active":true}],"last_page":3},"meta":{"version":"2026-10-24T09:00:00Z"}}"#.utf8))
        XCTAssertEqual(response.data.rows.first?["id"].text, "42")
        XCTAssertEqual(response.data.rows.first?["score"], .null)
        XCTAssertEqual(response.data.rows.first?["active"].flag, true)
        XCTAssertEqual(response.data["last_page"].text, "3")
        XCTAssertEqual(response.meta?["version"].text, "2026-10-24T09:00:00Z")
        XCTAssertEqual(try JSONDecoder().decode(JSON.self, from: JSONEncoder().encode(response.data)), response.data)
    }
    func testQueriesEncodeSearchAndOmitEmptyFilters() {
        let path = query("/teacher/students", ["search": "Ann & Bob+", "class_id": ""])
        let parts = URLComponents(string: "https://example.test" + path)!
        XCTAssertTrue(path.contains("%2B"))
        XCTAssertEqual(parts.queryItems, [URLQueryItem(name: "search", value: "Ann & Bob+")])
    }
    func testValidationAndConflictMessages() {
        let validation = API.error(Data(#"{"message":"Invalid data","errors":{"records.0.note":["Add a reason."]}}"#.utf8), 422)
        XCTAssertEqual(validation.message, "Add a reason.")
        let conflict = API.error(Data(#"{"message":"Marks changed on another device.","code":"conflict"}"#.utf8), 409)
        XCTAssertTrue(conflict.message.contains("Refresh"))
        XCTAssertEqual(conflict.status, 409)
        XCTAssertTrue(API.error(Data("<html>Forbidden</html>".utf8), 403).message.contains("403"))
    }
    func client() -> API {
        let config = URLSessionConfiguration.ephemeral
        config.protocolClasses = [MockTransport.self]
        return API(token: "test-device-token", baseURL: URL(string: "https://example.test/api/v1")!, transport: URLSession(configuration: config))
    }
    func testRequestsSendBearerTokenAndVersionedJSON() async throws {
        MockTransport.handler = { request in
            XCTAssertEqual(request.url?.path, "/api/v1/exam-papers/8/marks")
            XCTAssertEqual(request.httpMethod, "PUT")
            XCTAssertEqual(request.value(forHTTPHeaderField: "Authorization"), "Bearer test-device-token")
            XCTAssertEqual(request.value(forHTTPHeaderField: "Content-Type"), "application/json")
            return (200, Data(#"{"data":{"saved":2},"meta":{}}"#.utf8))
        }
        let response = try await client().request("/exam-papers/8/marks", method: "PUT", body: .object(["marks": .array([]), "base_version": .string("2026-10-24T09:00:00Z")]))
        XCTAssertEqual(response.data["saved"].text, "2")
    }
    func testUnauthorizedAndMalformedSuccessDoNotBecomeEmptySuccess() async {
        MockTransport.handler = { _ in (401, Data(#"{"message":"Unauthenticated."}"#.utf8)) }
        do { _ = try await client().request("/auth/me"); XCTFail("Expected unauthorized") } catch { XCTAssertEqual((error as? APIError)?.status, 401) }
        MockTransport.handler = { _ in (200, Data("<html>maintenance</html>".utf8)) }
        do { _ = try await client().request("/dashboard"); XCTFail("Expected decoding failure") } catch { XCTAssertTrue(error is DecodingError) }
    }
}
