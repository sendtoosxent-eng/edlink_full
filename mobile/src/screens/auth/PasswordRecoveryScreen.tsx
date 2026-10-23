import { useThemedStyles } from '../../theme/ThemeProvider';
import { Ionicons } from '@expo/vector-icons';
import { Text, TextInput } from '../../components/Typography';
import { useState, type ComponentProps } from 'react';
import { ActivityIndicator, Pressable, StyleSheet, View } from 'react-native';
import Svg, { Circle, Path, Rect } from 'react-native-svg';
import { colors } from './authTheme';
import type { SchoolIdentity } from '../../types';
import { AuthLayout } from './AuthLayout';

type ResetValues = { email: string; token: string; password: string; password_confirmation: string };
type Mode = 'request' | 'sent' | 'reset';
export function PasswordRecoveryScreen({ school, initialEmail, error, busy, onBack, onClearError, onRequest, onReset }: { school: SchoolIdentity; initialEmail: string; error: string; busy: boolean; onBack: () => void; onClearError: () => void; onRequest: (email: string) => Promise<boolean>; onReset: (values: ResetValues) => Promise<boolean> }) {
  const styles = useThemedStyles(baseStyles);
  const [mode, setMode] = useState<Mode>('request');
  const [email, setEmail] = useState(initialEmail);
  const [token, setToken] = useState('');
  const [password, setPassword] = useState('');
  const [confirmation, setConfirmation] = useState('');
  const changeMode = (next: Mode) => { onClearError(); setMode(next); };
  const edit = (setter: (value: string) => void) => (value: string) => { onClearError(); setter(value); };
  const back = () => {
    onClearError();
    if (mode === 'request') onBack();
    else if (mode === 'reset') { setPassword(''); setConfirmation(''); setMode('sent'); }
    else setMode('request');
  };
  const request = async () => { if (!busy && await onRequest(email.trim())) changeMode('sent'); };
  const reset = async () => { if (!busy) await onReset({ email: email.trim(), token: token.trim(), password, password_confirmation: confirmation }); };
  return <AuthLayout showSchoolBrand showFooter={false} eyebrow="" school={school}>
    <View style={styles.form}>
      {mode === 'request' && <>
        <Text accessibilityRole="header" style={styles.title}>Forgot password?</Text>
        <Text style={styles.lead}>Enter your school email and we’ll send you a secure password reset link.</Text>
        <Text style={styles.label}>Email address</Text>
        <Field kind="email" accessibilityLabel="Recovery email address" placeholder="you@school.com" value={email} onChangeText={edit(setEmail)} keyboardType="email-address" autoComplete="email" editable={!busy} returnKeyType="go" onSubmitEditing={() => void request()} />
      </>}
      {mode === 'sent' && <>
        <Text accessibilityRole="header" style={styles.title}>Check your email</Text>
        <Text style={styles.lead}>If {email.trim()} matches your school account, we’ve sent a reset link. Open it to choose a new password.</Text>
        <Pressable accessibilityRole="button" disabled={busy} onPress={() => changeMode('request')} style={styles.resend}><Text style={styles.linkText}>Change email or send again</Text></Pressable>
      </>}
      {mode === 'reset' && <>
        <Text accessibilityRole="header" style={styles.title}>Create a new password</Text>
        <Text style={styles.lead}>Paste the token from your reset email. Your new password needs at least 8 characters.</Text>
        <Field kind="token" accessibilityLabel="Reset token" placeholder="Reset token" value={token} onChangeText={edit(setToken)} editable={!busy} />
        <Field kind="password" accessibilityLabel="New password" placeholder="New password" value={password} onChangeText={edit(setPassword)} editable={!busy} secureTextEntry autoComplete="new-password" />
        <Field kind="password" accessibilityLabel="Confirm new password" placeholder="Confirm new password" value={confirmation} onChangeText={edit(setConfirmation)} editable={!busy} secureTextEntry autoComplete="new-password" returnKeyType="done" onSubmitEditing={() => void reset()} />
      </>}
      {!!error && <Text accessibilityLiveRegion="polite" style={styles.error}>{error}</Text>}
      <Pressable accessibilityRole="button" disabled={busy} onPress={() => { if (mode === 'request') void request(); else if (mode === 'sent') changeMode('reset'); else void reset(); }} style={({ pressed }) => [styles.primaryButton, (pressed || busy) && styles.muted]}>
        {busy ? <ActivityIndicator color={colors.gold} /> : <Text style={styles.primaryText}>{mode === 'request' ? 'Send reset link' : mode === 'sent' ? 'I have a reset token' : 'Set new password'}</Text>}
      </Pressable>
      <Pressable accessibilityRole="button" disabled={busy} onPress={back} style={({ pressed }) => [styles.backButton, (pressed || busy) && styles.muted]}><Text style={styles.backText}>Back</Text></Pressable>
    </View>
  </AuthLayout>;
}

