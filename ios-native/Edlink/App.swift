import SwiftUI

@main struct EdlinkApp: App {
    @StateObject private var session = Session()
    @Environment(\.scenePhase) private var phase
    @AppStorage("edlink.requireUnlock") private var requireUnlock = false
    @AppStorage("edlink.appearance") private var appearance = "light"
    @StateObject private var appUpdates = StoreUpdates()
    var body: some Scene {
        WindowGroup {
            appContent.environmentObject(session).font(Brand.font(14)).tint(Brand.ink).task { await session.restore() }
            .task { await appUpdates.check() }
            .safeAreaInset(edge: .top, spacing: 0) { if let release = appUpdates.release { StoreUpdateBanner(release: release) } }
            .onReceive(NotificationCenter.default.publisher(for: .edlinkSessionExpired).receive(on: RunLoop.main)) { notice in if let expiredToken = notice.object as? String, expiredToken == session.token { session.clear() } }
            .onChange(of: phase) { _, value in
                if value == .background && requireUnlock && session.token != nil { session.locked = true }
                if value == .active { Task { await appUpdates.check() } }
            }
            .overlay { if phase != .active && session.token != nil { Brand.background.ignoresSafeArea().overlay { BrandMark() } } }
        }
    }
    @ViewBuilder private var appContent: some View {
        #if DEBUG
        if let role = DesignPreview.requestedRole { DesignPreview(role: role) }
        else { liveContent }
        #else
        liveContent
        #endif
    }
    private var liveContent: some View {
        Group {
                if session.restoring { SplashPage() }
                else if let error = session.restoreError {
                    ContentUnavailableView { Label("Unable to reconnect", systemImage: "wifi.exclamationmark") } description: { Text(error) } actions: {
                        Button("Try again") { Task { await session.restore() } }.buttonStyle(.borderedProminent)
                        Button("Sign in again") { session.clear() }
                    }
                } else if session.token == nil { SignInView() }
                else if session.locked { UnlockView() }
                else { MainTabs().preferredColorScheme(appearance == "system" ? nil : appearance == "dark" ? .dark : .light) }

        }
    }

}

@MainActor final class StoreUpdates: ObservableObject {
    struct Release: Decodable { let version: String; let trackId: Int; let bundleId: String }
    private struct Lookup: Decodable { let results: [Release] }
    @Published var release: Release?
    private var lastCheck = Date.distantPast
    private var checking = false
    func check() async {
        #if !DEBUG
        guard !checking, Date().timeIntervalSince(lastCheck) > 6 * 60 * 60,
              let bundle = Bundle.main.bundleIdentifier,
              let version = Bundle.main.infoDictionary?["CFBundleShortVersionString"] as? String else { return }
        checking = true
        lastCheck = Date()
        defer { checking = false }
        var url = URLComponents(string: "https://itunes.apple.com/lookup")!
        let country = Locale.current.region?.identifier.lowercased() ?? "ug"
        url.queryItems = [URLQueryItem(name: "bundleId", value: bundle), URLQueryItem(name: "country", value: country)]
        do {
            let request = URLRequest(url: url.url!, cachePolicy: .reloadIgnoringLocalCacheData, timeoutInterval: 15)
            let (data, response) = try await API.secureTransport.data(for: request)
            guard (response as? HTTPURLResponse)?.statusCode == 200 else { return }
            let results = try JSONDecoder().decode(Lookup.self, from: data).results
            release = results.first { $0.bundleId == bundle && $0.trackId > 0 && AppVersion.isNewer($0.version, than: version) }
        } catch {
            // No network or no published listing must never block school work.
            lastCheck = Date().addingTimeInterval(-6 * 60 * 60 + 5 * 60)
        }
        #endif
    }
}

