import { Text } from '../../components/Typography';
import { Ionicons } from '@expo/vector-icons';
import { useEffect, useState } from 'react';
import { Alert, Image, Linking, Pressable, RefreshControl, ScrollView, StyleSheet, View } from 'react-native';
import { colors, radius, shadows } from '../../theme/index';
import { API_URL } from '../../api';
import type { Dashboard, User } from '../../types';

export type AppTab = 'home' | 'attendance' | 'homework' | 'results' | 'payments' | 'more' | 'notifications' | 'leave' | 'add_marks' | 'view_marks' | 'teacher_results' | 'add_homework';
type Lesson = Dashboard['next_lesson'];
type DashboardUser = User & { onSignOut?: () => Promise<void>; navigate?: (tab: AppTab) => void; nextLesson?: Lesson };
type Props = { data: Dashboard; user: DashboardUser; attendanceRate: number | null; navigate: (tab: AppTab) => void; refreshing?: boolean; onRefresh?: () => void };
type IconName = React.ComponentProps<typeof Ionicons>['name'];
const EMPTY_ANALYTICS = { attendance_labels: [] as string[], present_series: [] as number[], absent_series: [] as number[], performance_labels: [] as string[], performance_series: [] as number[], stats: {} as Record<string, number> };

function TopBar({ user }: { user: DashboardUser }) {
  return <View style={styles.topBar}><Image accessibilityLabel="Edlink" source={require('../../../assets/img/edlink-logo.png')} style={styles.topLogo} resizeMode="contain" /><View style={styles.topActions}><Pressable accessibilityRole="button" accessibilityLabel="Open notifications" onPress={() => user.navigate?.('notifications')} style={styles.bellButton}><Ionicons name="notifications-outline" size={21} color={colors.primary} /></Pressable><Pressable accessibilityRole="button" accessibilityLabel="Log out" onPress={() => void user.onSignOut?.()} style={styles.logoutButton}><Ionicons name="log-out-outline" size={21} color={colors.primary} /></Pressable></View></View>;
}

function Avatar({ name, uri, size = 54 }: { name: string; uri?: string | null; size?: number }) {
  const frame = { width: size, height: size, borderRadius: size / 2 };
  return <View style={[styles.avatar, frame]}>{uri ? <Image source={{ uri }} style={[styles.avatarImage, frame]} /> : <Text style={styles.avatarText}>{initials(name)}</Text>}</View>;
}

function GreetingCard({ user, title, subtitle, imageName, imageUri }: { user: User; title: string; subtitle: string; imageName?: string; imageUri?: string | null }) {
  const dashboardUser = user as DashboardUser; const [now, setNow] = useState(Date.now());
  useEffect(() => { const timer = setInterval(() => setNow(Date.now()), 30_000); return () => clearInterval(timer); }, []);
  const lesson = dashboardUser.role === 'parent' ? null : nextLessonCopy(dashboardUser.nextLesson, now);
  return <View style={[styles.greetingCard, shadows.card]}><View style={styles.greetingGlow} /><View style={styles.greetingCopy}><Text style={styles.greetingEyebrow}>{greeting().toUpperCase()}</Text><Text style={styles.greetingTitle}>{title}</Text><Text style={styles.greetingSubtitle}>{subtitle}</Text>{lesson && <View style={styles.lessonPill}><Ionicons name="time-outline" size={14} color={colors.secondary} /><Text style={styles.lessonText}>{lesson}</Text></View>}<View style={styles.datePill}><Ionicons name="calendar-outline" size={14} color={colors.primary} /><Text style={styles.dateText}>{formatDate(new Date().toISOString())}</Text></View></View><Avatar name={imageName ?? user.name} uri={imageUri ?? user.avatar_url} size={72} /></View>;
}

function StatGrid({ items }: { items: Array<{ icon: IconName; label: string; value: number | string; tone?: 'gold' | 'green' | 'blue' }> }) {
  return <View style={styles.statGrid}>{items.map(item => <View key={item.label} style={[styles.statCard, shadows.card]}><View style={[styles.statIcon, item.tone === 'green' && styles.statIconGreen, item.tone === 'blue' && styles.statIconBlue]}><Ionicons name={item.icon} size={19} color={colors.primary} /></View><Text style={styles.statValue}>{item.value}</Text><Text style={styles.statLabel}>{item.label}</Text></View>)}</View>;
}

