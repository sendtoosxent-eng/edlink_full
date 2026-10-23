import { useAppTheme, useThemedStyles } from '../../theme/ThemeProvider';
import type { ReactNode } from 'react';
import { useState } from 'react';
import { Image, Pressable, ScrollView, StyleSheet, View } from 'react-native';
import { Text } from '../../components/Typography';
import { AppSvg, type AppSvgName } from '../../components/AppSvg';
import { EdlinkAlert } from '../../components/EdlinkAlert';
import { colors } from '../../theme/index';
import type { User } from '../../types';
import type { AppTab } from './DashboardScreens';

export function SettingsScreen({ user, navigate, onBack, onSignOut, childSwitcher }: { childSwitcher?: ReactNode; user: User; navigate: (tab: AppTab) => void; onBack: () => void; onSignOut: () => Promise<void> }) {
  const styles = useThemedStyles(baseStyles);
  const { preference, isDark, setPreference } = useAppTheme();
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');
  const signOut = () => EdlinkAlert.alert('Log out of Edlink?', 'You can sign in again with your school account.', [
    { text: 'Cancel', style: 'cancel' },
    { text: 'Log out', style: 'destructive', onPress: () => { setBusy(true); setError(''); void onSignOut().catch(() => setError('Could not log out. Please try again.')).finally(() => setBusy(false)); } },
  ]);
  return <ScrollView contentContainerStyle={styles.content}>
    <Pressable accessibilityRole="button" onPress={onBack} style={styles.back}><AppSvg name="back" size={20} /><Text style={styles.backText}>Dashboard</Text></Pressable>
    <View style={styles.hero}><AppSvg name="settings" size={32} color={colors.secondary} /><Text style={styles.title}>Settings</Text><Text style={styles.subtitle}>Manage your account and school connections.</Text></View>
    {childSwitcher}
    <View style={styles.identity}>{user.avatar_url ? <Image source={{ uri: user.avatar_url }} style={styles.avatar} /> : <View style={styles.avatarFallback}><AppSvg name="profile" color={colors.primary} /></View>}<View style={styles.grow}><Text style={styles.name}>{user.name}</Text><Text style={styles.meta}>{user.email}</Text></View></View>
    <Text style={styles.section}>APPEARANCE</Text>
    <View style={styles.appearance}>
      <Text style={styles.name}>App theme</Text>
      <Text style={styles.meta}>System follows your device. Currently using {isDark ? 'dark' : 'light'} mode.</Text>
      <View style={styles.themeOptions}>{(['system', 'light', 'dark'] as const).map(value => <Pressable key={value} accessibilityRole="radio" accessibilityState={{ checked: preference === value }} onPress={() => setPreference(value)} style={[styles.themeOption, preference === value && styles.themeSelected]}><Text style={[styles.themeLabel, preference === value && styles.themeSelectedLabel]}>{value === 'system' ? 'System' : value === 'light' ? 'Light' : 'Dark'}</Text></Pressable>)}</View>
    </View>
    <Text style={styles.section}>ACCOUNT</Text>
    <SettingsRow icon="profile" title="My profile" subtitle="View and update your personal details" onPress={() => navigate('more')} />
    <SettingsRow icon="bell" title="Notifications" subtitle="School notices and account updates" onPress={() => navigate('notifications')} />
    <Text style={styles.section}>MY SCHOOL</Text>
    <View style={styles.school}><Text style={styles.name}>{user.school.name}</Text><Text style={styles.meta}>School number: {user.school.number}</Text></View>
    <Text style={styles.section}>SESSION</Text>
    <SettingsRow icon="logout" title={busy ? 'Logging out…' : 'Log out'} subtitle="Sign out of this device" onPress={signOut} disabled={busy} />
    {!!error && <Text accessibilityRole="alert" style={styles.error}>{error}</Text>}
    <Text style={styles.brand}>Edlink · Innovating paperless learning</Text>
  </ScrollView>;
}
function SettingsRow({ icon, title, subtitle, onPress, disabled }: { icon: AppSvgName; title: string; subtitle: string; onPress: () => void; disabled?: boolean }) {
  const styles = useThemedStyles(baseStyles);
  return <Pressable accessibilityRole="button" disabled={disabled} onPress={onPress} style={({ pressed }) => [styles.row, (pressed || disabled) && { opacity: 0.6 }]}><View style={styles.icon}><AppSvg name={icon} /></View><View style={styles.grow}><Text style={styles.name}>{title}</Text><Text style={styles.meta}>{subtitle}</Text></View><AppSvg name="chevron" size={17} /></Pressable>;
}
const baseStyles = StyleSheet.create({
  appearance: { backgroundColor: colors.surface, borderRadius: 18, padding: 16, gap: 6 },
  themeOptions: { flexDirection: 'row', gap: 8, marginTop: 12 },
  themeOption: { flex: 1, alignItems: 'center', paddingVertical: 13, borderRadius: 999, borderWidth: 1, borderColor: colors.border },
  themeSelected: { backgroundColor: colors.secondary, borderColor: colors.secondary },
  themeLabel: { color: colors.primary, fontSize: 12, fontWeight: '600' },
  themeSelectedLabel: { color: '#0B132B' },

  content: { padding: 20, paddingBottom: 36, backgroundColor: colors.background, flexGrow: 1 },
  back: { flexDirection: 'row', alignItems: 'center', gap: 8, minHeight: 44, marginBottom: 12 }, backText: { color: colors.primary, fontSize: 13, fontWeight: '600' },
  hero: { backgroundColor: colors.primary, borderRadius: 24, padding: 24 }, title: { color: colors.surface, fontSize: 28, fontWeight: '700', marginTop: 12 }, subtitle: { color: '#D1D7E2', fontSize: 12, lineHeight: 19, marginTop: 6 },
  identity: { flexDirection: 'row', alignItems: 'center', gap: 12, paddingVertical: 22 }, avatar: { width: 52, height: 52, borderRadius: 26 }, avatarFallback: { width: 52, height: 52, borderRadius: 26, backgroundColor: '#FFF5D5', alignItems: 'center', justifyContent: 'center' },
  grow: { flex: 1, minWidth: 0 }, name: { color: colors.primary, fontSize: 14, fontWeight: '600' }, meta: { color: colors.textMuted, fontSize: 11, lineHeight: 17, marginTop: 4 },
  section: { color: colors.textMuted, fontSize: 10, letterSpacing: 1.5, fontWeight: '600', marginTop: 18, marginBottom: 10 },
  row: { backgroundColor: colors.surface, padding: 16, borderRadius: 18, flexDirection: 'row', alignItems: 'center', gap: 12, marginBottom: 8 }, icon: { width: 42, height: 42, borderRadius: 14, backgroundColor: '#FFF5D5', alignItems: 'center', justifyContent: 'center' },
  school: { padding: 18, backgroundColor: colors.surface, borderRadius: 18 }, brand: { color: colors.textMuted, textAlign: 'center', fontSize: 10, marginTop: 28 }, error: { color: '#BA1A1A', fontSize: 12, marginTop: 10 },
});

const styles = baseStyles;
