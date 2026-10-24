import SwiftUI

struct TeacherToolsView: View {
    var section: String? = nil
    private var title: String { switch section { case "classes": return "My Classes"; case "teaching": return "Teaching"; case "reports": return "Reports"; case "school": return "My School"; default: return "Staff tools" } }
    @EnvironmentObject private var session: Session
    @State private var tools: [JSON] = []
    @State private var workspace: JSON = .null
    @State private var error: String?
    @State private var busy = false
    private var subtitle: String { switch section { case "classes": return "Learners, registers and class administration"; case "teaching": return "Subjects, homework, marks and timetable"; case "reports": return "Results, report cards and attendance reports"; default: return "Notices, leave, clubs and school services" } }
    private var artwork: String { section == "reports" ? "Reports" : section == "classes" ? "Learners" : section == "school" ? "School" : "Classroom" }
    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 14) {
                PageHero(title: title, subtitle: subtitle, artwork: artwork)
                if busy { ProgressView().frame(maxWidth: .infinity) }
                if let error { ErrorBanner(message: error) }
                if section == "classes" && !workspace["class_teacher_classes"].array.isEmpty {
                    VStack(alignment: .leading, spacing: 12) {
                        Text("Assigned classes").font(Brand.font(13, .semibold))
                        ForEach(workspace["class_teacher_classes"].array) { item in Text(item["name"].text).font(Brand.font(11, .semibold)).padding(.horizontal, 12).padding(.vertical, 7).foregroundStyle(Brand.gold).background(Brand.navy, in: Capsule()) }
                    }.schoolCard()
                }
                if section == "teaching" { NavigationLink { ScheduleView() } label: { WorkspaceToolCard(title: "My weekly timetable", description: "Your assigned lessons, organized by day", artwork: "Lessons") } }
                ForEach(tools) { tool in NavigationLink { destination(tool) } label: { WorkspaceToolCard(title: tool["label"].text, description: description(tool.id), artwork: toolArtwork(tool.id)) } }
                if !busy && error == nil && tools.isEmpty { Text("No tools are assigned here yet. Your school administrator can update your access.").font(Brand.font(12)).foregroundStyle(Brand.muted).schoolCard() }
                PrimaryButton(title: "Refresh tools", busy: busy, secondary: true) { Task { await load() } }
            }.frame(maxWidth: 720).padding(18).padding(.bottom, 20).frame(maxWidth: .infinity)
        }.background(Brand.background).foregroundStyle(Brand.ink).buttonStyle(.plain).navigationTitle(title).navigationBarTitleDisplayMode(.inline).task { await load() }.refreshable { await load() }
    }
    private func toolArtwork(_ id: String) -> String {
        if id.hasPrefix("attendance") { return "Attendance" }; if id.hasPrefix("exams") || id.hasPrefix("grading") { return "Marks" }; if id.contains("student") || id.contains("class") { return "Learners" }; if id.contains("timetable") || id.contains("event") || id.contains("leave") { return "Lessons" }; if id.contains("report") { return "Reports" }; if id.contains("subject") || id.contains("homework") { return "Subjects" }; return artwork
    }
    private func description(_ id: String) -> String {
        switch id {
        case "students.index": return "Find learners and view their school profiles"
        case "attendance.index": return "Complete your daily class registers"
        case "attendance.subject": return "Record attendance for your subject lessons"
        case "attendance.reports": return "Review attendance and export your reports"
        case "exams.marks": return "Enter, review and submit learner scores"
        case "exams.results": return "Published results and learner report cards"
        case "homework.index": return "Set learning tasks and review submissions"
        case "leaves.index": return "View and manage your leave requests"
        default: return "Manage records within your assigned school access"
        }
    }
    @ViewBuilder func destination(_ tool: JSON) -> some View {
        switch tool["id"].text {
        case "students.index": DirectoryView()
        case "attendance.index", "attendance.subject": AttendanceHome()
        case "attendance.reports": AttendanceReportView()
        case "exams.marks": MarksList()
        case "homework.index": HomeworkList()
        case "leaves.index": LeaveView()
        case "notifications.index": ResourceView(title: "Notifications", path: "/notifications", notifications: true)
        case "exams.results": TeacherReportsView()
        default: NativeToolView(tool: tool)
        }
    }
    func load() async { busy = true; error = nil; do { workspace = try await session.api.request("/dashboard").data["teacher_workspace"]; tools = workspace["tools"].array.filter { section == nil || workspaceSection($0["group"].text) == section } } catch { self.error = error.localizedDescription }; busy = false }
}
struct NativeToolView: View {
    @EnvironmentObject private var session: Session
    let tool: JSON
    @State private var data: JSON = .null
    @State private var filters: [String: JSON] = [:]
    @State private var search = ""
    @State private var page = 1
    @State private var busy = false
    @State private var error: String?
    @State private var editing: ToolDraft?
    struct ToolDraft: Identifiable { let id = UUID(); let form: JSON; let values: [String: JSON] }
    var path: String { "/teacher/tools/" + tool["id"].text }
    var body: some View {
        List {
            PageHeroRow(title: tool["label"].text, subtitle: "Manage records within your assigned school access", artwork: "Classroom")
            if busy { ProgressView() }
            if let error { ErrorBanner(message: error); Button("Try again") { Task { await load(1) } } }
            if !data["filters"].array.isEmpty {
                Section("Filters") {
                    ForEach(data["filters"].array, id: \.self) { field in SchemaField(field: field, value: Binding(get: { filters[field["key"].text] ?? field["default"] }, set: { value in filters[field["key"].text] = value; if field["key"].text == "termId" { filters["studentId"] = .string(""); filters["examId"] = .string("") }; if field["key"].text == "studentId" { filters["examId"] = .string("") } })) }
                    Button("Apply filters") { Task { await load(1) } }.disabled(busy)
                }
            }
            if error == nil {
                Section("Actions") { ForEach(data["forms"].array, id: \.self) { form in Button(form["label"].text) { editing = ToolDraft(form: form, values: defaults(form)) }.disabled(busy) } }
            }
            ForEach(data["rows"].array) { row in
                Section {
                    Text(row["title"].text).font(Brand.font(15, .bold))
                    DetailsView(value: row["details"])
                    ForEach(row["members"].array, id: \.self) { member in DataCard(item: member) }
                    if row["values"] != .null, let form = data["forms"].array.first {
                        Button("Edit") { editing = ToolDraft(form: form, values: defaults(form).merging(row["values"].object) { _, new in new }) }.disabled(busy || error != nil)
                    }
                }
            }
            if !busy && data["rows"].array.isEmpty && error == nil { Text("No records found.").foregroundStyle(.secondary) }
            HStack {
                if page > 1 { Button("Previous") { Task { await load(page - 1) } } }
                Spacer()
                Text("Page \(page)").font(Brand.font(10)).foregroundStyle(.secondary)
                Spacer()
                if page < (Int(data["last_page"].text) ?? 1) { Button("Next") { Task { await load(page + 1) } } }
            }.disabled(busy)
        }.edlinkScreen().navigationTitle(tool["label"].text).navigationBarTitleDisplayMode(.inline)
        .searchable(text: $search, prompt: "Search records").onSubmit(of: .search) { Task { await load(1) } }
        .task { await load(1) }.refreshable { await load(page) }
        .sheet(item: $editing, onDismiss: { Task { await load(page) } }) { draft in SchemaForm(path: path, form: draft.form, initialValues: draft.values) }
    }
    func defaults(_ form: JSON) -> [String: JSON] { Dictionary(uniqueKeysWithValues: form["fields"].array.filter { $0["default"] != .null }.map { ($0["key"].text, $0["default"]) }) }
    func load(_ requestedPage: Int) async {
        busy = true; error = nil
        var values = filters.mapValues(\.text); values["page"] = String(requestedPage); values["search"] = search
        do { data = try await session.api.request(query(path, values)).data; page = requestedPage } catch { self.error = error.localizedDescription }; busy = false
    }
}
struct SchemaForm: View {
    @EnvironmentObject private var session: Session
    @Environment(\.dismiss) private var dismiss
    let path: String
    let form: JSON
    let initialValues: [String: JSON]
    @State private var values: [String: JSON] = [:]
    @State private var busy = false
    @State private var error: String?
    @State private var confirmation = false
    var body: some View {
        NavigationStack {
            Form {
                ForEach(form["fields"].array.filter { !$0["hidden"].flag }, id: \.self) { field in
                    SchemaField(field: field, value: Binding(get: { values[field["key"].text] ?? .null }, set: { values[field["key"].text] = $0 }))
                }
                if let error { ErrorBanner(message: error) }
                PrimaryButton(title: form["label"].text, busy: busy) { confirmation = true }
            }.disabled(busy).navigationTitle(form["label"].text).navigationBarTitleDisplayMode(.inline)
            .toolbar { Button("Cancel") { dismiss() }.disabled(busy) }.onAppear { values = initialValues }
            .confirmationDialog("Apply these changes to your school records?", isPresented: $confirmation, titleVisibility: .visible) {
                Button("Confirm") { busy = true; error = nil; Task { do { var payload = values; payload["action"] = form["action"]; _ = try await session.api.request(path, method: "POST", body: .object(payload)); dismiss() } catch { self.error = error.localizedDescription }; busy = false } }
            }
        }
    }
}
struct SchemaField: View {
    let field: JSON
    @Binding var value: JSON
    var string: Binding<String> { Binding(get: { value.text }, set: { value = .string($0) }) }
    var body: some View {
        Group {
            if !field["repeat"].array.isEmpty { AnyView(RepeatField(field: field, value: $value)) }
            else if field["options"] != .null {
                if field["multiple"].flag {
                    DisclosureGroup(field["label"].text) {
                        ForEach(field["options"].array, id: \.self) { option in
                            Toggle(option["label"].text, isOn: Binding(get: { value.array.map(\.text).contains(option["value"].text) }, set: { selected in var values = value.array.map(\.text); values.removeAll { $0 == option["value"].text }; if selected { values.append(option["value"].text) }; value = .array(values.map(JSON.string)) }))
                        }
                    }
                } else {
                    Picker(field["label"].text, selection: string) { Text("Select…").tag(""); ForEach(field["options"].array, id: \.self) { Text($0["label"].text).tag($0["value"].text) } }
                }
            } else if field["secure"].flag { SecureField(field["label"].text, text: string) }
            else if field["multiline"].flag { TextField(field["label"].text, text: string, axis: .vertical).lineLimit(4...12) }
            else { TextField(field["label"].text, text: string).autocorrectionDisabled() }
        }
    }
}
struct RepeatField: View {
    let field: JSON
    @Binding var value: JSON
    var rows: [JSON] { if !value.array.isEmpty { return value.array }; if let bytes = value.text.data(using: .utf8), let parsed = try? JSONDecoder().decode(JSON.self, from: bytes) { return parsed.array }; return [] }
    func update(_ rows: [JSON]) { if let data = try? JSONEncoder().encode(JSON.array(rows)), let text = String(data: data, encoding: .utf8) { value = .string(text) } }
    var body: some View {
        Section(field["label"].text) {
            ForEach(Array(rows.enumerated()), id: \.offset) { index, _ in
                DisclosureGroup("Entry \(index + 1)") {
                    ForEach(field["repeat"].array, id: \.self) { child in
                        SchemaField(field: child, value: Binding(get: { rows.indices.contains(index) ? rows[index][child["key"].text] : .null }, set: { next in var nextRows = rows; guard nextRows.indices.contains(index) else { return }; var object = nextRows[index].object; object[child["key"].text] = next; nextRows[index] = .object(object); update(nextRows) }))
                    }
                    Button("Remove entry", role: .destructive) { var next = rows; next.remove(at: index); update(next) }
                }
            }
            Button("Add entry") { update(rows + [.object([:])]) }
        }
    }
}

struct WorkspaceToolCard: View {
    let title: String
    let description: String
    let artwork: String
    var body: some View {
        HStack(spacing: 12) {
            VStack(alignment: .leading, spacing: 5) { Text(title).font(Brand.font(15, .bold)); Text(description).font(Brand.font(11)).lineSpacing(3).foregroundStyle(Brand.muted) }.padding(.trailing, 48)
            Spacer(minLength: 0); Image(systemName: "chevron.right").font(.system(size: 12))
        }.padding(18).frame(maxWidth: .infinity, minHeight: 112, alignment: .leading).background(Brand.surface, in: RoundedRectangle(cornerRadius: 18)).overlay(RoundedRectangle(cornerRadius: 18).stroke(Brand.outline)).overlay(alignment: .trailing) { Image("Stat" + artwork).resizable().scaledToFit().frame(width: 88, height: 88).opacity(0.1).padding(.trailing, 24).allowsHitTesting(false) }
    }
}