function AttendanceChart({ data, rate, onPress }: { data: Dashboard; rate: number | null; onPress: () => void }) {
  const analytics = data.analytics ?? EMPTY_ANALYTICS;
  const labels = analytics.attendance_labels.length ? analytics.attendance_labels : ['M', 'T', 'W', 'T', 'F', 'S', 'S'];
  const present = analytics.present_series.length ? analytics.present_series : labels.map(() => 0);
  const absent = analytics.absent_series.length ? analytics.absent_series : labels.map(() => 0);
  const max = Math.max(1, ...present, ...absent);
  return <Pressable onPress={onPress} style={[styles.chartCard, shadows.card]}><View style={styles.cardHeading}><View><Text style={styles.cardTitle}>Attendance trend</Text><Text style={styles.cardCaption}>Last 7 days · live records</Text></View><Text style={styles.rateValue}>{rate == null ? '—' : `${rate}%`}</Text></View><View style={styles.legend}><Legend color={colors.status.present.text} label="Present" /><Legend color={colors.status.absent.text} label="Absent" /></View><View style={styles.barChart}>{labels.map((label, index) => <View key={`${label}-${index}`} style={styles.barColumn}><View style={styles.bars}><View style={[styles.bar, styles.presentBar, { height: Math.max(4, (present[index] ?? 0) / max * 76) }]} /><View style={[styles.bar, styles.absentBar, { height: Math.max(4, (absent[index] ?? 0) / max * 76) }]} /></View><Text style={styles.axisLabel}>{label.slice(0, 3)}</Text></View>)}</View></Pressable>;
}

function PerformanceChart({ data }: { data: Dashboard }) {
  const analytics = data.analytics ?? EMPTY_ANALYTICS;
  return <View style={[styles.chartCard, shadows.card]}><View style={styles.cardHeading}><View><Text style={styles.cardTitle}>Academic performance</Text><Text style={styles.cardCaption}>Published subject averages</Text></View><Ionicons name="analytics-outline" size={23} color={colors.secondaryDark} /></View>{analytics.performance_labels.length ? analytics.performance_labels.map((label, index) => { const value = Math.round(analytics.performance_series[index] ?? 0); return <View key={label} style={styles.performanceRow}><View style={styles.performanceLabelRow}><Text numberOfLines={1} style={styles.performanceLabel}>{label}</Text><Text style={styles.performanceValue}>{value}%</Text></View><View style={styles.performanceTrack}><View style={[styles.performanceFill, { width: `${Math.max(2, Math.min(100, value))}%` }]} /></View></View>; }) : <EmptyInline icon="bar-chart-outline" text="Performance appears after results are published." />}</View>;
}

function QuickActions({ role, navigate }: { role: User['role']; navigate: (tab: AppTab) => void }) {
  const actions: Array<{ label: string; icon: IconName; tab: AppTab }> = role === 'teacher'
    ? [{ label: 'Take attendance', icon: 'checkbox-outline', tab: 'attendance' }, { label: 'Add homework', icon: 'add-circle-outline', tab: 'add_homework' }, { label: 'Add marks', icon: 'create-outline', tab: 'add_marks' }, { label: 'View marks', icon: 'reader-outline', tab: 'view_marks' }, { label: 'Results', icon: 'trophy-outline', tab: 'teacher_results' }, { label: 'Request leave', icon: 'calendar-number-outline', tab: 'leave' }]
    : [{ label: 'Attendance', icon: 'calendar-outline', tab: 'attendance' }, { label: 'Results', icon: 'trophy-outline', tab: 'results' }, { label: 'Homework', icon: 'book-outline', tab: 'homework' }];
  return <View style={styles.quickRow}>{actions.map(action => <Pressable key={action.label} onPress={() => navigate(action.tab)} style={styles.quickAction}><View style={styles.quickIcon}><Ionicons name={action.icon} size={21} color={colors.primary} /></View><Text style={styles.quickLabel}>{action.label}</Text></Pressable>)}</View>;
}

