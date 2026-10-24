import SwiftUI

private enum AuthStep: String { case splash, connect, inform, progress, role, school, email, password, otp, recovery }
struct SignInView: View {
    @EnvironmentObject private var session: Session
    @Environment(\.accessibilityReduceMotion) private var reduceMotion
    @State private var step: AuthStep = {
        #if DEBUG
        if let argument = ProcessInfo.processInfo.arguments.first(where: { $0.hasPrefix("--edlink-auth-step=") }), let value = AuthStep(rawValue: String(argument.split(separator: "=").last ?? "")) { return value }
        #endif
        return .splash
    }()
    @State private var role = "student"
    @State private var school = ""
    @State private var identity: JSON = .null
    @State private var email = ""
    @State private var password = ""
    @State private var account: JSON = .null
    @State private var challenge = ""
    @State private var maskedEmail = ""
    @State private var code = ""
    @State private var busy = false
    @State private var error: String?
    @State private var recoveryMode = "request"
    @State private var resetToken = ""
    @State private var newPassword = ""
    @State private var confirmation = ""
    @State private var resendAvailable = Date().addingTimeInterval(60)
    @State private var resent = false
    @FocusState private var codeFocused: Bool
    var body: some View {
        Group {
            switch step {
            case .splash: SplashPage()
            case .connect, .inform, .progress:
                IntroPage(index: step == .connect ? 0 : step == .inform ? 1 : 2,
                    next: { go(step == .connect ? .inform : step == .inform ? .progress : .role) },
                    back: { go(step == .progress ? .inform : .connect) }, skip: { go(.role) })
            case .role: RolePage { value in
                if role != value { email = ""; password = ""; account = .null; code = ""; challenge = "" }
                role = value; go(.school)
            }
            case .school: schoolPage
            case .email: emailPage
            case .password: passwordPage
            case .otp: otpPage
            case .recovery: recoveryPage
            }
        }.font(Brand.font(14)).transition(.opacity.combined(with: .offset(x: reduceMotion ? 0 : 20))).preferredColorScheme(step == .splash ? .light : .dark)
         .task {
            #if DEBUG
            if ProcessInfo.processInfo.arguments.contains(where: { $0.hasPrefix("--edlink-auth-step=") }) {
                identity = .object(["name": .string("Edlink Demonstration School"), "number": .string("EDL-PREVIEW")])
                school = "EDL-PREVIEW"; email = "teacher@example.test"
                account = .object(["name": .string("Grace Namusoke"), "role": .string("teacher"), "staff_number": .string("STF-001")])
                maskedEmail = "t*****@example.test"
            }
            #endif
            if step == .splash { try? await Task.sleep(for: .milliseconds(1800)); guard !Task.isCancelled else { return }; go(.connect) }
        }
    }
    private var schoolPage: some View {
        AuthPage(heroHeight: 310) {
                    BrandMark(light: true, width: 170).padding(.top, 25)
                    Text("INNOVATING PAPERLESS LEARNING").font(Brand.font(9, .bold)).tracking(1.1).foregroundStyle(Brand.gold).padding(.top, 18)
                    Text("Find your school").font(Brand.font(28, .heavy)).foregroundStyle(.white).padding(.top, 14)
                    Text("Connect securely to your school workspace before signing in.").font(Brand.font(12)).foregroundStyle(.white.opacity(0.78)).multilineTextAlignment(.center).frame(maxWidth: 350).padding(.top, 8)
                } content: {
                    authTitle("Enter your school number", "Use the number provided by your school administrator. We’ll load the correct school name and logo for you.", size: 24)
                    AuthField(label: "School number", placeholder: "e.g. EDL-4K9P2", icon: "building.2", text: $school, uppercase: true).padding(.top, 18)
                    errors
                    PrimaryButton(title: "Continue", busy: busy, arrow: true) { perform { try await findSchool() } }.padding(.top, 20)
                    PrimaryButton(title: "Back", secondary: true) { go(.role) }.padding(.top, 12).disabled(busy)
                }
    }
    private var emailPage: some View {
        AuthPage(heroHeight: 350) { schoolHeader } content: {
                    authTitle(role == "parent" ? "Your parent email" : "Your school email", role == "parent" ? "Enter the email address registered with your child’s school." : "Enter the school email address your administrator gave you.")
                    AuthField(label: "Email address", placeholder: "you@school.com", icon: "envelope", text: $email, email: true).padding(.top, 18)
                    Text("Forgot your email? Please visit your school administrator, or").font(Brand.font(12)).foregroundStyle(Brand.muted).padding(.top, 12)
                    Button("click here to return to role selection.") { go(.role) }.font(Brand.font(12, .bold)).foregroundStyle(Brand.goldDark).disabled(busy)
                    errors
                    PrimaryButton(title: "Continue", busy: busy, arrow: true) { perform { try await findAccount() } }.padding(.top, 15)
                    PrimaryButton(title: "Back", secondary: true) { go(.school) }.padding(.top, 12).disabled(busy)
                }
    }
    private var passwordPage: some View {
        AuthPage(heroHeight: 365) { accountHeader(changeEmail: true) } content: {
                    authTitle("Your password", "One last step to your school workspace.")
                    AuthField(label: "Password", placeholder: "Enter your password", icon: "lock", text: $password, secure: true).padding(.top, 18)
                    HStack { Spacer(); Button("Forgot password?") { password = ""; recoveryMode = "request"; go(.recovery) }.font(Brand.font(13, .bold)).foregroundStyle(Brand.goldDark).frame(minHeight: 40) }
                    errors
                    PrimaryButton(title: "Log in to Edlink", busy: busy, arrow: true) { perform { try await signIn() } }.padding(.top, 15)
                    PrimaryButton(title: "Back", secondary: true) { password = ""; go(.email) }.padding(.top, 12).disabled(busy)
                }
    }
    private var otpPage: some View {
        AuthPage(heroHeight: 340) { accountHeader(changeEmail: false) } content: {
                    authTitle("Verify it’s you", "Enter the 6-digit code sent to \(maskedEmail). It expires in 10 minutes.", size: 30)
                    ZStack {
                        TextField("Verification code", text: $code).keyboardType(.numberPad).textContentType(.oneTimeCode).focused($codeFocused).opacity(0.015)
                            .onChange(of: code) { _, value in code = String(value.filter(\.isNumber).prefix(6)); error = nil }
                        HStack(spacing: 7) { ForEach(0..<6) { index in
                            Text(index < code.count ? String(Array(code)[index]) : "").font(Brand.font(23, .heavy)).foregroundStyle(Brand.navy).frame(maxWidth: 45).frame(height: 58).frame(maxWidth: .infinity)
                                .background(code.count == index ? Color(hex: 0xFFF8DD) : Color(hex: 0xEEF2F8), in: RoundedRectangle(cornerRadius: 14))
                                .overlay(RoundedRectangle(cornerRadius: 14).stroke(code.count == index ? Brand.goldDark : Brand.navy, lineWidth: 2))
                        } }.allowsHitTesting(false)
                    }.contentShape(Rectangle()).onTapGesture { codeFocused = true }.padding(.top, 28)
                    TimelineView(.periodic(from: .now, by: 1)) { context in
                        let seconds = max(0, Int(ceil(resendAvailable.timeIntervalSince(context.date))))
                        Button(seconds > 0 ? "Resend code in \(seconds)s" : "Resend verification code") {
                            perform { let response = try await API().request("/auth/otp/resend", method: "POST", body: .strings(["challenge_token": challenge])); maskedEmail = response.data["masked_email"].text; resendAvailable = Date().addingTimeInterval(60); resent = true }
                        }.font(Brand.font(13, .bold)).foregroundStyle(seconds > 0 ? Brand.muted : Brand.goldDark).frame(maxWidth: .infinity, minHeight: 44).disabled(seconds > 0 || busy)
                    }
                    errors
                    if resent { Text("A new verification code was sent.").font(Brand.font(12, .bold)).foregroundStyle(.green) }
                    PrimaryButton(title: "Verify and continue", busy: busy) { perform { try await verify() } }.disabled(code.count != 6).opacity(code.count == 6 ? 1 : 0.45).padding(.top, 16)
                    PrimaryButton(title: "Back", secondary: true) { code = ""; go(.password) }.padding(.top, 12).disabled(busy)
                }
    }
    private var recoveryPage: some View {
        AuthPage(heroHeight: recoveryMode == "reset" ? 210 : 350) { schoolHeader } content: {
                    if recoveryMode == "request" {
                        authTitle("Forgot password?", "Enter your school email and we’ll send you a secure password reset link.", size: 26)
                        AuthField(label: "Email address", placeholder: "you@school.com", icon: "envelope", text: $email, email: true).padding(.top, 12)
                    } else if recoveryMode == "sent" {
                        authTitle("Check your email", "If \(email.trimmingCharacters(in: .whitespaces)) matches your school account, we’ve sent a reset link. Open it to choose a new password.", size: 26)
                        Button("Change email or send again") { recoveryMode = "request"; error = nil }.font(Brand.font(12, .semibold)).foregroundStyle(Brand.goldDark).padding(.top, 16)
                    } else {
                        authTitle("Create a new password", "Paste the token from your reset email. Your new password needs at least 8 characters.", size: 26)
                        AuthField(label: "Reset token", placeholder: "Reset token", icon: "key", text: $resetToken).padding(.top, 10)
                        AuthField(label: "New password", placeholder: "New password", icon: "lock", text: $newPassword, secure: true).padding(.top, 10)
                        AuthField(label: "Confirm new password", placeholder: "Confirm new password", icon: "lock", text: $confirmation, secure: true).padding(.top, 10)
                    }
                    errors
                    PrimaryButton(title: recoveryMode == "request" ? "Send reset link" : recoveryMode == "sent" ? "I have a reset token" : "Set new password", busy: busy) { perform { try await recover() } }.padding(.top, 20)
                    PrimaryButton(title: "Back", secondary: true) { error = nil; if recoveryMode == "reset" { newPassword = ""; confirmation = ""; recoveryMode = "sent" } else if recoveryMode == "sent" { recoveryMode = "request" } else { go(.password) } }.padding(.top, 12).disabled(busy)
                }
    }
    private var errors: some View { Group { if let error { ErrorBanner(message: error).padding(.top, 10) } } }
    private func authTitle(_ title: String, _ subtitle: String, size: CGFloat = 27) -> some View {
        VStack(alignment: .leading, spacing: 7) { Text(title).font(Brand.font(size, .bold)).foregroundStyle(Brand.navy); Text(subtitle).font(Brand.font(13)).lineSpacing(3).foregroundStyle(Brand.muted) }.frame(maxWidth: .infinity, alignment: .leading)
    }
    private var schoolHeader: some View {
        VStack(spacing: 12) {
            BrandMark(light: true, width: 156)
            AsyncImage(url: URL(string: identity["logo_url"].text)) { image in image.resizable().scaledToFit().padding(10) } placeholder: { Text(String(identity["name"].text.prefix(1))).font(Brand.font(36, .heavy)).foregroundStyle(Brand.navy) }.frame(width: 120, height: 120).background(.white, in: Circle()).overlay(Circle().stroke(Brand.gold, lineWidth: 3))
            Text(identity["name"].text).font(Brand.font(25, .heavy)).foregroundStyle(.white).multilineTextAlignment(.center).lineLimit(3)
            Text(identity["number"].text).font(Brand.font(11, .semibold)).tracking(1).foregroundStyle(.white)
        }.padding(.top, 8)
    }
    private func accountHeader(changeEmail: Bool) -> some View {
        VStack(spacing: 0) {
            BrandMark(light: true, width: 156).padding(.bottom, 18)
            AsyncImage(url: URL(string: account["avatar_url"].text)) { image in image.resizable().scaledToFill() } placeholder: { DefaultPortrait().padding(15) }.frame(width: 130, height: 130).background(Color(hex: 0xFFF5D5), in: Circle()).clipShape(Circle()).overlay(Circle().stroke(Brand.gold, lineWidth: 2))
            Text(account["name"].text.isEmpty ? "Your account" : account["name"].text).font(Brand.font(20, .bold)).foregroundStyle(.white).padding(.top, 10)
            Text(email).font(Brand.font(12)).foregroundStyle(Color(hex: 0xD1D7E2)).padding(.top, 3)
            if !account["staff_number"].text.isEmpty { Text("Staff number: " + account["staff_number"].text).font(Brand.font(12)).foregroundStyle(Color(hex: 0xD1D7E2)).padding(.top, 3) }
            if changeEmail { Button("Not you? Change email") { password = ""; go(.email) }.font(Brand.font(12, .semibold)).foregroundStyle(Brand.gold).frame(minHeight: 44).disabled(busy) }
        }.padding(.top, 8).scaleEffect(0.9, anchor: .top)
    }
    private func go(_ next: AuthStep) { error = nil; codeFocused = false; withAnimation(reduceMotion ? nil : .easeInOut(duration: 0.25)) { step = next } }
    private func perform(_ action: @escaping () async throws -> Void) { guard !busy else { return }; busy = true; error = nil; Task { do { try await action() } catch { self.error = error.localizedDescription }; busy = false } }
    private func validEmail() throws { let value = email.trimmingCharacters(in: .whitespacesAndNewlines); guard value.range(of: #"^[^\s@]+@[^\s@]+\.[^\s@]+$"#, options: .regularExpression) != nil else { throw APIError(status: 422, message: "Enter a valid email address to continue.") } }
    private func findSchool() async throws {
        guard !school.trimmingCharacters(in: .whitespaces).isEmpty else { throw APIError(status: 422, message: "Enter the school number provided by your school.") }
        identity = try await API().request("/auth/school", method: "POST", body: .strings(["school_number": school.trimmingCharacters(in: .whitespacesAndNewlines)])).data
        school = identity["number"].text; go(.email)
    }
    private func findAccount() async throws {
        try validEmail(); email = email.trimmingCharacters(in: .whitespacesAndNewlines)
        let result = try await API().request("/auth/account", method: "POST", body: .strings(["school_number": school, "email": email, "expected_role": role])).data
        guard !result["name"].text.isEmpty && result["role"].text == role else { throw APIError(status: 422, message: "Profile not found. Please check your email address and try again.") }
        account = result; go(.password)
    }
    private func signIn() async throws {
        guard !password.isEmpty else { throw APIError(status: 422, message: "Enter your password.") }
        let result = try await API().request("/auth/login", method: "POST", body: .strings(["school_number": school, "email": email, "password": password, "expected_role": role, "device_name": "Edlink for iOS"])).data
        password = ""
        if result["otp_required"].flag { challenge = result["challenge_token"].text; maskedEmail = result["masked_email"].text; code = ""; resent = false; resendAvailable = Date().addingTimeInterval(60); go(.otp) }
        else { try await session.accept(result) }
    }
    private func verify() async throws {
        guard code.count == 6 && !challenge.isEmpty else { throw APIError(status: 422, message: "Enter the complete 6-digit code.") }
        let result = try await API().request("/auth/otp/verify", method: "POST", body: .strings(["challenge_token": challenge, "code": code, "device_name": "Edlink for iOS"])).data
        try await session.accept(result)
    }
    private func recover() async throws {
        if recoveryMode == "sent" { recoveryMode = "reset"; error = nil; return }
        try validEmail()
        if recoveryMode == "request" { _ = try await API().request("/auth/password/forgot", method: "POST", body: .strings(["school_number": school, "email": email])); recoveryMode = "sent" }
        else {
            guard !resetToken.trimmingCharacters(in: .whitespaces).isEmpty && newPassword.count >= 8 && newPassword == confirmation else { throw APIError(status: 422, message: "Enter your reset token and matching passwords of at least 8 characters.") }
            _ = try await API().request("/auth/password/reset", method: "POST", body: .strings(["school_number": school, "email": email, "token": resetToken.trimmingCharacters(in: .whitespaces), "password": newPassword, "password_confirmation": confirmation])); newPassword = ""; confirmation = ""; resetToken = ""; go(.password)
        }
    }
}

struct AuthPage<Header: View, Content: View>: View {
    let heroHeight: CGFloat
    @ViewBuilder let header: () -> Header
    @ViewBuilder let content: () -> Content
    var body: some View {
        GeometryReader { geometry in
            ScrollView(showsIndicators: false) {
                VStack(spacing: 0) {
                    ZStack(alignment: .topTrailing) {
                        Circle().fill(Brand.gold.opacity(0.16)).frame(width: 190, height: 190).offset(x: 70, y: -100)
                        Circle().stroke(Brand.gold.opacity(0.18), lineWidth: 9).frame(width: 54, height: 54).frame(maxWidth: .infinity, alignment: .leading).offset(x: -18, y: 90)
                        VStack(spacing: 0) { header() }.padding(.horizontal, 24).frame(maxWidth: .infinity)
                    }.frame(height: min(heroHeight, max(180, geometry.size.height * 0.49)), alignment: .top).clipped()
                    VStack(alignment: .leading, spacing: 0) { content() }.environment(\.colorScheme, .light).padding(.horizontal, 20).padding(.top, 12).padding(.bottom, 24).frame(maxWidth: 500)
                        .frame(maxWidth: .infinity).frame(minHeight: max(300, geometry.size.height - min(heroHeight, max(180, geometry.size.height * 0.49))), alignment: .top)
                        .background(.white).overlay(alignment: .top) { SheetCurve().fill(.white).frame(height: 56).offset(y: -55).allowsHitTesting(false) }
                }.frame(minHeight: geometry.size.height)
            }.scrollBounceBehavior(.basedOnSize).scrollDismissesKeyboard(.interactively)
        }.background(Brand.navy.ignoresSafeArea()).ignoresSafeArea(.container, edges: .bottom)
    }
}
struct AuthField: View {
    let label: String
    let placeholder: String
    let icon: String
    @Binding var text: String
    var secure = false
    var email = false
    var uppercase = false
    @State private var visible = false
    @FocusState private var focused: Bool
    var body: some View {
        VStack(alignment: .leading, spacing: 7) {
            Text(label).font(Brand.font(13, .heavy)).foregroundStyle(Brand.navy)
            HStack(spacing: 11) {
                Image(icon == "building.2" ? "SchoolGlyph" : icon == "envelope" ? "AuthEmailGlyph" : icon == "key" ? "AuthTokenGlyph" : "AuthLockGlyph").resizable().scaledToFit().frame(width: icon == "building.2" ? 30 : 26, height: icon == "building.2" ? 30 : 26).frame(width: 44, height: 44).background(Brand.navy, in: RoundedRectangle(cornerRadius: 14))
                Group {
                    if secure && !visible { SecureField(placeholder, text: $text).textContentType(.password) }
                    else { TextField(placeholder, text: $text).keyboardType(email ? .emailAddress : .default).textContentType(email ? .username : secure ? .password : .none) }
                }.font(Brand.font(15)).foregroundStyle(Brand.navy).textInputAutocapitalization(uppercase ? .characters : .never).autocorrectionDisabled().focused($focused).environment(\.colorScheme, .light)
                if secure { Button { visible.toggle() } label: { Image(systemName: visible ? "eye.slash" : "eye").foregroundStyle(Brand.navy) }.accessibilityLabel(visible ? "Hide password" : "Show password") }
                else if uppercase && !text.isEmpty { Button { text = "" } label: { Image(systemName: "xmark.circle.fill").foregroundStyle(Brand.muted) }.accessibilityLabel("Clear school number") }
            }.padding(.horizontal, 9).frame(minHeight: 62).background(.white, in: RoundedRectangle(cornerRadius: 20)).overlay(RoundedRectangle(cornerRadius: 20).stroke(focused ? Brand.gold : Brand.outline, lineWidth: 1))
        }.onChange(of: text) { _, value in if uppercase { text = value.uppercased() } }.onChange(of: focused) { _, value in if !value { visible = false } }
    }
}
struct DefaultPortrait: View {
    var body: some View {
        GeometryReader { geometry in
            let scale = geometry.size.width / 80
            ZStack {
                Circle().fill(Brand.gold).frame(width: 28 * scale, height: 28 * scale).position(x: 40 * scale, y: 28 * scale)
                Path { path in path.move(to: CGPoint(x: 14 * scale, y: 70 * scale)); path.addCurve(to: CGPoint(x: 66 * scale, y: 70 * scale), control1: CGPoint(x: 14 * scale, y: 35 * scale), control2: CGPoint(x: 66 * scale, y: 35 * scale)); path.closeSubpath() }.fill(Brand.navy)
                Path { path in path.move(to: CGPoint(x: 31 * scale, y: 47 * scale)); path.addLine(to: CGPoint(x: 40 * scale, y: 57 * scale)); path.addLine(to: CGPoint(x: 49 * scale, y: 47 * scale)) }.stroke(Color(hex: 0xFFF5D5), lineWidth: 3 * scale)
            }
        }
    }
}
