import SwiftUI

struct SchoolCalendarView: View {
    @EnvironmentObject private var session: Session
    @State private var rows: [JSON] = []
    @State private var page = 1
    @State private var lastPage = 1
    @State private var busy = false
    @State private var error: String?
    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 16) {
                Text("School calendar").font(Brand.font(28, .heavy))
                Text("Upcoming dates for your school.").font(Brand.font(13)).foregroundStyle(Brand.muted)
                ChildSwitcher()
                if busy { ProgressView().frame(maxWidth: .infinity) }
                if let error { ErrorBanner(message: error); Button("Try again") { Task { await load(page) } } }
                ForEach(rows) { event in
                    VStack(alignment: .leading, spacing: 12) {
                        Text(displayDate(event["event_date"].text)).font(Brand.font(12, .semibold))
                        Text(event["title"].text).font(Brand.font(18, .bold))
                        if !event["description"].text.isEmpty { Text(event["description"].text).font(Brand.font(13)).lineSpacing(4).foregroundStyle(Brand.muted) }
                    }.padding(6).schoolCard()
                }
                if !busy && rows.isEmpty && error == nil { Text("No upcoming events yet.").font(Brand.font(13)).foregroundStyle(Brand.muted) }
                if lastPage > 1 { HStack { Button("Previous") { Task { await load(page - 1) } }.disabled(busy || page <= 1); Spacer(); Text("\(page) / \(lastPage)").foregroundStyle(Brand.muted); Spacer(); Button("Next") { Task { await load(page + 1) } }.disabled(busy || page >= lastPage) }.font(Brand.font(12, .semibold)) }
            }.frame(maxWidth: 720).padding(22).frame(maxWidth: .infinity)
        }.background(Brand.background).foregroundStyle(Brand.ink).navigationTitle("School calendar").navigationBarTitleDisplayMode(.inline).task { await load(1) }.refreshable { await load(1) }
    }
    private func load(_ number: Int) async { busy = true; error = nil; do { let data = try await session.api.request("/events?page=\(number)").data; rows = data.rows; page = number; lastPage = Int(data["last_page"].text) ?? 1 } catch { self.error = error.localizedDescription }; busy = false }
}