type Kind = 'email' | 'token' | 'password';
function RecoveryGlyph({ kind, size = 26 }: { kind: Kind; size?: number }) {
  return <Svg width={size} height={size} viewBox="0 0 32 32" fill="none">
    {kind === 'email' ? <><Rect x={4} y={7} width={24} height={18} rx={3} stroke={colors.gold} strokeWidth={1.8} /><Path d="m5 9 11 8 11-8" stroke={colors.gold} strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round" /></> : kind === 'token' ? <><Circle cx={11} cy={11} r={6} stroke={colors.gold} strokeWidth={1.8} /><Path d="m15 16 11 11m-4-4 3-3m-7 0 3-3" stroke={colors.gold} strokeWidth={1.8} strokeLinecap="round" /></> : <><Path d="M10 14V9a6 6 0 0 1 12 0v5" stroke={colors.gold} strokeWidth={1.8} /><Rect x={6} y={14} width={20} height={15} rx={3} stroke={colors.gold} strokeWidth={1.8} /><Circle cx={16} cy={21} r={2} fill={colors.gold} /></>}
  </Svg>;
}
function Field({ kind, secureTextEntry, ...props }: ComponentProps<typeof TextInput> & { kind: Kind }) {
  const styles = useThemedStyles(baseStyles);
  const [focused, setFocused] = useState(false);
  const [visible, setVisible] = useState(false);
  return <View style={[styles.field, focused && styles.fieldFocused]}>
    <View style={styles.fieldIcon}><RecoveryGlyph kind={kind} /></View>
    <TextInput {...props} secureTextEntry={secureTextEntry && !visible} autoCapitalize="none" autoCorrect={false} placeholderTextColor={colors.muted} onFocus={() => setFocused(true)} onBlur={() => { setFocused(false); setVisible(false); }} style={styles.input} />
    {secureTextEntry && <Pressable accessibilityRole="button" accessibilityLabel={visible ? 'Hide password' : 'Show password'} disabled={props.editable === false} onPress={() => setVisible(value => !value)} hitSlop={8}><Ionicons name={visible ? 'eye-off-outline' : 'eye-outline'} size={21} color={colors.navy} /></Pressable>}
  </View>;
}
const baseStyles = StyleSheet.create({
  form: { width: '100%', maxWidth: 460, alignSelf: 'center', paddingBottom: 8 },
  title: { color: colors.navy, fontSize: 26, lineHeight: 32, fontWeight: '700' },
  lead: { color: colors.muted, fontSize: 13, lineHeight: 19, marginTop: 8, marginBottom: 6 },
  label: { color: colors.navy, fontSize: 13, fontWeight: '600', marginTop: 12 },
  field: { minHeight: 54, marginTop: 10, borderRadius: 16, borderWidth: 1, borderColor: colors.outline, flexDirection: 'row', alignItems: 'center', gap: 10, paddingHorizontal: 9 },
  fieldFocused: { borderColor: colors.gold, boxShadow: '0 0 0 3px rgba(239,176,0,0.20)' },
  fieldIcon: { width: 40, height: 40, borderRadius: 13, backgroundColor: colors.navy, alignItems: 'center', justifyContent: 'center' },
  input: { flex: 1, minWidth: 0, height: 52, color: colors.navy, fontSize: 14 }, showText: { color: colors.navy, fontSize: 11, fontWeight: '600' },
  primaryButton: { width: '100%', minHeight: 54, borderRadius: 999, backgroundColor: colors.navy, marginTop: 20, alignItems: 'center', justifyContent: 'center', paddingHorizontal: 14, paddingVertical: 12 },
  primaryText: { color: colors.gold, fontSize: 15, fontWeight: '600', textAlign: 'center' },
  backButton: { width: '100%', minHeight: 52, borderRadius: 999, backgroundColor: colors.gold, marginTop: 12, alignItems: 'center', justifyContent: 'center' },
  backText: { color: colors.navy, fontSize: 15, fontWeight: '600' },
  muted: { opacity: 0.6 }, error: { color: colors.danger, fontSize: 12, lineHeight: 18, marginTop: 12 },
  sentIcon: { width: 64, height: 64, borderRadius: 22, backgroundColor: colors.navy, alignItems: 'center', justifyContent: 'center', alignSelf: 'center', marginBottom: 16 },
  resend: { minHeight: 44, justifyContent: 'center', marginTop: 6 }, linkText: { color: colors.goldDark, fontSize: 12, fontWeight: '600', textDecorationLine: 'underline' },
});

const styles = baseStyles;
