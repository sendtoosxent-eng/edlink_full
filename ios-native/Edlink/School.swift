import SwiftUI

struct PaymentsView: View {
    @EnvironmentObject private var session: Session
    @State private var data: JSON = .null
    @State private var busy = false
    @State private var error: String?
    @State private var receipt: URL?
    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 12) {
                PageIntro(title: "Payments", description: "View the learner’s current fee position and confirmed payment history.")
                ChildSwitcher()
                if busy { ProgressView().frame(maxWidth: .infinity) }
                if let error { ErrorBanner(message: error); Button("Try again") { Task { await load() } } }
                if data != .null {
                    VStack(alignment: .leading, spacing: 3) {
                        Text(data["student"]["name"].text).font(Brand.font(18, .heavy))
                        Text((data["student"]["class"].text.isEmpty ? "Class not assigned" : data["student"]["class"].text) + " · " + (data["term"].text.isEmpty ? "No current term" : data["term"].text)).font(Brand.font(12)).foregroundStyle(Brand.muted)
                    }.padding(.vertical, 5)
                    HStack(spacing: 10) { summary("Amount due", key: "due"); summary("Paid", key: "paid") }
                    VStack(alignment: .leading, spacing: 6) { Text("Remaining balance").font(Brand.font(11, .heavy)); Text(money(data["summary"]["balance"])).font(Brand.font(27, .heavy)).minimumScaleFactor(0.6).lineLimit(1) }.padding(18).frame(maxWidth: .infinity, minHeight: 112, alignment: .leading).foregroundStyle(Brand.navy).background(Brand.gold, in: RoundedRectangle(cornerRadius: 16))
                    Text("Payment history").font(Brand.font(19, .heavy)).padding(.top, 10)
                    ForEach(data["payments"].array) { payment in paymentCard(payment) }
                    if data["payments"].array.isEmpty { Text("No posted payments for this term yet.").font(Brand.font(12)).foregroundStyle(Brand.muted).frame(maxWidth: .infinity, minHeight: 80).schoolCard() }
                }
                if let receipt { ShareLink(item: receipt) { Label("Open or share receipt", systemImage: "square.and.arrow.up") }.font(Brand.font(12, .semibold)).schoolCard() }
            }.frame(maxWidth: 720).padding(20).frame(maxWidth: .infinity)
        }.background(Brand.background).foregroundStyle(Brand.ink).navigationTitle("Payments").navigationBarTitleDisplayMode(.inline).task { await load() }.refreshable { await load() }
    }
    private func summary(_ title: String, key: String) -> some View { VStack(alignment: .leading, spacing: 16) { Text(title).font(Brand.font(11, .bold)).foregroundStyle(Brand.muted); Text(money(data["summary"][key])).font(Brand.font(17, .heavy)).lineLimit(1).minimumScaleFactor(0.5) }.frame(maxWidth: .infinity, minHeight: 64, alignment: .leading).schoolCard() }
    private func paymentCard(_ payment: JSON) -> some View {
        VStack(alignment: .leading, spacing: 10) {
            HStack(spacing: 12) {
                Image(systemName: "receipt").font(.system(size: 20)).foregroundStyle(Brand.navy).frame(width: 42, height: 42).background(Color(hex: 0xFFF2CF), in: RoundedRectangle(cornerRadius: 13))
                VStack(alignment: .leading, spacing: 3) { Text(money(payment["amount"])).font(Brand.font(15, .heavy)); Text(payment["method"].text.replacingOccurrences(of: "_", with: " ").capitalized + " · " + displayDate(payment["paid_at"].text)).font(Brand.font(11)).foregroundStyle(Brand.muted); Text(payment["transaction_id"].text.isEmpty ? (payment["bank_slip_number"].text.isEmpty ? "School receipt" : payment["bank_slip_number"].text) : payment["transaction_id"].text).font(Brand.font(10, .bold)) }
                Spacer(minLength: 0); Image(systemName: "checkmark.circle.fill").foregroundStyle(Color(hex: 0x176B5B))
            }
            if session.role == "parent" { Button("Download receipt") { download(payment) }.font(Brand.font(11, .semibold)).disabled(busy) }
        }.schoolCard()
    }
    private func download(_ payment: JSON) { busy = true; error = nil; Task { do { receipt = try await session.api.download("/payments/\(payment.id)/receipt", filename: "Edlink-receipt-\(payment.id).pdf") } catch { self.error = error.localizedDescription }; busy = false } }
    private func load() async { busy = true; error = nil; do { data = try await session.api.request(session.scoped("/payments")).data } catch { self.error = error.localizedDescription }; busy = false }
}
struct LeaveView: View {
    @EnvironmentObject private var session: Session
    @State private var rows: [JSON] = []
    @State private var type = "Sick leave"
    @State private var start = Date()
    @State private var end = Date()
    @State private var reason = ""
    @State private var busy = false
    @State private var error: String?
    @State private var message: String?
    var body: some View {
        Form {
            PageHeroRow(title: "Request leave", subtitle: "Plan time away and track your approvals", artwork: "Lessons")
            Section("Request leave") { TextField("Leave type", text: $type); DatePicker("From", selection: $start, in: Date()..., displayedComponents: .date); DatePicker("To", selection: $end, in: start..., displayedComponents: .date); TextField("Reason", text: $reason, axis: .vertical).lineLimit(3...8) }
            if let error { ErrorBanner(message: error) }
            if let message { Text(message).foregroundStyle(.green) }
            PrimaryButton(title: "Submit leave request", busy: busy) {
                busy = true; error = nil; message = nil
                Task { do { _ = try await session.api.request("/leave-requests", method: "POST", body: .strings(["type": type, "starts_on": dayString(start), "ends_on": dayString(max(start, end)), "reason": reason])); reason = ""; await load(); message = "Your request is pending approval." } catch { self.error = error.localizedDescription }; busy = false }
            }.disabled(type.isEmpty)
            Section("Request history") { ForEach(rows) { DataCard(item: $0) } }
        }.edlinkScreen().navigationTitle("Leave requests").task { await load() }.onChange(of: start) { _, value in if end < value { end = value } }
    }
    func load() async { do { rows = try await session.api.request("/leave-requests").data.rows } catch { self.error = error.localizedDescription } }
}
struct DirectoryView: View {
    @EnvironmentObject private var session: Session
    @State private var rows: [JSON] = []
    @State private var search = ""
    @State private var page = 1
    @State private var lastPage = 1
    @State private var busy = false
    @State private var error: String?
    var body: some View {
        List {
            PageHeroRow(title: "My class students", subtitle: "Find learners and view their school profiles", artwork: "Learners")
            if busy { ProgressView() }
            if let error { ErrorBanner(message: error); Button("Try again") { Task { await load(reset: true) } } }
            ForEach(rows) { student in NavigationLink { StudentProfile(student: student) } label: {
                HStack(spacing: 12) {
                    AsyncImage(url: URL(string: student["photo_url"].text)) { image in image.resizable().scaledToFill() } placeholder: { Image(systemName: "person.crop.circle.fill").resizable().foregroundStyle(.secondary) }.frame(width: 44, height: 44).clipShape(Circle())
                    VStack(alignment: .leading, spacing: 4) { Text(student["name"].text).font(Brand.font(15, .bold)); Text(student["admission_no"].text + " · " + student["school_class"]["name"].text).font(Brand.font(10)).foregroundStyle(.secondary) }
                }.padding(.vertical, 4)
            } }
            if !busy && rows.isEmpty && error == nil { ContentUnavailableView.search(text: search) }
            if page < lastPage { Button("Load more") { Task { await load(reset: false) } }.disabled(busy) }
        }.edlinkScreen().navigationTitle("Learners").searchable(text: $search, prompt: "Name or admission number").onSubmit(of: .search) { Task { await load(reset: true) } }.task { await load(reset: true) }.refreshable { await load(reset: true) }
    }
    func load(reset: Bool) async { busy = true; error = nil; let next = reset ? 1 : page + 1; do { let result = try await session.api.request(query("/teacher/students", ["search": search, "page": String(next)])).data; rows = reset ? result.rows : rows + result.rows; page = next; lastPage = Int(result["last_page"].text) ?? 1 } catch { self.error = error.localizedDescription }; busy = false }
}
struct StudentProfile: View {
    @EnvironmentObject private var session: Session
    let student: JSON
    @State private var data: JSON = .null
    @State private var error: String?
    @State private var editing = false
    var body: some View {
        List {
            if let error { ErrorBanner(message: error) }
            Section { Text(student["name"].text).font(Brand.font(24, .bold)); DetailsView(value: data["student"]) }
            if data["can_edit"].flag && data["student"].object.keys.contains("lin") { Button("Edit learner profile") { editing = true } }
            else if data["can_edit"].flag { Text("Your school server needs an update before learner profiles can be edited safely.").foregroundStyle(.secondary) }
        }.edlinkScreen().navigationTitle("Learner profile").task { await load() }.sheet(isPresented: $editing, onDismiss: { Task { await load() } }) {
            StudentEditor(studentID: student.id, initial: data["student"])
        }
    }
    func load() async { do { data = try await session.api.request("/teacher/students/\(student.id)").data } catch { self.error = error.localizedDescription } }
}
struct StudentEditor: View {
    @EnvironmentObject private var session: Session
    @Environment(\.dismiss) private var dismiss
    let studentID: String
    let initial: JSON
    @State private var values: [String: String] = [:]
    @State private var error: String?
    @State private var busy = false
    var body: some View {
        NavigationStack {
            Form {
                ForEach(initial.object.keys.sorted(), id: \.self) { key in TextField(key.replacingOccurrences(of: "_", with: " ").capitalized, text: Binding(get: { values[key] ?? "" }, set: { values[key] = $0 })) }
                if let error { ErrorBanner(message: error) }
                PrimaryButton(title: "Save profile", busy: busy) { busy = true; Task { do { _ = try await session.api.request("/teacher/students/\(studentID)", method: "POST", body: .strings(values)); dismiss() } catch { self.error = error.localizedDescription }; busy = false } }
            }.edlinkScreen().navigationTitle("Edit learner").toolbar { Button("Cancel") { dismiss() }.disabled(busy) }.onAppear { values = initial.object.mapValues(\.text) }
        }
    }
}
