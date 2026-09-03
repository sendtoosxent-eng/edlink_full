import type { ComponentProps } from 'react';
import { Pressable, StyleSheet, Text, TextInput, View } from 'react-native';
import { colors, radius } from '../../theme';

export function AuthHeader({ onBack, accountStep = false }: { onBack: () => void; accountStep?: boolean }) {
  return <View style={styles.topRow}><Pressable onPress={onBack} style={styles.back}><Text style={styles.backText}>‹</Text></Pressable><View style={styles.dots}><View style={[styles.dot, styles.active]} /><View style={[styles.dot, accountStep && styles.active]} /></View></View>;
}
export function AuthField({ label, ...props }: ComponentProps<typeof TextInput> & { label: string }) { return <View style={styles.field}><Text style={styles.label}>{label}</Text><TextInput style={styles.input} placeholderTextColor="#9B97A0" autoCapitalize="none" {...props} /></View>; }
export function AuthButton({ label, onPress, disabled }: { label: string; onPress: () => void; disabled?: boolean }) { return <Pressable onPress={onPress} disabled={disabled} style={[styles.button, disabled && styles.disabled]}><Text style={styles.buttonText}>{label}</Text></Pressable>; }

const styles = StyleSheet.create({
  topRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: 28 }, back: { width: 48, height: 48, borderRadius: radius.medium, backgroundColor: colors.surfaceLow, alignItems: 'center', justifyContent: 'center' }, backText: { color: colors.navy, fontSize: 33, lineHeight: 35 }, dots: { flexDirection: 'row', gap: 7 }, dot: { width: 24, height: 5, borderRadius: 3, backgroundColor: colors.surfaceHigh }, active: { backgroundColor: colors.gold },
  field: { gap: 8 }, label: { color: colors.ink, fontSize: 14, fontWeight: '700' }, input: { height: 56, borderWidth: 1, borderColor: colors.outline, borderRadius: radius.card, paddingHorizontal: 16, color: colors.ink, fontSize: 16, backgroundColor: colors.surface },
  button: { minHeight: 56, borderRadius: radius.card, backgroundColor: colors.gold, alignItems: 'center', justifyContent: 'center', paddingHorizontal: 20, marginTop: 4, shadowColor: colors.navy, shadowOpacity: 0.12, shadowRadius: 12, elevation: 3 }, buttonText: { color: colors.goldDark, fontWeight: '800', fontSize: 16 }, disabled: { opacity: 0.5 },
});
