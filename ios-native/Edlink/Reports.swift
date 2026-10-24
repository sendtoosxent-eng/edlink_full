import SwiftUI

struct TeacherReportsView: View {
    @EnvironmentObject private var session: Session
    @State private var exams: [JSON] = []
    @State private var error: String?
    @State private var loading = false
    var body: some View {
        List {
            PageHeroRow(title: "Results & report cards", subtitle: "Review learner performance and share school reports", artwork: "Reports")
            if loading { ProgressView() }
            if let error { ErrorBanner(message: error); Button("Try again") { Task { await load() } } }
            ForEach(exams) { exam in NavigationLink { ExamReportView(examID: exam.id) } label: {
                VStack(alignment: .leading, spacing: 6) { Text(exam["name"].text).font(Brand.font(15, .bold)); Text(exam["class_name"].text + " · " + exam["term"].text).font(Brand.font(12)).foregroundStyle(.secondary); Text(exam["published"].flag ? "Published" : "Internal results").font(.caption.bold()) }
            } }
            if !loading && exams.isEmpty && error == nil { ContentUnavailableView("No examinations", systemImage: "chart.bar.doc.horizontal", description: Text("Exams within your academic scope appear here.")) }
        }.edlinkScreen().navigationTitle("Exam reports").task { await load() }.refreshable { await load() }
    }
    func load() async { loading = true; error = nil; do { exams = try await session.api.request("/teacher/exams").data.rows } catch { self.error = error.localizedDescription }; loading = false }
}
struct ExamReportView: View {
    @EnvironmentObject private var session: Session
    let examID: String
    @State private var report: JSON = .null
    @State private var search = ""
    @State private var error: String?
    @State private var loading = false
    var body: some View {
        List {
            if loading { ProgressView() }
            if let error { ErrorBanner(message: error); Button("Try again") { Task { await load() } } }
            Section {
                Text(report["name"].text).font(Brand.font(24, .bold)); Text(report["class_name"].text)
                Text(report["published"].flag ? "Published results" : "Internal results").font(.subheadline.bold())
                if report != .null && !report["readiness"]["ready"].flag { Text("Results are incomplete. Missing marks: " + report["readiness"]["missing_marks"].text + (report["readiness"]["all_papers_approved"].flag ? "" : ". Some papers await approval.")).foregroundStyle(.secondary) }
            }
            ForEach(report["learners"].array.filter { search.isEmpty || ($0["name"].text + " " + $0["admission_no"].text).localizedCaseInsensitiveContains(search) }) { learner in
                Section {
                    Text(learner["name"].text).font(Brand.font(15, .bold))
                    Text(learner["admission_no"].text).font(Brand.font(10)).foregroundStyle(.secondary)
                    LabeledContent("Average", value: learner["average"].text + "%")
                    LabeledContent("Grade", value: learner["grade"].text)
                    LabeledContent("Position", value: learner["position"].text)
                    ForEach(learner["subjects"].array.filter { $0["applicable"].flag }, id: \.self) { subject in LabeledContent(subject["name"].text, value: (subject["score"].text.isEmpty ? "—" : subject["score"].text) + "/" + subject["maximum"].text + " · " + subject["grade"].text) }
                    ShareLink(item: summary(learner)) { Label("Share result summary", systemImage: "square.and.arrow.up") }
                }
            }
        }.edlinkScreen().navigationTitle("Learner results").navigationBarTitleDisplayMode(.inline).searchable(text: $search, prompt: "Find learner results").task { await load() }.refreshable { await load() }
    }
    func summary(_ learner: JSON) -> String {
        let subjects = learner["subjects"].array.filter { $0["applicable"].flag }.map { $0["name"].text + ": " + ($0["score"].text.isEmpty ? "—" : $0["score"].text) + "/" + $0["maximum"].text + " · " + $0["grade"].text }.joined(separator: "\n")
        return "\(report["name"].text) · \(report["class_name"].text)\n\(report["published"].flag ? "Published results" : "Internal results")\n\(learner["name"].text) · \(learner["admission_no"].text)\nAverage: \(learner["average"].text)% · Grade: \(learner["grade"].text) · Position: \(learner["position"].text)\n\n\(subjects)"
    }
    func load() async { loading = true; error = nil; do { report = try await session.api.request("/teacher/exams/\(examID)").data } catch { self.error = error.localizedDescription }; loading = false }
}
struct AttendanceReportView: View {
    @EnvironmentObject private var session: Session
    @State private var from = Calendar.current.date(byAdding: .day, value: -7, to: Date()) ?? Date()
    @State private var to = Date()
    @State private var status = "all"
    @State private var search = ""
    @State private var data: JSON = .null
    @State private var page = 1
    @State private var busy = false
    @State private var error: String?
    var body: some View {
        List {
            PageHeroRow(title: "Attendance reports", subtitle: "Review school attendance and learner activity", artwork: "Attendance")
            Section("Filters") {
                DatePicker("From", selection: $from, displayedComponents: .date)
                DatePicker("To", selection: $to, in: from..., displayedComponents: .date)
                Picker("Status", selection: $status) { ForEach(["all", "present", "absent", "late", "excused"], id: \.self) { Text($0.capitalized).tag($0) } }
                TextField("Learner name", text: $search)
                Button("Apply filters") { Task { await load(1) } }.disabled(busy)
            }
            if busy { ProgressView() }
            if let error { ErrorBanner(message: error) }
            Section("Summary") { ForEach(data["counts"].object.keys.sorted(), id: \.self) { key in LabeledContent(key.capitalized, value: data["counts"][key].text) } }
            ForEach(data["records"].rows) { DataCard(item: $0) }
            HStack { if page > 1 { Button("Previous") { Task { await load(page - 1) } } }; Spacer(); Text("Page \(page)").font(Brand.font(10)); Spacer(); if page < (Int(data["records"]["last_page"].text) ?? 1) { Button("Next") { Task { await load(page + 1) } } } }.disabled(busy)
        }.edlinkScreen().navigationTitle("Attendance report").task { await load(1) }.onChange(of: from) { _, value in if to < value { to = value } }
    }
    func load(_ next: Int) async { busy = true; error = nil; do { data = try await session.api.request(query("/teacher/attendance-report", ["from": dayString(from), "to": dayString(to), "status": status == "all" ? "" : status, "search": search, "page": String(next)])).data; page = next } catch { self.error = error.localizedDescription }; busy = false }
}
struct ResourceDetail: View {
    @EnvironmentObject private var session: Session
    let title: String
    let item: JSON
    @State private var download: URL?
    @State private var error: String?
    @State private var busy = false
    var body: some View {
        List {
            DetailsView(value: .object(item.object.filter { !["attachment_path", "attachment_name", "id", "school_id", "user_id", "student_id"].contains($0.key) }))
            if !item["attachment_name"].text.isEmpty && item["attachment_path"].text.hasPrefix("/") {
                Button(item["attachment_name"].text) { busy = true; Task { do { download = try await session.api.download(session.scoped(item["attachment_path"].text), filename: item["attachment_name"].text) } catch { self.error = error.localizedDescription }; busy = false } }.disabled(busy)
            }
            if busy { ProgressView() }
            if let error { ErrorBanner(message: error) }
            if let download { ShareLink(item: download) { Label("Open or share attachment", systemImage: "square.and.arrow.up") } }
        }.edlinkScreen().navigationTitle(item["title"].text.isEmpty ? title : item["title"].text).navigationBarTitleDisplayMode(.inline)
    }
}
