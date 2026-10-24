import SwiftUI

struct DashboardView: View {
    @EnvironmentObject private var session: Session
    @EnvironmentObject private var navigation: AppNavigation
    var previewData: JSON? = nil
    @State private var data: JSON = .null
    @State private var loading = true
    @State private var error: String?
    @State private var search = ""
    @State private var unread = 0
    @State private var searchDestination: String?
    @FocusState private var searchFocused: Bool
    private var workspace: JSON { data["teacher_workspace"] }
    private var stats: JSON { data["analytics"]["stats"] }
    private var firstName: String { let name = session.role == "student" ? data["student"]["name"].text : session.user["name"].text; return name.split(separator: " ").first.map(String.init) ?? "there" }
    private var attendanceRate: String {
        let counts = data["attendance"].object
        let total = counts.values.reduce(0) { $0 + (Double($1.text) ?? 0) }
        guard total > 0 else { return "—" }
        let present = (Double(counts["present"]?.text ?? "") ?? 0) + (Double(counts["late"]?.text ?? "") ?? 0)
        return "\(Int((present / total * 100).rounded()))%"
    }
    var body: some View {
        ScrollView(showsIndicators: false) {
            VStack(alignment: .leading, spacing: 0) {
                topBar
                searchBar.padding(.bottom, 16)
                greeting
                if session.role == "parent" { ChildSwitcher().padding(.top, 16) }
                if loading { ProgressView("Loading your school day…").frame(maxWidth: .infinity).padding() }
                if let error { ErrorBanner(message: error).padding(.top, 14); Button("Try again") { Task { await load() } } }
                if data != .null {
                    if session.role != "parent" { nextLesson.padding(.top, 16) }
                    if session.isTeacher { responsibilities.padding(.top, 16); pendingTasks.padding(.top, 16) }
                    statGrid.padding(.top, 13)
                    section(session.isTeacher ? "Teacher workspace" : session.role == "parent" ? "Parent workspace" : "Student workspace", subtitle: session.isTeacher ? "Tools available for your assignments and school permissions" : session.role == "parent" ? "Stay connected to your child’s school day" : "Everything you need for your school day")
                    actionGrid
                    section(session.isTeacher ? "My attendance activity" : session.role == "parent" ? "Attendance overview" : "My attendance", subtitle: session.isTeacher ? "Registers you recorded over the last seven days" : session.role == "parent" ? "Updated from school registers" : nil)
                    NavigationLink { AttendanceHome() } label: { attendanceChart }.buttonStyle(.plain)
                    section(session.isTeacher ? "Subject insights" : session.role == "parent" ? "Academic progress" : "My performance")
                    performance
                    HStack { section(session.isTeacher ? "Today’s timetable" : session.role == "parent" ? "Today at school" : "Today’s lessons"); Spacer(); NavigationLink("Full week") { ScheduleView() }.font(Brand.font(12, .heavy)).padding(.top, 20) }
                    todayLessons
                    section(session.isTeacher ? "Homework activity" : session.role == "parent" ? "Homework due" : "Upcoming homework")
                    homework
                    section(session.role == "student" ? "School calendar" : "Upcoming events")
                    events
                }
            }.frame(maxWidth: 720).padding(.horizontal, 18).padding(.bottom, 32).frame(maxWidth: .infinity)
        }.background(Brand.background).foregroundStyle(Brand.ink).toolbar(.hidden, for: .navigationBar).task { await load() }.refreshable { await load() }.navigationDestination(item: $searchDestination) { DashboardDestination(route: $0) }
    }
    private var topBar: some View {
        HStack { BrandMark(); Spacer(); NavigationLink { ResourceView(title: "Notifications", path: "/notifications", notifications: true) } label: {
            BrandGlyph(name: "Bell", size: 26).frame(width: 42, height: 42).overlay(alignment: .topTrailing) { if unread > 0 { Text(unread > 9 ? "9+" : String(unread)).font(Brand.font(9, .heavy)).foregroundStyle(.white).padding(4).background(.red, in: Circle()) } }
        }.accessibilityLabel("Open notifications"); NavigationLink { SettingsView() } label: { BrandGlyph(name: "Settings", size: 26).frame(width: 42, height: 42) }.accessibilityLabel("Open settings") }.frame(minHeight: 75)
    }
    private var searchBar: some View {
        VStack(spacing: 8) {
            HStack(spacing: 10) {
                Image("AppSearch").resizable().renderingMode(.template).scaledToFit().frame(width: 20, height: 20).foregroundStyle(Brand.muted)
                TextField("Search school tools…", text: $search).font(Brand.font(13)).focused($searchFocused).submitLabel(.search).onSubmit { if let first = searchResults.first { openSearch(first.2) } }
                if !search.isEmpty { Button { search = "" } label: { Text("×").font(Brand.font(24)) }.accessibilityLabel("Clear search") }
            }.padding(.horizontal, 15).frame(minHeight: 50).background(searchFocused ? Brand.surface : Color(hex: 0xF0F2F7), in: RoundedRectangle(cornerRadius: 16)).overlay(RoundedRectangle(cornerRadius: 16).stroke(searchFocused ? Brand.gold : .clear))
            if !search.trimmingCharacters(in: .whitespaces).isEmpty {
                VStack(spacing: 0) {
                    ForEach(Array(searchResults.prefix(6)), id: \.0) { entry in Button { openSearch(entry.2) } label: { HStack { Text(entry.0).font(Brand.font(13)); Spacer(); Image(systemName: "chevron.right").font(.system(size: 12)) }.frame(minHeight: 48) }.buttonStyle(.plain) }
                    if searchResults.isEmpty { Text("No tools found. Try “homework” or “results”.").font(Brand.font(12)).foregroundStyle(Brand.muted).padding(.vertical, 16) }
                }.padding(.horizontal, 14).background(Brand.surface, in: RoundedRectangle(cornerRadius: 16))
            }
        }
    }
    private var searchResults: [(String, String, String)] {
        var entries = [("My profile", "account personal details", "profile"), ("Notifications", "notices announcements messages", "notifications"), ("Settings", "logout sign out", "settings"), ("Homework", "assignments learning tasks", "homework")]
        if session.isTeacher {
            entries += workspace["tools"].array.map { tool in (tool["label"].text, tool["group"].text, searchRoute(tool)) }
            entries.append(("Timetable", "schedule lessons week", "timetable"))
        } else {
            entries += [("Results", "marks report performance", "results"), ("Attendance", "register present absent", "attendance"), ("Payments", "fees balance finance", "payments")]
            if session.role == "student" { entries += [("Timetable", "schedule lessons week", "timetable"), ("School calendar", "events dates", "calendar")] }
        }
        let term = search.trimmingCharacters(in: .whitespaces)
        var seen = Set<String>()
        return entries.filter { seen.insert($0.0).inserted && ($0.0 + " " + $0.1).localizedCaseInsensitiveContains(term) }
    }
    private func searchRoute(_ tool: JSON) -> String {
        switch tool["id"].text {
        case "exams.results": return "teacher_results"
        case "communications.index": return "communications"
        case "attendance.index", "attendance.subject": return "registers"
        case "attendance.reports": return "attendance_reports"
        case "students.index": return "directory"
        case "exams.marks": return "marks"
        case "homework.index": return "homework"
        case "leaves.index": return "leave"
        case "notifications.index": return "notifications"
        default: return workspaceSection(tool["group"].text)
        }
    }
    private func openSearch(_ route: String) {
        search = ""; searchFocused = false
        if navigation.isRoot(route, role: session.role) { navigation.active = route } else { searchDestination = route }
    }
    private var greeting: some View {
        VStack(alignment: .leading, spacing: 13) {
            HStack(spacing: 12) {
                VStack(alignment: .leading, spacing: 6) {
                    Text(greetingTime).font(Brand.font(11)).foregroundStyle(Brand.gold)
                    Text(session.role == "parent" ? "Hello, \(firstName)" : "Hi, \(firstName)!").font(Brand.font(20, .heavy)).foregroundStyle(.white)
                    Text(greetingSubtitle).font(Brand.font(11)).lineSpacing(3).foregroundStyle(Color(hex: 0xDDE5F2))
                }.frame(maxWidth: .infinity, alignment: .leading)
                UserAvatar(name: session.role == "student" ? data["student"]["name"].text : session.user["name"].text, url: session.role == "student" ? data["student"]["photo_url"].text : session.user["avatar_url"].text, size: 100)
            }
            HStack(spacing: 8) {
                Label(Date.now.formatted(.dateTime.weekday(.abbreviated).day().month(.abbreviated)), systemImage: "calendar").font(Brand.font(10, .heavy)).padding(.horizontal, 10).padding(.vertical, 6).foregroundStyle(Brand.navy).background(Brand.gold, in: Capsule())
                HStack(spacing: 6) { Circle().fill(Brand.gold).frame(width: 5, height: 5); Text(data["current_term"]["name"].text.isEmpty ? "No open term" : data["current_term"]["name"].text).font(Brand.font(10, .semibold)).lineLimit(1) }.padding(.horizontal, 10).padding(.vertical, 6).foregroundStyle(Brand.gold).background(.white.opacity(0.08), in: Capsule())
            }
        }.padding(19).frame(maxWidth: .infinity, minHeight: 174).background(Brand.navy, in: RoundedRectangle(cornerRadius: 24))
    }
    private var greetingTime: String { let hour = Calendar.current.component(.hour, from: Date()); return hour < 12 ? "Good morning" : hour < 18 ? "Good afternoon" : "Good evening" }
    private var greetingSubtitle: String {
        if session.isTeacher { return [workspace["role_label"].text.isEmpty ? "Teacher" : workspace["role_label"].text, workspace["term"].text, session.user["school"]["name"].text].filter { !$0.isEmpty }.joined(separator: " · ") }
        if session.role == "parent" { return "A live overview of \(data["student"]["name"].text.isEmpty ? "your learner" : data["student"]["name"].text)’s school progress." }
        return [data["student"]["class"].text, data["student"]["stream"].text, session.user["school"]["name"].text].filter { !$0.isEmpty }.joined(separator: " · ")
    }
    private var nextLesson: some View {
        DashboardLessonCard(lesson: data["next_lesson"], date: data["date"].text, teacher: session.isTeacher)
    }
    private var responsibilities: some View {
        VStack(alignment: .leading, spacing: 12) {
            Text("Responsibilities").font(Brand.font(15, .bold))
            let labels = workspace["class_teacher_classes"].array.map { $0["name"].text } + workspace["subject_assignments"].array.map { $0["subject_name"].text + " · " + $0["class_name"].text }
            if labels.isEmpty { Text("No subject assignments.").font(Brand.font(12)).foregroundStyle(Brand.muted) }
            else { ForEach(Array(labels.enumerated()), id: \.offset) { _, label in Text(label).font(Brand.font(11, .semibold)).padding(.horizontal, 12).padding(.vertical, 7).background(Brand.navy, in: Capsule()).foregroundStyle(Brand.gold) } }
        }.frame(maxWidth: .infinity, alignment: .leading).schoolCard().overlay(alignment: .topTrailing) { Image("LessonArtwork").resizable().scaledToFit().frame(width: 80, height: 96).opacity(0.07).padding(12).allowsHitTesting(false) }
    }
    private var pendingTasks: some View {
        VStack(alignment: .leading, spacing: 12) {
            HStack { Text("Pending tasks").font(Brand.font(15, .bold)); Spacer(); Text(String(workspace["pending_attendance"].array.count + workspace["pending_results"].array.count)).font(Brand.font(12, .bold)).padding(7).background(Brand.gold, in: Circle()).foregroundStyle(Brand.navy) }
            ForEach(workspace["pending_attendance"].array, id: \.self) { item in NavigationLink { AttendanceHome() } label: { pendingRow(item["label"].text, item["attendance_type"].text == "daily" ? "Class attendance" : "Subject attendance", artwork: "Attendance") } }
            ForEach(workspace["pending_results"].array, id: \.self) { item in NavigationLink { MarksEditor(paperID: item["paper_id"].text) } label: { pendingRow(item["subject_name"].text + " · " + item["exam_name"].text, item["class_name"].text + " · Marks awaiting submission", artwork: "Marks") } }
            if workspace["pending_attendance"].array.isEmpty && workspace["pending_results"].array.isEmpty { Text("You’re all caught up. No pending attendance or marks submissions.").font(Brand.font(12)).foregroundStyle(Brand.muted) }
        }.schoolCard().buttonStyle(.plain)
    }
    private func pendingRow(_ title: String, _ subtitle: String, artwork: String) -> some View {
        HStack(spacing: 12) { Image("Stat" + artwork).resizable().scaledToFit().frame(width: 24, height: 24).padding(6).background(Color(hex: 0xFFF5D5), in: RoundedRectangle(cornerRadius: 12)); VStack(alignment: .leading, spacing: 4) { Text(title).font(Brand.font(12, .bold)); Text(subtitle).font(Brand.font(10)).foregroundStyle(Brand.muted) }; Spacer(); Image(systemName: "chevron.right").font(.system(size: 12)) }.padding(.vertical, 4)
    }
    private var statItems: [(String, String, String)] {
        if session.isTeacher { return [("Learners", stats["learners"].text, "Learners"), ("Classes", stats["classes"].text, "Classroom"), ("Subjects", stats["subjects"].text, "Subjects"), ("Lessons today", stats["lessons_today"].text, "Lessons"), ("Recorded today", stats["attendance_today"].text, "Attendance"), ("Pending marks", stats["pending_marks"].text, "Marks")] }
        return [("Attendance", attendanceRate, "Attendance"), ("Lessons today", stats["lessons_today"].text.isEmpty ? String(data["today_timetable"].array.count) : stats["lessons_today"].text, "Lessons"), ("Homework", stats["homework"].text.isEmpty ? String(data["homework"].array.count) : stats["homework"].text, "Subjects"), ("Results", stats["published_results"].text, "Marks")]
    }
    private var statGrid: some View {
        LazyVGrid(columns: [GridItem(.flexible()), GridItem(.flexible())], spacing: 10) {
            ForEach(statItems, id: \.0) { item in
                VStack(alignment: .leading, spacing: 2) { Text(item.1.isEmpty ? "0" : item.1).font(Brand.font(40, .heavy)); Text(item.0).font(Brand.font(10, .semibold)).foregroundStyle(Brand.muted) }.frame(maxWidth: .infinity, minHeight: 102, alignment: .leading).padding(13).background(Brand.surface, in: RoundedRectangle(cornerRadius: 16)).overlay(RoundedRectangle(cornerRadius: 16).stroke(Brand.outline, lineWidth: 0.5)).overlay(alignment: .trailing) { Image("Stat" + item.2).resizable().scaledToFit().frame(width: 74, height: 80).opacity(0.12).padding(.trailing, 3).allowsHitTesting(false) }
            }
        }
    }
    private var actions: [(String, String, String, String)] {
        if session.isTeacher { return [("My Classes", "Learners, registers and class administration", "Learners", "classes"), ("Teaching", "Subjects, homework, marks and timetable", "Classroom", "teaching"), ("Reports", "Results, report cards and attendance reports", "Reports", "reports"), ("My School", "Notices, leave, clubs and school services", "School", "school")] }
        if session.role == "parent" { return [("Attendance", "Daily presence and attendance records", "Attendance", "attendance"), ("Homework", "Assignments and upcoming due dates", "Subjects", "homework"), ("Results", "Published marks and academic progress", "Marks", "results"), ("School fees", "Payments and your child’s balance", "Reports", "payments"), ("School updates", "Announcements and notifications", "School", "notifications"), ("More services", "Explore your school services", "Classroom", "profile")] }
        return [("My homework", "Assignments, answers and feedback", "Subjects", "homework"), ("My timetable", "Plan your school week", "Lessons", "timetable"), ("My results", "Published scores and performance", "Marks", "results"), ("Attendance", "Your school attendance record", "Attendance", "attendance"), ("School fees", "Payments and your balance", "Reports", "payments"), ("School calendar", "Upcoming events and dates", "School", "calendar")]
    }
    private var actionGrid: some View {
        LazyVGrid(columns: [GridItem(.flexible()), GridItem(.flexible())], spacing: 10) {
            ForEach(actions, id: \.3) { action in
                if navigation.isRoot(action.3, role: session.role) {
                    Button { navigation.active = action.3 } label: { actionTile(action) }.buttonStyle(.plain)
                } else {
                    NavigationLink { DashboardDestination(route: action.3) } label: { actionTile(action) }.buttonStyle(.plain)
                }
            }
        }
    }
    private func actionTile(_ action: (String, String, String, String)) -> some View {
                    VStack(alignment: .leading, spacing: 6) { Text(action.0).font(Brand.font(14, .bold)); Text(action.1).font(Brand.font(10)).lineSpacing(2).foregroundStyle(Brand.muted); if session.isTeacher { Text("\(toolCount(action.3)) tools").font(Brand.font(10, .semibold)).foregroundStyle(Brand.goldDark).padding(.top, 2) } }.frame(maxWidth: .infinity, minHeight: 112, alignment: .leading).padding(13).padding(.trailing, 12).background(Brand.surface, in: RoundedRectangle(cornerRadius: 16)).overlay(RoundedRectangle(cornerRadius: 16).stroke(Brand.outline, lineWidth: 0.5)).overlay(alignment: .bottomTrailing) { Image("Stat" + action.2).resizable().scaledToFit().frame(width: 86, height: 90).opacity(0.10).padding(4).allowsHitTesting(false) }
    }
    private func toolCount(_ section: String) -> Int { workspace["tools"].array.filter { workspaceSection($0["group"].text) == section }.count }
    private func section(_ title: String, subtitle: String? = nil) -> some View {
        VStack(alignment: .leading, spacing: 4) { Text(title).font(Brand.font(18, .heavy)).tracking(-0.3); if let subtitle { Text(subtitle).font(Brand.font(11)).lineSpacing(3).foregroundStyle(Brand.muted) } }.padding(.top, 28).padding(.bottom, 12)
    }
    private var attendanceChart: some View {
        let analytics = data["analytics"]
        let labels = analytics["attendance_labels"].array.isEmpty ? ["M", "T", "W", "T", "F", "S", "S"].map(JSON.string) : analytics["attendance_labels"].array
        let present = analytics["present_series"].array.map { Double($0.text) ?? 0 }
        let absent = analytics["absent_series"].array.map { Double($0.text) ?? 0 }
        let maximum = max(1, (present + absent).max() ?? 1)
        return VStack(alignment: .leading, spacing: 16) {
            HStack { VStack(alignment: .leading, spacing: 4) { Text("Attendance trend").font(Brand.font(15, .bold)); Text("Last 7 days · live records").font(Brand.font(10)).foregroundStyle(Brand.muted) }; Spacer(); Text(attendanceRate).font(Brand.font(14, .heavy)).padding(10).background(Color(hex: 0xFFF5D5), in: Capsule()).foregroundStyle(Brand.navy) }
            HStack(spacing: 14) { Label("Present / late  \(Int(present.reduce(0,+)))", systemImage: "circle.fill").foregroundStyle(Color(hex: 0x176B5B)); Label("Absent  \(Int(absent.reduce(0,+)))", systemImage: "circle.fill").foregroundStyle(Color(hex: 0xDE3B55)) }.font(Brand.font(9))
            HStack(alignment: .bottom, spacing: 0) {
                ForEach(Array(labels.enumerated()), id: \.offset) { index, label in
                    VStack(spacing: 6) { HStack(alignment: .bottom, spacing: 4) { RoundedRectangle(cornerRadius: 4).fill(Color(hex: 0x176B5B)).frame(width: 9, height: max(4, (present.indices.contains(index) ? present[index] : 0) / maximum * 84)); RoundedRectangle(cornerRadius: 4).fill(Color(hex: 0xDE3B55)).frame(width: 9, height: max(4, (absent.indices.contains(index) ? absent[index] : 0) / maximum * 84)) }.frame(height: 84, alignment: .bottom); Text(String(label.text.prefix(3))).font(Brand.font(9)).foregroundStyle(Brand.muted) }.frame(maxWidth: .infinity)
                }
            }
        }.schoolCard()
    }
    private var performance: some View {
        VStack(alignment: .leading, spacing: 14) {
            Text("Academic performance").font(Brand.font(15, .bold)); Text(session.isTeacher ? "Current-term marks for your assignments" : "Published subject averages").font(Brand.font(10)).foregroundStyle(Brand.muted)
            let labels = data["analytics"]["performance_labels"].array
            let values = data["analytics"]["performance_series"].array
            if labels.isEmpty { Text("Performance appears after results are published.").font(Brand.font(12)).foregroundStyle(Brand.muted).padding(.vertical, 10) }
            ForEach(Array(labels.enumerated()), id: \.offset) { index, label in
                let value = values.indices.contains(index) ? Double(values[index].text) ?? 0 : 0
                let color = Color(hex: value >= 70 ? 0x176B5B : value >= 50 ? 0x8A6500 : 0xDE3B55)
                VStack(spacing: 6) { HStack { Text(label.text).font(Brand.font(11, .semibold)); Spacer(); Text("\(Int(value.rounded()))%").font(Brand.font(11, .bold)).foregroundStyle(color) }; GeometryReader { geometry in Capsule().fill(Brand.outline).overlay(alignment: .leading) { Capsule().fill(color).frame(width: geometry.size.width * min(1, max(0.02, value / 100))) } }.frame(height: 6) }
            }
        }.schoolCard()
    }
    private var todayLessons: some View {
        VStack(spacing: 12) {
            if data["today_timetable"].array.isEmpty { Text("No lessons scheduled today.").font(Brand.font(12)).foregroundStyle(Brand.muted).frame(maxWidth: .infinity, alignment: .leading) }
            ForEach(data["today_timetable"].array) { lesson in HStack(spacing: 12) { Text(String(lesson["starts_at"].text.prefix(5))).font(Brand.font(12, .semibold)).foregroundStyle(Brand.goldDark); Rectangle().fill(Brand.gold).frame(width: 3, height: 36); VStack(alignment: .leading, spacing: 4) { Text(lesson["subject"].text.isEmpty ? lesson["label"].text : lesson["subject"].text).font(Brand.font(12, .bold)); Text(lesson["class_name"].text).font(Brand.font(10)).foregroundStyle(Brand.muted) }; Spacer() } }
        }.schoolCard()
    }
    private var homework: some View {
        VStack(spacing: 12) {
            if data["homework"].array.isEmpty { Text("No homework to show yet.").font(Brand.font(12)).foregroundStyle(Brand.muted).frame(maxWidth: .infinity, alignment: .leading) }
            ForEach(data["homework"].array) { assignment in NavigationLink { HomeworkDetail(assignmentID: assignment.id) } label: { HStack { Image("StatSubjects").resizable().scaledToFit().frame(width: 32, height: 32); VStack(alignment: .leading, spacing: 4) { Text(assignment["title"].text).font(Brand.font(12, .bold)); Text(assignment["subject"]["name"].text).font(Brand.font(10)).foregroundStyle(Brand.muted) }; Spacer(); Image(systemName: "chevron.right").font(.system(size: 12)) } }.buttonStyle(.plain) }
        }.schoolCard()
    }
    private var events: some View {
        VStack(alignment: .leading, spacing: 14) {
            if data["events"].array.isEmpty { Text("No upcoming events.").font(Brand.font(12)).foregroundStyle(Brand.muted) }
            ForEach(data["events"].array) { event in HStack(spacing: 12) { Text(String(event["event_date"].text.prefix(10))).font(Brand.font(10, .semibold)).padding(10).background(Color(hex: 0xFFF5D5), in: RoundedRectangle(cornerRadius: 12)).foregroundStyle(Brand.navy); Text(event["title"].text.isEmpty ? event["name"].text : event["title"].text).font(Brand.font(12, .bold)); Spacer() } }
        }.frame(maxWidth: .infinity, alignment: .leading).schoolCard()
    }
    private func load() async {
        if let previewData { data = previewData; loading = false; return }
        loading = true; error = nil
        do { data = try await session.api.request(session.scoped("/dashboard")).data; if let result = try? await session.api.request(session.scoped("/notifications")) { unread = Int(result.meta?["unread_count"].text ?? "0") ?? 0 } } catch { self.error = error.localizedDescription }; loading = false
    }
}
func workspaceSection(_ group: String) -> String { if ["My classes", "Students"].contains(group) { return "classes" }; if ["Teaching", "Exams"].contains(group) { return "teaching" }; if group == "Reports" { return "reports" }; return "school" }
struct DashboardDestination: View {
    let route: String
    @ViewBuilder var body: some View {
        switch route {
        case "classes", "teaching", "reports", "school": TeacherToolsView(section: route)
        case "settings": SettingsView()
        case "teacher_results": TeacherReportsView()
        case "communications": NativeToolView(tool: .object(["id": .string("communications.index"), "label": .string("Communications")]))
        case "registers": AttendanceHome()
        case "attendance_reports": AttendanceReportView()
        case "directory": DirectoryView()
        case "marks": MarksList()
        case "leave": LeaveView()
        case "homework": HomeworkList()
        case "timetable": ScheduleView()
        case "results": ResultsView()
        case "attendance": AttendanceHome()
        case "payments": PaymentsView()
        case "calendar": SchoolCalendarView()
        case "notifications": ResourceView(title: "Notifications", path: "/notifications", notifications: true)
        default: ProfileView()
        }
    }
}
struct SchoolCard: ViewModifier {
    func body(content: Content) -> some View { content.padding(16).frame(maxWidth: .infinity, alignment: .leading).background(Brand.surface, in: RoundedRectangle(cornerRadius: 18)).overlay(RoundedRectangle(cornerRadius: 18).stroke(Brand.outline, lineWidth: 0.5)).shadow(color: Brand.navy.opacity(0.04), radius: 12, y: 4) }
}
extension View { func schoolCard() -> some View { modifier(SchoolCard()) } }

