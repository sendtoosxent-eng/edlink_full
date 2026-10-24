import SwiftUI

struct AttendanceHome: View {
    @EnvironmentObject private var session: Session
    @State private var date = Date()
    @State private var registers: [JSON] = []
    @State private var streams: [JSON] = []
    @State private var canRecord = false
    @State private var error: String?
    @State private var loading = false
    var body: some View {
        Group {
            if session.isTeacher {
                List {
            PageHeroRow(title: "Attendance", subtitle: "Record and review your school registers", artwork: "Attendance")
                    DatePicker("Register date", selection: $date, in: ...Date(), displayedComponents: .date)
                    if loading { ProgressView() }
                    if let error { ErrorBanner(message: error); Button("Try again") { Task { await load() } } }
                    if !canRecord && !loading { Text("Attendance entry is unavailable while the term is closed.").foregroundStyle(.secondary) }
                    ForEach(Array(registers.enumerated()), id: \.offset) { _, register in
                        NavigationLink {
                            RegisterView(register: register, date: dayString(date), streams: streams.filter { $0["school_class_id"] == register["school_class_id"] }, canRecord: canRecord)
                        } label: {
                            VStack(alignment: .leading, spacing: 5) { Text(register["class_name"].text).font(Brand.font(15, .bold)); Text(register["subject_name"].text + " " + register["starts_at"].text).font(Brand.font(12)).foregroundStyle(.secondary) }
                        }
                    }
                    if !loading && registers.isEmpty && error == nil { ContentUnavailableView("No registers", systemImage: "checklist", description: Text("No attendance registers are assigned for this date.")) }
                }.task(id: dayString(date)) { await load() }.refreshable { await load() }
            } else { AttendanceHistoryView() }
        }.edlinkScreen().navigationTitle("Attendance")
    }
    func load() async { loading = true; error = nil; do { let result = try await session.api.request(query("/attendance/registers", ["attendance_date": dayString(date)])).data; registers = result["registers"].array; streams = result["streams"].array; canRecord = result["can_record"].flag } catch { self.error = error.localizedDescription }; loading = false }
}
struct RegisterView: View {
    @EnvironmentObject private var session: Session
    let register: JSON
    let date: String
    let streams: [JSON]
    let canRecord: Bool
    @State private var stream = ""
    @State private var rows: [JSON] = []
    @State private var statuses: [String: String] = [:]
    @State private var notes: [String: String] = [:]
    @State private var version: JSON = .null
    @State private var busy = false
    @State private var loaded = false
    @State private var error: String?
    @State private var message: String?
    @State private var conflict = false
    var context: [String: String] { ["school_class_id": register["school_class_id"].text, "subject_id": register["subject_id"].text, "session_key": register["session_key"].text, "stream_id": register["stream_id"].text.isEmpty ? stream : register["stream_id"].text, "attendance_date": date] }
    var body: some View {
        List {
            PageHeroRow(title: "Class register", subtitle: "Mark attendance and keep every learner accounted for", artwork: "Attendance")
            Section { LabeledContent("Date", value: date); Text(register["subject_name"].text)
                if register["stream_id"].text.isEmpty && !streams.isEmpty {
                    Picker("Stream", selection: $stream) { Text("All streams").tag(""); ForEach(streams) { Text($0["name"].text).tag($0.id) } }.disabled(busy)
                }
            }
            if let error { ErrorBanner(message: error) }
            if let message { Text(message).foregroundStyle(.green) }
            if busy { ProgressView() }
            if conflict { Button("Reload latest register") { Task { await load() } } }
            ForEach(rows, id: \.self) { row in
                let id = row["student"]["id"].text
                Section {
                    Text(row["student"]["name"].text).font(Brand.font(15, .bold))
                    Text(row["student"]["admission_no"].text).font(Brand.font(10)).foregroundStyle(.secondary)
                    Picker("Status", selection: Binding(get: { statuses[id] ?? "present" }, set: { statuses[id] = $0 })) {
                        ForEach(["present", "absent", "late", "excused"], id: \.self) { Text($0.capitalized).tag($0) }
                    }
                    if statuses[id] != "present" { TextField(statuses[id] == "late" ? "Minutes late (1–1440)" : "Reason", text: Binding(get: { notes[id] ?? "" }, set: { notes[id] = $0 })) }
                }.disabled(!canRecord || busy || conflict)
            }
            if loaded && rows.isEmpty { Text("No learners in this register.") }
            if canRecord && !rows.isEmpty { PrimaryButton(title: "Save attendance", busy: busy) { Task { await save() } }.disabled(!loaded || conflict) }
        }.edlinkScreen().navigationTitle(register["class_name"].text).navigationBarTitleDisplayMode(.inline)
        .task(id: stream) { await load() }
        .toolbar { Button { Task { await load() } } label: { Image(systemName: "arrow.clockwise") }.disabled(busy) }
    }
    func load() async {
        busy = true; loaded = false; error = nil; message = nil
        do { let response = try await session.api.request(query("/attendance", context)); rows = response.data.rows; version = response.meta?["version"] ?? .null; statuses = [:]; notes = [:]; for row in rows { let id = row["student"]["id"].text; statuses[id] = row["status"].text; notes[id] = row["note"].text }; loaded = true; conflict = false } catch { self.error = error.localizedDescription }; busy = false
    }
    func save() async {
        for row in rows {
            let id = row["student"]["id"].text; let status = statuses[id] ?? "present"; let note = (notes[id] ?? "").trimmingCharacters(in: .whitespaces)
            if status != "present" && note.isEmpty { error = "Add a reason or minutes late for \(row["student"]["name"].text)."; return }
            if status == "late" && (!(1...1440).contains(Int(note) ?? 0)) { error = "Minutes late must be between 1 and 1440."; return }
        }
        busy = true; error = nil; message = nil
        var payload = context.filter { !$0.value.isEmpty }.mapValues(JSON.string)
        payload["base_version"] = version
        payload["records"] = .array(rows.map { row in let id = row["student"]["id"].text; return .object(["student_id": row["student"]["id"], "status": .string(statuses[id] ?? "present"), "note": .string(notes[id] ?? "")]) })
        do { _ = try await session.api.request("/attendance", method: "POST", body: .object(payload)); await load(); message = "Attendance saved." }
        catch { self.error = error.localizedDescription; conflict = (error as? APIError)?.status == 409 }; busy = false
    }
}
