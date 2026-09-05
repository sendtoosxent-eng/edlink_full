import { Text } from '../../components/Typography';
import { Ionicons } from '@expo/vector-icons';
import type { ComponentProps } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';
import { colors, radius } from '../../theme';
import { MotionView } from '../../components/MotionView';
import type { Role } from '../../types';
import { AuthLayout } from './AuthLayout';

type RoleOption = { key: Role; label: string; eyebrow: string; description: string; icon: ComponentProps<typeof Ionicons>['name'] };
const ROLES: RoleOption[] = [
  { key: 'teacher', label: 'Teacher', eyebrow: 'Teach & manage', description: 'Take attendance, share homework, enter marks, and follow your classes.', icon: 'school-outline' },
  { key: 'student', label: 'Student', eyebrow: 'Learn & grow', description: 'View lessons, homework, attendance, payments, and published results.', icon: 'book-outline' },
  { key: 'parent', label: 'Parent or guardian', eyebrow: 'Stay connected', description: 'Follow your child’s school day, progress, payments, and notices.', icon: 'people-outline' },
];

export function RoleSelectorScreen({ onSelect }: { onSelect: (role: Role) => void }) {
  return <AuthLayout eyebrow="Personalised school access" heroTitle="Choose how you use Edlink" heroSubtitle="Select your account type so we can take you to the right school workspace.">
    <View style={styles.roleList}>{ROLES.map((item, index) => <MotionView key={item.key} delay={140 + index * 90} distance={18}><Pressable accessibilityRole="button" accessibilityLabel={`Continue as ${item.label}`} onPress={() => onSelect(item.key)} style={({ pressed }) => [styles.card, pressed && styles.cardPressed]}>
      <View style={styles.iconTile}><Ionicons name={item.icon} size={28} color={colors.gold} /></View><View style={styles.cardCopy}><Text style={styles.eyebrow}>{item.eyebrow}</Text><Text style={styles.roleTitle}>{item.label}</Text><Text style={styles.description}>{item.description}</Text></View><View style={styles.arrow}><Ionicons name="arrow-forward" size={20} color={colors.navy} /></View>
    </Pressable></MotionView>)}</View>
      <View style={styles.secureNote}><Ionicons name="shield-checkmark-outline" size={18} color={colors.navy} /><Text style={styles.secureText}>Your role is checked against your school account when you sign in.</Text></View>
  </AuthLayout>;
}

const styles = StyleSheet.create({
  roleList: { gap: 13 }, card: { minHeight: 124, borderRadius: 22, borderWidth: 2, borderColor: colors.navy, backgroundColor: colors.surface, padding: 15, flexDirection: 'row', alignItems: 'center', gap: 14, shadowColor: colors.navy, shadowOffset: { width: 0, height: 5 }, shadowOpacity: 0.06, shadowRadius: 10, elevation: 2 }, cardPressed: { backgroundColor: '#FFF8E3', borderColor: colors.gold, transform: [{ scale: 0.985 }] }, iconTile: { width: 50, height: 50, borderRadius: 50, backgroundColor: colors.navy, alignItems: 'center', justifyContent: 'center' }, cardCopy: { flex: 1 }, eyebrow: { color: colors.gold, fontSize: 9, fontWeight: '900', letterSpacing: 1, textTransform: 'uppercase' }, roleTitle: { color: colors.navy, fontSize: 18, fontWeight: '900', marginTop: 3 }, description: { color: colors.muted, fontSize: 11, lineHeight: 16, marginTop: 4 }, arrow: { width: 35, height: 35, borderRadius: 18, backgroundColor: colors.gold, alignItems: 'center', justifyContent: 'center' }, secureNote: { flexDirection: 'row', alignItems: 'center', gap: 9, padding: 13, marginTop: 18, borderRadius: radius.card, backgroundColor: colors.surfaceLow }, secureText: { flex: 1, color: colors.muted, fontSize: 10, lineHeight: 15, fontWeight: '600' },
});
