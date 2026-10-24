import SwiftUI

struct MarksList: View {
    @EnvironmentObject private var session: Session
    @State private var papers: [JSON] = []
    @State private var error: String?
    @State private var loading = true
    var body: some View {
        List {
            PageHeroRow(title: "Enter & review marks", subtitle: "Enter, review and submit learner scores", artwork: "Marks")
            if loading { ProgressView() }
            if let error { ErrorBanner(message: error); Button("Try again") { Task { await load() } } }
            ForEach(papers) { paper in NavigationLink { MarksEditor(paperID: paper.id) } label: {
                VStack(alignment: .leading, spacing: 5) { Text(paper["subject"]["name"].text).font(Brand.font(15, .bold)); Text(paper["exam"]["name"].text + " · " + paper["exam"]["school_class"]["name"].text).font(Brand.font(12)).foregroundStyle(.secondary) }
            } }
            if !loading && papers.isEmpty && error == nil { ContentUnavailableView("No exam papers", systemImage: "doc.text", description: Text("Assigned papers for the current open term appear here.")) }
        }.edlinkScreen().navigationTitle("Exam marks").task { await load() }.refreshable { await load() }
    }
    func load() async { loading = true; error = nil; do { papers = try await session.api.request("/exam-papers").data.rows } catch { self.error = error.localizedDescription }; loading = false }
}
struct MarksEditor: View {
    @EnvironmentObject private var session: Session
    let paperID: String
    @State private var data: JSON = .null
    @State private var version: JSON = .null
    @State private var scores: [String: String] = [:]
    @State private var busy = false
    @State private var error: String?
    @State private var message: String?
    @State private var conflict = false
    @State private var confirmSubmit = false
    var editable: Bool { data["status"].text == "draft" && !conflict }
    var body: some View {
        Form {
            PageHeroRow(title: "Enter marks", subtitle: "Record learner scores and submit for approval", artwork: "Marks")
            Section { Text(data["paper"]["subject"]["name"].text).font(Brand.font(18, .bold)); LabeledContent("Status", value: data["status"].text.capitalized); LabeledContent("Maximum score", value: data["paper"]["maximum_score"].text) }
            if let error { ErrorBanner(message: error) }
            if let message { Text(message).foregroundStyle(.green) }
            if busy { ProgressView() }
            if conflict { Button("Reload latest marks") { Task { await load() } } }
            Section("Learner scores") {
                ForEach(data["students"].array, id: \.self) { row in
                    let id = row["student"]["id"].text
                    HStack { Text(row["student"]["name"].text); Spacer(); TextField("—", text: Binding(get: { scores[id] ?? "" }, set: { scores[id] = $0 })).keyboardType(.decimalPad).multilineTextAlignment(.trailing).frame(width: 85) }.disabled(!editable || busy)
                }
            }
            if editable && !data["students"].array.isEmpty {
                Button("Save draft") { Task { await save(submit: false) } }.disabled(busy)
                Button("Submit for approval") { confirmSubmit = true }.disabled(busy)
            }
        }.edlinkScreen().navigationTitle("Enter marks").navigationBarTitleDisplayMode(.inline).task { await load() }
        .toolbar { Button { Task { await load() } } label: { Image(systemName: "arrow.clockwise") }.disabled(busy) }
        .confirmationDialog("Submit these marks? Submitted marks become read-only.", isPresented: $confirmSubmit, titleVisibility: .visible) { Button("Save & submit") { Task { await save(submit: true) } } }
    }
    func load() async {
        busy = true; error = nil
        do { let response = try await session.api.request("/exam-papers/\(paperID)/marks"); data = response.data; version = response.meta?["version"] ?? .null; scores = [:]; for row in data["students"].array { scores[row["student"]["id"].text] = row["score"].text }; conflict = false } catch { self.error = error.localizedDescription }; busy = false
    }
    func save(submit: Bool) async {
        let maxScore = Double(data["paper"]["maximum_score"].text) ?? 0
        for value in scores.values where !value.isEmpty { guard let number = Double(value), number.isFinite, number >= 0, number <= maxScore else { error = "Scores must be between 0 and \(maxScore)."; return } }
        if submit && data["students"].array.contains(where: { (scores[$0["student"]["id"].text] ?? "").isEmpty }) { error = "Enter a score for every learner before submitting."; return }
        busy = true; error = nil; message = nil
        let marks = data["students"].array.map { row -> JSON in let value = scores[row["student"]["id"].text] ?? ""; return .object(["student_id": row["student"]["id"], "score": Double(value).map(JSON.number) ?? .null]) }
        do {
            _ = try await session.api.request("/exam-papers/\(paperID)/marks", method: "PUT", body: .object(["marks": .array(marks), "base_version": version]))
            if submit { _ = try await session.api.request("/exam-papers/\(paperID)/submit", method: "POST") }
            await load(); message = submit ? "Marks submitted for approval." : "Draft saved."
        } catch { self.error = error.localizedDescription; conflict = (error as? APIError)?.status == 409 }; busy = false
    }
}
