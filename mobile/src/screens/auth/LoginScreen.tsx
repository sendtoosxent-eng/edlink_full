import { useThemedStyles } from '../../theme/ThemeProvider';
import { api } from '../../api';
import { Text, TextInput } from '../../components/Typography';
import { Ionicons } from '@expo/vector-icons';
import { useEffect, useRef, useState } from 'react';
import Svg, { Circle, Path, Rect } from 'react-native-svg';
import Animated, { useAnimatedStyle, withTiming, Easing, ReduceMotion } from 'react-native-reanimated';
import { ActivityIndicator, Keyboard, Pressable, StyleSheet, View } from 'react-native';
import { colors, radius } from './authTheme';
import type { AccountPreview, Role, SchoolIdentity } from '../../types';
import { AuthUserHeader } from './AuthUserHeader';
import { AuthLayout } from './AuthLayout';

export function LoginScreen({ role, school, email, password, error, busy, onEmailChange, onPasswordChange, onChangeSchool, onChangeRole, onForgotPassword, onSubmit, onAccountValidated }: { role: Role; school: SchoolIdentity; email: string; password: string; error: string; busy: boolean; biometricAvailable: boolean; onEmailChange: (value: string) => void; onPasswordChange: (value: string) => void; onChangeSchool: () => void; onChangeRole: () => void; onForgotPassword: () => void; onBiometricSignIn: () => void; onSubmit: () => void; onAccountValidated?: (account: AccountPreview | null) => void }) {
  const styles = useThemedStyles(baseStyles);
  const [account, setAccount] = useState<AccountPreview | null>(null);
  const [focusedField, setFocusedField] = useState<'email' | 'password' | null>(null);
  const [showPassword, setShowPassword] = useState(false);
  const [stage, setStage] = useState<'email' | 'password'>('email');
  const [checking, setChecking] = useState(false);
  const lookupVersion = useRef(0);
  useEffect(() => () => { lookupVersion.current += 1; }, []);
  const [emailError, setEmailError] = useState('');
  const [width, setWidth] = useState(0);
  const [emailHeight, setEmailHeight] = useState(340);
  const [passwordHeight, setPasswordHeight] = useState(0);
  const slide = useAnimatedStyle(() => ({ transform: [{ translateX: withTiming(stage === 'password' ? -width : 0, { duration: 320, easing: Easing.out(Easing.cubic), reduceMotion: ReduceMotion.System }) }] }));
  const changeStage = (next: 'email' | 'password') => { Keyboard.dismiss(); setShowPassword(false); setStage(next); };
  const next = async () => {
    if (checking || busy) return;
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim())) { setEmailError('Enter a valid email address to continue.'); return; }
    const version = ++lookupVersion.current;
    setEmailError(''); setChecking(true); setAccount(null); onAccountValidated?.(null);
    try {
      const { data } = await api.account(school.number, email.trim(), role);
      if (version !== lookupVersion.current) return;
      // A profile is required before moving to password entry.
      if ('accepted' in data && data.accepted === true) {
        throw new Error('Profile not found. Please check your email address and try again.');
      } else if ('name' in data && data.name && data.role === role) {
        setAccount(data); onAccountValidated?.(data);
      } else {
        throw new Error('The Edlink could not load your profile. Please try again.');
      }
      onEmailChange(email.trim()); changeStage('password');
    } catch (caught) {
      if (version === lookupVersion.current) setEmailError(caught instanceof Error ? caught.message : 'We could not validate your email. Please try again.');
    } finally {
      if (version === lookupVersion.current) setChecking(false);
    }
  };
  const passwordHeader = <AuthUserHeader account={account} email={email} disabled={busy || checking} onChangeEmail={() => { onPasswordChange(''); changeStage('email'); }} />;
  return <AuthLayout headerContent={stage === 'password' ? passwordHeader : undefined} showSchoolBrand={stage === 'email'} fitContentHeight={stage === 'email' ? emailHeight : passwordHeight || undefined} showFooter={false} eyebrow="" school={stage === 'email' ? school : undefined}>
    <View style={styles.slider} onLayout={event => setWidth(event.nativeEvent.layout.width)}>
      <Animated.View style={[styles.track, { width: width ? width * 2 : '200%', height: stage === 'email' ? emailHeight : passwordHeight || undefined }, slide]}>
        <View onLayout={event => setEmailHeight(event.nativeEvent.layout.height)} style={[styles.panel, { width: width || '50%' }]} pointerEvents={stage === 'email' ? 'auto' : 'none'} accessibilityElementsHidden={stage !== 'email'} importantForAccessibility={stage === 'email' ? 'auto' : 'no-hide-descendants'}>
          <Text style={[styles.title, styles.emailTitle]}>{role === 'parent' ? 'Your parent email' : 'Your school email'}</Text>
          <Text style={styles.lead}>{role === 'parent' ? 'Enter the email address registered with your child’s school.' : 'Enter the school email address your administrator gave you.'}</Text>
          <Text style={styles.label}>Email address</Text>
          <View style={[styles.field, !!emailError && styles.fieldError, focusedField === 'email' && styles.fieldFocused]}><View style={styles.emailIconTile}><Svg width={26} height={26} viewBox="0 0 32 32" fill="none"><Rect x={4} y={7} width={24} height={18} rx={3} stroke={colors.gold} strokeWidth={1.8} /><Path d="m5 9 11 8L27 9" stroke={colors.gold} strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round" /></Svg></View><TextInput onFocus={() => setFocusedField('email')} onBlur={() => setFocusedField(null)} accessibilityLabel="Email address" value={email} onChangeText={value => { lookupVersion.current += 1; setChecking(false); setEmailError(''); setAccount(null); onAccountValidated?.(null); onEmailChange(value); }} placeholder="you@school.com" placeholderTextColor="#85838B" keyboardType="email-address" autoCapitalize="none" autoCorrect={false} autoComplete="email" returnKeyType="next" onSubmitEditing={next} style={styles.input} /></View>
          {!!emailError && <Text accessibilityLiveRegion="polite" style={styles.validation}>{emailError}</Text>}
          {!!error && stage === 'email' && <Text style={styles.validation}>{error}</Text>}
          <Text style={styles.emailHelp}>Forgot your email? Please visit your school administrator, or <Text accessibilityRole="link" accessibilityLabel="Return to role selection" disabled={busy || checking} onPress={onChangeRole} style={styles.helpLink}>click here</Text> to return to role selection.</Text>
          <Pressable disabled={checking || busy} onPress={() => void next()} style={({ pressed }) => [styles.button, pressed && styles.buttonMuted]}><Text style={styles.buttonText}>{checking ? 'Finding your account…' : 'Continue'}</Text>{checking ? <ActivityIndicator color={colors.gold} /> : <Ionicons name="arrow-forward-circle" size={23} color={colors.gold} />}</Pressable>
          <Pressable accessibilityRole="button" accessibilityLabel="Back to school" disabled={busy || checking} onPress={onChangeSchool} style={({ pressed }) => [styles.backButton, (busy || checking || pressed) && styles.buttonMuted]}><Text style={styles.backText}>Back</Text></Pressable>
        </View>
        <View onLayout={event => setPasswordHeight(event.nativeEvent.layout.height)} style={[styles.panel, { width: width || '50%' }]} pointerEvents={stage === 'password' ? 'auto' : 'none'} accessibilityElementsHidden={stage !== 'password'} importantForAccessibility={stage === 'password' ? 'auto' : 'no-hide-descendants'}>
          <Text style={[styles.title, styles.emailTitle]}>Your password</Text><Text style={styles.lead}>{account ? 'One last step to your school workspace.' : `Continue signing in with ${email.trim()}.`}</Text>
          <Text style={styles.label}>Password</Text><View style={[styles.field, !!error && styles.fieldError, focusedField === 'password' && styles.fieldFocused]}><View style={styles.emailIconTile}><Svg width={26} height={26} viewBox="0 0 32 32" fill="none"><Path d="M10 14V9a6 6 0 0 1 12 0v5" stroke={colors.gold} strokeWidth={1.8} strokeLinecap="round" /><Rect x={6} y={14} width={20} height={15} rx={3} stroke={colors.gold} strokeWidth={1.8} /><Circle cx={16} cy={21} r={2} fill={colors.gold} /><Path d="M16 23v2" stroke={colors.gold} strokeWidth={1.8} strokeLinecap="round" /></Svg></View><TextInput onFocus={() => setFocusedField('password')} onBlur={() => setFocusedField(null)} accessibilityLabel="Password" value={password} onChangeText={onPasswordChange} placeholder="Enter your password" placeholderTextColor="#85838B" secureTextEntry={!showPassword} autoCapitalize="none" autoCorrect={false} autoComplete="password" returnKeyType="done" onSubmitEditing={() => { if (!busy) onSubmit(); }} style={styles.input} /><Pressable accessibilityLabel={showPassword ? 'Hide password' : 'Show password'} onPress={() => setShowPassword(value => !value)} hitSlop={10}><Ionicons name={showPassword ? 'eye-off-outline' : 'eye-outline'} size={21} color={colors.navy} /></Pressable></View>
          <Pressable disabled={busy || checking} onPress={onForgotPassword} style={styles.forgot}><Text style={styles.forgotText}>Forgot password?</Text></Pressable>
          {!!error && <View style={styles.errorRow}><Ionicons name="alert-circle" size={16} color={colors.danger} /><Text accessibilityLiveRegion="polite" style={styles.error}>{error}</Text></View>}
          <Pressable disabled={busy || checking} onPress={onSubmit} style={({ pressed }) => [styles.button, (busy || pressed) && styles.buttonMuted]}>{busy ? <ActivityIndicator color={colors.gold} /> : <><Text style={styles.buttonText}>Log in to Edlink</Text><Ionicons name="arrow-forward-circle" size={23} color={colors.gold} /></>}</Pressable>
          <Pressable accessibilityRole="button" accessibilityLabel="Back to email" disabled={busy || checking} onPress={() => changeStage('email')} style={({ pressed }) => [styles.backButton, (busy || checking || pressed) && styles.buttonMuted]}><Text style={styles.backText}>Back</Text></Pressable>
        </View>
      </Animated.View>
    </View>
  </AuthLayout>;
}

