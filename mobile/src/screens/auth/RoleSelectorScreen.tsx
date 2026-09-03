import { useState } from 'react';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { RoleIcon } from '../../components/RoleIcons';
import { colors, radius } from '../../theme';
import type { Role } from '../../types';

const roles: Array<{ key: Role; label: string; description: string }> = [
  { key: 'teacher', label: 'Teacher', description: 'Manage classes, students, and curriculum.' },
  { key: 'student', label: 'Student', description: 'Access courses, assignments, and grades.' },
  { key: 'parent', label: 'Parent or Guardian', description: 'Monitor progress and communicate with teachers.' },
];

export function RoleSelectorScreen({ onSelect }: { onSelect: (role: Role) => void }) {
  const [selected, setSelected] = useState<Role>();
  return (
    <ScrollView style={styles.screen} contentContainerStyle={styles.content}>
      <Text style={styles.title}>How will you use Edlink?</Text>
      <Text style={styles.lead}>Choose your role to continue.</Text>
      <View style={styles.list}>
        {roles.map(role => {
          const active = selected === role.key;
          return (
            <Pressable key={role.key} onPress={() => setSelected(role.key)} style={({ pressed }) => [styles.card, active && styles.cardActive, pressed && styles.pressed]}>
              <View style={[styles.icon, active && styles.iconActive]}><RoleIcon role={role.key} /></View>
              <View style={styles.cardCopy}><Text style={styles.cardTitle}>{role.label}</Text><Text style={styles.description}>{role.description}</Text></View>
            </Pressable>
          );
        })}
      </View>
      <Pressable disabled={!selected} onPress={() => selected && onSelect(selected)} style={[styles.continue, !selected && styles.disabled]}><Text style={styles.continueText}>Continue</Text></Pressable>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.background },
  content: { flexGrow: 1, paddingHorizontal: 20, paddingTop: 42, paddingBottom: 28 },
  title: { color: colors.ink, fontSize: 30, lineHeight: 38, fontWeight: '800', letterSpacing: -0.7, textAlign: 'center' },
  lead: { color: colors.muted, fontSize: 17, marginTop: 8, marginBottom: 24, textAlign: 'center' },
  list: { gap: 14 },
  card: { minHeight: 165, padding: 18, borderRadius: radius.card, borderWidth: 1, borderColor: colors.outline, backgroundColor: colors.surface, alignItems: 'center', justifyContent: 'center', gap: 10, shadowColor: colors.navy, shadowOpacity: 0.05, shadowRadius: 8, elevation: 1 },
  cardActive: { borderWidth: 2, borderColor: colors.goldDark, backgroundColor: '#FFF9E8' },
  pressed: { opacity: 0.82 },
  icon: { width: 56, height: 56, borderRadius: radius.medium, backgroundColor: colors.navy, alignItems: 'center', justifyContent: 'center' },
  iconActive: { backgroundColor: colors.goldDark },
  cardCopy: { alignItems: 'center' },
  cardTitle: { color: colors.ink, fontSize: 20, fontWeight: '800', textAlign: 'center' },
  description: { color: colors.muted, fontSize: 14, lineHeight: 20, marginTop: 7, textAlign: 'center' },
  continue: { minHeight: 56, marginTop: 22, borderRadius: radius.card, backgroundColor: colors.gold, alignItems: 'center', justifyContent: 'center' },
  disabled: { opacity: 0.45 },
  continueText: { color: colors.goldDark, fontSize: 16, fontWeight: '800' },
});
