import { useEffect, useState } from 'react';
import { KeyboardAvoidingView, Platform, ScrollView, StyleSheet, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';
import type { Role } from '../../types';
import { LoginScreen } from './LoginScreen';
import { RoleSelectorScreen } from './RoleSelectorScreen';
import { SchoolScreen } from './SchoolScreen';
import { SplashScreen } from './SplashScreen';
import { ConnectOnboardingScreen, InformOnboardingScreen, ProgressOnboardingScreen } from './OnboardingScreens';

type Step = 'splash' | 'connect' | 'inform' | 'progress' | 'role' | 'school' | 'login';
export function AuthFlow({ onSignIn, messageFor }: { onSignIn: (school: string, email: string, password: string) => Promise<void>; messageFor: (error: unknown) => string }) {
  const [step, setStep] = useState<Step>('splash'); const [role, setRole] = useState<Role>('student'); const [school, setSchool] = useState(''); const [email, setEmail] = useState(''); const [password, setPassword] = useState(''); const [busy, setBusy] = useState(false); const [error, setError] = useState('');
  useEffect(() => { const timer = setTimeout(() => setStep('connect'), 1800); return () => clearTimeout(timer); }, []);
  const clear = () => setError(''); const continueToLogin = () => { if (!school.trim()) return setError('Enter the school number provided by your school.'); clear(); setStep('login'); };
  const submit = async () => { if (!email.trim() || !password) return setError('Enter your email address and password.'); setBusy(true); clear(); try { await onSignIn(school.trim(), email.trim(), password); } catch (caught) { setError(messageFor(caught)); } finally { setBusy(false); } };
  if (step === 'splash') return <View style={styles.bleed}><StatusBar style="dark" /><SplashScreen /></View>;
  if (step === 'connect') return <View style={styles.bleed}><StatusBar style="dark" /><ConnectOnboardingScreen onNext={() => setStep('inform')} onSkip={() => setStep('role')} /></View>;
  if (step === 'inform') return <View style={styles.bleed}><StatusBar style="dark" /><InformOnboardingScreen onBack={() => setStep('connect')} onNext={() => setStep('progress')} /></View>;
  if (step === 'progress') return <View style={styles.bleed}><StatusBar style="dark" /><ProgressOnboardingScreen onBack={() => setStep('inform')} onNext={() => setStep('role')} /></View>;
  if (step === 'role') return <View style={styles.bleed}><StatusBar style="dark" /><RoleSelectorScreen onSelect={value => { setRole(value); setStep('school'); }} /></View>;
  return <SafeAreaView style={styles.safe}><StatusBar style="dark" /><KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}><ScrollView contentContainerStyle={styles.form} keyboardShouldPersistTaps="handled">{step === 'school' ? <SchoolScreen school={school} error={error} onChange={value => { setSchool(value); clear(); }} onBack={() => { clear(); setStep('role'); }} onContinue={continueToLogin} /> : <LoginScreen role={role} school={school} email={email} password={password} error={error} busy={busy} onEmailChange={value => { setEmail(value); clear(); }} onPasswordChange={value => { setPassword(value); clear(); }} onBack={() => { clear(); setStep('school'); }} onChangeSchool={() => { clear(); setStep('school'); }} onChangeRole={() => { clear(); setStep('role'); }} onSubmit={submit} />}</ScrollView></KeyboardAvoidingView></SafeAreaView>;
}
const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: 'white' },
  bleed: { flex: 1, backgroundColor: '#F9F9FF' },
  flex: { flex: 1 },
  form: { flexGrow: 1, justifyContent: 'center', padding: 20 },
});