function Schedule({ data }: { data: Dashboard }) {
  return <View style={[styles.listCard, shadows.card]}>{data.today_timetable.length ? data.today_timetable.slice(0, 4).map((slot, index) => <View key={slot.id} style={[styles.scheduleRow, index > 0 && styles.rowBorder]}><View style={styles.timeBlock}><Text style={styles.time}>{shortTime(slot.starts_at)}</Text><Text style={styles.timeEnd}>{shortTime(slot.ends_at)}</Text></View><View style={styles.grow}><Text style={styles.rowTitle}>{slot.subject ?? slot.label ?? 'Lesson'}</Text><Text style={styles.rowMeta}>{slot.class_name ?? 'Scheduled class'}</Text></View><Ionicons name="chevron-forward" size={18} color={colors.textMuted} /></View>) : <EmptyInline icon="calendar-clear-outline" text="No lessons scheduled today." />}</View>;
}

function HomeworkList({ data, navigate }: { data: Dashboard; navigate: (tab: AppTab) => void }) {
  return <View style={[styles.listCard, shadows.card]}>{data.homework.length ? data.homework.slice(0, 3).map((item, index) => <View key={item.id} style={[styles.homeworkRow, index > 0 && styles.rowBorder]}><View style={styles.homeworkIcon}><Ionicons name="document-text-outline" size={19} color={colors.primary} /></View><View style={styles.grow}><Text style={styles.rowTitle}>{item.title}</Text><Text style={styles.rowMeta}>{item.subject?.name ?? 'General'}{item.due_at ? ` · ${formatDate(item.due_at)}` : ''}</Text></View></View>) : <EmptyInline icon="checkmark-done-circle-outline" text="No homework is due right now." />}<Pressable onPress={() => navigate('homework')} style={styles.cardButton}><Text style={styles.cardButtonText}>View all homework</Text><Ionicons name="chevron-forward-circle" size={19} color={colors.primary} /></Pressable></View>;
}

function Events({ data }: { data: Dashboard }) {
  return <View style={styles.eventRow}>{data.events.length ? data.events.slice(0, 3).map(event => <View key={event.id} style={styles.eventCard}><Text style={styles.eventDay}>{eventDate(event.event_date, 'day')}</Text><Text style={styles.eventMonth}>{eventDate(event.event_date, 'month')}</Text><Text numberOfLines={2} style={styles.eventTitle}>{event.title ?? event.name ?? 'School event'}</Text></View>) : <View style={[styles.listCard, styles.fullWidth]}><EmptyInline icon="sparkles-outline" text="No upcoming school events." /></View>}</View>;
}

function SectionHeader({ title, subtitle }: { title: string; subtitle?: string }) { return <View style={styles.sectionHeader}><Text style={styles.sectionTitle}>{title}</Text>{subtitle ? <Text style={styles.sectionSubtitle}>{subtitle}</Text> : null}</View>; }
function Legend({ color, label }: { color: string; label: string }) { return <View style={styles.legendItem}><View style={[styles.legendDot, { backgroundColor: color }]} /><Text style={styles.legendText}>{label}</Text></View>; }
function EmptyInline({ icon, text }: { icon: IconName; text: string }) { return <View style={styles.emptyInline}><Ionicons name={icon} size={22} color={colors.textMuted} /><Text style={styles.emptyText}>{text}</Text></View>; }

