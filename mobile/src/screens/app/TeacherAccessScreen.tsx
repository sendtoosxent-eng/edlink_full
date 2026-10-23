import { useThemedStyles } from '../../theme/ThemeProvider';
import { ErrorScreen } from '../../components/ErrorScreen';
import { NativeModuleScreen } from './NativeModuleScreen';
import { useCallback, useEffect, useState } from 'react';
import { RefreshControl, ScrollView, StyleSheet, View } from 'react-native';
import { TeacherHero, TeacherToolCard, TeacherButton, useTeacherBack, teacherScreenStyles } from '../../components/TeacherScreenUI';
import type { StatArtworkName } from '../../components/StatArtwork';
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
  const styles = useThemedStyles(baseStyles);
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
    if (tool.id === 'attendance.index') { navigate('daily_attendance'); return; }
    if (tool.id === 'attendance.subject') { navigate('subject_attendance'); return; }
    if (tool.id === 'attendance.reports') { navigate('attendance_reports'); return; }
    if (tool.id === 'students.index') { navigate('class_students'); return; }
    if (['exams.results'].includes(tool.id)) { navigate('teacher_results'); return; }
    if (tool.native) { navigate(tool.native as AppTab); return; }
    setSelectedTool(tool.id);
  };
  useTeacherBack(() => { if (selectedTool) setSelectedTool(undefined); else onBack(); });
  const artwork: StatArtworkName = section === 'class_access' ? 'learners' : section === 'teaching_access' ? 'classroom' : section === 'reports_access' ? 'reports' : 'school';
  if (error && !workspace) return <ErrorScreen message={error} retry={load} onBack={onBack} />;
  if (selectedTool) return <NativeModuleScreen key={selectedTool} token={token} tool={selectedTool} onBack={() => setSelectedTool(undefined)} />;
  return <ScrollView contentContainerStyle={teacherScreenStyles.content} keyboardShouldPersistTaps="handled" refreshControl={<RefreshControl refreshing={loading} onRefresh={load} tintColor={colors.primary} />}>
    <TeacherHero title={definition.title} subtitle={definition.description} artwork={artwork} detail={workspace ? `${workspace.role_label} · ${tools.length} available tools` : undefined} />
    {section === 'class_access' && !!workspace?.class_teacher_classes.length && <View style={styles.responsibilities}><Text style={styles.sectionTitle}>Assigned classes</Text><View style={styles.chips}>{workspace.class_teacher_classes.map(item => <View key={item.id} style={styles.chip}><Text style={styles.chipText}>{item.name}</Text></View>)}</View></View>}
    {section === 'teaching_access' && <TeacherToolCard title="My weekly timetable" description="Your assigned lessons, organized by day" artwork="lessons" onPress={() => navigate('teacher_schedule')} />}
    {!!error && <Text accessibilityLiveRegion="polite" style={styles.error}>{error}</Text>}
    {tools.map(tool => <TeacherToolCard key={tool.id} title={tool.label} description={toolDescription(tool.id)} artwork={toolArtwork(tool.id, artwork)} onPress={() => open(tool)} />)}
    {!loading && !error && !tools.length && <View style={teacherScreenStyles.panel}><Text style={styles.error}>No tools are assigned here yet. Your school administrator can update your access.</Text></View>}
    <TeacherButton label={loading ? 'Refreshing…' : 'Refresh tools'} secondary disabled={loading} onPress={() => void load()} />
  </ScrollView>;
}
function toolArtwork(id: string, fallback: StatArtworkName): StatArtworkName {
  if (id.startsWith('attendance')) return 'attendance';
  if (id.startsWith('exams') || id.startsWith('grading')) return 'marks';
  if (id.includes('student') || id.includes('class')) return 'learners';
  if (id.includes('timetable') || id.includes('event') || id.includes('leave')) return 'lessons';
  if (id.includes('report')) return 'reports';
  if (id.includes('subject') || id.includes('homework')) return 'subjects';
  return fallback;
}
function toolDescription(id: string): string {
  if (id === 'students.index') return 'Find learners and view their school profiles';
  if (id === 'attendance.index') return 'Complete your daily class registers';
  if (id === 'attendance.subject') return 'Record attendance for your subject lessons';
  if (id === 'attendance.reports') return 'Review attendance and export your reports';
  if (id === 'exams.marks') return 'Enter, review and submit learner scores';
  if (id === 'exams.results') return 'Published results and learner report cards';
  if (id === 'homework.index') return 'Set learning tasks and review submissions';
  if (id === 'leaves.index') return 'View and manage your leave requests';
  return 'Manage records within your assigned school access';
}
const baseStyles = StyleSheet.create({
  responsibilities: { padding: 16, borderRadius: 18, backgroundColor: colors.surface, borderWidth: 1, borderColor: colors.border, gap: 12 }, sectionTitle: { color: colors.primary, fontSize: 13, fontWeight: '600' }, chips: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 }, chip: { paddingHorizontal: 12, paddingVertical: 7, borderRadius: 999, backgroundColor: colors.primary }, chipText: { color: colors.secondary, fontSize: 11, fontWeight: '600' }, error: { color: colors.textMuted, fontSize: 12, lineHeight: 20 },
});

const styles = baseStyles;
