import SwiftUI

enum Brand {
    static let navy = Color(red: 0.043, green: 0.075, blue: 0.169)
    static let gold = Color(red: 0.937, green: 0.69, blue: 0)
    static let background = adaptive(0xF7F8FC, 0x080E1D)
    static let surface = adaptive(0xFFFFFF, 0x172239)
    static let ink = adaptive(0x0B132B, 0xEEF2FA)
    static let muted = Color(hex: 0x707785)
    static let outline = adaptive(0xE3E7EF, 0x2C3B55)
    static let goldDark = Color(hex: 0x8A6500)
    static func adaptive(_ light: UInt32, _ dark: UInt32) -> Color { Color(uiColor: UIColor { traits in let hex = traits.userInterfaceStyle == .dark ? dark : light; return UIColor(red: CGFloat((hex >> 16) & 255) / 255, green: CGFloat((hex >> 8) & 255) / 255, blue: CGFloat(hex & 255) / 255, alpha: 1) }) }
    static func font(_ size: CGFloat, _ weight: Font.Weight = .regular) -> Font {
        let name: String
        switch weight { case .medium: name = "Medium"; case .semibold: name = "SemiBold"; case .bold: name = "Bold"; case .heavy, .black: name = "ExtraBold"; default: name = "Regular" }
        return .custom("Poppins-" + name, size: size)
    }
}
struct BrandMark: View {
    @Environment(\.colorScheme) private var colorScheme
    var light = false
    var width: CGFloat = 134
    var body: some View { Image(light || colorScheme == .dark ? "BrandLogoLight" : "BrandLogo").resizable().scaledToFit().frame(width: width, height: width * 0.42).accessibilityLabel("Edlink — Innovating paperless learning") }
}
struct PrimaryButton: View {
    let title: String
    var busy = false
    var secondary = false
    var arrow = false
    let action: () -> Void
    var body: some View {
        Button(action: action) {
            HStack(spacing: 10) { Spacer(); if busy { ProgressView().tint(secondary ? Brand.navy : Brand.gold) } else { Text(title).font(Brand.font(14, .semibold)); if arrow { Image(systemName: "arrow.right.circle.fill").font(.system(size: 23)) } }; Spacer() }.frame(minHeight: 54)
        }.buttonStyle(.plain).foregroundStyle(secondary ? Brand.navy : Brand.gold).background(secondary ? Brand.gold : Brand.navy, in: Capsule()).opacity(busy ? 0.65 : 1).disabled(busy)
    }
}
struct ErrorBanner: View {
    let message: String
    var body: some View { Label(message, systemImage: "exclamationmark.circle").font(.subheadline).foregroundStyle(.red).accessibilityLabel(message) }
}
struct DataCard: View {
    let item: JSON
    var body: some View {
        VStack(alignment: .leading, spacing: 8) {
            Text(title).font(.headline)
            ForEach(fields, id: \.self) { key in
                HStack(alignment: .top) {
                    Text(key.replacingOccurrences(of: "_", with: " ").capitalized).foregroundStyle(.secondary)
                    Spacer()
                    Text(item[key].text).multilineTextAlignment(.trailing)
                }.font(.subheadline)
            }
        }.padding(.vertical, 5)
    }
    var title: String { ["title", "name", "subject", "label", "attendance_date", "type", "period"].map { item[$0].text }.first { !$0.isEmpty } ?? "Record" }
    var fields: [String] { item.object.keys.sorted().filter { !["id", "title", "name", "school_id", "user_id", "student_id", "attachment_path", "photo_path", "created_at", "updated_at"].contains($0) && !item[$0].text.isEmpty } }
}
struct DetailsView: View {
    let value: JSON
    var body: some View {
        ForEach(value.object.keys.sorted(), id: \.self) { key in
            let entry = value[key]
            if !entry.text.isEmpty {
                LabeledContent(key.replacingOccurrences(of: "_", with: " ").capitalized, value: entry.text)
            } else if !entry.array.isEmpty {
                Section(key.replacingOccurrences(of: "_", with: " ").capitalized) {
                    ForEach(Array(entry.array.enumerated()), id: \.offset) { _, row in
                        if !row.object.isEmpty { DetailsView(value: row) } else { Text(row.text) }
                    }
                }
            } else if !entry.object.isEmpty { Section(key.replacingOccurrences(of: "_", with: " ").capitalized) { DetailsView(value: entry) } }
        }
    }
}

extension Color {
    init(hex: UInt32) { self.init(red: Double((hex >> 16) & 255) / 255, green: Double((hex >> 8) & 255) / 255, blue: Double(hex & 255) / 255) }
}

func displayDate(_ text: String) -> String {
    let formatter = ISO8601DateFormatter()
    formatter.formatOptions = [.withInternetDateTime, .withFractionalSeconds]
    let date = formatter.date(from: text) ?? ISO8601DateFormatter().date(from: text)
    if let date { return date.formatted(date: .abbreviated, time: .shortened) }
    let day = DateFormatter(); day.locale = Locale(identifier: "en_US_POSIX"); day.dateFormat = "yyyy-MM-dd"
    if let date = day.date(from: String(text.prefix(10))) { return date.formatted(.dateTime.weekday(.wide).day().month(.wide)) }
    return text
}

struct NoticeCard: View {
    let item: JSON
    var body: some View {
        VStack(alignment: .leading, spacing: 12) {
            HStack(spacing: 12) {
                Image(systemName: "bell").font(.system(size: 21)).foregroundStyle(Brand.navy).frame(width: 42, height: 42).background(Color(hex: 0xFFF5D5), in: RoundedRectangle(cornerRadius: 14))
                VStack(alignment: .leading, spacing: 4) { Text(item["category"].text.replacingOccurrences(of: "_", with: " ").uppercased()).font(Brand.font(9, .semibold)).foregroundStyle(Brand.goldDark); Text(item["title"].text).font(Brand.font(15, .bold)) }
                Spacer(minLength: 0)
                if item.object.keys.contains("read_at") && item["read_at"] == .null { Circle().fill(Brand.gold).frame(width: 7, height: 7) }
            }
            Text(item["message"].text.isEmpty ? item["body"].text : item["message"].text).font(Brand.font(12)).lineSpacing(4).foregroundStyle(Brand.muted)
            if !item["attachment_name"].text.isEmpty { Label(item["attachment_name"].text, systemImage: "paperclip").font(Brand.font(11, .semibold)) }
            Text(displayDate(item["created_at"].text.isEmpty ? item["sent_at"].text : item["created_at"].text)).font(Brand.font(10)).foregroundStyle(Brand.muted)
        }.padding(.vertical, 8)
    }
}

struct PageIntro: View {
    let title: String
    let description: String
    var body: some View {
        VStack(alignment: .leading, spacing: 5) { Text("EDLINK").font(Brand.font(10, .heavy)).tracking(1.4); Text(title).font(Brand.font(25, .heavy)); Text(description).font(Brand.font(12)).lineSpacing(3) }.foregroundStyle(Brand.gold).padding(20).frame(maxWidth: .infinity, minHeight: 150, alignment: .leading).background(Brand.navy, in: RoundedRectangle(cornerRadius: 24))
    }
}
func money(_ value: JSON) -> String { let number = Double(value.text) ?? 0; return "UGX " + number.rounded().formatted(.number.precision(.fractionLength(0))) }

struct BrandGlyph: View {
    let name: String
    var size: CGFloat = 24
    @Environment(\.colorScheme) private var scheme
    var body: some View { Image("App" + name + (scheme == .dark ? "Dark" : "")).resizable().scaledToFit().frame(width: size, height: size).accessibilityHidden(true) }
}
