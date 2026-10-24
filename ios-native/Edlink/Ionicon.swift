import SwiftUI

// The same Ionicons font and glyphs used by @expo/vector-icons in the TypeScript app.
struct Ionicon: View {
    let name: String
    var size: CGFloat = 21
    private static let glyphs: [String: UInt32] = [
        "home": 62338,
        "home-outline": 62339,
        "calendar": 61903,
        "calendar-outline": 61910,
        "book": 61861,
        "book-outline": 61862,
        "trophy": 62977,
        "trophy-outline": 62978,
        "wallet": 63013,
        "wallet-outline": 63014,
        "person": 62629,
        "person-outline": 62636
    ]
    var body: some View { Text(Self.glyphs[name].flatMap(UnicodeScalar.init).map(String.init) ?? "").font(.custom("Ionicons", size: size)).accessibilityHidden(true) }
}
