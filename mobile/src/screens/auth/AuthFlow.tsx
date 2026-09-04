import { useCallback, useEffect, useRef, useState } from 'react';
import { Animated, Easing, KeyboardAvoidingView, Platform, ScrollView, StyleSheet, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';
import { api } from '../../api';
import type { LoginResult, Role, SchoolIdentity } from '../../types';
import { LoginScreen } from './LoginScreen';
import { OtpScreen } from './OtpScreen';
import { PasswordRecoveryScreen } from './PasswordRecoveryScreen';
import { RoleSelectorScreen } from './RoleSelectorScreen';
import { SchoolScreen } from './SchoolScreen';
import { SplashScreen } from './SplashScreen';
import { ConnectOnboardingScreen, InformOnboardingScreen, ProgressOnboardingScreen } from './OnboardingScreens';

type Step = 'splash' | 'connect' | 'inform' | 'progress' | 'role' | 'school' | 'login' | 'otp' | 'recovery';
type Props = { onSignIn: (school: string, email: string, password: string, role: Role) => Promise<LoginResult>; onVerifyOtp: (challenge: string, code: string) => Promise<void>; onBiometricSignIn: () => Promise<void>; biometricAvailable: boolean; messageFor: (error: unknown) => string };

export function AuthFlow({ onSignIn, onVerifyOtp, onBiometricSignIn, biometricAvailable, messageFor }: Props) {
  const [step, setStep] = useState<Step>('splash'); const [role, setRole] = useState<Role>('student'); const [school, setSchool] = useState(''); const [schoolIdentity, setSchoolIdentity] = useState<SchoolIdentity>(); const [email, setEmail] = useState(''); const [password, setPassword] = useState(''); const [code, setCode] = useState(''); const [challenge, setChallenge] = useState(''); const [maskedEmail, setMaskedEmail] = useState(''); const [busy, setBusy] = useState(false); const [error, setError] = useState('');
  const opacity = useRef(new Animated.Value(1)).current; const translateX = useRef(new Animated.Value(0)).current; const transitioning = useRef(false);
  const goTo = useCallback((next: Step, direction: 1 | -1 = 1) => { if (transitioning.current || next === step) return; transitioning.current = true; Animated.parallel([Animated.timing(opacity, { toValue: 0, duration: 160, easing: Easing.out(Easing.quad), useNativeDriver: true }), Animated.timing(translateX, { toValue: -18 * direction, duration: 160, easing: Easing.out(Easing.quad), useNativeDriver: true })]).start(() => { setStep(next); translateX.setValue(24 * direction); Animated.parallel([Animated.timing(opacity, { toValue: 1, duration: 300, easing: Easing.out(Easing.cubic), useNativeDriver: true }), Animated.timing(translateX, { toValue: 0, duration: 300, easing: Easing.out(Easing.cubic), useNativeDriver: true })]).start(() => { transitioning.current = false; }); }); }, [opacity, step, translateX]);
  useEffect(() => { if (step !== 'splash') return; const timer = setTimeout(() => goTo('connect'), 1800); return () => clearTimeout(timer); }, [goTo, step]);
  const clear = () => setError('');
  const continueToLogin = async () => { if (!school.trim()) return setError('Enter the school number provided by your school.'); setBusy(true); clear(); try { const { data } = await api.school(school.trim()); setSchool(data.number); setSchoolIdentity(data); goTo('login'); } catch (caught) { setError(messageFor(caught)); } finally { setBusy(false); } };
  const submit = async () => { if (!email.trim() || !password) return setError('Enter your email address and password.'); setBusy(true); clear(); try { const result = await onSignIn(school, email.trim(), password, role); if (result.otp_required) { setChallenge(result.challenge_token); setMaskedEmail(result.masked_email); setCode(''); setPassword(''); goTo('otp'); } } catch (caught) { setError(messageFor(caught)); } finally { setBusy(false); } };
  const verify = async () => { if (code.length !== 6) return setError('Enter the complete 6-digit code.'); setBusy(true); clear(); try { await onVerifyOtp(challenge, code); } catch (caught) { setError(messageFor(caught)); } finally { setBusy(false); } };
  const resend = async () => { setBusy(true); clear(); try { const response = await api.resendOtp(challenge); setMaskedEmail(response.data.masked_email); } catch (caught) { setError(messageFor(caught)); } finally { setBusy(false); } };
  const biometric = async () => { setBusy(true); clear(); try { await onBiometricSignIn(); } catch (caught) { setError(messageFor(caught)); } finally { setBusy(false); } };
  const requestReset = async (resetEmail: string) => { if (!resetEmail.trim()) { setError('Enter your email address.'); return false; } setBusy(true); clear(); try { await api.forgotPassword(school, resetEmail.trim()); return true; } catch (caught) { setError(messageFor(caught)); return false; } finally { setBusy(false); } };
  const resetPassword = async (values: { email: string; token: string; password: string; password_confirmation: string }) => { if (!values.email || !values.token || !values.password) { setError('Complete every password reset field.'); return false; } if (values.password !== values.password_confirmation) { setError('The passwords do not match.'); return false; } setBusy(true); clear(); try { await api.resetPassword({ ...values, school_number: school }); setEmail(values.email); setPassword(''); goTo('login', -1); return true; } catch (caught) { setError(messageFor(caught)); return false; } finally { setBusy(false); } };

  let content;
  if (step === 'splash') content = <View style={styles.bleed}><SplashScreen /></View>;
  else if (step === 'connect') content = <View style={styles.bleed}><ConnectOnboardingScreen onNext={() => goTo('inform')} onSkip={() => goTo('role')} /></View>;
  else if (step === 'inform') content = <View style={styles.bleed}><InformOnboardingScreen onBack={() => goTo('connect', -1)} onNext={() => goTo('progress')} /></View>;
  else if (step === 'progress') content = <View style={styles.bleed}><ProgressOnboardingScreen onBack={() => goTo('inform', -1)} onNext={() => goTo('role')} /></View>;
  else if (step === 'role') content = <View style={styles.bleed}><RoleSelectorScreen onSelect={value => { setRole(value); goTo('school'); }} /></View>;
  else if (step === 'school') content = <SchoolScreen school={school} error={error} busy={busy} onChange={value => { setSchool(value); setSchoolIdentity(undefined); clear(); }} onBack={() => { clear(); goTo('role', -1); }} onContinue={() => void continueToLogin()} />;
  else if (step === 'login' && schoolIdentity) content = <LoginScreen role={role} school={schoolIdentity} email={email} password={password} error={error} busy={busy} biometricAvailable={biometricAvailable} onEmailChange={value => { setEmail(value); clear(); }} onPasswordChange={value => { setPassword(value); clear(); }} onChangeSchool={() => { clear(); goTo('school', -1); }} onChangeRole={() => { clear(); goTo('role', -1); }} onForgotPassword={() => { clear(); goTo('recovery'); }} onBiometricSignIn={() => void biometric()} onSubmit={() => void submit()} />;
  else if (step === 'otp' && schoolIdentity) content = <OtpScreen school={schoolIdentity} maskedEmail={maskedEmail} code={code} error={error} busy={busy} onCodeChange={value => { setCode(value); clear(); }} onBack={() => { clear(); goTo('login', -1); }} onVerify={() => void verify()} onResend={resend} />;
  else if (schoolIdentity) content = <PasswordRecoveryScreen school={schoolIdentity} initialEmail={email} error={error} busy={busy} onBack={() => { clear(); goTo('login', -1); }} onRequest={requestReset} onReset={resetPassword} />;
  else content = <SchoolScreen school={school} error={error} busy={busy} onChange={setSchool} onBack={() => goTo('role', -1)} onContinue={() => void continueToLogin()} />;

  const formStep = ['school', 'login', 'otp', 'recovery'].includes(step);
  return <SafeAreaView style={styles.safe}><StatusBar style="dark" /><KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}><Animated.View style={[styles.flex, { opacity, transform: [{ translateX }] }]}>{formStep ? <ScrollView contentContainerStyle={styles.form} keyboardShouldPersistTaps="handled" showsVerticalScrollIndicator={false}>{content}</ScrollView> : content}</Animated.View></KeyboardAvoidingView></SafeAreaView>;
}
const styles = StyleSheet.create({ safe: { flex: 1, backgroundColor: '#FFCE4B' }, bleed: { flex: 1, backgroundColor: '#F9F9FF' }, flex: { flex: 1 }, form: { flexGrow: 1 } });
