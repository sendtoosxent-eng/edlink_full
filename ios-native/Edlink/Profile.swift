import SwiftUI

struct ProfileView: View {
    @EnvironmentObject private var session: Session
    @State private var timetable: [JSON] = []
    @State private var announcements: [JSON] = []
    @State private var error: String?
    var body: some View {
        ScrollView(showsIndicators: false) {
            VStack(alignment: .leading, spacing: 16) {
                VStack(spacing: 10) {
                    UserAvatar(name: session.user["name"].text, url: session.user["avatar_url"].text, size: 88).overlay(Circle().stroke(Brand.gold, lineWidth: 4))
                    Text(session.user["name"].text).font(Brand.font(24, .bold)).foregroundStyle(.white).multilineTextAlignment(.center)
                    Label(session.role.capitalized, systemImage: "checkmark.shield.fill").font(Brand.font(11, .semibold)).padding(.horizontal, 12).padding(.vertical, 6).foregroundStyle(Brand.navy).background(Brand.gold, in: Capsule())
                    Text(session.user["school"]["name"].text).font(Brand.font(12)).foregroundStyle(Color(hex: 0xDDE5F2))
                }.padding(24).frame(maxWidth: .infinity, minHeight: 258).background(Brand.navy, in: RoundedRectangle(cornerRadius: 24))
                ChildSwitcher()
                if session.isTeacher { NavigationLink { EditProfileView() } label: { Label("Edit profile", systemImage: "square.and.pencil").font(Brand.font(14, .bold)).frame(maxWidth: .infinity, minHeight: 54).foregroundStyle(.white).background(Brand.navy, in: RoundedRectangle(cornerRadius: 16)) } }
                Text("Account information").font(Brand.font(18, .heavy)).padding(.top, 6)
                VStack(spacing: 0) {
                    info("Email address", value: session.user["email"].text, icon: "envelope")
                    Divider().padding(.vertical, 12)
                    info("Phone number", value: session.user["phone"].text.isEmpty ? "Not added" : session.user["phone"].text, icon: "phone")
                    Divider().padding(.vertical, 12)
                    info("School number", value: session.user["school"]["number"].text, icon: "building.2")
                    Divider().padding(.vertical, 12)
                    info("Account ID", value: "EDL-" + String(format: "%05d", Int(session.user["id"].text) ?? 0), icon: "key")
                }.schoolCard()
                HStack { Text("This week").font(Brand.font(18, .heavy)); Spacer(); Text("\(timetable.count) timetable items").font(Brand.font(10)).foregroundStyle(Brand.muted) }.padding(.top, 8)
                VStack(alignment: .leading, spacing: 16) {
                    ForEach(Array(timetable.prefix(3))) { lesson in info(lesson["day_of_week"].text + " · " + String(lesson["starts_at"].text.prefix(5)) + "–" + String(lesson["ends_at"].text.prefix(5)), value: lesson["subject"].text.isEmpty ? lesson["label"].text : lesson["subject"].text, icon: "calendar") }
                    if timetable.isEmpty { Text("No lessons scheduled yet.").font(Brand.font(12)).foregroundStyle(Brand.muted) }
                    NavigationLink("View timetable") { ScheduleView() }.font(Brand.font(12, .bold))
                }.schoolCard()
                Text("School announcements").font(Brand.font(18, .heavy)).padding(.top, 8)
                VStack(alignment: .leading, spacing: 16) {
                    ForEach(Array(announcements.prefix(3))) { item in NavigationLink { ResourceDetail(title: "Announcement", item: item) } label: { VStack(alignment: .leading, spacing: 5) { Text(item["title"].text).font(Brand.font(13, .bold)); Text(item["message"].text).font(Brand.font(11)).foregroundStyle(Brand.muted).lineLimit(2) } } }
                    if announcements.isEmpty { Text("You're all caught up.").font(Brand.font(12)).foregroundStyle(Brand.muted) }
                }.schoolCard()
                if let error { ErrorBanner(message: error) }
                if session.isTeacher { NavigationLink { LeaveView() } label: { Label("Request leave", systemImage: "calendar").font(Brand.font(13, .semibold)).frame(maxWidth: .infinity, minHeight: 52).background(Brand.gold, in: Capsule()).foregroundStyle(Brand.navy) } }
                NavigationLink { SchoolHub() } label: { info("Explore your school services", value: "School services", icon: "building.2").schoolCard() }
                NavigationLink { SettingsView() } label: { Label("Settings", systemImage: "gearshape").font(Brand.font(13, .semibold)).frame(maxWidth: .infinity, minHeight: 52).background(Brand.surface, in: Capsule()).overlay(Capsule().stroke(Brand.outline)) }
                Text("Edlink Mobile · Secure school access").font(Brand.font(10)).foregroundStyle(Brand.muted).frame(maxWidth: .infinity).padding(.top, 10)
            }.frame(maxWidth: 720).padding(.horizontal, 20).padding(.top, 16).padding(.bottom, 32).frame(maxWidth: .infinity)
        }.foregroundStyle(Brand.ink).background(Brand.background).toolbar(.hidden, for: .navigationBar).task { await load() }.refreshable { await load() }
    }
    private func info(_ label: String, value: String, icon: String) -> some View {
        HStack(spacing: 12) { Image(systemName: icon).font(.system(size: 19)).foregroundStyle(Brand.navy).frame(width: 42, height: 42).background(Color(hex: 0xFFF5D5), in: RoundedRectangle(cornerRadius: 14)); VStack(alignment: .leading, spacing: 4) { Text(label).font(Brand.font(10)).foregroundStyle(Brand.muted); Text(value).font(Brand.font(13, .semibold)).foregroundStyle(Brand.ink) }; Spacer(minLength: 0) }
    }
    private func load() async {
        error = nil
        do { async let slots = session.api.request(session.scoped("/timetable")); async let notices = session.api.request("/announcements"); let (s,n) = try await (slots,notices); timetable = s.data.rows; announcements = n.data.rows } catch { self.error = error.localizedDescription }
    }
}
struct EditProfileView: View {
    @EnvironmentObject private var session: Session
    @Environment(\.dismiss) private var dismiss
    @State private var name = ""
    @State private var phone = ""
    @State private var busy = false
    @State private var error: String?
    var body: some View {
        Form {
            PageHeroRow(title: "Edit profile", subtitle: "Keep your school account details up to date", artwork: "Learners")
            Section("Your profile") { TextField("Name", text: $name); LabeledContent("Email", value: session.user["email"].text); TextField("Phone number", text: $phone).keyboardType(.phonePad) }
            if let error { ErrorBanner(message: error) }
            PrimaryButton(title: "Save changes", busy: busy) {
                busy = true; error = nil
                Task { do { session.user = try await session.api.request("/auth/profile", method: "POST", body: .strings(["name": name, "phone": phone, "email": session.user["email"].text])).data; dismiss() } catch { self.error = error.localizedDescription }; busy = false }
            }.disabled(name.trimmingCharacters(in: .whitespaces).isEmpty)
        }.edlinkScreen().navigationTitle("Edit profile").onAppear { name = session.user["name"].text; phone = session.user["phone"].text }
    }
}
struct SettingsView: View {
    @EnvironmentObject private var session: Session
    @AppStorage("edlink.appearance") private var appearance = "light"
    @State private var busy = false
    @State private var error: String?
    @State private var confirmLogout = false
    var body: some View {
        List {
            PageHeroRow(title: "Settings", subtitle: "Manage your account and school connections.", artwork: "School")
            ChildSwitcher().listRowInsets(EdgeInsets()).listRowBackground(Color.clear)
            HStack(spacing: 12) { UserAvatar(name: session.user["name"].text, url: session.user["avatar_url"].text, size: 52); VStack(alignment: .leading, spacing: 4) { Text(session.user["name"].text).font(Brand.font(14, .semibold)); Text(session.user["email"].text).font(Brand.font(11)).foregroundStyle(Brand.muted) } }.listRowBackground(Color.clear)
            Section("APPEARANCE") {
                VStack(alignment: .leading, spacing: 12) { Text("App theme").font(Brand.font(14, .semibold)); Text("System follows your device.").font(Brand.font(11)).foregroundStyle(Brand.muted)
                    HStack(spacing: 8) { ForEach(["system", "light", "dark"], id: \.self) { value in Button { appearance = value } label: { Text(value.capitalized).font(Brand.font(12, .semibold)).frame(maxWidth: .infinity, minHeight: 44).foregroundStyle(appearance == value ? Brand.navy : Brand.ink).background(appearance == value ? Brand.gold : Brand.surface, in: Capsule()).overlay(Capsule().stroke(appearance == value ? Brand.gold : Brand.outline)) }.buttonStyle(.plain) } }
                }.padding(.vertical, 4)
            }
            PrivacySettings()
            Section("ACCOUNT") { NavigationLink("My profile") { ProfileView() }; NavigationLink("Notifications") { ResourceView(title: "Notifications", path: "/notifications", notifications: true) } }
            Section("MY SCHOOL") { Text(session.user["school"]["name"].text).font(Brand.font(14, .semibold)); Text("School number: " + session.user["school"]["number"].text).font(Brand.font(11)).foregroundStyle(Brand.muted) }
            Section("SESSION") { Button { confirmLogout = true } label: { Label(busy ? "Logging out…" : "Log out", systemImage: "rectangle.portrait.and.arrow.right").font(Brand.font(14, .semibold)).frame(minHeight: 44) }.disabled(busy) }
            if let error { ErrorBanner(message: error) }
            Text("Edlink · Innovating paperless learning").font(Brand.font(10)).foregroundStyle(Brand.muted).frame(maxWidth: .infinity).listRowBackground(Color.clear)
        }.edlinkScreen().navigationTitle("Settings").confirmationDialog("Log out of Edlink?", isPresented: $confirmLogout, titleVisibility: .visible) { Button("Log out", role: .destructive) { busy = true; Task { do { try await session.logout() } catch { self.error = error.localizedDescription }; busy = false } } } message: { Text("You can sign in again with your school account.") }
    }
}