export function TeacherDashboardScreen({ data, user, attendanceRate, navigate, refreshing = false, onRefresh }: Props) {
  const stats = data.analytics?.stats ?? {};
  const workspace = data.teacher_workspace;
  const openTool = async (tool: NonNullable<Dashboard['teacher_workspace']>['tools'][number]) => {
    if (tool.native) { navigate(tool.native as AppTab); return; }
    try { await Linking.openURL(`${API_URL.replace(/\/api\/v1$/, '')}${tool.path}`); }
    catch { Alert.alert('Unable to open tool', 'Please try again.'); }
  };
  return <DashboardScroll refreshing={refreshing} onRefresh={onRefresh}>
    <TopBar user={user} />
    <GreetingCard user={user} title={`Welcome, ${firstName(user.name)}`} subtitle={`${workspace?.role_label ?? 'Teacher'}${workspace?.term ? ` · ${workspace.term}` : ''} · ${user.school.name}`} />
    {workspace && <View style={[styles.listCard, { marginTop: 14, paddingVertical: 14 }]}>
      <Text style={styles.cardTitle}>{workspace.class_teacher_classes.length ? 'My class responsibility' : 'My teaching assignments'}</Text>
      {!!workspace.class_teacher_classes.length && <Text style={styles.bodyCopy}>{workspace.class_teacher_classes.map(item => item.name).join(' · ')} — daily register and class learner tools</Text>}
      <Text style={styles.bodyCopy}>{workspace.subject_assignments.length ? workspace.subject_assignments.map(item => `${item.subject_name} · ${item.class_name}`).join('\n') : 'No subject assignments yet. Your school administrator can assign your teaching subjects.'}</Text>
    </View>}
    <StatGrid items={[{ icon: 'people-outline', label: 'Learners', value: stats.learners ?? 0, tone: 'blue' }, { icon: 'easel-outline', label: 'Classes', value: stats.classes ?? 0 }, { icon: 'book-outline', label: 'Subjects', value: stats.subjects ?? 0, tone: 'green' }]} />
    <StatGrid items={[{ icon: 'time-outline', label: 'Lessons today', value: stats.lessons_today ?? 0 }, { icon: 'checkbox-outline', label: 'Recorded today', value: stats.attendance_today ?? 0, tone: 'green' }, { icon: 'document-text-outline', label: 'Pending marks', value: stats.pending_marks ?? 0, tone: 'blue' }]} />
    <SectionHeader title="Teacher workspace" subtitle="Tools available for your assignments and school permissions" />
    {workspace ? [...new Set(workspace.tools.map(tool => tool.group))].map(group => <View key={group} style={{ marginBottom: 16 }}>
      <Text style={[styles.cardTitle, { marginBottom: 9 }]}>{group}</Text>
      <View style={styles.quickRow}>{workspace.tools.filter(tool => tool.group === group).map(tool => <Pressable accessibilityRole="button" key={tool.id} onPress={() => void openTool(tool)} style={styles.quickAction}>
        <View style={styles.quickIcon}><Ionicons name={tool.native ? 'apps-outline' : 'open-outline'} size={21} color={colors.primary} /></View>
        <Text style={styles.quickLabel}>{tool.label}</Text>
        {!tool.native && <Text style={styles.webLabel}>Website</Text>}
      </Pressable>)}</View>
    </View>) : <QuickActions role="teacher" navigate={navigate} />}
    {workspace && <Text style={styles.bodyCopy}>Website tools open in your browser and may ask you to sign in.</Text>}
    <SectionHeader title="My attendance activity" subtitle="Registers you recorded over the last seven days" /><AttendanceChart data={data} rate={attendanceRate} onPress={() => navigate('attendance')} />
    <SectionHeader title="Subject insights" /><PerformanceChart data={data} />
    <SectionHeader title="Today’s timetable" /><Schedule data={data} />
    <SectionHeader title="Homework activity" /><HomeworkList data={data} navigate={navigate} />
    <SectionHeader title="Upcoming events" /><Events data={data} />
  </DashboardScroll>;
}

export function StudentDashboardScreen({ data, user, attendanceRate, navigate, refreshing = false, onRefresh }: Props) {
  const stats = data.analytics?.stats ?? {}; const student = data.student;
  return <DashboardScroll refreshing={refreshing} onRefresh={onRefresh}><TopBar user={user} /><GreetingCard user={user} title={`Keep going, ${firstName(student?.name ?? user.name)}!`} subtitle={`${student?.class ?? 'Student'}${student?.stream ? ` · ${student.stream}` : ''} · Here’s your learning snapshot.`} imageName={student?.name} imageUri={student?.photo_url} /><StatGrid items={[{ icon: 'calendar-outline', label: 'Attendance', value: attendanceRate == null ? '—' : `${attendanceRate}%`, tone: 'green' }, { icon: 'book-outline', label: 'Homework', value: stats.homework ?? data.homework.length }, { icon: 'trophy-outline', label: 'Results', value: stats.published_results ?? 0, tone: 'blue' }]} /><SectionHeader title="Quick access" /><QuickActions role="student" navigate={navigate} /><SectionHeader title="My attendance" /><AttendanceChart data={data} rate={attendanceRate} onPress={() => navigate('attendance')} /><SectionHeader title="My performance" /><PerformanceChart data={data} /><SectionHeader title="Today’s lessons" /><Schedule data={data} /><SectionHeader title="Upcoming homework" /><HomeworkList data={data} navigate={navigate} /><SectionHeader title="School calendar" /><Events data={data} /></DashboardScroll>;
}

