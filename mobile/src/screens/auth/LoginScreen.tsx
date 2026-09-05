import { api } from '../../api';
import { Text, TextInput } from '../../components/Typography';
import { Ionicons } from '@expo/vector-icons';
import { useRef, useState } from 'react';
import Animated, { useAnimatedStyle, withTiming, Easing, ReduceMotion } from 'react-native-reanimated';
import { AuthProgress } from './AuthProgress';
import { ActivityIndicator, Keyboard, Pressable, StyleSheet, View } from 'react-native';
import { colors, radius } from '../../theme';
import type { AccountIdentity, Role, SchoolIdentity } from '../../types';
import { AuthLayout } from './AuthLayout';

export function LoginScreen({ role, school, email, password, error, busy, biometricAvailable, onEmailChange, onPasswordChange, onChangeSchool, onChangeRole, onForgotPassword, onBiometricSignIn, onSubmit }: { role: Role; school: SchoolIdentity; email: string; password: string; error: string; busy: boolean; biometricAvailable: boolean; onEmailChange: (value: string) => void; onPasswordChange: (value: string) => void; onChangeSchool: () => void; onChangeRole: () => void; onForgotPassword: () => void; onBiometricSignIn: () => void; onSubmit: () => void }) {
  const [showPassword, setShowPassword] = useState(false);
  const [stage, setStage] = useState<'email' | 'password'>('email');
  const [account, setAccount] = useState<AccountIdentity>();
  const [checking, setChecking] = useState(false);
  const lookupVersion = useRef(0);
  const [emailError, setEmailError] = useState('');
  const [width, setWidth] = useState(0);
  const roleLabel = role === 'parent' ? 'Parent or guardian' : `${role.charAt(0).toUpperCase()}${role.slice(1)}`;
  const slide = useAnimatedStyle(() => ({ transform: [{ translateX: withTiming(stage === 'password' ? -width : 0, { duration: 320, easing: Easing.out(Easing.cubic), reduceMotion: ReduceMotion.System }) }] }));
  const changeStage = (next: 'email' | 'password') => { Keyboard.dismiss(); setShowPassword(false); setStage(next); };
  const next = async () => {
    if (checking || busy) return;
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim())) { setEmailError('Enter a valid email address to continue.'); return; }
    const version = ++lookupVersion.current;
    setEmailError(''); setChecking(true); setAccount(undefined);
    try {
      const { data } = await api.account(school.number, email.trim(), role);
      if (version !== lookupVersion.current) return;
      setAccount(data); onEmailChange(email.trim()); changeStage('password');
    } catch (caught) {
      if (version === lookupVersion.current) setEmailError(caught instanceof Error ? caught.message : 'Could not check your account. Please try again.');
    } finally { if (version === lookupVersion.current) setChecking(false); }
  };
  return <AuthLayout eyebrow="Welcome back" school={school} account={stage === 'password' ? account : undefined}>
    <View style={styles.headingRow}>
      <Pressable disabled={busy || checking} accessibilityLabel={stage === 'email' ? 'Change school' : 'Back to email'} onPress={() => stage === 'email' ? onChangeSchool() : changeStage('email')} style={styles.back}><Ionicons name="chevron-back" size={23} color={colors.navy} /></Pressable>
      <AuthProgress step={stage === 'email' ? 2 : 3} />
    </View>
    <View style={styles.slider} onLayout={event => setWidth(event.nativeEvent.layout.width)}>
      <Animated.View style={[styles.track, { width: width ? width * 2 : '200%' }, slide]}>
        <View style={[styles.panel, { width: width || '50%' }]} pointerEvents={stage === 'email' ? 'auto' : 'none'} accessibilityElementsHidden={stage !== 'email'} importantForAccessibility={stage === 'email' ? 'auto' : 'no-hide-descendants'}>
          <Text style={styles.title}>Your email address</Text><Text style={styles.lead}>Enter the email registered with your school.</Text>
          <Text style={styles.label}>Email address</Text>
          <View style={[styles.field, !!emailError && styles.fieldError]}><Ionicons name="mail-outline" size={21} color={colors.navy} /><TextInput accessibilityLabel="Email address" value={email} onChangeText={value => { lookupVersion.current += 1; setChecking(false); setAccount(undefined); setEmailError(''); onEmailChange(value); }} placeholder="you@school.com" placeholderTextColor="#85838B" keyboardType="email-address" autoCapitalize="none" autoCorrect={false} autoComplete="email" returnKeyType="next" onSubmitEditing={next} style={styles.input} /></View>
          {!!emailError && <Text accessibilityLiveRegion="polite" style={styles.validation}>{emailError}</Text>}
          <Pressable disabled={checking || busy} onPress={() => void next()} style={({ pressed }) => [styles.button, pressed && styles.buttonMuted]}><Text style={styles.buttonText}>{checking ? 'Finding your account…' : 'Continue'}</Text>{checking ? <ActivityIndicator color={colors.gold} /> : <Ionicons name="arrow-forward-circle" size={23} color={colors.gold} />}</Pressable>
          <Pressable disabled={checking || busy} onPress={onChangeRole} style={[styles.role, styles.roleChoice]}><Ionicons name="person-circle-outline" size={20} color={colors.navy} /><Text style={styles.roleText}>Signing in as {roleLabel}</Text><Ionicons name="chevron-down" size={15} color={colors.navy} /></Pressable>
          {biometricAvailable && <Pressable disabled={busy || checking} onPress={onBiometricSignIn} style={styles.biometric}><Ionicons name="finger-print" size={25} color={colors.navy} /><Text style={styles.biometricText}>Use device biometrics</Text></Pressable>}
          {!!error && stage === 'email' && <Text style={styles.validation}>{error}</Text>}
        </View>
        <View style={[styles.panel, { width: width || '50%' }]} pointerEvents={stage === 'password' ? 'auto' : 'none'} accessibilityElementsHidden={stage !== 'password'} importantForAccessibility={stage === 'password' ? 'auto' : 'no-hide-descendants'}>
          <Text style={styles.title}>Your password</Text><Text style={styles.lead}>One last step to your school workspace.</Text>
          <Text style={styles.label}>Password</Text><View style={[styles.field, !!error && styles.fieldError]}><Ionicons name="lock-closed-outline" size={21} color={colors.navy} /><TextInput accessibilityLabel="Password" value={password} onChangeText={onPasswordChange} placeholder="Enter your password" placeholderTextColor="#85838B" secureTextEntry={!showPassword} autoCapitalize="none" autoCorrect={false} autoComplete="password" returnKeyType="done" onSubmitEditing={() => { if (!busy) onSubmit(); }} style={styles.input} /><Pressable accessibilityLabel={showPassword ? 'Hide password' : 'Show password'} onPress={() => setShowPassword(value => !value)} hitSlop={10}><Ionicons name={showPassword ? 'eye-off-outline' : 'eye-outline'} size={21} color={colors.navy} /></Pressable></View>
          <Pressable disabled={busy || checking} onPress={onForgotPassword} style={styles.forgot}><Text style={styles.forgotText}>Forgot password?</Text></Pressable>
          {!!error && <View style={styles.errorRow}><Ionicons name="alert-circle" size={16} color={colors.danger} /><Text accessibilityLiveRegion="polite" style={styles.error}>{error}</Text></View>}
          <Pressable disabled={busy || checking} onPress={onSubmit} style={({ pressed }) => [styles.button, (busy || pressed) && styles.buttonMuted]}>{busy ? <ActivityIndicator color={colors.gold} /> : <><Text style={styles.buttonText}>Log in to Edlink</Text><Ionicons name="arrow-forward-circle" size={23} color={colors.gold} /></>}</Pressable>
        </View>
      </Animated.View>
    </View>
  </AuthLayout>;
}

