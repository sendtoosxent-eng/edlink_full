import SwiftUI

struct AttendanceHistoryView: View {
    @EnvironmentObject private var session: Session
    @State private var records: [JSON] = []
    @State private var page = 1
    @State private var lastPage = 1
    @State private var busy = false
    @State private var error: String?
    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 16) {
                PageHero(title: "Attendance", subtitle: "Recent school attendance records", artwork: "Attendance")
                ChildSwitcher()
                HStack(spacing: 10) { ForEach(["present", "absent", "late"], id: \.self) { status in VStack(alignment: .leading, spacing: 5) { Text(String(records.filter { $0["status"].text == status }.count)).font(Brand.font(26, .heavy)); Text(status.capitalized).font(Brand.font(10, .semibold)).foregroundStyle(Brand.muted) }.frame(maxWidth: .infinity, alignment: .leading).schoolCard() } }
                if busy { ProgressView().frame(maxWidth: .infinity) }
                if let error { ErrorBanner(message: error); Button("Try again") { Task { await load(reset: true) } } }
                ForEach(records) { row in
                    HStack(spacing: 12) {
                        VStack(alignment: .leading, spacing: 4) { Text(displayDate(row["attendance_date"].text)).font(Brand.font(13, .bold)); Text(row["session_key"].text.isEmpty ? "Daily attendance" : row["session_key"].text).font(Brand.font(10)).foregroundStyle(Brand.muted) }
                        Spacer(); StatusPill(status: row["status"].text)
                    }.schoolCard()
                }
                if !busy && records.isEmpty && error == nil { Text("No attendance records yet.").font(Brand.font(12)).foregroundStyle(Brand.muted) }
                if page < lastPage { PrimaryButton(title: "Load more", busy: busy) { Task { await load(reset: false) } } }
            }.frame(maxWidth: 720).padding(18).frame(maxWidth: .infinity)
        }.background(Brand.background).foregroundStyle(Brand.ink).navigationTitle("Attendance").navigationBarTitleDisplayMode(.inline).task { await load(reset: true) }.refreshable { await load(reset: true) }
    }
    private func load(reset: Bool) async { busy = true; error = nil; let next = reset ? 1 : page + 1; do { let data = try await session.api.request(session.scoped("/attendance?page=\(next)")).data; records = reset ? data.rows : records + data.rows; page = next; lastPage = Int(data["last_page"].text) ?? 1 } catch { self.error = error.localizedDescription }; busy = false }
}
struct StatusPill: View {
    let status: String
    private var palette: (UInt32, UInt32) { switch status { case "present": return (0xDDF2E7, 0x176B5B); case "absent": return (0xF8DEDD, 0xDE3B55); case "late": return (0xF7EBCB, 0x8A6D0B); case "excused": return (0xDEE8F5, 0x1E5180); default: return (0xEEF2F8, 0x0B132B) } }
    var body: some View { Text(status.capitalized).font(Brand.font(10, .semibold)).padding(.horizontal, 11).padding(.vertical, 7).foregroundStyle(Color(hex: palette.1)).background(Color(hex: palette.0), in: Capsule()) }
}