export function ParentDashboardScreen({ data, user, attendanceRate, navigate, refreshing = false, onRefresh }: Props) {
  const stats = data.analytics?.stats ?? {}; const child = data.student;
  return <DashboardScroll refreshing={refreshing} onRefresh={onRefresh}><TopBar user={user} /><GreetingCard user={user} title={`Hello, ${firstName(user.name)}`} subtitle={`A live overview of ${child?.name ?? 'your learner'}’s school progress.`} imageName={child?.name} imageUri={child?.photo_url} /><View style={[styles.learnerStrip, shadows.card]}><Avatar name={child?.name ?? 'Student'} uri={child?.photo_url} size={52} /><View style={styles.grow}><Text style={styles.learnerName}>{child?.name ?? 'Your learner'}</Text><Text style={styles.rowMeta}>{child?.class ?? 'Class not assigned'}{child?.stream ? ` · ${child.stream}` : ''}</Text></View><View style={styles.livePill}><View style={styles.liveDot} /><Text style={styles.liveText}>Live</Text></View></View><StatGrid items={[{ icon: 'calendar-outline', label: 'Attendance', value: attendanceRate == null ? '—' : `${attendanceRate}%`, tone: 'green' }, { icon: 'document-text-outline', label: 'Homework', value: stats.homework ?? data.homework.length }, { icon: 'school-outline', label: 'Results', value: stats.published_results ?? 0, tone: 'blue' }]} /><SectionHeader title="Quick access" /><QuickActions role="parent" navigate={navigate} /><SectionHeader title="Attendance overview" subtitle="Updated from school registers" /><AttendanceChart data={data} rate={attendanceRate} onPress={() => navigate('attendance')} /><SectionHeader title="Academic progress" /><PerformanceChart data={data} /><SectionHeader title="Today at school" /><Schedule data={data} /><SectionHeader title="Homework due" /><HomeworkList data={data} navigate={navigate} /><SectionHeader title="Upcoming events" /><Events data={data} /></DashboardScroll>;
}

function DashboardScroll({ children, refreshing, onRefresh }: { children: React.ReactNode; refreshing: boolean; onRefresh?: () => void }) {
  return <ScrollView style={styles.screen} contentContainerStyle={styles.content} showsVerticalScrollIndicator={false} refreshControl={onRefresh ? <RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.primary} colors={[colors.primary]} /> : undefined}>{children}</ScrollView>;
}

function greeting() { const hour = new Date().getHours(); return hour < 12 ? 'Good morning' : hour < 18 ? 'Good afternoon' : 'Good evening'; }
function firstName(name: string) { return name.trim().split(/\s+/)[0] || 'there'; }
function initials(name: string) { return name.trim().split(/\s+/).slice(0, 2).map(word => word[0]).join('').toUpperCase(); }
function shortTime(value: string) { return value?.slice(0, 5) ?? '—'; }
function formatDate(value: string) { const date = new Date(value.length === 10 ? `${value}T12:00:00` : value); return Number.isNaN(date.valueOf()) ? value : date.toLocaleDateString(undefined, { weekday: 'short', day: 'numeric', month: 'short' }); }
function eventDate(value: string, part: 'day' | 'month') { const date = new Date(value.length === 10 ? `${value}T12:00:00` : value); if (Number.isNaN(date.valueOf())) return '—'; return part === 'day' ? `${date.getDate()}` : date.toLocaleDateString(undefined, { month: 'short' }).toUpperCase(); }
function nextLessonCopy(lesson: Lesson | undefined, now: number) { if (!lesson) return 'No more lessons scheduled today'; const day = new Date(); const [startHour, startMinute] = lesson.starts_at.slice(0, 5).split(':').map(Number); const [endHour, endMinute] = lesson.ends_at.slice(0, 5).split(':').map(Number); const start = new Date(day.getFullYear(), day.getMonth(), day.getDate(), startHour, startMinute).getTime(); const end = new Date(day.getFullYear(), day.getMonth(), day.getDate(), endHour, endMinute).getTime(); const name = lesson.subject ?? lesson.label ?? 'Next lesson'; if (now >= start && now < end) return `${name} is going on · ends ${shortTime(lesson.ends_at)}`; const minutes = Math.max(0, Math.ceil((start - now) / 60_000)); const countdown = minutes >= 60 ? `${Math.floor(minutes / 60)}h ${minutes % 60}m` : `${minutes} min`; return `${name} starts in ${countdown}`; }

