// swift-tools-version: 5.9
import PackageDescription

let package = Package(
    name: "EdlinkCoreChecks",
    platforms: [.macOS(.v14), .iOS(.v17)],
    targets: [
        .target(name: "EdlinkCore", path: "Edlink", exclude: ["App.swift", "Ionicon.swift", "AttendanceHistory.swift", "Calendar.swift", "DesignPreview.swift", "Assets.xcassets", "Attendance.swift", "Auth.swift", "Dashboard.swift", "Design.swift", "Fonts", "Homework.swift", "Info.plist", "Marks.swift", "Navigation.swift", "Onboarding.swift", "Privacy.swift", "PrivacyInfo.xcprivacy", "Profile.swift", "Reports.swift", "Schedule.swift", "School.swift", "Workspace.swift"], sources: ["Core.swift"]),
        .testTarget(name: "EdlinkCoreTests", dependencies: ["EdlinkCore"], path: "Tests")
    ]
)
