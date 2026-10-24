import SwiftUI

struct ScheduleView: View {
    @EnvironmentObject private var session: Session
    @State private var day = Date().formatted(.dateTime.weekday(.wide).locale(Locale(identifier: "en_US")))
    @State private var lessons: [JSON] = []
    @State private var busy = false
    @State private var error: String?
    @State private var message: String?
    @State private var selected: JSON?
    private let days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"]
    var body: some View {
        List {
            PageHeroRow(title: "My timetable", subtitle: "Plan your school week and stay ready for every lesson", artwork: "Lessons")
            Section {
                Picker("Day", selection: $day) { ForEach(days, id: \.self) { Text($0).tag($0) } }
            }
            if busy { ProgressView() }
            if let error { ErrorBanner(message: error); Button("Try again") { Task { await load() } } }
            if let message { Text(message).foregroundStyle(.green) }
            ForEach(lessons.filter { $0["day_of_week"].text == day }) { lesson in
                Section {
                    HStack(alignment: .top, spacing: 18) {
                        VStack(alignment: .leading, spacing: 6) { Text(String(lesson["starts_at"].text.prefix(5))).font(Brand.font(15, .bold)); Text(String(lesson["ends_at"].text.prefix(5))).foregroundStyle(.secondary).font(Brand.font(10)) }.monospacedDigit()
                        RoundedRectangle(cornerRadius: 3).fill(Brand.gold).frame(width: 4)
                        VStack(alignment: .leading, spacing: 6) { Text(lesson["subject"].text.isEmpty ? lesson["label"].text : lesson["subject"].text).font(Brand.font(15, .bold)); Text([lesson["class_name"].text, lesson["stream_name"].text].filter { !$0.isEmpty }.joined(separator: " · ")).font(Brand.font(12)).foregroundStyle(.secondary) }
                    }.padding(.vertical, 10)
                    if session.isTeacher && day == Date().formatted(.dateTime.weekday(.wide).locale(Locale(identifier: "en_US"))) {
                        Button("Report that I cannot attend", role: .destructive) { selected = lesson }.disabled(busy)
                    }
                }
            }
            if !busy && lessons.filter({ $0["day_of_week"].text == day }).isEmpty && error == nil { ContentUnavailableView("No lessons scheduled", systemImage: "calendar", description: Text("There are no scheduled lessons for \(day).")) }
        }.edlinkScreen().navigationTitle("Your timetable").task { await load() }.refreshable { await load() }
        .confirmationDialog("Notify your school administrators that you cannot attend this lesson?", isPresented: Binding(get: { selected != nil }, set: { if !$0 { selected = nil } }), titleVisibility: .visible) {
            Button("Report absence", role: .destructive) { if let lesson = selected { Task { await report(lesson) } }; selected = nil }
        }
    }
    func load() async { busy = true; error = nil; do { lessons = try await session.api.request(session.scoped("/timetable")).data.rows } catch { self.error = error.localizedDescription }; busy = false }
    func report(_ lesson: JSON) async { busy = true; error = nil; do { let data = try await session.api.request("/teacher/lessons/\(lesson.id)/absence", method: "POST").data; message = data["message"].text } catch { self.error = error.localizedDescription }; busy = false }
}

struct ResultsView: View {
    @EnvironmentObject private var session: Session
    @State private var exams: [JSON] = []
    @State private var busy = false
    @State private var error: String?
    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 16) {
                PageIntro(title: "Academic results", description: "Track published marks, subject scores, and overall academic performance.")
                ChildSwitcher()
                Text(exams.first?["term"].text ?? "Current Term").font(Brand.font(11, .semibold)).padding(.horizontal, 16).padding(.vertical, 10).background(Brand.surface, in: Capsule()).overlay(Capsule().stroke(Brand.outline))
                if busy { ProgressView().frame(maxWidth: .infinity) }
                if let error { ErrorBanner(message: error); Button("Try again") { Task { await load() } } }
                ForEach(exams) { exam in resultCard(exam) }
                if !busy && exams.isEmpty && error == nil { Text("No published results are available yet.").font(Brand.font(12)).foregroundStyle(Brand.muted).frame(maxWidth: .infinity, minHeight: 100).schoolCard() }
            }.frame(maxWidth: 720).padding(20).frame(maxWidth: .infinity)
        }.background(Brand.background).foregroundStyle(Brand.ink).navigationTitle("Academic results").navigationBarTitleDisplayMode(.inline).task { await load() }.refreshable { await load() }
    }
    private func resultCard(_ exam: JSON) -> some View {
        VStack(alignment: .leading, spacing: 16) {
            HStack { Text(exam["name"].text).font(Brand.font(17, .bold)); Spacer(); Text("Overall Average").font(Brand.font(9, .bold)).padding(8).background(Color(hex: 0xFFF5D5), in: Capsule()).foregroundStyle(Brand.navy) }
            Text((exam["average"].text.isEmpty ? "—" : exam["average"].text) + "%").font(Brand.font(40, .heavy))
            ForEach(exam["papers"].array) { paper in
                HStack(spacing: 12) { Image("StatSubjects").resizable().scaledToFit().frame(width: 24, height: 24).padding(6).background(Color(hex: 0xFFF5D5), in: RoundedRectangle(cornerRadius: 10)); Text(paper["subject"].text).font(Brand.font(12, .semibold)); Spacer(); Text((paper["score"].text.isEmpty ? "—" : paper["score"].text) + "/" + paper["maximum_score"].text).font(Brand.font(12, .bold)) }.padding(.vertical, 3)
            }
        }.schoolCard()
    }
    private func load() async { busy = true; error = nil; do { exams = try await session.api.request(session.scoped("/results")).data.rows } catch { self.error = error.localizedDescription }; busy = false }
}