const styles = StyleSheet.create({
  bodyCopy: { color: colors.textMuted, fontSize: 12, lineHeight: 21, marginTop: 7 }, webLabel: { color: colors.textMuted, fontSize: 9, marginTop: 3, marginBottom: 8 },
  screen: { flex: 1, backgroundColor: colors.background }, content: { paddingHorizontal: 18, paddingBottom: 34 },
  topBar: { minHeight: 72, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' }, topLogo: { width: 142, height: 62, marginLeft: 10 }, topActions: { flexDirection: 'row', alignItems: 'center', gap: 8 }, bellButton: { width: 42, height: 42, borderRadius: 21, backgroundColor: colors.secondary, borderWidth: 1.5, borderColor: colors.primary, alignItems: 'center', justifyContent: 'center' }, logoutButton: { width: 42, height: 42, borderRadius: 21, backgroundColor: colors.secondary, borderWidth: 1.5, borderColor: colors.primary, alignItems: 'center', justifyContent: 'center' },
  avatar: { backgroundColor: colors.secondary, alignItems: 'center', justifyContent: 'center', overflow: 'hidden', borderWidth: 2, borderColor: colors.surface }, avatarImage: { resizeMode: 'cover' }, avatarText: { color: colors.primary, fontWeight: '900', fontSize: 15 },
  greetingCard: { minHeight: 190, borderRadius: radius.lg, backgroundColor: colors.primaryCard, padding: 22, flexDirection: 'row', alignItems: 'center', overflow: 'hidden' }, greetingGlow: { position: 'absolute', width: 210, height: 210, borderRadius: 105, backgroundColor: colors.primary, right: -65, top: -85, opacity: 0.7 }, greetingCopy: { flex: 1, paddingRight: 12 }, greetingEyebrow: { color: colors.secondary, fontSize: 10, fontWeight: '900', letterSpacing: 1.3 }, greetingTitle: { color: colors.textLight, fontSize: 23, lineHeight: 29, fontWeight: '900', marginTop: 8 }, greetingSubtitle: { color: '#C9D1E5', fontSize: 12, lineHeight: 18, marginTop: 6 },
  datePill: { alignSelf: 'flex-start', flexDirection: 'row', alignItems: 'center', gap: 6, backgroundColor: colors.secondary, borderRadius: radius.full, paddingHorizontal: 10, paddingVertical: 6, marginTop: 13 }, dateText: { color: colors.primary, fontSize: 10, fontWeight: '800' },
  lessonPill: { alignSelf: 'flex-start', flexDirection: 'row', alignItems: 'center', gap: 6, marginTop: 10 }, lessonText: { color: colors.secondary, fontSize: 10, lineHeight: 14, fontWeight: '800', flexShrink: 1 },
  statGrid: { flexDirection: 'row', gap: 9, marginTop: 14 }, statCard: { flex: 1, minHeight: 116, borderRadius: radius.md, backgroundColor: colors.surface, padding: 12, borderWidth: 1, borderColor: colors.border }, statIcon: { width: 34, height: 34, borderRadius: 11, backgroundColor: '#FFF2CF', alignItems: 'center', justifyContent: 'center' }, statIconGreen: { backgroundColor: colors.status.present.bg }, statIconBlue: { backgroundColor: colors.status.excused.bg }, statValue: { color: colors.textDark, fontSize: 22, fontWeight: '900', marginTop: 10 }, statLabel: { color: colors.textMuted, fontSize: 10, lineHeight: 14, marginTop: 2 },
  sectionHeader: { marginTop: 25, marginBottom: 11 }, sectionTitle: { color: colors.textDark, fontSize: 19, fontWeight: '900' }, sectionSubtitle: { color: colors.textMuted, fontSize: 11, marginTop: 3 },
  quickRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 9 }, quickAction: { width: '31%', flexGrow: 1, minHeight: 91, borderRadius: radius.md, backgroundColor: colors.surface, borderWidth: 1, borderColor: colors.border, alignItems: 'center', justifyContent: 'center', paddingHorizontal: 5 }, quickIcon: { width: 40, height: 40, borderRadius: 13, backgroundColor: colors.secondary, alignItems: 'center', justifyContent: 'center' }, quickLabel: { color: colors.textDark, fontSize: 11, fontWeight: '800', marginTop: 7, textAlign: 'center' },
  chartCard: { borderRadius: radius.md, backgroundColor: colors.surface, padding: 16, borderWidth: 1, borderColor: colors.border }, cardHeading: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' }, cardTitle: { color: colors.textDark, fontSize: 16, fontWeight: '900' }, cardCaption: { color: colors.textMuted, fontSize: 10, marginTop: 3 }, rateValue: { color: colors.primary, fontSize: 25, fontWeight: '900' },
  legend: { flexDirection: 'row', gap: 15, marginTop: 14 }, legendItem: { flexDirection: 'row', alignItems: 'center', gap: 5 }, legendDot: { width: 7, height: 7, borderRadius: 4 }, legendText: { color: colors.textMuted, fontSize: 10 }, barChart: { height: 112, flexDirection: 'row', alignItems: 'flex-end', justifyContent: 'space-between', marginTop: 6 }, barColumn: { flex: 1, alignItems: 'center' }, bars: { height: 82, flexDirection: 'row', alignItems: 'flex-end', gap: 3 }, bar: { width: 8, borderTopLeftRadius: 4, borderTopRightRadius: 4 }, presentBar: { backgroundColor: colors.status.present.text }, absentBar: { backgroundColor: colors.status.absent.text }, axisLabel: { color: colors.textMuted, fontSize: 9, marginTop: 7 },
  performanceRow: { marginTop: 14 }, performanceLabelRow: { flexDirection: 'row', justifyContent: 'space-between', gap: 12 }, performanceLabel: { flex: 1, color: colors.textDark, fontSize: 12, fontWeight: '700' }, performanceValue: { color: colors.primary, fontSize: 11, fontWeight: '900' }, performanceTrack: { height: 8, borderRadius: 4, backgroundColor: '#E8ECF4', marginTop: 6, overflow: 'hidden' }, performanceFill: { height: '100%', borderRadius: 4, backgroundColor: colors.secondary },
  listCard: { borderRadius: radius.md, backgroundColor: colors.surface, paddingHorizontal: 15, borderWidth: 1, borderColor: colors.border }, scheduleRow: { minHeight: 72, flexDirection: 'row', alignItems: 'center', gap: 12 }, rowBorder: { borderTopWidth: StyleSheet.hairlineWidth, borderTopColor: colors.border }, timeBlock: { width: 48 }, time: { color: colors.primary, fontSize: 13, fontWeight: '900' }, timeEnd: { color: colors.textMuted, fontSize: 9, marginTop: 2 }, grow: { flex: 1 }, rowTitle: { color: colors.textDark, fontSize: 13, fontWeight: '800' }, rowMeta: { color: colors.textMuted, fontSize: 10, marginTop: 3 },
  homeworkRow: { minHeight: 67, flexDirection: 'row', alignItems: 'center', gap: 11 }, homeworkIcon: { width: 38, height: 38, borderRadius: 11, backgroundColor: '#FFF2CF', alignItems: 'center', justifyContent: 'center' }, cardButton: { minHeight: 48, borderTopWidth: StyleSheet.hairlineWidth, borderTopColor: colors.border, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 7 }, cardButtonText: { color: colors.primary, fontSize: 11, fontWeight: '900' },
  eventRow: { flexDirection: 'row', gap: 9 }, eventCard: { flex: 1, minHeight: 125, borderRadius: radius.md, backgroundColor: colors.primaryCard, padding: 13 }, eventDay: { color: colors.secondary, fontSize: 25, fontWeight: '900' }, eventMonth: { color: colors.secondary, fontSize: 9, fontWeight: '800' }, eventTitle: { color: colors.textLight, fontSize: 11, fontWeight: '700', lineHeight: 16, marginTop: 12 }, fullWidth: { flex: 1 }, emptyInline: { minHeight: 86, alignItems: 'center', justifyContent: 'center', gap: 8 }, emptyText: { color: colors.textMuted, fontSize: 11, textAlign: 'center' },
  learnerStrip: { marginTop: 13, minHeight: 76, flexDirection: 'row', alignItems: 'center', gap: 12, borderRadius: radius.md, backgroundColor: colors.surface, padding: 12, borderWidth: 1, borderColor: colors.border }, learnerName: { color: colors.textDark, fontSize: 15, fontWeight: '900' }, livePill: { flexDirection: 'row', alignItems: 'center', gap: 5, backgroundColor: colors.status.present.bg, borderRadius: radius.full, paddingHorizontal: 9, paddingVertical: 6 }, liveDot: { width: 6, height: 6, borderRadius: 3, backgroundColor: colors.status.present.text }, liveText: { color: colors.status.present.text, fontSize: 9, fontWeight: '900' },
});
