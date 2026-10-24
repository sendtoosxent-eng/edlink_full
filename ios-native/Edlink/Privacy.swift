import SwiftUI
import LocalAuthentication

struct UnlockView: View {
    @EnvironmentObject private var session: Session
    @Environment(\.scenePhase) private var phase
    @State private var busy = false
    @State private var error: String?
    var body: some View {
        VStack(spacing: 24) {
            BrandMark()
            Image(systemName: "lock.shield").font(.system(size: 56)).foregroundStyle(Brand.gold).padding(.top, 24)
            Text("Welcome back").font(Brand.font(30, .bold))
            Text("Unlock Edlink to view your school account.").foregroundStyle(.secondary)
            if let error { ErrorBanner(message: error) }
            PrimaryButton(title: "Unlock Edlink", busy: busy) { Task { await unlock() } }.frame(maxWidth: 300)
        }.padding(24).task { if phase == .active { await unlock() } }
        .onChange(of: phase) { _, next in if next == .active { Task { await unlock() } } }
    }
    func unlock() async {
        guard !busy else { return }; busy = true; error = nil
        do { let success = try await LAContext().evaluatePolicy(.deviceOwnerAuthentication, localizedReason: "Unlock your Edlink school account"); if success { session.locked = false } }
        catch { self.error = error.localizedDescription }; busy = false
    }
}
struct PrivacySettings: View {
    @AppStorage("edlink.requireUnlock") private var enabled = false
    @State private var error: String?
    var body: some View {
        Section("Privacy") {
            Toggle("Require Face ID or device passcode", isOn: Binding(get: { enabled }, set: { next in
                Task {
                    do { let success = try await LAContext().evaluatePolicy(.deviceOwnerAuthentication, localizedReason: next ? "Protect access to your Edlink account" : "Change your Edlink unlock preference"); if success { enabled = next } }
                    catch { self.error = error.localizedDescription }
                }
            }))
            Text("Edlink locks when you leave the app. Your iPhone handles authentication.").font(Brand.font(10)).foregroundStyle(.secondary)
            if let error { ErrorBanner(message: error) }
        }
    }
}