private struct DashboardLessonCard: View {
    let lesson: JSON
    let date: String
    let teacher: Bool
    private var title: String { lesson["subject"].text.isEmpty ? lesson["label"].text : lesson["subject"].text }
    private var details: String { let start = String(lesson["starts_at"].text.prefix(5)); let end = String(lesson["ends_at"].text.prefix(5)); return lesson["class_name"].text + " · " + start + "–" + end }
    var body: some View {
        VStack(alignment: .leading, spacing: 8) {
            if lesson == .null { Text("You have no remaining lessons today.").font(Brand.font(12)).foregroundStyle(Brand.muted) }
            else {
                NavigationLink { ScheduleView() } label: { lessonHeading }.buttonStyle(.plain)
                TimelineView(.periodic(from: .now, by: 1)) { timeline in countdown(at: timeline.date) }
            }
        }.padding(16).background(Brand.surface, in: RoundedRectangle(cornerRadius: 18)).overlay(RoundedRectangle(cornerRadius: 18).stroke(lesson == .null ? Brand.outline : Brand.gold))
    }
    private var lessonHeading: some View {
        HStack {
            VStack(alignment: .leading, spacing: 8) {
                Text("Your next lesson").font(Brand.font(12, .semibold))
                Text(title).font(Brand.font(15, .bold))
                Text(details).font(Brand.font(12)).foregroundStyle(Brand.muted)
            }
            Spacer()
            Image("LessonClock").resizable().scaledToFit().frame(width: 80, height: 80).opacity(0.22)
        }
    }
    private func countdown(at now: Date) -> some View {
        let start = lessonTime("starts_at")
        let end = lessonTime("ends_at")
        let live = now >= start && now < end
        let seconds = max(0, Int((live ? end : start).timeIntervalSince(now)))
        let hours = seconds / 3600
        let text = (hours > 0 ? "\(hours)h " : "") + "\(seconds / 60 % 60)m " + String(format: "%02d", seconds % 60) + "s"
        return HStack {
            VStack(alignment: .leading, spacing: 3) {
                Text(live ? "Time remaining" : "Starts in").font(Brand.font(11, .semibold)).foregroundStyle(Color(hex: 0xD8E2F2))
                Text(text).font(Brand.font(26, .heavy)).foregroundStyle(Brand.gold)
            }
            Spacer()
            if teacher {
                NavigationLink { ScheduleView() } label: { Text("Report absence").font(Brand.font(11, .semibold)).padding(12).foregroundStyle(Brand.navy).background(Brand.gold, in: Capsule()) }
            }
        }.padding(16).background(Brand.navy, in: RoundedRectangle(cornerRadius: 16))
    }
    private func lessonTime(_ key: String) -> Date {
        if let value = ISO8601DateFormatter().date(from: lesson[key + "_iso"].text) { return value }
        let formatter = DateFormatter(); formatter.locale = Locale(identifier: "en_US_POSIX"); formatter.dateFormat = "yyyy-MM-dd HH:mm:ss"
        return formatter.date(from: date + " " + lesson[key].text) ?? Date()
    }
}
