import { Ionicons } from '@expo/vector-icons';
import type { ComponentProps } from 'react';
import { Image, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView, useSafeAreaInsets } from 'react-native-safe-area-context';
import { colors, radius } from '../../theme';
import { MotionView } from '../../components/MotionView';
import type { Role } from '../../types';
import { AuthFooter } from './AuthLayout';

const LOGO_IMAGE = require('../../../assets/img/edlink-logo.png');
type RoleOption = { key: Role; label: string; eyebrow: string; description: string; icon: ComponentProps<typeof Ionicons>['name'] };
const ROLES: RoleOption[] = [
  { key: 'teacher', label: 'Teacher', eyebrow: 'Teach & manage', description: 'Take attendance, share homework, enter marks, and follow your classes.', icon: 'school-outline' },
  { key: 'student', label: 'Student', eyebrow: 'Learn & grow', description: 'View lessons, homework, attendance, payments, and published results.', icon: 'book-outline' },
  { key: 'parent', label: 'Parent or guardian', eyebrow: 'Stay connected', description: 'Follow your child’s school day, progress, payments, and notices.', icon: 'people-outline' },
];

export function RoleSelectorScreen({ onSelect }: { onSelect: (role: Role) => void }) {
  const insets = useSafeAreaInsets();
  return <SafeAreaView edges={[]} style={styles.screen}><ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.container}>
    <MotionView distance={0} style={[styles.hero, { paddingTop: insets.top }]}><View style={styles.goldOrb} /><MotionView distance={-8}><MotionView float><Image accessibilityLabel="Edlink" source={LOGO_IMAGE} style={styles.logo} resizeMode="contain" /></MotionView></MotionView><View style={styles.kicker}><Ionicons name="sparkles" size={14} color={colors.gold} /><Text style={styles.kickerText}>PERSONALISED SCHOOL ACCESS</Text></View><Text style={styles.title}>Choose how you use Edlink</Text><Text style={styles.subtitle}>Select your account type so we can take you to the right school workspace.</Text></MotionView>
    <MotionView distance={22} style={styles.sheet}><View style={styles.roleList}>{ROLES.map((item, index) => <MotionView key={item.key} delay={140 + index * 90} distance={18}><Pressable accessibilityRole="button" accessibilityLabel={`Continue as ${item.label}`} onPress={() => onSelect(item.key)} style={({ pressed }) => [styles.card, pressed && styles.cardPressed]}>
      <View style={styles.iconTile}><Ionicons name={item.icon} size={28} color={colors.gold} /></View><View style={styles.cardCopy}><Text style={styles.eyebrow}>{item.eyebrow}</Text><Text style={styles.roleTitle}>{item.label}</Text><Text style={styles.description}>{item.description}</Text></View><View style={styles.arrow}><Ionicons name="arrow-forward" size={20} color={colors.navy} /></View>
    </Pressable></MotionView>)}</View>
      <View style={styles.secureNote}><Ionicons name="shield-checkmark-outline" size={18} color={colors.navy} /><Text style={styles.secureText}>Your role is checked against your school account when you sign in.</Text></View><AuthFooter />
    </MotionView>
  </ScrollView></SafeAreaView>;
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.surface }, container: { flexGrow: 1, backgroundColor: colors.surface }, hero: { minHeight: 325, paddingHorizontal: 24, paddingBottom: 30, backgroundColor: colors.gold, alignItems: 'center', overflow: 'hidden' }, goldOrb: { position: 'absolute', width: 190, height: 190, borderRadius: 95, backgroundColor: '#FFFFFF', opacity: 0.2, right: -70, top: -70 }, logo: { width: 190, height: 118 }, kicker: { flexDirection: 'row', alignItems: 'center', gap: 7, borderRadius: radius.pill, backgroundColor: colors.navy, paddingHorizontal: 13, paddingVertical: 7 }, kickerText: { color: '#FFFFFF', fontSize: 9, fontWeight: '900', letterSpacing: 1.1 }, title: { maxWidth: 330, color: colors.navy, fontSize: 29, lineHeight: 35, fontWeight: '900', textAlign: 'center', letterSpacing: -0.5, marginTop: 17 }, subtitle: { maxWidth: 330, color: colors.navy, opacity: 0.78, fontSize: 13, lineHeight: 20, textAlign: 'center', marginTop: 8 },
  sheet: { flexGrow: 1, marginTop: -24, paddingHorizontal: 20, paddingTop: 27, paddingBottom: 24, backgroundColor: colors.surface, borderTopLeftRadius: 32, borderTopRightRadius: 32, borderWidth: 2, borderBottomWidth: 0, borderColor: colors.navy }, roleList: { gap: 13 }, card: { minHeight: 124, borderRadius: 22, borderWidth: 2, borderColor: colors.navy, backgroundColor: colors.surface, padding: 15, flexDirection: 'row', alignItems: 'center', gap: 14, shadowColor: colors.navy, shadowOffset: { width: 0, height: 5 }, shadowOpacity: 0.06, shadowRadius: 10, elevation: 2 }, cardPressed: { backgroundColor: '#FFF8E3', borderColor: colors.gold, transform: [{ scale: 0.985 }] }, iconTile: { width: 58, height: 72, borderRadius: 18, backgroundColor: colors.navy, alignItems: 'center', justifyContent: 'center' }, cardCopy: { flex: 1 }, eyebrow: { color: colors.gold, fontSize: 9, fontWeight: '900', letterSpacing: 1, textTransform: 'uppercase' }, roleTitle: { color: colors.navy, fontSize: 18, fontWeight: '900', marginTop: 3 }, description: { color: colors.muted, fontSize: 11, lineHeight: 16, marginTop: 4 }, arrow: { width: 35, height: 35, borderRadius: 18, backgroundColor: colors.gold, alignItems: 'center', justifyContent: 'center' }, secureNote: { flexDirection: 'row', alignItems: 'center', gap: 9, padding: 13, marginTop: 18, borderRadius: radius.card, backgroundColor: colors.surfaceLow }, secureText: { flex: 1, color: colors.muted, fontSize: 10, lineHeight: 15, fontWeight: '600' },
});
