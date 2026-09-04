import { useCallback, useEffect, useRef, useState } from 'react';
import { Animated, Easing, KeyboardAvoidingView, Platform, ScrollView, StyleSheet, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';
import type { Role } from '../../types';
import { LoginScreen } from './LoginScreen';
import { RoleSelectorScreen } from './RoleSelectorScreen';
import { SchoolScreen } from './SchoolScreen';
import { SplashScreen } from './SplashScreen';
import { ConnectOnboardingScreen, InformOnboardingScreen, ProgressOnboardingScreen } from './OnboardingScreens';

type Step = 'splash' | 'connect' | 'inform' | 'progress' | 'role' | 'school' | 'login';
export function AuthFlow({ onSignIn, messageFor }: { onSignIn: (school: string, email: string, password: string, role: Role) => Promise<void>; messageFor: (error: unknown) => string }) {
  const [step, setStep] = useState<Step>('splash'); const [role, setRole] = useState<Role>('student'); const [school, setSchool] = useState(''); const [email, setEmail] = useState(''); const [password, setPassword] = useState(''); const [busy, setBusy] = useState(false); const [error, setError] = useState('');
  const opacity = useRef(new Animated.Value(1)).current;
  const translateX = useRef(new Animated.Value(0)).current;
  const transitioning = useRef(false);
  const goTo = useCallback((next: Step, direction: 1 | -1 = 1) => {
    if (transitioning.current || next === step) return;
    transitioning.current = true;
    Animated.parallel([
      Animated.timing(opacity, { toValue: 0, duration: 180, easing: Easing.out(Easing.quad), useNativeDriver: true }),
      Animated.timing(translateX, { toValue: -18 * direction, duration: 180, easing: Easing.out(Easing.quad), useNativeDriver: true }),
    ]).start(() => {
      setStep(next);
      translateX.setValue(22 * direction);
      Animated.parallel([
        Animated.timing(opacity, { toValue: 1, duration: 320, easing: Easing.out(Easing.cubic), useNativeDriver: true }),
        Animated.timing(translateX, { toValue: 0, duration: 320, easing: Easing.out(Easing.cubic), useNativeDriver: true }),
      ]).start(() => { transitioning.current = false; });
    });
  }, [opacity, step, translateX]);
  useEffect(() => { if (step !== 'splash') return; const timer = setTimeout(() => goTo('connect'), 1800); return () => clearTimeout(timer); }, [goTo, step]);
  const clear = () => setError(''); const continueToLogin = () => { if (!school.trim()) return setError('Enter the school number provided by your school.'); clear(); goTo('login'); };
  const submit = async () => { if (!email.trim() || !password) return setError('Enter your email address and password.'); setBusy(true); clear(); try { await onSignIn(school.trim(), email.trim(), password, role); } catch (caught) { setError(messageFor(caught)); } finally { setBusy(false); } };
  let content;
  if (step === 'splash') content = <View style={styles.bleed}><StatusBar style="dark" /><SplashScreen /></View>;
  else if (step === 'connect') content = <View style={styles.bleed}><StatusBar style="dark" /><ConnectOnboardingScreen onNext={() => goTo('inform')} onSkip={() => goTo('role')} /></View>;
  else if (step === 'inform') content = <View style={styles.bleed}><StatusBar style="dark" /><InformOnboardingScreen onBack={() => goTo('connect', -1)} onNext={() => goTo('progress')} /></View>;
  else if (step === 'progress') content = <View style={styles.bleed}><StatusBar style="dark" /><ProgressOnboardingScreen onBack={() => goTo('inform', -1)} onNext={() => goTo('role')} /></View>;
  else if (step === 'role') content = <View style={styles.bleed}><StatusBar style="dark" /><RoleSelectorScreen onSelect={value => { setRole(value); goTo('school'); }} /></View>;
  else content = <SafeAreaView style={styles.safe}><StatusBar style="dark" /><KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}><ScrollView contentContainerStyle={styles.form} keyboardShouldPersistTaps="handled">{step === 'school' ? <SchoolScreen school={school} error={error} onChange={value => { setSchool(value); clear(); }} onBack={() => { clear(); goTo('role', -1); }} onContinue={continueToLogin} /> : <LoginScreen role={role} school={school} email={email} password={password} error={error} busy={busy} onEmailChange={value => { setEmail(value); clear(); }} onPasswordChange={value => { setPassword(value); clear(); }} onBack={() => { clear(); goTo('school', -1); }} onChangeSchool={() => { clear(); goTo('school', -1); }} onChangeRole={() => { clear(); goTo('role', -1); }} onSubmit={submit} />}</ScrollView></KeyboardAvoidingView></SafeAreaView>;
  return <Animated.View style={[styles.flex, { opacity, transform: [{ translateX }] }]}>{content}</Animated.View>;
}
const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: 'white' },
  bleed: { flex: 1, backgroundColor: '#F9F9FF' },
  flex: { flex: 1 },
  form: { flexGrow: 1, justifyContent: 'center', padding: 20 },
});
