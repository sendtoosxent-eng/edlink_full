import { ErrorScreen } from '../../components/ErrorScreen';
import { NativeModuleScreen } from './NativeModuleScreen';
import { useCallback, useEffect, useState } from 'react';
import { Pressable, RefreshControl, ScrollView, StyleSheet, View } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { Text } from '../../components/Typography';
import { api } from '../../api';
import { colors } from '../../theme/index';
import type { Dashboard, TeacherWorkspace } from '../../types';
import type { AppTab } from './DashboardScreens';

export const ACCESS_SECTIONS = [
  { tab: 'class_access', title: 'My Classes', icon: 'people-outline', description: 'Learners, registers and class administration' },
  { tab: 'teaching_access', title: 'Teaching', icon: 'book-outline', description: 'Subjects, homework, marks and timetable' },
  { tab: 'reports_access', title: 'Reports', icon: 'bar-chart-outline', description: 'Results, report cards and attendance reports' },
  { tab: 'school_access', title: 'My School', icon: 'school-outline', description: 'Notices, leave, clubs and school services' },
] as const;
export type AccessTab = typeof ACCESS_SECTIONS[number]['tab'];
export function toolSection(group: string): AccessTab {
  if (['My classes', 'Students'].includes(group)) return 'class_access';
  if (['Teaching', 'Exams'].includes(group)) return 'teaching_access';
  if (group === 'Reports') return 'reports_access';
  return 'school_access';
}

export function TeacherAccessScreen({ token, section, navigate, onBack }: { token: string; section: AccessTab; navigate: (tab: AppTab) => void; onBack: () => void }) {
  const [selectedTool, setSelectedTool] = useState<string>();
  const [workspace, setWorkspace] = useState<TeacherWorkspace | null>();
  const [loading, setLoading] = useState(false); const [error, setError] = useState('');
  const load = useCallback(async () => {
    setLoading(true); setError('');
    try { setWorkspace((await api.get<Dashboard>('/dashboard', token)).data.teacher_workspace); }
    catch (e) { setError(e instanceof Error ? e.message : 'Unable to load tools.'); }
    finally { setLoading(false); }
  }, [token]);
  useEffect(() => { void load(); }, [load]);
  const definition = ACCESS_SECTIONS.find(item => item.tab === section)!;
  const tools = workspace?.tools.filter(tool => toolSection(tool.group) === section) ?? [];
  const open = (tool: TeacherWorkspace['tools'][number]) => {
    if (tool.id === 'students.index') { navigate('class_students'); return; }
    if (['exams.results'].includes(tool.id)) { navigate('teacher_results'); return; }
    if (tool.native) { navigate(tool.native as AppTab); return; }
    setSelectedTool(tool.id);
  };
  if (error && !workspace) return <ErrorScreen message={error} retry={load} onBack={onBack} />;
  if (selectedTool) return <NativeModuleScreen key={selectedTool} token={token} tool={selectedTool} onBack={() => setSelectedTool(undefined)} />;
  return <ScrollView contentContainerStyle={styles.content} refreshControl={<RefreshControl refreshing={loading} onRefresh={load} tintColor={colors.primary} />}>
    <Pressable accessibilityRole="button" onPress={onBack} style={styles.back}><Ionicons name="arrow-back" color={colors.primary} size={20} /><Text style={styles.backText}>Dashboard</Text></Pressable>
    <View style={styles.hero}><Ionicons name={definition.icon} color="white" size={32} /><Text style={styles.title}>{definition.title}</Text><Text style={styles.description}>{definition.description}</Text>{workspace && <Text style={styles.description}>{workspace.role_label} · {tools.length} available tools</Text>}</View>
    {section === 'teaching_access' && <Pressable accessibilityRole="button" onPress={() => navigate('teacher_schedule')} style={styles.card}><View style={styles.icon}><Ionicons name="calendar-outline" size={23} color="white" /></View><View style={{ flex: 1 }}><Text style={styles.label}>My weekly timetable</Text><Text style={styles.meta}>Your assigned lessons</Text></View><Ionicons name="chevron-forward" size={20} color={colors.primary} /></Pressable>}
    {!!error && <Text style={styles.error}>{error}</Text>}
    {tools.map(tool => <Pressable accessibilityRole="button" key={tool.id} onPress={() => void open(tool)} style={({ pressed }) => [styles.card, pressed && { opacity: 0.75 }]}>
      <View style={styles.icon}><Ionicons name={definition.icon} size={23} color="white" /></View><View style={{ flex: 1 }}><Text style={styles.label}>{tool.label}</Text><Text style={styles.meta}>{'Open tool'}</Text></View><Ionicons name="chevron-forward" size={20} color={colors.primary} />
    </Pressable>)}
    {!loading && !error && !tools.length && <Text style={styles.error}>No tools are assigned here yet. Your school controls access through your teaching assignments and permissions.</Text>}
    <Pressable onPress={() => void load()} style={styles.back}><Text style={styles.backText}>Refresh tools</Text></Pressable>
  </ScrollView>;
}
const styles = StyleSheet.create({
  content: { padding: 20, gap: 14, paddingBottom: 36 }, back: { flexDirection: 'row', alignItems: 'center', gap: 8, minHeight: 44, alignSelf: 'flex-start' }, backText: { color: colors.primary, fontWeight: '700' },
  hero: { backgroundColor: colors.primary, borderRadius: 24, padding: 24, gap: 9 }, title: { color: 'white', fontSize: 28, fontWeight: '800' }, description: { color: '#D8E2F2', fontSize: 13, lineHeight: 20 },
  card: { backgroundColor: 'white', borderRadius: 18, padding: 16, flexDirection: 'row', gap: 14, alignItems: 'center', borderWidth: 1, borderColor: '#DDE3EE', minHeight: 88 }, icon: { width: 48, height: 48, backgroundColor: colors.primary, borderRadius: 15, justifyContent: 'center', alignItems: 'center' }, label: { color: colors.primary, fontSize: 14, fontWeight: '700' }, meta: { color: colors.textMuted, fontSize: 11, lineHeight: 18, marginTop: 3 }, error: { color: colors.textMuted, lineHeight: 22, paddingVertical: 16 },
});
