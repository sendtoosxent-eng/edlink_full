import { AuthProgress } from './AuthProgress';
import { Text, TextInput } from '../../components/Typography';
import { Ionicons } from '@expo/vector-icons';
import { ActivityIndicator, Pressable, StyleSheet, View } from 'react-native';
import { colors, radius } from '../../theme';
import { AuthLayout } from './AuthLayout';

export function SchoolScreen({ school, error, busy, onChange, onBack, onContinue }: { school: string; error: string; busy: boolean; onChange: (value: string) => void; onBack: () => void; onContinue: () => void }) {
  return <AuthLayout eyebrow="Your school, your space" heroTitle="Find your school" heroSubtitle="Connect securely to your school workspace before signing in.">
    <View style={styles.headingRow}><Pressable accessibilityLabel="Go back" onPress={onBack} style={styles.back}><Ionicons name="chevron-back" size={23} color={colors.navy} /></Pressable><AuthProgress step={1} /></View>
    <View>
      <Text style={styles.title}>Enter your school number</Text><Text style={styles.lead}>Use the number provided by your school administrator. We’ll load the correct school name and logo for you.</Text>
      <Text style={styles.label}>School number</Text><View style={[styles.field, !!error && styles.fieldError]}><View style={styles.fieldIcon}><Ionicons name="school-outline" size={22} color={colors.gold} /></View><TextInput accessibilityLabel="School number" value={school} onChangeText={value => onChange(value.toUpperCase())} placeholder="e.g. EDL-4K9P2" placeholderTextColor="#85838B" autoCapitalize="characters" autoCorrect={false} returnKeyType="go" onSubmitEditing={onContinue} style={styles.input} />{school ? <Pressable accessibilityLabel="Clear school number" onPress={() => onChange('')} hitSlop={10}><Ionicons name="close-circle" size={20} color={colors.muted} /></Pressable> : null}</View>
    {!!error && <View style={styles.errorRow}><Ionicons name="alert-circle" size={16} color={colors.danger} /><Text style={styles.error}>{error}</Text></View>}
    <Pressable disabled={busy} onPress={onContinue} style={({ pressed }) => [styles.button, (busy || pressed) && styles.buttonMuted]}>{busy ? <ActivityIndicator color={colors.gold} /> : <><Text style={styles.buttonText}>Continue</Text><Ionicons name="arrow-forward-circle" size={23} color={colors.gold} /></>}</Pressable>
    <View style={styles.tip}><Ionicons name="shield-checkmark-outline" size={20} color={colors.navy} /><Text style={styles.tipText}>Your school number only identifies the school. Your account details remain private.</Text></View>
    </View>
  </AuthLayout>;
}

const styles = StyleSheet.create({
  headingRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' }, back: { width: 44, height: 44, borderRadius: 22, borderWidth: 2, borderColor: colors.navy, alignItems: 'center', justifyContent: 'center' }, step: { flexDirection: 'row', gap: 6 }, dot: { width: 22, height: 6, borderRadius: 3, backgroundColor: colors.surfaceHigh }, dotActive: { backgroundColor: colors.gold }, title: { color: colors.navy, fontSize: 24, lineHeight: 30, fontWeight: '900', letterSpacing: -0.4, marginTop: 22 }, lead: { color: colors.muted, fontSize: 13, lineHeight: 20, marginTop: 7 }, label: { color: colors.ink, fontSize: 13, fontWeight: '800', marginTop: 24, marginBottom: 8 }, field: { minHeight: 62, borderRadius: 20, borderWidth: 2, borderColor: colors.navy, backgroundColor: colors.surface, paddingHorizontal: 9, flexDirection: 'row', alignItems: 'center', gap: 11 }, fieldIcon: { width: 44, height: 44, borderRadius: 14, backgroundColor: colors.navy, alignItems: 'center', justifyContent: 'center' }, fieldError: { borderColor: colors.danger }, input: { flex: 1, height: 58, color: colors.ink, fontSize: 16, fontWeight: '700', letterSpacing: 0.5 }, errorRow: { flexDirection: 'row', gap: 6, marginTop: 8, alignItems: 'flex-start' }, error: { flex: 1, color: colors.danger, fontSize: 12, lineHeight: 17 }, button: { minHeight: 58, marginTop: 24, borderRadius: radius.card, backgroundColor: colors.navy, flexDirection: 'row', gap: 10, alignItems: 'center', justifyContent: 'center' }, buttonText: { color: '#FFFFFF', fontSize: 16, fontWeight: '900' }, buttonMuted: { opacity: 0.65 }, tip: { flexDirection: 'row', gap: 10, marginTop: 18, padding: 14, borderRadius: radius.card, backgroundColor: colors.surfaceLow, borderWidth: 1, borderColor: colors.navy }, tipText: { flex: 1, color: colors.muted, fontSize: 12, lineHeight: 18 },
});
