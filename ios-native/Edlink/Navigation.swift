import SwiftUI

@MainActor final class AppNavigation: ObservableObject {
    @Published var active = "home"
    func isRoot(_ route: String, role: String) -> Bool {
        let roots = role == "teacher" ? ["home", "attendance", "homework", "profile"] : role == "student" ? ["home", "homework", "results", "profile"] : ["home", "attendance", "payments", "profile"]
        return roots.contains(route)
    }
}
struct MainTabs: View {
    var previewData: JSON? = nil
    @EnvironmentObject private var session: Session
    @StateObject private var navigation = AppNavigation()
    @Namespace private var indicator
    private var tabs: [(String, String, String)] {
        if session.isTeacher { return [("home", "Home", "home"), ("attendance", "Attendance", "calendar"), ("homework", "Homework", "book"), ("profile", "Profile", "person")] }
        if session.role == "student" { return [("home", "Home", "home"), ("homework", "Homework", "book"), ("results", "Results", "trophy"), ("profile", "Profile", "person")] }
        return [("home", "Home", "home"), ("attendance", "Attendance", "calendar"), ("payments", "Payments", "wallet"), ("profile", "Profile", "person")]
    }
    var body: some View {
        NavigationStack {
            Group {
                switch navigation.active {
                case "attendance": AttendanceHome()
                case "homework": HomeworkList()
                case "results": ResultsView()
                case "payments": PaymentsView()
                case "profile": ProfileView()
                default: DashboardView(previewData: previewData)
                }
            }.id(session.studentID)
        }.id(navigation.active).environmentObject(navigation).safeAreaInset(edge: .bottom, spacing: 0) {
            HStack(spacing: 0) {
                ForEach(tabs, id: \.0) { tab in
                    Button { withAnimation(.spring(response: 0.35, dampingFraction: 0.85)) { navigation.active = tab.0 } } label: {
                        VStack(spacing: 3) {
                            Ionicon(name: tab.2 + (navigation.active == tab.0 ? "" : "-outline"), size: 21)
                            Text(tab.1).font(Brand.font(10, navigation.active == tab.0 ? .heavy : .semibold))
                        }.foregroundStyle(navigation.active == tab.0 ? Brand.gold : Brand.muted).frame(maxWidth: .infinity).frame(height: 52)
                            .background { if navigation.active == tab.0 { RoundedRectangle(cornerRadius: 16).fill(Brand.navy).matchedGeometryEffect(id: "activeTab", in: indicator) } }
                    }.buttonStyle(.plain).accessibilityAddTraits(navigation.active == tab.0 ? [.isSelected] : [])
                }
            }.padding(.horizontal, 18).padding(.top, 8).padding(.bottom, 6).background(Brand.surface).overlay(alignment: .top) { Brand.outline.frame(height: 0.5) }
        }
    }
}
struct ChildSwitcher: View {
    @EnvironmentObject private var session: Session
    var body: some View {
        if session.role == "parent" {
            VStack(alignment: .leading, spacing: 8) {
                Text("YOUR CHILDREN").font(Brand.font(10, .semibold)).tracking(1.3).foregroundStyle(Brand.muted)
                if session.children.isEmpty { Text("No learners are linked to this account. Contact your school administrator.").font(Brand.font(12)).foregroundStyle(Brand.muted) }
                else {
                    ScrollView(.horizontal, showsIndicators: false) {
                        HStack(spacing: 9) {
                            ForEach(session.children) { child in
                                Button { session.studentID = child.id } label: {
                                    HStack(spacing: 9) { UserAvatar(name: child["name"].text, url: child["photo_url"].text, size: 34); VStack(alignment: .leading, spacing: 2) { Text(child["name"].text).font(Brand.font(12, .bold)); Text(child["class"].text).font(Brand.font(10)) } }.padding(10).foregroundStyle(session.studentID == child.id ? .white : Brand.ink).background(session.studentID == child.id ? Brand.navy : Brand.surface, in: RoundedRectangle(cornerRadius: 16)).overlay(RoundedRectangle(cornerRadius: 16).stroke(session.studentID == child.id ? Brand.gold : Brand.outline))
                                }.buttonStyle(.plain)
                            }
                        }
                    }
                }
            }.padding(.vertical, 6)
        }
    }
}
struct UserAvatar: View {
    let name: String
    let url: String
    var size: CGFloat = 54
    var body: some View {
        AsyncImage(url: URL(string: url)) { image in image.resizable().scaledToFill() } placeholder: { Text(name.split(separator: " ").prefix(2).compactMap(\.first).map(String.init).joined()).font(Brand.font(size * 0.24, .heavy)).foregroundStyle(Brand.navy) }
            .frame(width: size, height: size).background(Brand.gold, in: Circle()).clipShape(Circle()).overlay(Circle().stroke(.white, lineWidth: 2))
    }
}
struct PageHero: View {
    let title: String
    let subtitle: String
    var artwork = "Classroom"
    var body: some View {
        HStack {
            VStack(alignment: .leading, spacing: 7) { Text(title).font(Brand.font(23, .bold)).foregroundStyle(Brand.gold); Text(subtitle).font(Brand.font(12)).lineSpacing(3).foregroundStyle(Color(hex: 0xD8E2F2)) }
            Spacer(minLength: 0)
        }.padding(22).frame(maxWidth: .infinity, minHeight: 126, alignment: .leading).padding(.trailing, 52)
            .background(Brand.navy, in: RoundedRectangle(cornerRadius: 24))
            .overlay(alignment: .trailing) { Image("Stat" + artwork).resizable().scaledToFit().frame(width: 88, height: 96).opacity(0.22).padding(.trailing, 12).allowsHitTesting(false) }
    }
}
struct PageHeroRow: View {
    let title: String
    let subtitle: String
    var artwork = "Classroom"
    var body: some View { PageHero(title: title, subtitle: subtitle, artwork: artwork).listRowInsets(EdgeInsets()).listRowBackground(Color.clear).listRowSeparator(.hidden) }
}
struct EdlinkScreen: ViewModifier {
    func body(content: Content) -> some View { content.font(Brand.font(13)).scrollContentBackground(.hidden).background(Brand.background).listStyle(.insetGrouped).navigationBarTitleDisplayMode(.inline).tint(Brand.ink) }
}
extension View { func edlinkScreen() -> some View { modifier(EdlinkScreen()) } }
