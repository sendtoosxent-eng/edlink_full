import { useThemedStyles } from '../theme/ThemeProvider';
import { useEffect, useState } from 'react';
import { BackHandler, Pressable, StyleSheet, View, type StyleProp, type ViewStyle } from 'react-native';
import { Text, TextInput } from './Typography';
import { AppSvg } from './AppSvg';
import { StatArtwork, type StatArtworkName } from './StatArtwork';
import { colors } from '../theme/index';

export function useTeacherBack(onBack: () => void) {
  useEffect(() => { const listener = BackHandler.addEventListener('hardwareBackPress', () => { onBack(); return true; }); return () => listener.remove(); }, [onBack]);
}
export function TeacherHero({ title, subtitle, artwork = 'classroom', detail }: { title: string; subtitle: string; artwork?: StatArtworkName; detail?: string }) {
  const styles = useThemedStyles(baseStyles);
  return <View style={styles.hero}>
    <View pointerEvents="none" accessible={false} accessibilityElementsHidden importantForAccessibility="no-hide-descendants" style={styles.heroArtwork}><StatArtwork name={artwork} color={colors.secondary} /></View>
    <View style={styles.heroCopy}><Text accessibilityRole="header" style={styles.heroTitle}>{title}</Text><Text style={styles.heroSubtitle}>{subtitle}</Text>{!!detail && <Text style={styles.heroDetail}>{detail}</Text>}</View>
  </View>;
}
export function TeacherSearch({ value, onChange, placeholder = 'Search records…', label = 'Search records' }: { value: string; onChange: (value: string) => void; placeholder?: string; label?: string }) {
  const styles = useThemedStyles(baseStyles);
  const [focused, setFocused] = useState(false);
  return <View style={[styles.search, focused && styles.focused]}><View style={styles.searchIcon}><AppSvg name="search" size={21} /></View><TextInput accessibilityLabel={label} placeholder={placeholder} placeholderTextColor={colors.textMuted} value={value} onChangeText={onChange} onFocus={() => setFocused(true)} onBlur={() => setFocused(false)} style={styles.searchInput} /></View>;
}
export function TeacherButton({ label, onPress, secondary = false, disabled, style }: { label: string; onPress: () => void; secondary?: boolean; disabled?: boolean; style?: StyleProp<ViewStyle> }) {
  const styles = useThemedStyles(baseStyles);
  return <Pressable accessibilityRole="button" disabled={disabled} onPress={onPress} style={({ pressed }) => [styles.button, secondary && styles.secondary, style, (pressed || disabled) && styles.muted]}><Text style={[styles.buttonText, secondary && styles.secondaryText]}>{label}</Text></Pressable>;
}
export function TeacherToolCard({ title, description, artwork, onPress, badge }: { title: string; description: string; artwork: StatArtworkName; onPress: () => void; badge?: string }) {
  const styles = useThemedStyles(baseStyles);
  return <Pressable accessibilityRole="button" onPress={onPress} style={({ pressed }) => [styles.tool, pressed && styles.muted]}>
    <View pointerEvents="none" accessible={false} accessibilityElementsHidden importantForAccessibility="no-hide-descendants" style={styles.toolArtwork}><StatArtwork name={artwork} /></View>
    <View style={styles.toolCopy}><Text style={styles.toolTitle}>{title}</Text><Text style={styles.toolDescription}>{description}</Text>{!!badge && <Text style={styles.toolBadge}>{badge}</Text>}</View>
    <AppSvg name="chevron" size={17} />
  </Pressable>;
}
export const teacherScreenStyles = StyleSheet.create({
  content: { padding: 18, paddingBottom: 40, gap: 14, backgroundColor: colors.background, flexGrow: 1 },
  panel: { backgroundColor: colors.surface, padding: 16, borderRadius: 18, borderWidth: 1, borderColor: colors.border, gap: 10 },
  input: { minHeight: 52, backgroundColor: colors.surface, borderRadius: 14, borderWidth: 1, borderColor: colors.border, paddingHorizontal: 14, paddingVertical: 12, color: colors.primary, fontSize: 13 },
});
const baseStyles = StyleSheet.create({
  hero: { minHeight: 126, padding: 22, borderRadius: 24, backgroundColor: colors.primary, overflow: 'hidden', justifyContent: 'center' },
  heroArtwork: { position: 'absolute', right: 12, top: 14, width: 88, height: 96, opacity: 0.25 }, heroCopy: { paddingRight: 68 }, heroTitle: { color: colors.secondary, fontSize: 23, lineHeight: 29, fontWeight: '700' }, heroSubtitle: { color: '#D8E2F2', fontSize: 12, lineHeight: 19, marginTop: 7 }, heroDetail: { color: colors.secondary, fontSize: 10, lineHeight: 16, marginTop: 8 },
  search: { minHeight: 54, flexDirection: 'row', alignItems: 'center', gap: 10, borderWidth: 1, borderColor: colors.border, borderRadius: 16, paddingHorizontal: 15, backgroundColor: colors.surface }, focused: { borderColor: colors.secondary }, searchIcon: { opacity: 0.35 }, searchInput: { flex: 1, minWidth: 0, paddingVertical: 14, color: colors.primary, fontSize: 13 },
  button: { minHeight: 52, paddingHorizontal: 18, paddingVertical: 13, borderRadius: 999, backgroundColor: colors.primary, justifyContent: 'center', alignItems: 'center' }, secondary: { backgroundColor: colors.secondary }, buttonText: { color: colors.secondary, fontSize: 14, fontWeight: '600', textAlign: 'center' }, secondaryText: { color: colors.primary }, muted: { opacity: 0.55 },
  tool: { minHeight: 112, flexDirection: 'row', alignItems: 'center', gap: 12, padding: 18, backgroundColor: colors.surface, borderRadius: 18, borderWidth: 1, borderColor: colors.border, overflow: 'hidden' }, toolArtwork: { position: 'absolute', right: 24, top: 12, width: 88, height: 88, opacity: 0.1 }, toolCopy: { flex: 1, paddingRight: 48 }, toolTitle: { color: colors.primary, fontSize: 15, fontWeight: '700', lineHeight: 21 }, toolDescription: { color: colors.textMuted, fontSize: 11, lineHeight: 17, marginTop: 5 }, toolBadge: { color: '#8A6500', fontSize: 10, fontWeight: '600', marginTop: 8 },
});

const styles = baseStyles;
