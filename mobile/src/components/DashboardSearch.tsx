import { useThemedStyles } from '../theme/ThemeProvider';
import { useState } from 'react';
import { Keyboard, Pressable, StyleSheet, View } from 'react-native';
import { Text, TextInput } from './Typography';
import { AppSvg } from './AppSvg';
import { colors } from '../theme/index';
import type { AppTab } from '../screens/app/DashboardScreens';
import type { Dashboard, User } from '../types';
import { toolSection } from '../screens/app/TeacherAccessScreen';

type Entry = { label: string; keywords: string; tab: AppTab };
export function DashboardSearch({ user, data, navigate }: { user: User; data: Dashboard; navigate: (tab: AppTab) => void }) {
  const styles = useThemedStyles(baseStyles);
  const [query, setQuery] = useState('');
  const [focused, setFocused] = useState(false);
  const entries: Entry[] = [
    { label: 'My profile', keywords: 'account personal details', tab: 'more' },
    { label: 'Notifications', keywords: 'notices announcements messages', tab: 'notifications' },
    { label: 'Settings', keywords: 'logout sign out', tab: 'settings' },
    { label: 'Homework', keywords: 'assignments learning tasks', tab: 'homework' },
  ];
  if (user.role === 'teacher') {
    for (const tool of data.teacher_workspace?.tools ?? []) entries.push({ label: tool.label, keywords: tool.group, tab: tool.id === 'exams.results' ? 'teacher_results' : tool.id === 'communications.index' ? 'teacher_communications' : tool.id === 'attendance.index' ? 'daily_attendance' : tool.id === 'attendance.subject' ? 'subject_attendance' : tool.id === 'attendance.reports' ? 'attendance_reports' : tool.id === 'students.index' ? 'class_students' : tool.native ? tool.native as AppTab : toolSection(tool.group) });
    entries.push({ label: 'Timetable', keywords: 'schedule lessons week', tab: 'teacher_schedule' });
  } else {
    entries.push({ label: 'Results', keywords: 'marks report performance', tab: 'results' }, { label: 'Attendance', keywords: 'register present absent', tab: 'attendance' }, { label: 'Payments', keywords: 'fees balance finance', tab: 'payments' });
    if (user.role === 'student') entries.push({ label: 'Timetable', keywords: 'schedule lessons week', tab: 'student_schedule' }, { label: 'School calendar', keywords: 'events dates', tab: 'student_calendar' });
  }
  const term = query.trim().toLowerCase();
  const results = entries.filter((entry, index) => entries.findIndex(other => other.label === entry.label) === index && `${entry.label} ${entry.keywords}`.toLowerCase().includes(term));
  const open = (tab: AppTab) => { Keyboard.dismiss(); setQuery(''); setFocused(false); navigate(tab); };
  return <View style={styles.container}>
    <View style={[styles.field, focused && styles.focused]}><AppSvg name="search" size={20} color={colors.textMuted} /><TextInput accessibilityLabel="Search school tools" placeholder="Search school tools…" placeholderTextColor={colors.textMuted} value={query} onChangeText={setQuery} onFocus={() => setFocused(true)} onBlur={() => setFocused(false)} returnKeyType="search" onSubmitEditing={() => { if (results[0] && term) open(results[0].tab); }} style={styles.input} />{!!query && <Pressable accessibilityRole="button" accessibilityLabel="Clear search" onPress={() => setQuery('')} hitSlop={8}><Text style={styles.clear}>×</Text></Pressable>}</View>
    {!!term && <View style={styles.results}>{results.slice(0, 6).map(entry => <Pressable key={entry.label} accessibilityRole="button" onPress={() => open(entry.tab)} style={styles.result}><Text style={styles.resultText}>{entry.label}</Text><AppSvg name="chevron" size={15} /></Pressable>)}{!results.length && <Text accessibilityLiveRegion="polite" style={styles.empty}>No tools found. Try “homework” or “results”.</Text>}</View>}
  </View>;
}
const baseStyles = StyleSheet.create({
  container: { marginBottom: 16 }, field: { flexDirection: 'row', alignItems: 'center', gap: 10, backgroundColor: '#F0F2F7', borderRadius: 16, minHeight: 50, paddingHorizontal: 15, borderWidth: 1, borderColor: 'transparent' }, focused: { borderColor: colors.secondary, backgroundColor: colors.surface }, input: { flex: 1, minWidth: 0, color: colors.primary, fontSize: 13, paddingVertical: 12 }, clear: { color: colors.primary, fontSize: 24 }, results: { marginTop: 8, borderRadius: 16, backgroundColor: colors.surface, paddingHorizontal: 14 }, result: { minHeight: 48, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 12 }, resultText: { flex: 1, color: colors.primary, fontSize: 13 }, empty: { color: colors.textMuted, fontSize: 12, lineHeight: 18, paddingVertical: 16 },
});

const styles = baseStyles;
