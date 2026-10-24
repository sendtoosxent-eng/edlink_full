import SwiftUI

@main struct EdlinkApp: App {
    @StateObject private var session = Session()
    @Environment(\.scenePhase) private var phase
    @AppStorage("edlink.requireUnlock") private var requireUnlock = false
    @AppStorage("edlink.appearance") private var appearance = "light"
    var body: some Scene {
        WindowGroup {
            appContent.environmentObject(session).font(Brand.font(14)).tint(Brand.ink).task { await session.restore() }
            .onReceive(NotificationCenter.default.publisher(for: .edlinkSessionExpired).receive(on: RunLoop.main)) { notice in if let expiredToken = notice.object as? String, expiredToken == session.token { session.clear() } }
            .onChange(of: phase) { _, value in if value == .background && requireUnlock && session.token != nil { session.locked = true } }
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
