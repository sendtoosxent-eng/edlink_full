import { useThemedStyles } from '../../theme/ThemeProvider';
import { TeacherHero, useTeacherBack, teacherScreenStyles } from '../../components/TeacherScreenUI';
import { ErrorScreen } from '../../components/ErrorScreen';
import { useCallback, useEffect, useState } from 'react';
import { Pressable, RefreshControl, ScrollView, StyleSheet, View } from 'react-native';
import { Text } from '../../components/Typography';
import { api } from '../../api';
import { colors } from '../../theme/index';

type Lesson = { id: number; day_of_week: string; starts_at: string; ends_at: string; subject?: string; label?: string; class_name?: string; stream_name?: string };
const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
export function TeacherScheduleScreen({ token, onBack, backLabel = 'Teaching' }: { token: string; onBack: () => void; backLabel?: string }) {
  const styles = useThemedStyles(baseStyles);
  const [items, setItems] = useState<Lesson[]>([]); const [day, setDay] = useState(DAYS[(new Date().getDay() + 6) % 7]);
  const [busy, setBusy] = useState(false); const [error, setError] = useState('');
  const load = useCallback(async () => { setBusy(true); setError(''); try { setItems((await api.get<Lesson[]>('/timetable', token)).data); } catch (e) { setError(e instanceof Error ? e.message : 'Unable to load timetable.'); } finally { setBusy(false); } }, [token]);
  useEffect(() => { void load(); }, [load]);
  const lessons = items.filter(item => item.day_of_week === day).sort((a, b) => a.starts_at.localeCompare(b.starts_at));
  useTeacherBack(onBack);
  if (error) return <ErrorScreen message={error} retry={load} onBack={onBack} />;
  return <ScrollView contentContainerStyle={teacherScreenStyles.content} refreshControl={<RefreshControl refreshing={busy} onRefresh={load} tintColor={colors.primary} />}>
    <TeacherHero title="My timetable" subtitle="Your assigned classes and lessons for the week" artwork="lessons" />
    <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.days}>{DAYS.map(value => <Pressable accessibilityRole="button" accessibilityState={{ selected: day === value }} key={value} onPress={() => setDay(value)} style={[styles.day, day === value && styles.selected]}><Text style={{ color: day === value ? colors.secondary : colors.primary }}>{value.slice(0, 3)}</Text></Pressable>)}</ScrollView>
    {!!error && <Text>{error}</Text>}{lessons.map(lesson => <View key={lesson.id} style={styles.card}><Text style={styles.time}>{lesson.starts_at.slice(0, 5)} – {lesson.ends_at.slice(0, 5)}</Text><Text style={styles.name}>{lesson.subject || lesson.label || 'Lesson'}</Text><Text style={styles.meta}>{lesson.class_name}{lesson.stream_name ? ` · ${lesson.stream_name}` : ''}</Text></View>)}
    {!busy && !error && !lessons.length && <Text style={styles.meta}>No lessons scheduled for {day}.</Text>}
  </ScrollView>;
}
const baseStyles = StyleSheet.create({ content: { padding: 20, gap: 17 }, back: { paddingVertical: 12, color: colors.primary, fontWeight: '700' }, title: { fontSize: 27, color: colors.primary, fontWeight: '800' }, days: { gap: 8 }, day: { minHeight: 46, paddingHorizontal: 17, paddingVertical: 13, borderRadius: 999, backgroundColor: '#FFF5D5' }, selected: { backgroundColor: colors.primary }, card: { backgroundColor: colors.surface, borderRadius: 18, padding: 20, gap: 7, borderWidth: 1, borderColor: colors.border, borderLeftWidth: 4, borderLeftColor: colors.secondary }, time: { color: colors.textMuted, fontSize: 12 }, name: { color: colors.primary, fontWeight: '700', fontSize: 18 }, meta: { color: colors.textMuted, lineHeight: 20 } });

const styles = baseStyles;