const styles = StyleSheet.create({
  slider: { overflow: 'hidden' }, track: { flexDirection: 'row', alignItems: 'flex-start' }, panel: { flexShrink: 0, paddingBottom: 8 },
  roleChoice: { alignSelf: 'center', marginTop: 20 }, validation: { color: colors.danger, fontSize: 12, lineHeight: 18, marginTop: 10 },
  emailSummary: { flexDirection: 'row', alignItems: 'center', gap: 9, backgroundColor: colors.surfaceLow, borderRadius: 12, padding: 12, marginTop: 16 }, emailValue: { flex: 1, fontSize: 12, fontWeight: '600', color: colors.navy },
  headingRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }, back: { width: 44, height: 44, borderRadius: 22, borderWidth: 2, borderColor: colors.navy, alignItems: 'center', justifyContent: 'center' }, role: { minHeight: 42, borderRadius: 21, paddingHorizontal: 13, backgroundColor: colors.surfaceLow, borderWidth: 1, borderColor: colors.navy, flexDirection: 'row', alignItems: 'center', gap: 7 }, roleText: { color: colors.navy, fontSize: 12, fontWeight: '900' }, title: { color: colors.navy, fontSize: 31, fontWeight: '900', marginTop: 18 }, lead: { color: colors.muted, fontSize: 14, lineHeight: 20, marginTop: 4 }, label: { color: colors.ink, fontSize: 13, fontWeight: '800', marginTop: 18, marginBottom: 7 }, field: { minHeight: 56, borderRadius: radius.card, borderWidth: 2, borderColor: colors.navy, paddingHorizontal: 14, flexDirection: 'row', alignItems: 'center', gap: 10 }, fieldError: { borderColor: colors.danger }, input: { flex: 1, height: 54, color: colors.ink, fontSize: 15 }, forgot: { alignSelf: 'flex-end', minHeight: 40, justifyContent: 'center' }, forgotText: { color: colors.goldDark, fontSize: 13, fontWeight: '900' }, errorRow: { flexDirection: 'row', alignItems: 'flex-start', gap: 6 }, error: { flex: 1, color: colors.danger, fontSize: 12, lineHeight: 17 }, button: { minHeight: 58, marginTop: 15, borderRadius: radius.card, backgroundColor: colors.navy, flexDirection: 'row', gap: 10, justifyContent: 'center', alignItems: 'center' }, buttonText: { color: '#FFFFFF', fontWeight: '900', fontSize: 16 }, buttonMuted: { opacity: 0.65 }, biometric: { minHeight: 52, marginTop: 13, borderRadius: radius.card, borderWidth: 2, borderColor: colors.navy, flexDirection: 'row', gap: 9, alignItems: 'center', justifyContent: 'center' }, biometricText: { color: colors.navy, fontSize: 13, fontWeight: '900' },
});
