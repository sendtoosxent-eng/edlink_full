import SwiftUI

struct SheetCurve: Shape {
    func path(in rect: CGRect) -> Path {
        var path = Path()
        path.move(to: CGPoint(x: 0, y: rect.height * 48 / 56))
        path.addQuadCurve(to: CGPoint(x: rect.width, y: rect.height * 48 / 56), control: CGPoint(x: rect.midX, y: rect.height * -40 / 56))
        path.addLine(to: CGPoint(x: rect.width, y: rect.height))
        path.addLine(to: CGPoint(x: 0, y: rect.height)); path.closeSubpath()
        return path
    }
}
struct IntroPage: View {
    let index: Int
    let next: () -> Void
    let back: () -> Void
    let skip: () -> Void
    @Environment(\.accessibilityReduceMotion) private var reduceMotion
    @State private var breathing = false
    private let pictures = ["OnboardingConnect", "OnboardingInform", "OnboardingProgress"]
    private let titles = ["Your school day,\nall in one place", "A little closer,\nevery school day", "Every step forward,\nworth celebrating"]
    private let descriptions = ["Connect with your school. Stay close to every moment of learning.", "Attendance, announcements and timetables. Everything you need to stay in the loop.", "Follow homework, results and report cards as learning turns into progress."]
    var body: some View {
        GeometryReader { geometry in
            let height = geometry.size.height
            let heroHeight = max(300, min((height + geometry.safeAreaInsets.top + geometry.safeAreaInsets.bottom) * 0.58, 620)) + 26 - geometry.safeAreaInsets.top
            ScrollView(showsIndicators: false) {
                VStack(spacing: 0) {
                    ZStack(alignment: .bottom) {
                        Circle().fill(Brand.gold.opacity(0.07)).frame(width: 320, height: 320)
                        Image(pictures[index]).resizable().scaledToFit()
                            .frame(maxWidth: min(geometry.size.width * 1.12, 460), maxHeight: heroHeight - 64)
                            .scaleEffect(breathing && !reduceMotion ? 1.025 : 1)
                            .offset(y: breathing && !reduceMotion ? -4 : 0)
                            .padding(.bottom, -22)
                            .accessibilityLabel(["Smiling students ready for school", "Students ready for their school day", "Student holding a laptop and school books"][index])
                        HStack {
                            Image(["IntroLearning", "IntroConnection", "IntroGrowth"][index]).resizable().scaledToFit().frame(width: 60, height: 60).rotationEffect(.degrees(-17)).offset(y: -heroHeight * 0.47)
                            Spacer()
                            Image("IntroOrbit").resizable().scaledToFit().frame(width: 50, height: 50).rotationEffect(.degrees(16)).offset(y: -heroHeight * 0.32)
                        }.padding(.horizontal, 22).accessibilityHidden(true)
                        Circle().fill(Brand.gold).frame(width: 9, height: 9).offset(x: geometry.size.width / 2 - 46, y: -heroHeight * 0.69).accessibilityHidden(true)
                        VStack {
                            HStack {
                                Group { if index > 0 { Button(action: back) { Image(systemName: "arrow.left").frame(width: 44, height: 44).background(.white.opacity(0.08), in: Circle()) }.accessibilityLabel("Previous introduction") } else { Color.clear.frame(width: 44, height: 44) } }.frame(width: 64)
                                Spacer(minLength: 0)
                                Image("BrandLogoLight").resizable().scaledToFit().frame(width: 148, height: 46).accessibilityLabel("Edlink — Innovating paperless learning")
                                Spacer(minLength: 0)
                                Group { if index == 0 { Button("Skip", action: skip).font(Brand.font(12, .medium)).frame(minWidth: 56, minHeight: 44) } else { Color.clear.frame(width: 56, height: 44) } }.frame(width: 64)
                            }.foregroundStyle(.white)
                            Spacer()
                        }.padding(.horizontal, 24).padding(.top, 10)
                    }.frame(height: heroHeight).clipped()
                    VStack(spacing: 0) {
                        Text(titles[index]).font(Brand.font(height < 650 ? 24 : 27, .bold)).tracking(-0.5).lineSpacing(2).multilineTextAlignment(.center).foregroundStyle(Brand.navy)
                        Text(descriptions[index]).font(Brand.font(13)).lineSpacing(3).multilineTextAlignment(.center).foregroundStyle(Brand.muted).frame(maxWidth: 310).padding(.top, 10)
                        IntroDots(index: index).padding(.top, 24)
                        Spacer(minLength: 22)
                        PrimaryButton(title: index == 2 ? "Get started" : "Next", action: next).frame(maxWidth: 420)
                    }.padding(.horizontal, 28).padding(.top, height < 650 ? 12 : 20).padding(.bottom, 20)
                        .frame(maxWidth: .infinity).frame(minHeight: max(290, height - heroHeight), alignment: .top)
                        .background(.white).overlay(alignment: .top) { SheetCurve().fill(.white).frame(height: 56).offset(y: -55).allowsHitTesting(false) }
                }.frame(minHeight: height)
            }.scrollBounceBehavior(.basedOnSize).background(Brand.navy)
        }.background(Brand.navy.ignoresSafeArea()).ignoresSafeArea(.container, edges: .bottom).onAppear { withAnimation(reduceMotion ? nil : .easeInOut(duration: 2.6).repeatForever(autoreverses: true)) { breathing = true } }
    }
}
struct IntroDots: View {
    let index: Int
    var body: some View { HStack(spacing: 6) { ForEach(0..<3) { dot in Circle().fill(index == dot ? Brand.gold : Color(hex: 0xDDE0E6)).frame(width: index == dot ? 7 : 5, height: index == dot ? 7 : 5) } }.accessibilityElement(children: .ignore).accessibilityLabel("Introduction \(index + 1) of 3") }
}
struct RolePage: View {
    let select: (String) -> Void
    @State private var active = 0
    private let roles = ["teacher", "student", "parent"]
    private let eyebrows = ["Inspire. Teach. Make a difference.", "Your learning journey starts here.", "Stay close to their school day."]
    private let descriptions = ["Take attendance, share homework and enter marks. Keep every class moving forward, all from one place.", "Find your timetable, keep up with homework and celebrate your results. Your school day, made simpler.", "Follow your child’s attendance, progress and school notices. Stay connected to every step of their learning."]
    var body: some View {
        GeometryReader { geometry in
            TabView(selection: $active) {
                ForEach(0..<3) { index in
                    ScrollView(showsIndicators: false) {
                        VStack(spacing: 0) {
                            VStack(spacing: 18) {
                                Image("BrandLogoLight").resizable().scaledToFit().frame(width: 150, height: 48).accessibilityLabel("Edlink")
                                Text("Choose your Role to continue").font(Brand.font(9, .semibold)).tracking(1.6).foregroundStyle(Color(hex: 0xD1D7E2))
                                Image("Role" + roles[index].capitalized).resizable().scaledToFit().padding(min(geometry.size.width * 0.66, 290) * 0.12)
                                    .frame(width: min(geometry.size.width * 0.66, 290), height: min(geometry.size.width * 0.66, 290))
                                    .background(Color(hex: 0xFFF5D5), in: Circle()).overlay(Circle().stroke(Brand.gold, lineWidth: 2)).padding(.top, 4)
                            }.padding(.top, 14).padding(.bottom, 60).frame(maxWidth: .infinity).background(Brand.navy)
                            VStack(spacing: 0) {
                                Text(eyebrows[index]).font(Brand.font(11, .medium)).foregroundStyle(Brand.goldDark).multilineTextAlignment(.center)
                                Text("I’m a \(roles[index])").font(Brand.font(30, .bold)).tracking(-0.7).foregroundStyle(Brand.navy).padding(.top, 8)
                                Text(descriptions[index]).font(Brand.font(13)).lineSpacing(3).foregroundStyle(Brand.muted).multilineTextAlignment(.center).frame(maxWidth: 320).padding(.top, 10)
                                HStack(spacing: 24) {
                                    Button { withAnimation { active -= 1 } } label: { Image(systemName: "chevron.left").frame(width: 44, height: 44).background(Color(hex: 0xF4F6FA), in: Circle()) }.opacity(index == 0 ? 0 : 1).disabled(index == 0).accessibilityLabel("Previous role")
                                    HStack(spacing: 8) { ForEach(0..<3) { dot in Button { withAnimation { active = dot } } label: { Capsule().fill(active == dot ? Brand.gold : Color(hex: 0xDDE0E6)).frame(width: active == dot ? 20 : 6, height: 6).frame(width: 20, height: 44) }.accessibilityLabel("Show \(roles[dot]) role") } }
                                    Button { withAnimation { active += 1 } } label: { Image(systemName: "chevron.right").frame(width: 44, height: 44).background(Color(hex: 0xF4F6FA), in: Circle()) }.opacity(index == 2 ? 0 : 1).disabled(index == 2).accessibilityLabel("Next role")
                                }.foregroundStyle(Brand.navy).padding(.top, 16)
                                Text("Swipe to explore roles").font(Brand.font(10)).foregroundStyle(Brand.muted).padding(.top, 3)
                                Spacer(minLength: 24)
                                PrimaryButton(title: "Continue as \(roles[index])") { select(roles[index]) }.frame(maxWidth: 420)
                                Text("Use the role linked to your school account.").font(Brand.font(10)).foregroundStyle(Brand.muted).padding(.top, 12)
                            }.padding(.horizontal, 28).padding(.top, 12).padding(.bottom, 20).frame(maxWidth: .infinity)
                                .frame(minHeight: max(325, geometry.size.height - (min(geometry.size.width * 0.66, 290) + 211)))
                                .background(.white).overlay(alignment: .top) { SheetCurve().fill(.white).frame(height: 56).offset(y: -55).allowsHitTesting(false) }
                        }.frame(minHeight: geometry.size.height)
                    }.scrollBounceBehavior(.basedOnSize).tag(index)
                }
            }.tabViewStyle(.page(indexDisplayMode: .never))
        }.background(Brand.navy.ignoresSafeArea()).ignoresSafeArea(.container, edges: .bottom)
    }
}
struct SplashPage: View {
    @State private var visible = false
    @Environment(\.accessibilityReduceMotion) private var reduceMotion
    var body: some View {
        VStack(spacing: -54) {
            Image("BrandLogo").resizable().scaledToFit().frame(width: 340, height: 250)
            Text("Innovating paperless learning.").font(Brand.font(17, .heavy)).foregroundStyle(Brand.navy)
        }.opacity(visible ? 1 : 0).scaleEffect(visible || reduceMotion ? 1 : 0.94).frame(maxWidth: .infinity, maxHeight: .infinity).background(Brand.gold.ignoresSafeArea())
            .onAppear { withAnimation(reduceMotion ? nil : .easeOut(duration: 0.6)) { visible = true } }
    }
}

// MARK: - Previews (Xcode canvas)

/// Interactive host so Next / Back / Skip actually move between the three intro screens in the canvas.
private struct IntroPreviewHost: View {
    @State private var index = 0
    var body: some View {
        IntroPage(
            index: index,
            next: { if index < 2 { index += 1 } },
            back: { if index > 0 { index -= 1 } },
            skip: { index = 2 }
        )
    }
}

#Preview("Intro – Interactive") {
    IntroPreviewHost()
}

#Preview("Intro 1 – Connect") {
    IntroPage(index: 0, next: {}, back: {}, skip: {})
}

#Preview("Intro 2 – Inform") {
    IntroPage(index: 1, next: {}, back: {}, skip: {})
}

#Preview("Intro 3 – Progress") {
    IntroPage(index: 2, next: {}, back: {}, skip: {})
}

#Preview("Role Picker") {
    RolePage(select: { role in print("Selected role: \(role)") })
}

#Preview("Splash") {
    SplashPage()
}
