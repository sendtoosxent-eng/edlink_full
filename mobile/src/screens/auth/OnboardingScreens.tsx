import { Pressable, StyleSheet, Text, View } from 'react-native';
import { colors, radius } from '../../theme';

type Controls = { onNext: () => void; onBack?: () => void; onSkip?: () => void };

function Dots({ active }: { active: number }) {
  return <View style={styles.dots}>{[0, 1, 2].map(index => <View key={index} style={[styles.dot, index === active && styles.dotActive]} />)}</View>;
}

function Action({ label, onPress }: { label: string; onPress: () => void }) {
  return <Pressable onPress={onPress} style={styles.action}><Text style={styles.actionText}>{label}  →</Text></Pressable>;
}

export function ConnectOnboardingScreen({ onNext, onSkip }: Controls) {
  return <View style={styles.screen}><Pressable onPress={onSkip} style={styles.skip}><Text style={styles.skipText}>Skip</Text></Pressable><View style={styles.illustration}><View style={styles.people}><Text style={styles.peopleText}>👩🏾‍🏫  👧🏾  👨🏾‍🎓</Text><Text style={styles.board}>CONNECT • ENGAGE • THRIVE</Text></View></View><View style={styles.sheet}><Text style={styles.title}>Everything about school in one place</Text><Text style={styles.lead}>Edlink connects teachers, students, and parents seamlessly.</Text><View style={styles.bottom}><Dots active={0} /><Action label="Next" onPress={onNext} /></View></View></View>;
}

export function InformOnboardingScreen({ onNext, onBack }: Controls) {
  return <View style={styles.screen}><View style={styles.illustration}><View style={styles.phone}><Text style={styles.phoneTitle}>TODAY AT SCHOOL</Text><View style={styles.phoneRow}><Text>✓ Attendance</Text><Text>95%</Text></View><View style={styles.phoneRow}><Text>▤ Homework</Text><Text>2</Text></View><View style={styles.phoneRow}><Text>◉ Notices</Text><Text>3</Text></View></View><View style={styles.notification}><Text style={styles.bell}>♢</Text><View><View style={styles.skeletonWide} /><View style={styles.skeletonShort} /></View></View></View><View style={styles.copy}><Text style={styles.title}>Stay informed every school day</Text><Text style={styles.lead}>Check attendance, announcements, and timetables at a glance.</Text><Dots active={1} /></View><View style={styles.actions}><Pressable onPress={onBack} style={styles.back}><Text style={styles.backText}>Back</Text></Pressable><Action label="Next" onPress={onNext} /></View></View>;
}

