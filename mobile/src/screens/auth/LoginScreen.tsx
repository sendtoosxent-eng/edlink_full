import { useState } from 'react';
import { Alert, Pressable, StyleSheet, Text, TextInput, View } from 'react-native';
import { BrandLogo } from '../../components/BrandLogo';
import { colors, radius } from '../../theme';
import type { Role } from '../../types';

type Props = {
  role: Role; school: string; email: string; password: string; error: string; busy: boolean;
  onEmailChange: (value: string) => void; onPasswordChange: (value: string) => void;
  onBack: () => void; onChangeSchool: () => void; onChangeRole: () => void; onSubmit: () => void;
};

export function LoginScreen({ role, school, email, password, error, busy, onEmailChange, onPasswordChange, onChangeSchool, onChangeRole, onSubmit }: Props) {
  const [showPassword, setShowPassword] = useState(false);
  const roleLabel = role === 'parent' ? 'Parent or Guardian' : role.charAt(0).toUpperCase() + role.slice(1);

  return (
    <View style={styles.screen}>
      <BrandLogo />

      <Text style={styles.title}>Login</Text>
      <Text style={styles.lead}>Access your Edlink account to manage classes and connect with your school.</Text>

      <Pressable onPress={onChangeRole} style={styles.rolePill}><Text style={styles.roleIcon}>♙</Text><Text style={styles.roleText}>{roleLabel}</Text><Text style={styles.chevron}>⌄</Text></Pressable>

      <Text style={styles.label}>School Number</Text>
      <Pressable onPress={onChangeSchool} style={styles.field}>
        <Text style={styles.fieldIcon}>▦</Text>
        <Text style={[styles.fieldText, !school && styles.placeholderText]}>{school || 'e.g. 10245'}</Text>
      </Pressable>

      <Text style={styles.label}>Email Address</Text>
      <View style={styles.field}>
        <Text style={styles.fieldIcon}>✉</Text>
        <TextInput value={email} onChangeText={onEmailChange} placeholder="teacher@school.edu" placeholderTextColor="#85838B" keyboardType="email-address" autoCapitalize="none" autoComplete="email" style={styles.input} />
      </View>

      <Text style={styles.label}>Password</Text>
      <View style={styles.field}>
        <Text style={styles.fieldIcon}>▣</Text>
        <TextInput value={password} onChangeText={onPasswordChange} placeholder="••••••••" placeholderTextColor="#85838B" secureTextEntry={!showPassword} autoCapitalize="none" autoComplete="password" returnKeyType="done" onSubmitEditing={onSubmit} style={styles.input} />
        <Pressable onPress={() => setShowPassword(value => !value)} hitSlop={12}><Text style={styles.eye}>{showPassword ? '◉' : '◎'}</Text></Pressable>
      </View>

      <Pressable onPress={() => Alert.alert('Forgot password', 'Please contact your school administrator to reset your Edlink password.')} style={styles.forgot}><Text style={styles.forgotText}>Forgot Password?</Text></Pressable>
      {!!error && <Text style={styles.error}>{error}</Text>}

      <Pressable disabled={busy} onPress={onSubmit} style={({ pressed }) => [styles.loginButton, (pressed || busy) && styles.mutedButton]}>
        <Text style={styles.loginText}>{busy ? 'Signing in…' : 'Login  →'}</Text>
      </Pressable>

      <Pressable onPress={onChangeRole} style={styles.changeRole}><Text style={styles.changeRoleText}>Not a {role}?  <Text style={styles.changeRoleStrong}>Change role</Text></Text></Pressable>
    </View>
  );
}

const styles = StyleSheet.create({
  screen: { width: '100%', minHeight: 760, paddingBottom: 4 },
  title: { marginTop: 24, color: colors.navy, fontSize: 34, fontWeight: '800', textAlign: 'center', letterSpacing: -0.7 },
  lead: { alignSelf: 'center', maxWidth: 330, marginTop: 10, color: colors.muted, fontSize: 15, lineHeight: 22, textAlign: 'center' },
  rolePill: { alignSelf: 'center', marginTop: 20, marginBottom: 28, minHeight: 48, paddingHorizontal: 20, flexDirection: 'row', alignItems: 'center', gap: 10, borderRadius: radius.pill, backgroundColor: colors.surfaceLow, borderWidth: 1, borderColor: colors.outline },
  roleIcon: { color: colors.navy, fontSize: 19 }, roleText: { color: colors.ink, fontSize: 16, fontWeight: '800' }, chevron: { color: colors.muted, fontSize: 19 },
  label: { marginTop: 14, marginBottom: 7, color: colors.ink, fontSize: 14, fontWeight: '700' },
  field: { minHeight: 58, paddingHorizontal: 16, flexDirection: 'row', alignItems: 'center', gap: 12, borderRadius: radius.card, borderWidth: 1, borderColor: colors.outline, backgroundColor: colors.surface, shadowColor: colors.navy, shadowOpacity: 0.04, shadowRadius: 8, elevation: 1 },
  fieldIcon: { width: 24, color: '#7E7C84', fontSize: 22, textAlign: 'center' },
  fieldText: { flex: 1, color: colors.ink, fontSize: 16 }, placeholderText: { color: '#85838B' },
  input: { flex: 1, height: 56, padding: 0, color: colors.ink, fontSize: 16 },
  eye: { color: '#77757D', fontSize: 23 },
  forgot: { alignSelf: 'flex-end', minHeight: 44, justifyContent: 'center' }, forgotText: { color: colors.navy, fontSize: 14, fontWeight: '600' },
  error: { color: colors.danger, fontSize: 13, lineHeight: 19, marginBottom: 8 },
  loginButton: { minHeight: 58, marginTop: 72, borderRadius: radius.card, backgroundColor: colors.gold, alignItems: 'center', justifyContent: 'center', shadowColor: colors.goldDark, shadowOpacity: 0.2, shadowRadius: 12, elevation: 3 },
  loginText: { color: colors.goldDark, fontSize: 17, fontWeight: '800' }, mutedButton: { opacity: 0.6 },
  changeRole: { minHeight: 48, marginTop: 18, alignItems: 'center', justifyContent: 'center' }, changeRoleText: { color: colors.muted, fontSize: 14 }, changeRoleStrong: { color: colors.navy, fontWeight: '800' },
});
