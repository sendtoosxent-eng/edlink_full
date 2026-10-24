import SwiftUI
import UniformTypeIdentifiers

struct HomeworkList: View {
    @EnvironmentObject private var session: Session
    @State private var rows: [JSON] = []
    @State private var page = 1
    @State private var lastPage = 1
    @State private var busy = false
    @State private var error: String?
    @State private var creating = false
    @State private var filter = "pending"
    var body: some View {
        List {
            PageHeroRow(title: session.role == "student" ? "My homework" : "Homework", subtitle: session.role == "student" ? "Keep learning, complete your assignments and see your teacher’s feedback." : "Assignments, submissions and teacher reviews in one place", artwork: "Subjects")
            ChildSwitcher().listRowInsets(EdgeInsets()).listRowBackground(Color.clear)
            if session.role == "student" {
                ScrollView(.horizontal, showsIndicators: false) { HStack(spacing: 8) {
                    ForEach(["pending", "submitted", "reviewed", "all"], id: \.self) { value in
                        Button { filter = value } label: { Text(value == "pending" ? "To do" : value.capitalized).font(Brand.font(12, .semibold)).padding(.horizontal, 16).padding(.vertical, 11).foregroundStyle(filter == value ? Brand.gold : Brand.ink).background(filter == value ? Brand.navy : Brand.surface, in: Capsule()).overlay(Capsule().stroke(Brand.outline)) }.buttonStyle(.plain).disabled(busy)
                    }
                } }.listRowInsets(EdgeInsets()).listRowBackground(Color.clear)
            }
            if busy { ProgressView() }
            if let error { ErrorBanner(message: error); Button("Try again") { Task { await load(reset: true) } } }
            ForEach(rows) { item in NavigationLink { HomeworkDetail(assignmentID: item.id) } label: {
                VStack(alignment: .leading, spacing: 14) {
                    Text(item["subject"]["name"].text.isEmpty ? "Homework" : item["subject"]["name"].text).font(Brand.font(11, .semibold)).padding(.horizontal, 10).padding(.vertical, 6).foregroundStyle(Brand.navy).background(Color(hex: 0xFFF5D5), in: Capsule())
                    Text(item["title"].text).font(Brand.font(17, .bold))
                    Label("Due " + displayDate(item["due_at"].text), systemImage: "clock").font(Brand.font(11)).foregroundStyle(Brand.muted)
                    HStack { Text(item["submissions"].array.first?["status"].text.capitalized ?? "To do").font(Brand.font(10, .semibold)).padding(.horizontal, 10).padding(.vertical, 6).background(Brand.background, in: Capsule()); Spacer(); Text(item["submissions"].array.isEmpty ? "Start assignment" : "View submission").font(Brand.font(10, .semibold)) }
                }.padding(.vertical, 10).overlay(alignment: .trailing) { Image("StatSubjects").resizable().scaledToFit().frame(width: 80, height: 80).opacity(0.06).allowsHitTesting(false) }
            } }
            if !busy && rows.isEmpty && error == nil { ContentUnavailableView("You're all caught up", systemImage: "book.closed", description: Text("Assignments from your school appear here.")) }
            if page < lastPage { Button("Load more") { Task { await load(reset: false) } }.disabled(busy) }
        }.edlinkScreen().navigationTitle("Homework").task(id: filter) { await load(reset: true) }.refreshable { await load(reset: true) }
        .toolbar { if session.isTeacher { Button { creating = true } label: { Image(systemName: "plus") }.accessibilityLabel("Create homework") } }
        .sheet(isPresented: $creating, onDismiss: { Task { await load(reset: true) } }) { CreateHomework() }
    }
    func load(reset: Bool) async { busy = true; error = nil; let next = reset ? 1 : page + 1; do { let data = try await session.api.request(session.scoped("/homework?page=\(next)&status=\(session.isTeacher ? "all" : filter)")).data; rows = reset ? data.rows : rows + data.rows; page = next; lastPage = Int(data["last_page"].text) ?? 1 } catch { self.error = error.localizedDescription }; busy = false }
}
struct HomeworkDetail: View {
    @EnvironmentObject private var session: Session
    let assignmentID: String
    @State private var data: JSON = .null
    @State private var answer = ""
    @State private var busy = false
    @State private var loaded = false
    @State private var conflict = false
    @State private var error: String?
    @State private var message: String?
    @State private var attachment: URL?
    @State private var importing = false
    @State private var downloaded: URL?
    var body: some View {
        List {
            if busy { ProgressView() }
            if let error { ErrorBanner(message: error) }
            if let message { Text(message).foregroundStyle(.green) }
            Section { Text(data["title"].text).font(Brand.font(24, .bold)); Text(data["subject"]["name"].text).foregroundStyle(.secondary); Text(data["instructions"].text); LabeledContent("Due", value: data["due_at"].text); LabeledContent("Maximum score", value: data["maximum_score"].text) }
            if !data["attachment_name"].text.isEmpty {
                Button { download("/homework/\(assignmentID)/attachment", name: data["attachment_name"].text) } label: { Label(data["attachment_name"].text, systemImage: "paperclip") }.disabled(busy)
            }
            if let downloaded { ShareLink(item: downloaded) { Label("Open or share downloaded file", systemImage: "square.and.arrow.up") } }
            if session.role == "student" && loaded {
                Section("Your work") {
                    TextEditor(text: $answer).frame(minHeight: 160).accessibilityLabel("Homework answer")
                    Button(attachment?.lastPathComponent ?? "Attach a file (up to 10 MB)") { importing = true }
                    if attachment != nil { Button("Remove attachment", role: .destructive) { attachment = nil } }
                    PrimaryButton(title: "Submit work", busy: busy) { Task { await submit() } }.disabled(conflict || (answer.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty && attachment == nil && data["submissions"].array.first?["attachment_name"].text.isEmpty != false))
                }.disabled(conflict)
            }
            if conflict { Button("Reload latest assignment") { Task { await load() } } }
            ForEach(data["submissions"].array) { submission in
                Section(session.isTeacher ? submission["student"]["name"].text : "Submission") {
                    Text(submission["answer"].text)
                    LabeledContent("Status", value: submission["status"].text.capitalized)
                    if !submission["score"].text.isEmpty { LabeledContent("Score", value: submission["score"].text) }
                    if !submission["feedback"].text.isEmpty { Text(submission["feedback"].text) }
                    if !submission["attachment_name"].text.isEmpty { Button(submission["attachment_name"].text) { download("/homework/\(assignmentID)/submissions/\(submission.id)/attachment", name: submission["attachment_name"].text) }.disabled(busy) }
                    if session.isTeacher { NavigationLink("Review work") { ReviewHomework(assignmentID: assignmentID, submission: submission) } }
                }
            }
        }.edlinkScreen().navigationTitle("Assignment").navigationBarTitleDisplayMode(.inline).task { await load() }
        .toolbar { Button { Task { await load() } } label: { Image(systemName: "arrow.clockwise") }.disabled(busy) }
        .fileImporter(isPresented: $importing, allowedContentTypes: [.pdf, .image, .plainText, .zip, .spreadsheet, .presentation, .content]) { result in do { attachment = try result.get() } catch { self.error = error.localizedDescription } }
    }
    func load() async { busy = true; error = nil; do { data = try await session.api.request(session.scoped("/homework/\(assignmentID)")).data; answer = data["submissions"].array.first?["answer"].text ?? ""; loaded = true; conflict = false } catch { self.error = error.localizedDescription }; busy = false }
    func download(_ path: String, name: String) { busy = true; error = nil; Task { do { downloaded = try await session.api.download(session.scoped(path), filename: name) } catch { self.error = error.localizedDescription }; busy = false } }
    func submit() async {
        busy = true; error = nil; message = nil
        var fields = ["answer": answer]
        if let version = data["submissions"].array.first?["updated_at"].text, !version.isEmpty { fields["base_version"] = version }
        do { _ = try await session.api.upload("/homework/\(assignmentID)/submit", fields: fields, file: attachment); attachment = nil; await load(); message = "Your work has been submitted." }
        catch { self.error = error.localizedDescription; conflict = (error as? APIError)?.status == 409 }; busy = false
    }
}
struct CreateHomework: View {
    @EnvironmentObject private var session: Session
    @Environment(\.dismiss) private var dismiss
    @State private var assignments: [JSON] = []
    @State private var assignmentIndex = 0
    @State private var title = ""
    @State private var instructions = ""
    @State private var maximum = "100"
    @State private var due = Date().addingTimeInterval(86400)
    @State private var file: URL?
    @State private var importing = false
    @State private var busy = false
    @State private var error: String?
    var body: some View {
        NavigationStack {
            Form {
                if let error { ErrorBanner(message: error) }
                Section("Class & subject") {
                    Picker("Teaching assignment", selection: $assignmentIndex) { ForEach(Array(assignments.enumerated()), id: \.offset) { index, item in Text(item["class_name"].text + " · " + item["subject_name"].text).tag(index) } }
                }
                Section("Assignment") { TextField("Title", text: $title); TextField("Instructions", text: $instructions, axis: .vertical).lineLimit(5...12); TextField("Maximum score", text: $maximum).keyboardType(.numberPad); DatePicker("Due", selection: $due, in: Date()...) }
                Button(file?.lastPathComponent ?? "Add attachment") { importing = true }
                PrimaryButton(title: "Publish homework", busy: busy) { Task { await publish() } }.disabled(title.isEmpty || instructions.isEmpty || assignments.isEmpty || !(1...1000).contains(Int(maximum) ?? 0))
            }.edlinkScreen().navigationTitle("New homework").toolbar { Button("Cancel") { dismiss() }.disabled(busy) }
            .task { do { assignments = try await session.api.request("/teaching-assignments").data.rows.filter { !$0["subject_id"].text.isEmpty } } catch { self.error = error.localizedDescription } }
            .fileImporter(isPresented: $importing, allowedContentTypes: [.content]) { result in do { file = try result.get() } catch { self.error = error.localizedDescription } }
        }
    }
    func publish() async {
        guard assignments.indices.contains(assignmentIndex) else { return }; busy = true; error = nil
        let assignment = assignments[assignmentIndex]
        do { _ = try await session.api.upload("/homework", fields: ["school_class_id": assignment["school_class_id"].text, "subject_id": assignment["subject_id"].text, "title": title, "instructions": instructions, "maximum_score": maximum, "due_at": ISO8601DateFormatter().string(from: due)], file: file); dismiss() } catch { self.error = error.localizedDescription }; busy = false
    }
}
struct ReviewHomework: View {
    @EnvironmentObject private var session: Session
    @Environment(\.dismiss) private var dismiss
    let assignmentID: String
    let submission: JSON
    @State private var score = ""
    @State private var feedback = ""
    @State private var busy = false
    @State private var error: String?
    var body: some View {
        Form {
            Text(submission["student"]["name"].text).font(Brand.font(15, .bold))
            Text(submission["answer"].text)
            TextField("Score", text: $score).keyboardType(.decimalPad)
            TextField("Feedback", text: $feedback, axis: .vertical).lineLimit(4...10)
            if let error { ErrorBanner(message: error) }
            PrimaryButton(title: "Save review", busy: busy) {
                guard score.isEmpty || (Double(score) ?? -1) >= 0 else { error = "Enter a valid score."; return }; busy = true
                Task { do { _ = try await session.api.request("/homework/\(assignmentID)/submissions/\(submission.id)/review", method: "POST", body: .object(["score": Double(score).map(JSON.number) ?? .null, "feedback": .string(feedback)])); dismiss() } catch { self.error = error.localizedDescription }; busy = false }
            }
        }.edlinkScreen().navigationTitle("Review work").onAppear { score = submission["score"].text; feedback = submission["feedback"].text }
    }
}
extension API {
    func upload(_ path: String, fields: [String: String], file: URL?) async throws -> Envelope {
        let boundary = "Edlink-" + UUID().uuidString
        var body = Data()
        func append(_ text: String) { body.append(Data(text.utf8)) }
        for (key, value) in fields.sorted(by: { $0.key < $1.key }) { append("--\(boundary)\r\nContent-Disposition: form-data; name=\"\(key)\"\r\n\r\n\(value)\r\n") }
        if let file {
            let access = file.startAccessingSecurityScopedResource(); defer { if access { file.stopAccessingSecurityScopedResource() } }
            let size = try file.resourceValues(forKeys: [.fileSizeKey]).fileSize ?? 0
            guard size <= 10 * 1024 * 1024 else { throw APIError(status: 422, message: "Attachments must be 10 MB or smaller.") }
            let filename = file.lastPathComponent.replacingOccurrences(of: "\"", with: "").replacingOccurrences(of: "\r", with: "").replacingOccurrences(of: "\n", with: "")
            let type = UTType(filenameExtension: file.pathExtension)?.preferredMIMEType ?? "application/octet-stream"
            append("--\(boundary)\r\nContent-Disposition: form-data; name=\"attachment\"; filename=\"\(filename)\"\r\nContent-Type: \(type)\r\n\r\n"); body.append(try Data(contentsOf: file)); append("\r\n")
        }
        append("--\(boundary)--\r\n")
        var request = URLRequest(url: URL(string: baseURL.absoluteString + path)!, cachePolicy: .reloadIgnoringLocalCacheData, timeoutInterval: 60)
        request.httpMethod = "POST"; request.httpBody = body
        request.setValue("multipart/form-data; boundary=\(boundary)", forHTTPHeaderField: "Content-Type"); request.setValue("application/json", forHTTPHeaderField: "Accept")
        if let token { request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization") }
        let (data, response) = try await transport.data(for: request)
        guard let response = response as? HTTPURLResponse else { throw APIError(status: 0, message: "Invalid server response.") }
        if response.statusCode == 401, let token { NotificationCenter.default.post(name: .edlinkSessionExpired, object: token) }
        guard (200..<300).contains(response.statusCode) else { throw Self.error(data, response.statusCode) }
        return try JSONDecoder().decode(Envelope.self, from: data)
    }
}