const baseStyles = StyleSheet.create({
  passwordHeader: { alignItems: 'center', paddingTop: 8, paddingBottom: 24 },
  headerLogo: { width: 156, height: 46, marginBottom: 12 },
  changeEmail: { minHeight: 44, alignItems: 'center', justifyContent: 'center' },
  changeEmailText: { color: colors.gold, fontSize: 12, fontWeight: '600', textDecorationLine: 'underline' },
  emailTitle: { marginTop: 0, fontSize: 27, lineHeight: 34 },
  emailIconTile: { width: 44, height: 44, marginLeft: -5, borderRadius: 14, backgroundColor: colors.navy, alignItems: 'center', justifyContent: 'center' },
  profile: { alignItems: 'center', paddingTop: 8, paddingBottom: 4 },
  avatar: { width: 140, height: 140, borderRadius: 70, backgroundColor: '#FFF5D5', borderWidth: 2, borderColor: colors.gold, overflow: 'hidden', alignItems: 'center', justifyContent: 'center' },
  avatarImage: { width: '100%', height: '100%' },
  initials: { color: colors.navy, fontSize: 28, fontWeight: '700' },
  profileName: { color: colors.surface, fontSize: 20, fontWeight: '700', textAlign: 'center', marginTop: 10 },
  profileEmail: { color: '#D1D7E2', fontSize: 12, textAlign: 'center', marginTop: 3 },
  profileRole: { color: colors.gold, fontSize: 11, fontWeight: '600', marginTop: 5 },

  emailHelp: { color: colors.muted, fontSize: 12, lineHeight: 18, marginTop: 12 },
  helpLink: { color: colors.goldDark, fontWeight: '700', textDecorationLine: 'underline' },
  fieldFocused: { borderColor: colors.gold, boxShadow: '0 0 0 3px rgba(239, 176, 0, 0.20)' },
  backButton: { minHeight: 52, marginTop: 12, borderRadius: 999, borderWidth: 1, borderColor: colors.gold, backgroundColor: colors.gold, alignItems: 'center', justifyContent: 'center' },
  backText: { color: colors.navy, fontSize: 15, fontWeight: '600' },
  slider: { overflow: 'hidden' }, track: { flexDirection: 'row', alignItems: 'flex-start' }, panel: { flexShrink: 0, paddingBottom: 8 },
  roleChoice: { alignSelf: 'center', marginTop: 20 }, validation: { color: colors.danger, fontSize: 12, lineHeight: 18, marginTop: 10 },
  emailSummary: { flexDirection: 'row', alignItems: 'center', gap: 9, backgroundColor: colors.surfaceLow, borderRadius: 12, padding: 12, marginTop: 16 }, emailValue: { flex: 1, fontSize: 12, fontWeight: '600', color: colors.navy },
  headingRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', gap: 20 }, back: { width: 44, height: 44, borderRadius: 22, borderWidth: 1, borderColor: colors.outline, alignItems: 'center', justifyContent: 'center' }, role: { minHeight: 42, borderRadius: 21, paddingHorizontal: 13, backgroundColor: colors.surfaceLow, borderWidth: 1, borderColor: colors.navy, flexDirection: 'row', alignItems: 'center', gap: 7 }, roleText: { color: colors.navy, fontSize: 12, fontWeight: '900' }, title: { color: colors.navy, fontSize: 31, fontWeight: '900', marginTop: 18 }, lead: { color: colors.muted, fontSize: 14, lineHeight: 20, marginTop: 4 }, label: { color: colors.ink, fontSize: 13, fontWeight: '800', marginTop: 18, marginBottom: 7 }, field: { minHeight: 56, borderRadius: radius.card, borderWidth: 1, borderColor: colors.outline, paddingHorizontal: 14, flexDirection: 'row', alignItems: 'center', gap: 10 }, fieldError: { borderColor: colors.danger }, input: { flex: 1, height: 54, color: colors.ink, fontSize: 15 }, forgot: { alignSelf: 'flex-end', minHeight: 40, justifyContent: 'center' }, forgotText: { color: colors.goldDark, fontSize: 13, fontWeight: '900' }, errorRow: { flexDirection: 'row', alignItems: 'flex-start', gap: 6 }, error: { flex: 1, color: colors.danger, fontSize: 12, lineHeight: 17 }, button: { minHeight: 58, marginTop: 15, borderRadius: 999, backgroundColor: colors.navy, flexDirection: 'row', gap: 10, justifyContent: 'center', alignItems: 'center' }, buttonText: { color: colors.gold, fontWeight: '900', fontSize: 16 }, buttonMuted: { opacity: 0.65 }, biometric: { minHeight: 52, marginTop: 13, borderRadius: radius.card, borderWidth: 1, borderColor: colors.outline, flexDirection: 'row', gap: 9, alignItems: 'center', justifyContent: 'center' }, biometricText: { color: colors.navy, fontSize: 13, fontWeight: '900' },
});

const styles = baseStyles;
