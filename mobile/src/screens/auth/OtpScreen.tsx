import { Ionicons } from '@expo/vector-icons';
import { useEffect, useRef, useState } from 'react';
import { ActivityIndicator, Pressable, StyleSheet, Text, TextInput, View } from 'react-native';
import { colors, radius } from '../../theme';
import type { SchoolIdentity } from '../../types';
import { AuthLayout } from './AuthLayout';

export function OtpScreen({ school, maskedEmail, code, error, busy, onCodeChange, onBack, onVerify, onResend }: { school: SchoolIdentity; maskedEmail: string; code: string; error: string; busy: boolean; onCodeChange: (value: string) => void; onBack: () => void; onVerify: () => void; onResend: () => Promise<void> }) {
  const input = useRef<TextInput>(null); const [seconds, setSeconds] = useState(60); const [resent, setResent] = useState(false);
  useEffect(() => { if (!seconds) return; const timer = setInterval(() => setSeconds(value => Math.max(0, value - 1)), 1000); return () => clearInterval(timer); }, [seconds]);
  const resend = async () => { if (seconds) return; await onResend(); setResent(true); setSeconds(60); };
  return <AuthLayout eyebrow="Secure verification" school={school}>
    <Pressable accessibilityLabel="Back to login" onPress={onBack} style={styles.back}><Ionicons name="chevron-back" size={23} color={colors.navy} /></Pressable>
    <Text style={styles.title}>Verify it’s you</Text><Text style={styles.lead}>Enter the 6-digit code sent to <Text style={styles.strong}>{maskedEmail}</Text>. It expires in 10 minutes.</Text>
    <Pressable style={styles.codeRow} onPress={() => input.current?.focus()}>{Array.from({ length: 6 }, (_, index) => <View key={index} style={[styles.codeBox, code.length === index && styles.codeBoxActive]}><Text style={styles.codeText}>{code[index] ?? ''}</Text></View>)}</Pressable>
    <TextInput ref={input} autoFocus value={code} onChangeText={value => onCodeChange(value.replace(/\D/g, '').slice(0, 6))} keyboardType="number-pad" textContentType="oneTimeCode" autoComplete="sms-otp" maxLength={6} style={styles.hiddenInput} onSubmitEditing={onVerify} />
    {!!error && <View style={styles.errorRow}><Ionicons name="alert-circle" size={16} color={colors.danger} /><Text style={styles.error}>{error}</Text></View>}
    {resent && <Text style={styles.sent}>A new verification code was sent.</Text>}
    <Pressable disabled={busy || code.length !== 6} onPress={onVerify} style={[styles.button, (busy || code.length !== 6) && styles.buttonMuted]}>{busy ? <ActivityIndicator color={colors.gold} /> : <><Text style={styles.buttonText}>Verify and continue</Text><Ionicons name="shield-checkmark" size={22} color={colors.gold} /></>}</Pressable>
    <Pressable disabled={seconds > 0} onPress={() => void resend()} style={styles.resend}><Text style={[styles.resendText, seconds > 0 && styles.resendMuted]}>{seconds ? `Resend code in ${seconds}s` : 'Resend verification code'}</Text></Pressable>
  </AuthLayout>;
}

const styles = StyleSheet.create({ back: { width: 44, height: 44, borderRadius: 22, borderWidth: 2, borderColor: colors.navy, alignItems: 'center', justifyContent: 'center' }, title: { color: colors.navy, fontSize: 30, lineHeight: 36, fontWeight: '900', marginTop: 20 }, lead: { color: colors.muted, fontSize: 14, lineHeight: 21, marginTop: 7 }, strong: { color: colors.navy, fontWeight: '900' }, codeRow: { flexDirection: 'row', gap: 7, marginTop: 28, justifyContent: 'center' }, codeBox: { width: 45, height: 58, borderRadius: 14, borderWidth: 2, borderColor: colors.navy, backgroundColor: colors.surfaceLow, alignItems: 'center', justifyContent: 'center' }, codeBoxActive: { borderColor: colors.goldDark, backgroundColor: '#FFF8DD' }, codeText: { color: colors.navy, fontSize: 23, fontWeight: '900' }, hiddenInput: { position: 'absolute', width: 1, height: 1, opacity: 0 }, errorRow: { flexDirection: 'row', alignItems: 'flex-start', gap: 6, marginTop: 14 }, error: { flex: 1, color: colors.danger, fontSize: 12, lineHeight: 17 }, sent: { color: colors.success, textAlign: 'center', fontSize: 12, fontWeight: '800', marginTop: 12 }, button: { minHeight: 58, borderRadius: radius.card, backgroundColor: colors.navy, marginTop: 28, flexDirection: 'row', gap: 10, alignItems: 'center', justifyContent: 'center' }, buttonMuted: { opacity: 0.45 }, buttonText: { color: '#FFFFFF', fontWeight: '900', fontSize: 16 }, resend: { minHeight: 50, justifyContent: 'center', alignItems: 'center' }, resendText: { color: colors.goldDark, fontSize: 13, fontWeight: '900' }, resendMuted: { color: colors.muted, fontWeight: '700' } });