export function ProgressOnboardingScreen({ onNext, onBack }: Controls) {
  return <View style={[styles.screen, styles.progressScreen]}><Pressable onPress={onBack} style={styles.roundBack}><Text style={styles.roundBackText}>←</Text></Pressable><View style={styles.progressArt}><Text style={styles.progressEmoji}>🎓</Text><View style={styles.report}><Text style={styles.reportTitle}>ACADEMIC PROGRESS</Text><Text>✓ Results</Text><Text>✓ Homework</Text><Text>✓ Attendance</Text><Text style={styles.chart}>▂▄▆█ ↗</Text></View></View><View style={styles.sheet}><Text style={styles.title}>Follow learning and progress</Text><Text style={styles.lead}>View results, report cards, and homework feedback easily.</Text><Dots active={2} /><Pressable onPress={onNext} style={styles.getStarted}><Text style={styles.getStartedText}>Get Started</Text></Pressable></View></View>;
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.background, paddingTop: 54 },
  progressScreen: { backgroundColor: colors.surfaceLow },
  skip: { position: 'absolute', zIndex: 2, top: 50, right: 20, paddingHorizontal: 20, paddingVertical: 12, borderRadius: radius.pill, backgroundColor: colors.surfaceHigh },
  skipText: { color: colors.ink, fontWeight: '700' },
  illustration: { flex: 1, minHeight: 350, margin: 20, borderRadius: radius.large, backgroundColor: colors.surfaceLow, alignItems: 'center', justifyContent: 'center', overflow: 'hidden' },
  people: { width: '88%', minHeight: 230, borderRadius: radius.card, backgroundColor: colors.surface, alignItems: 'center', justifyContent: 'center', borderWidth: 1, borderColor: colors.surfaceHigh },
  peopleText: { fontSize: 43 }, board: { marginTop: 24, color: colors.navy, fontWeight: '900', letterSpacing: 1 },
  sheet: { backgroundColor: colors.background, borderTopLeftRadius: 42, borderTopRightRadius: 42, padding: 28, gap: 18 },
  title: { color: colors.ink, fontSize: 30, lineHeight: 37, fontWeight: '800', textAlign: 'center', letterSpacing: -0.5 },
  lead: { color: colors.muted, fontSize: 16, lineHeight: 24, textAlign: 'center' },
  bottom: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginTop: 8 },
  dots: { flexDirection: 'row', justifyContent: 'center', alignItems: 'center', gap: 8 },
  dot: { width: 9, height: 9, borderRadius: 5, backgroundColor: '#D5DDF0' },
  dotActive: { width: 34, backgroundColor: colors.gold },
  action: { minWidth: 150, height: 56, borderRadius: radius.card, backgroundColor: colors.navy, alignItems: 'center', justifyContent: 'center' },
  actionText: { color: '#FFFFFF', fontSize: 17, fontWeight: '800' },
  phone: { width: 210, padding: 20, borderRadius: 28, backgroundColor: colors.surface, transform: [{ rotate: '-8deg' }], shadowColor: colors.navy, shadowOpacity: 0.12, shadowRadius: 18, elevation: 4 },
  phoneTitle: { color: colors.navy, fontSize: 12, fontWeight: '900', marginBottom: 18 },
  phoneRow: { flexDirection: 'row', justifyContent: 'space-between', paddingVertical: 12, borderBottomWidth: 1, borderColor: colors.surfaceHigh },
  notification: { position: 'absolute', right: 8, top: 70, flexDirection: 'row', alignItems: 'center', gap: 10, backgroundColor: colors.surface, padding: 14, borderRadius: radius.card, shadowColor: colors.navy, shadowOpacity: 0.13, shadowRadius: 12, elevation: 4 },
  bell: { width: 38, height: 38, textAlign: 'center', textAlignVertical: 'center', borderRadius: 19, backgroundColor: colors.gold, fontSize: 22 },
  skeletonWide: { width: 68, height: 8, borderRadius: 4, backgroundColor: colors.surfaceHigh }, skeletonShort: { width: 46, height: 8, borderRadius: 4, backgroundColor: colors.surfaceHigh, marginTop: 7 },
  copy: { paddingHorizontal: 28, gap: 18 }, actions: { flexDirection: 'row', gap: 14, padding: 20 },
  back: { flex: 1, height: 56, borderRadius: radius.pill, backgroundColor: colors.surfaceHigh, alignItems: 'center', justifyContent: 'center' }, backText: { color: colors.muted, fontWeight: '700' },
  roundBack: { position: 'absolute', zIndex: 2, top: 48, left: 20, width: 52, height: 52, borderRadius: 26, backgroundColor: colors.background, alignItems: 'center', justifyContent: 'center' }, roundBackText: { fontSize: 28, color: colors.ink },
  progressArt: { flex: 1, alignItems: 'center', justifyContent: 'center', flexDirection: 'row' }, progressEmoji: { fontSize: 70, marginRight: -15, marginTop: -150 }, report: { width: 210, padding: 22, gap: 10, borderRadius: radius.large, backgroundColor: colors.surface, shadowColor: colors.gold, shadowOpacity: 0.2, shadowRadius: 24, elevation: 4 },
  reportTitle: { color: colors.navy, fontWeight: '900' }, chart: { color: colors.goldDark, fontSize: 25, fontWeight: '900' },
  getStarted: { height: 58, borderRadius: radius.card, backgroundColor: colors.gold, alignItems: 'center', justifyContent: 'center', marginTop: 8 }, getStartedText: { color: colors.ink, fontSize: 17, fontWeight: '800' },
});