private struct StoreUpdateBanner: View {
    let release: StoreUpdates.Release
    var body: some View {
        HStack(spacing: 12) {
            Text("Edlink \(release.version) is available.").font(.footnote).foregroundStyle(.white)
            Spacer()
            Link("Update", destination: URL(string: "https://apps.apple.com/app/id\(release.trackId)")!)
                .font(.footnote.bold()).foregroundStyle(.yellow).padding(.vertical, 12)
        }
        .padding(.horizontal, 16).background(Color(red: 0.145, green: 0.149, blue: 0.255))
    }
}
struct ResourceView: View {
    @EnvironmentObject private var session: Session
    let title: String
    let path: String
    var notifications = false
    @State private var data: JSON = .null
    @State private var rows: [JSON] = []
    @State private var page = 1
    @State private var lastPage = 1
    @State private var loading = false
    @State private var error: String?
    var body: some View {
        List {
            PageHeroRow(title: title, subtitle: notifications ? "The latest updates from your school community." : "Stay connected to your school.", artwork: "School")
            ChildSwitcher().listRowInsets(EdgeInsets()).listRowBackground(Color.clear)
            if let error { ErrorBanner(message: error); Button("Try again") { Task { await load(reset: true) } } }
            if loading { ProgressView() }
            if !loading && rows.isEmpty && error == nil && data.object.isEmpty { ContentUnavailableView("Nothing here yet", systemImage: "tray", description: Text("Your school will share updates here when available.")) }
            if !rows.isEmpty {
                ForEach(Array(rows.enumerated()), id: \.offset) { _, item in
                    NavigationLink { ResourceDetail(title: title, item: item) } label: { NoticeCard(item: item) }
                }
            } else if !data.object.isEmpty && data["data"] == .null { DetailsView(value: data) }
            if page < lastPage { Button("Load more") { Task { await load(reset: false) } }.disabled(loading) }
        }.edlinkScreen().navigationTitle(title).refreshable { await load(reset: true) }.task { await load(reset: true) }
            .toolbar { if notifications && !rows.isEmpty { Button("Mark read") { Task { do { _ = try await session.api.request("/notifications/read", method: "POST", body: .object(["notification_ids": .array(rows.map { $0["id"] })])); await load(reset: true) } catch { self.error = error.localizedDescription } } } } }
    }
    func load(reset: Bool) async {
        loading = true; error = nil
        let next = reset ? 1 : page + 1
        do {
            let response = try await session.api.request(session.scoped(query(path, ["page": String(next)])))
            data = response.data; rows = reset ? data.rows : rows + data.rows; page = next; lastPage = Int(data["last_page"].text) ?? 1
        } catch { self.error = error.localizedDescription }; loading = false
    }
}
struct SchoolHub: View {
    @EnvironmentObject private var session: Session
    var body: some View {
        List {
            Section("School life") {
                NavigationLink("Attendance") { AttendanceHome() }
                NavigationLink("Announcements") { ResourceView(title: "Announcements", path: "/announcements") }
                NavigationLink("School calendar") { SchoolCalendarView() }
                NavigationLink("Notifications") { ResourceView(title: "Notifications", path: "/notifications", notifications: true) }
            }
            if session.isTeacher {
                Section("Teaching") {
                    NavigationLink("Exam marks") { MarksList() }
                    NavigationLink("Learner directory") { DirectoryView() }
                    NavigationLink("My subjects") { ResourceView(title: "Teaching assignments", path: "/teaching-assignments") }
                    NavigationLink("Examination reports") { TeacherReportsView() }
                    NavigationLink("Houses & clubs") { ResourceView(title: "Houses & clubs", path: "/activities") }
                    NavigationLink("Leave requests") { LeaveView() }
                    NavigationLink("Staff tools") { TeacherToolsView() }
                }
            } else {
                Section("Progress") {
                    NavigationLink("Published results") { ResultsView() }
                    NavigationLink("Fees & payments") { PaymentsView() }
                }
            }
        }.edlinkScreen().navigationTitle(session.isTeacher ? "Workspace" : "Your school")
    }
}
