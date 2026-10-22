import { EdlinkAlert } from '../../components/EdlinkAlert';
import { Text } from '../../components/Typography';
import { Ionicons } from '@expo/vector-icons';
import { useEffect, useState } from 'react';
import { Image, Pressable, RefreshControl, ScrollView, StyleSheet, View, useWindowDimensions } from 'react-native';
import { colors, radius, shadows } from '../../theme/index';
import { ACCESS_SECTIONS, toolSection } from './TeacherAccessScreen';
import type { Dashboard, User } from '../../types';

export type AppTab = 'teacher_communications' | 'daily_attendance' | 'subject_attendance' | 'attendance_reports' | 'student_schedule' | 'student_calendar' | 'teacher_schedule' | 'class_students' | 'class_access' | 'teaching_access' | 'reports_access' | 'school_access' | 'home' | 'attendance' | 'homework' | 'results' | 'payments' | 'more' | 'notifications' | 'leave' | 'add_marks' | 'view_marks' | 'teacher_results' | 'add_homework';

type Lesson = Dashboard['next_lesson'];
type DashboardUser = User & { onSignOut?: () => Promise<void>; navigate?: (tab: AppTab) => void; nextLesson?: Lesson; unreadNotifications?: number };
type Props = { data: Dashboard; user: DashboardUser; attendanceRate: number | null; navigate: (tab: AppTab) => void; refreshing?: boolean; onRefresh?: () => void; openRegister?: (item: NonNullable<NonNullable<Dashboard['teacher_workspace']>['pending_attendance']>[number]) => void };
type IconName = React.ComponentProps<typeof Ionicons>['name'];
type Tone = 'gold' | 'green' | 'blue';

const EMPTY_ANALYTICS = { attendance_labels: [] as string[], present_series: [] as number[], absent_series: [] as number[], performance_labels: [] as string[], performance_series: [] as number[], stats: {} as Record<string, number> };

const MAX_WIDTH = 720;
const GUTTER = 18;
const GAP = 10;

/* ------------------------------------------------------------------ */
/* Shared hooks                                                        */
/* ------------------------------------------------------------------ */

function useNow(interval = 30_000) {
  const [now, setNow] = useState(Date.now());
  useEffect(() => {
    const timer = setInterval(() => setNow(Date.now()), interval);
    return () => clearInterval(timer);
  }, [interval]);
  return now;
}

/** Usable content width, capped so tablets don't stretch the layout. */
function useContentWidth() {
  const { width } = useWindowDimensions();
  return Math.min(width, MAX_WIDTH) - GUTTER * 2;
}

/* ------------------------------------------------------------------ */
/* Top bar + identity                                                  */
/* ------------------------------------------------------------------ */

function TopBar({ user }: { user: DashboardUser }) {
  const confirmLogout = () =>
    EdlinkAlert.alert('Log out of Edlink?', 'You will need to sign in again to access your school account.', [
      { text: 'Cancel', style: 'cancel' },
      { text: 'Log out', style: 'destructive', onPress: () => user.onSignOut?.() },
    ]);

  return (
    <View style={styles.topBar}>
      <Image accessibilityLabel="Edlink" source={require('../../../assets/img/edlink-logo.png')} style={styles.topLogo} resizeMode="contain" />
      <View style={styles.topActions}>
        <Pressable accessibilityRole="button" accessibilityLabel="Open notifications" onPress={() => user.navigate?.('notifications')} style={({ pressed }) => [styles.iconButton, pressed && styles.pressed]}>
          <Ionicons name="notifications-outline" size={21} color={colors.primary} />
          {!!user.unreadNotifications && (
            <View style={styles.notificationBadge}>
              <Text style={styles.notificationBadgeText}>{user.unreadNotifications > 9 ? '9+' : user.unreadNotifications}</Text>
            </View>
          )}
        </Pressable>
        <Pressable accessibilityRole="button" accessibilityLabel="Log out" onPress={confirmLogout} style={({ pressed }) => [styles.iconButton, pressed && styles.pressed]}>
          <Ionicons name="log-out-outline" size={21} color={colors.primary} />
        </Pressable>
      </View>
    </View>
  );
}

function Avatar({ name, uri, size = 54 }: { name: string; uri?: string | null; size?: number }) {
  const frame = { width: size, height: size, borderRadius: size / 2 };
  return (
    <View style={[styles.avatar, frame]}>
      {uri ? <Image source={{ uri }} style={[styles.avatarImage, frame]} /> : <Text style={styles.avatarText}>{initials(name)}</Text>}
    </View>
  );
}

/* Welcome card intentionally preserved exactly from Edlink Premium V1. */
function GreetingCard({ user, title, subtitle, imageName, imageUri }: { user: User; title: string; subtitle: string; imageName?: string; imageUri?: string | null }) {
  const dashboardUser = user as DashboardUser;
  const now = useNow();
  const lesson = dashboardUser.role !== 'student' ? null : nextLessonCopy(dashboardUser.nextLesson, now);
  return (
    <View style={[styles.greetingCard, shadows.card]}>
      <View style={styles.greetingGlow} />
      <View style={styles.greetingCopy}>
        <Text style={styles.greetingEyebrow}>{greeting().toUpperCase()}</Text>
        <Text style={styles.greetingTitle}>{title}</Text>
        <Text style={styles.greetingSubtitle}>{subtitle}</Text>
        {lesson && (
          <View style={styles.lessonPill}>
            <Ionicons name="time-outline" size={14} color={colors.secondary} />
            <Text style={styles.lessonText}>{lesson}</Text>
          </View>
        )}
        <View style={styles.datePill}>
          <Ionicons name="calendar-outline" size={14} color={colors.primary} />
          <Text style={styles.dateText}>{formatDate(new Date().toISOString())}</Text>
        </View>
      </View>
      <Avatar name={imageName ?? user.name} uri={imageUri ?? user.avatar_url} size={60} />
    </View>
  );
}

/* ------------------------------------------------------------------ */
/* Building blocks                                                     */
/* ------------------------------------------------------------------ */

function SectionHeader({ title, subtitle, actionLabel, onAction }: { title: string; subtitle?: string; actionLabel?: string; onAction?: () => void }) {
  return (
    <View style={styles.sectionHeader}>
      <View style={styles.sectionHeaderText}>
        <View style={styles.sectionTitleRow}>
          <View style={styles.sectionMarker} />
          <Text style={styles.sectionTitle}>{title}</Text>
        </View>
        {subtitle ? <Text style={styles.sectionSubtitle}>{subtitle}</Text> : null}
      </View>
      {actionLabel && onAction ? (
        <Pressable accessibilityRole="button" onPress={onAction} hitSlop={8} style={({ pressed }) => [styles.sectionAction, pressed && styles.pressed]}>
          <Text style={styles.sectionActionText}>{actionLabel}</Text>
          <Ionicons name="chevron-forward" size={14} color={colors.primary} />
        </Pressable>
      ) : null}
    </View>
  );
}

function toneColor(tone?: Tone) {
  return tone === 'green' ? colors.status.present.text : tone === 'blue' ? colors.primary : colors.secondaryDark;
}

function toneTint(tone?: Tone) {
  return tone === 'green' ? colors.status.present.bg : tone === 'blue' ? '#E8EDF8' : '#FFF4D9';
}

function StatGrid({ items }: { items: Array<{ icon: IconName; label: string; value: number | string; tone?: Tone }> }) {
  const { width } = useWindowDimensions();
  const compact = width < 375;
  return (
    <View style={styles.statGrid}>
      {items.map(item => (
        <View key={item.label} style={[styles.statCard, shadows.card, compact && styles.statCardCompact]}>
          <View style={[styles.statIcon, { backgroundColor: toneTint(item.tone) }]}>
            <Ionicons name={item.icon} size={18} color={toneColor(item.tone)} />
          </View>
          <Text adjustsFontSizeToFit minimumFontScale={0.7} numberOfLines={1} style={styles.statValue}>{item.value}</Text>
          <Text numberOfLines={2} style={styles.statLabel}>{item.label}</Text>
        </View>
      ))}
    </View>
  );
}

/** Responsive grid of tappable tiles: 2 columns on phones, 3 on wide screens. */
function ActionGrid({ items }: { items: Array<{ key: string; title: string; description: string; icon: IconName; onPress: () => void; badge?: string }> }) {
  const contentWidth = useContentWidth();
  const columns = contentWidth >= 560 ? 3 : 2;
  const tileWidth = Math.floor((contentWidth - GAP * (columns - 1)) / columns);
  return (
    <View style={styles.actionGrid}>
      {items.map(item => (
        <Pressable key={item.key} accessibilityRole="button" accessibilityLabel={item.title} onPress={item.onPress} style={({ pressed }) => [styles.actionTile, shadows.card, { width: tileWidth }, pressed && styles.pressed]}>
          <View style={styles.actionTileTop}>
            <View style={styles.actionIcon}>
              <Ionicons name={item.icon} size={21} color={colors.primary} />
            </View>
            <Ionicons name="arrow-forward" size={16} color={colors.textMuted} />
          </View>
          <Text numberOfLines={1} style={styles.actionTitle}>{item.title}</Text>
          <Text numberOfLines={2} style={styles.actionSubtitle}>{item.description}</Text>
          {item.badge ? (
            <View style={styles.actionBadge}>
              <Text style={styles.actionBadgeText}>{item.badge}</Text>
            </View>
          ) : null}
        </Pressable>
      ))}
    </View>
  );
}

function QuickActions({ role, navigate }: { role: User['role']; navigate: (tab: AppTab) => void }) {
  const actions: Array<{ label: string; icon: IconName; tab: AppTab }> = role === 'teacher'
    ? [
        { label: 'Take attendance', icon: 'checkbox-outline', tab: 'attendance' },
        { label: 'Add homework', icon: 'add-circle-outline', tab: 'add_homework' },
        { label: 'Add marks', icon: 'create-outline', tab: 'add_marks' },
        { label: 'View marks', icon: 'reader-outline', tab: 'view_marks' },
        { label: 'Results', icon: 'trophy-outline', tab: 'teacher_results' },
        { label: 'Request leave', icon: 'calendar-number-outline', tab: 'leave' },
      ]
    : [
        { label: 'Attendance', icon: 'calendar-outline', tab: 'attendance' },
        { label: 'Results', icon: 'trophy-outline', tab: 'results' },
        { label: 'Homework', icon: 'book-outline', tab: 'homework' },
        { label: 'Payments', icon: 'wallet-outline', tab: 'payments' },
        { label: 'Notices', icon: 'notifications-outline', tab: 'notifications' },
      ];
  return (
    <View style={styles.quickRow}>
      {actions.map(action => (
        <Pressable key={action.label} accessibilityRole="button" onPress={() => navigate(action.tab)} style={({ pressed }) => [styles.quickAction, shadows.card, pressed && styles.pressed]}>
          <View style={styles.quickIcon}>
            <Ionicons name={action.icon} size={21} color={colors.primary} />
          </View>
          <Text numberOfLines={2} style={styles.quickLabel}>{action.label}</Text>
        </Pressable>
      ))}
    </View>
  );
}

function Legend({ color, label, value }: { color: string; label: string; value?: number }) {
  return (
    <View style={styles.legendItem}>
      <View style={[styles.legendDot, { backgroundColor: color }]} />
      <Text style={styles.legendText}>{label}</Text>
      {value != null ? <Text style={styles.legendValue}>{value}</Text> : null}
    </View>
  );
}

function EmptyInline({ icon, text }: { icon: IconName; text: string }) {
  return (
    <View style={styles.emptyInline}>
      <View style={styles.emptyIcon}>
        <Ionicons name={icon} size={20} color={colors.textMuted} />
      </View>
      <Text style={styles.emptyText}>{text}</Text>
    </View>
  );
}

/* ------------------------------------------------------------------ */
/* Charts                                                              */
/* ------------------------------------------------------------------ */

const CHART_HEIGHT = 84;

function AttendanceChart({ data, rate, onPress }: { data: Dashboard; rate: number | null; onPress: () => void }) {
  const analytics = data.analytics ?? EMPTY_ANALYTICS;
  const labels = analytics.attendance_labels.length ? analytics.attendance_labels : ['M', 'T', 'W', 'T', 'F', 'S', 'S'];
  const present = analytics.present_series.length ? analytics.present_series : labels.map(() => 0);
  const absent = analytics.absent_series.length ? analytics.absent_series : labels.map(() => 0);
  const max = Math.max(1, ...present, ...absent);
  const totalPresent = present.reduce((sum, value) => sum + (value ?? 0), 0);
  const totalAbsent = absent.reduce((sum, value) => sum + (value ?? 0), 0);

  return (
    <Pressable accessibilityRole="button" accessibilityLabel="Open attendance" onPress={onPress} style={({ pressed }) => [styles.card, shadows.card, pressed && styles.pressed]}>
      <View style={styles.cardHeading}>
        <View style={styles.grow}>
          <Text style={styles.cardTitle}>Attendance trend</Text>
          <Text style={styles.cardCaption}>Last 7 days · live records</Text>
        </View>
        <View style={styles.ratePill}>
          <Text style={styles.rateValue}>{rate == null ? '—' : `${rate}%`}</Text>
        </View>
      </View>
      <View style={styles.legend}>
        <Legend color={colors.status.present.text} label="Present / late" value={totalPresent} />
        <Legend color={colors.status.absent.text} label="Absent" value={totalAbsent} />
      </View>
      <View style={styles.barChart}>
        <View pointerEvents="none" style={[styles.chartGuide, { bottom: 18 }]} />
        <View pointerEvents="none" style={[styles.chartGuide, { bottom: 18 + CHART_HEIGHT / 2 }]} />
        <View pointerEvents="none" style={[styles.chartGuide, { bottom: 18 + CHART_HEIGHT }]} />
        {labels.map((label, index) => (
          <View key={`${label}-${index}`} style={styles.barColumn}>
            <View style={styles.bars}>
              <View style={[styles.bar, styles.presentBar, { height: Math.max(4, ((present[index] ?? 0) / max) * CHART_HEIGHT) }]} />
              <View style={[styles.bar, styles.absentBar, { height: Math.max(4, ((absent[index] ?? 0) / max) * CHART_HEIGHT) }]} />
            </View>
            <Text style={styles.axisLabel}>{label.slice(0, 3)}</Text>
          </View>
        ))}
      </View>
    </Pressable>
  );
}

function performanceColor(value: number) {
  if (value >= 70) return colors.status.present.text;
  if (value >= 50) return colors.secondaryDark;
  return colors.status.absent.text;
}

function PerformanceChart({ data }: { data: Dashboard }) {
  const analytics = data.analytics ?? EMPTY_ANALYTICS;
  return (
    <View style={[styles.card, shadows.card]}>
      <View style={styles.cardHeading}>
        <View style={styles.grow}>
          <Text style={styles.cardTitle}>Academic performance</Text>
          <Text style={styles.cardCaption}>{data.teacher_workspace ? 'Current-term marks for your assignments' : 'Published subject averages'}</Text>
        </View>
        <View style={styles.cardIcon}>
          <Ionicons name="analytics-outline" size={20} color={colors.secondaryDark} />
        </View>
      </View>
      {analytics.performance_labels.length ? (
        analytics.performance_labels.map((label, index) => {
          const value = Math.round(analytics.performance_series[index] ?? 0);
          const tint = performanceColor(value);
          return (
            <View key={label} style={styles.performanceRow}>
              <View style={styles.performanceLabelRow}>
                <Text numberOfLines={1} style={styles.performanceLabel}>{label}</Text>
                <Text style={[styles.performanceValue, { color: tint }]}>{value}%</Text>
              </View>
              <View style={styles.performanceTrack}>
                <View style={[styles.performanceFill, { width: `${Math.max(2, Math.min(100, value))}%`, backgroundColor: tint }]} />
              </View>
            </View>
          );
        })
      ) : (
        <EmptyInline icon="bar-chart-outline" text="Performance appears after results are published." />
      )}
    </View>
  );
}

/* ------------------------------------------------------------------ */
/* Lists                                                               */
/* ------------------------------------------------------------------ */

function minutesOf(value?: string | null) {
  if (!value) return null;
  const [hour, minute] = value.slice(0, 5).split(':').map(Number);
  return Number.isNaN(hour) || Number.isNaN(minute) ? null : hour * 60 + minute;
}

function Schedule({ data }: { data: Dashboard }) {
  const now = useNow();
  const current = new Date(now);
  const nowMinutes = current.getHours() * 60 + current.getMinutes();
  const slots = data.today_timetable;

  return (
    <View style={[styles.listCard, shadows.card]}>
      {slots.length ? (
        slots.map((slot, index) => {
          const start = minutesOf(slot.starts_at);
          const end = minutesOf(slot.ends_at);
          const live = start != null && end != null && nowMinutes >= start && nowMinutes < end;
          const done = end != null && nowMinutes >= end;
          return (
            <View key={slot.id} style={[styles.scheduleRow, index > 0 && styles.rowBorder, done && styles.rowDone]}>
              <View style={styles.timeBlock}>
                <Text style={[styles.time, live && styles.timeLive]}>{shortTime(slot.starts_at)}</Text>
                <Text style={styles.timeEnd}>{shortTime(slot.ends_at)}</Text>
              </View>
              <View style={[styles.timelineBar, live && styles.timelineBarLive]} />
              <View style={styles.grow}>
                <Text numberOfLines={1} style={styles.rowTitle}>{slot.subject ?? slot.label ?? 'Lesson'}</Text>
                <Text numberOfLines={2} style={styles.rowMeta}>{slot.class_name ?? 'Scheduled class'}{slot.stream_name ? ` · ${slot.stream_name}` : ''}</Text>
              </View>
              {live ? (
                <View style={styles.livePill}>
                  <View style={styles.liveDot} />
                  <Text style={styles.liveText}>Now</Text>
                </View>
              ) : done ? (
                <Ionicons name="checkmark-circle" size={18} color={colors.status.present.text} />
              ) : (
                <Ionicons name="chevron-forward" size={18} color={colors.textMuted} />
              )}
            </View>
          );
        })
      ) : (
        <EmptyInline icon="calendar-clear-outline" text="No lessons scheduled today." />
      )}
    </View>
  );
}

function HomeworkList({ data, navigate }: { data: Dashboard; navigate: (tab: AppTab) => void }) {
  return (
    <View style={[styles.listCard, shadows.card]}>
      {data.homework.length ? (
        data.homework.slice(0, 3).map((item, index) => (
          <View key={item.id} style={[styles.homeworkRow, index > 0 && styles.rowBorder]}>
            <View style={styles.homeworkIcon}>
              <Ionicons name="document-text-outline" size={19} color={colors.primary} />
            </View>
            <View style={styles.grow}>
              <Text numberOfLines={1} style={styles.rowTitle}>{item.title}</Text>
              <Text numberOfLines={1} style={styles.rowMeta}>{item.subject?.name ?? 'General'}</Text>
            </View>
            {item.due_at ? (
              <View style={styles.duePill}>
                <Ionicons name="time-outline" size={12} color={colors.secondaryDark} />
                <Text style={styles.dueText}>{formatDate(item.due_at)}</Text>
              </View>
            ) : null}
          </View>
        ))
      ) : (
        <EmptyInline icon="checkmark-done-circle-outline" text="No homework is due right now." />
      )}
      <Pressable accessibilityRole="button" onPress={() => navigate('homework')} style={({ pressed }) => [styles.cardButton, pressed && styles.pressed]}>
        <Text style={styles.cardButtonText}>View all homework</Text>
        <Ionicons name="chevron-forward-circle" size={19} color={colors.primary} />
      </Pressable>
    </View>
  );
}

function Events({ data }: { data: Dashboard }) {
  const contentWidth = useContentWidth();
  const cardWidth = Math.min(240, Math.round(contentWidth * 0.62));
  const events = data.events.slice(0, 5);

  if (!events.length) {
    return (
      <View style={[styles.listCard, shadows.card]}>
        <EmptyInline icon="sparkles-outline" text="No upcoming school events." />
      </View>
    );
  }

  return (
    <ScrollView horizontal showsHorizontalScrollIndicator={false} snapToInterval={cardWidth + GAP} decelerationRate="fast" style={styles.eventScroll} contentContainerStyle={styles.eventRow}>
      {events.map(event => (
        <View key={event.id} style={[styles.eventCard, shadows.card, { width: cardWidth }]}>
          <View style={styles.eventDateBlock}>
            <Text style={styles.eventDay}>{eventDate(event.event_date, 'day')}</Text>
            <Text style={styles.eventMonth}>{eventDate(event.event_date, 'month')}</Text>
          </View>
          <Text numberOfLines={3} style={styles.eventTitle}>{event.title ?? event.name ?? 'School event'}</Text>
        </View>
      ))}
    </ScrollView>
  );
}

/* ------------------------------------------------------------------ */
/* Screens                                                             */
/* ------------------------------------------------------------------ */

export function TeacherDashboardScreen({ data, user, attendanceRate, navigate, refreshing = false, onRefresh, openRegister }: Props) {
  const stats = data.analytics?.stats ?? {};
  const workspace = data.teacher_workspace;

  return (
    <DashboardScroll refreshing={refreshing} onRefresh={onRefresh}>
      <TopBar user={user} />
      <GreetingCard user={user} title={`Hi, ${firstName(user.name)}!`} subtitle={`${workspace?.role_label ?? 'Teacher'}${workspace?.term ? ` · ${workspace.term}` : ''} · ${user.school.name}`} />
      <TeacherLessonCard data={data} navigate={navigate} />

      {workspace && (
        <View style={[styles.card, shadows.card, styles.assignmentCard]}>
          <View style={styles.cardHeading}>
            <Text style={styles.cardTitle}>{workspace.class_teacher_classes.length ? 'My class responsibility' : 'My teaching assignments'}</Text>
            <View style={styles.cardIcon}>
              <Ionicons name="ribbon-outline" size={19} color={colors.secondaryDark} />
            </View>
          </View>
          {!!workspace.class_teacher_classes.length && (
            <Text style={styles.bodyCopy}>{workspace.class_teacher_classes.map(item => item.name).join(' · ')} — daily register and class learner tools</Text>
          )}
          {workspace.subject_assignments.length ? (
            <View style={styles.chipWrap}>
              {workspace.subject_assignments.map((item, index) => (
                <View key={`${item.subject_name}-${item.class_name}-${index}`} style={styles.chip}>
                  <Text numberOfLines={1} style={styles.chipText}>{item.subject_name} · {item.class_name}</Text>
                </View>
              ))}
            </View>
          ) : (
            <Text style={styles.bodyCopy}>No subject assignments yet. Your school administrator can assign your teaching subjects.</Text>
          )}
        </View>
      )}

      {!!workspace?.pending_attendance?.length && <View style={[styles.card, shadows.card]}>
        <View style={styles.cardHeading}><Text style={styles.cardTitle}>Attendance pending today</Text><Ionicons name="alert-circle-outline" size={22} color={colors.secondary} /></View>
        <Text style={styles.bodyCopy}>These registers still need your attention.</Text>
        {workspace.pending_attendance.map(item => <Pressable key={`${item.school_class_id}-${item.session_key ?? item.subject_id}`} accessibilityRole="button" accessibilityLabel={`Take ${item.label} attendance`} onPress={() => openRegister ? openRegister(item) : navigate(item.attendance_type === 'daily' ? 'daily_attendance' : 'subject_attendance')} style={({ pressed }) => [styles.pendingRow, pressed && styles.pressed]}>
          <View style={styles.grow}><Text style={styles.rowTitle}>{item.label}</Text><Text style={styles.rowMeta}>{item.attendance_type === 'daily' ? 'Daily class register' : 'Subject register'}</Text></View><Ionicons name="arrow-forward-circle" size={26} color={colors.primary} />
        </Pressable>)}
      </View>}

      <SectionHeader title="Quick actions" subtitle="Your everyday teaching tasks" />
      <ActionGrid items={(workspace?.tools ?? []).filter(tool => ['attendance.index', 'attendance.subject', 'exams.marks', 'exams.results', 'students.index', 'communications.index', 'attendance.reports', 'leaves.index'].includes(tool.id)).map(tool => ({ key: tool.id, title: tool.label, description: 'Open your assigned tools', icon: (tool.id.startsWith('attendance') ? 'checkbox-outline' : tool.id === 'students.index' ? 'people-outline' : tool.id === 'leaves.index' ? 'calendar-outline' : tool.id === 'communications.index' ? 'chatbubbles-outline' : 'document-text-outline') as IconName, onPress: () => navigate(tool.id === 'exams.results' ? 'teacher_results' : tool.id === 'communications.index' ? 'teacher_communications' : tool.native ? tool.native as AppTab : 'school_access') }))} />

      <StatGrid items={[
        { icon: 'people-outline', label: 'Learners', value: stats.learners ?? 0, tone: 'blue' },
        { icon: 'easel-outline', label: 'Classes', value: stats.classes ?? 0 },
        { icon: 'book-outline', label: 'Subjects', value: stats.subjects ?? 0, tone: 'green' },
      ]} />
      <StatGrid items={[
        { icon: 'time-outline', label: 'Lessons today', value: stats.lessons_today ?? 0 },
        { icon: 'checkbox-outline', label: 'Recorded today', value: stats.attendance_today ?? 0, tone: 'green' },
        { icon: 'document-text-outline', label: 'Pending marks', value: stats.pending_marks ?? 0, tone: 'blue' },
      ]} />

      <SectionHeader title="Teacher workspace" subtitle="Tools available for your assignments and school permissions" />
      <ActionGrid items={ACCESS_SECTIONS.map(section => ({
        key: section.tab,
        title: section.title,
        description: section.description,
        icon: section.icon,
        onPress: () => navigate(section.tab),
        badge: workspace ? `${workspace.tools.filter(tool => toolSection(tool.group) === section.tab).length} tools` : undefined,
      }))} />

      <SectionHeader title="My attendance activity" subtitle="Registers you recorded over the last seven days" />
      <AttendanceChart data={data} rate={attendanceRate} onPress={() => navigate('attendance')} />

      <SectionHeader title="Subject insights" />
      <PerformanceChart data={data} />

      <SectionHeader title="Today’s timetable" actionLabel="Full week" onAction={() => navigate('teacher_schedule')} />
      <Schedule data={data} />

      <SectionHeader title="Homework activity" />
      <HomeworkList data={data} navigate={navigate} />

      <SectionHeader title="Upcoming events" />
      <Events data={data} />
    </DashboardScroll>
  );
}

export function StudentDashboardScreen({ data, user, attendanceRate, navigate, refreshing = false, onRefresh }: Props) {
  const stats = data.analytics?.stats ?? {};
  const student = data.student;
  const actions: Array<{ title: string; description: string; icon: IconName; tab: AppTab }> = [
    { title: 'My homework', description: 'Assignments, answers and feedback', icon: 'book-outline', tab: 'homework' },
    { title: 'My timetable', description: 'Plan your school week', icon: 'calendar-outline', tab: 'student_schedule' },
    { title: 'My results', description: 'Published scores and performance', icon: 'ribbon-outline', tab: 'results' },
    { title: 'Attendance', description: 'Your school attendance record', icon: 'checkbox-outline', tab: 'attendance' },
    { title: 'School fees', description: 'Payments and your balance', icon: 'wallet-outline', tab: 'payments' },
    { title: 'School calendar', description: 'Upcoming events and dates', icon: 'today-outline', tab: 'student_calendar' },
  ];

  return (
    <DashboardScroll refreshing={refreshing} onRefresh={onRefresh}>
      <TopBar user={user} />
      <GreetingCard user={user} title={`Hi, ${firstName(student?.name ?? user.name)}!`} subtitle={`${student?.class ?? 'Student'}${student?.stream ? ` · ${student.stream}` : ''} · ${user.school.name}`} imageName={student?.name} imageUri={student?.photo_url} />

      <StatGrid items={[
        { icon: 'calendar-outline', label: 'Attendance', value: attendanceRate == null ? '—' : `${attendanceRate}%`, tone: 'green' },
        { icon: 'time-outline', label: 'Lessons today', value: stats.lessons_today ?? 0 },
        { icon: 'trophy-outline', label: 'Results', value: stats.published_results ?? 0, tone: 'blue' },
      ]} />

      <SectionHeader title="My learning" subtitle="Everything you need for your school day" />
      <ActionGrid items={actions.map(action => ({ key: action.tab, title: action.title, description: action.description, icon: action.icon, onPress: () => navigate(action.tab) }))} />

      <SectionHeader title="Today’s lessons" actionLabel="Timetable" onAction={() => navigate('student_schedule')} />
      <Schedule data={data} />

      <SectionHeader title="My attendance" />
      <AttendanceChart data={data} rate={attendanceRate} onPress={() => navigate('attendance')} />

      <SectionHeader title="My performance" />
      <PerformanceChart data={data} />

      <SectionHeader title="Upcoming homework" />
      <HomeworkList data={data} navigate={navigate} />

      <SectionHeader title="School calendar" actionLabel="Open" onAction={() => navigate('student_calendar')} />
      <Events data={data} />
    </DashboardScroll>
  );
}

export function ParentDashboardScreen({ data, user, attendanceRate, navigate, refreshing = false, onRefresh }: Props) {
  const stats = data.analytics?.stats ?? {};
  const child = data.student;

  return (
    <DashboardScroll refreshing={refreshing} onRefresh={onRefresh}>
      <TopBar user={user} />
      <GreetingCard user={user} title={`Hello, ${firstName(user.name)}`} subtitle={`A live overview of ${child?.name ?? 'your learner'}’s school progress.`} imageName={child?.name} imageUri={child?.photo_url} />

      <View style={[styles.learnerStrip, shadows.card]}>
        <Avatar name={child?.name ?? 'Student'} uri={child?.photo_url} size={52} />
        <View style={styles.grow}>
          <Text numberOfLines={1} style={styles.learnerName}>{child?.name ?? 'Your learner'}</Text>
          <Text numberOfLines={1} style={styles.rowMeta}>{child?.class ?? 'Class not assigned'}{child?.stream ? ` · ${child.stream}` : ''}</Text>
        </View>
        <View style={styles.livePill}>
          <View style={styles.liveDot} />
          <Text style={styles.liveText}>Live</Text>
        </View>
      </View>

      <StatGrid items={[
        { icon: 'calendar-outline', label: 'Attendance', value: attendanceRate == null ? '—' : `${attendanceRate}%`, tone: 'green' },
        { icon: 'document-text-outline', label: 'Homework', value: stats.homework ?? data.homework.length },
        { icon: 'school-outline', label: 'Results', value: stats.published_results ?? 0, tone: 'blue' },
      ]} />

      <SectionHeader title="Quick access" />
      <QuickActions role="parent" navigate={navigate} />

      <SectionHeader title="Attendance overview" subtitle="Updated from school registers" />
      <AttendanceChart data={data} rate={attendanceRate} onPress={() => navigate('attendance')} />

      <SectionHeader title="Academic progress" />
      <PerformanceChart data={data} />

      <SectionHeader title="Today at school" />
      <Schedule data={data} />

      <SectionHeader title="Homework due" />
      <HomeworkList data={data} navigate={navigate} />

      <SectionHeader title="Upcoming events" />
      <Events data={data} />
    </DashboardScroll>
  );
}

function DashboardScroll({ children, refreshing, onRefresh }: { children: React.ReactNode; refreshing: boolean; onRefresh?: () => void }) {
  return (
    <ScrollView
      style={styles.screen}
      contentContainerStyle={styles.content}
      showsVerticalScrollIndicator={false}
      refreshControl={onRefresh ? <RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={colors.primary} colors={[colors.primary]} /> : undefined}
    >
      <View style={styles.inner}>{children}</View>
    </ScrollView>
  );
}

function TeacherLessonCard({ data, navigate }: { data: Dashboard; navigate: (tab: AppTab) => void }) {
  const now = useNow(1000);
  const lesson = data.today_timetable.find(slot => (slot.ends_at_iso ? new Date(slot.ends_at_iso).getTime() : new Date(`${data.date}T${slot.ends_at}`).getTime()) > now);
  if (!lesson) return <View style={[styles.card, shadows.card]}><Text style={styles.bodyCopy}>You have no remaining lessons today.</Text></View>;
  const start = new Date(lesson.starts_at_iso ?? `${data.date}T${lesson.starts_at}`).getTime();
  const end = new Date(lesson.ends_at_iso ?? `${data.date}T${lesson.ends_at}`).getTime();
  const live = now >= start && now < end;
  const seconds = Math.max(0, Math.floor(((live ? end : start) - now) / 1000));
  const countdown = `${Math.floor(seconds / 3600) ? `${Math.floor(seconds / 3600)}h ` : ''}${Math.floor(seconds / 60) % 60}m ${String(seconds % 60).padStart(2, '0')}s`;
  return <Pressable accessibilityRole="button" accessibilityLabel="Open timetable" onPress={() => navigate('teacher_schedule')} style={({ pressed }) => [styles.card, styles.lessonFocus, pressed && styles.pressed]}>
    <Text style={styles.greetingEyebrow}>{live ? 'LESSON IN PROGRESS' : 'YOUR NEXT LESSON'}</Text>
    <Text style={styles.cardTitle}>{lesson.subject ?? lesson.label ?? 'Lesson'}</Text>
    <Text style={styles.bodyCopy}>{lesson.class_name ?? 'Class'}{lesson.stream_name ? ` · ${lesson.stream_name}` : ''} · {shortTime(lesson.starts_at)}–{shortTime(lesson.ends_at)}</Text>
    <View style={styles.countdownBox}><Text style={styles.countdownLabel}>{live ? 'Time remaining' : 'Starts in'}</Text><Text style={styles.countdownValue}>{countdown}</Text></View>
  </Pressable>;
}

/* ------------------------------------------------------------------ */
/* Helpers                                                             */
/* ------------------------------------------------------------------ */

function greeting() {
  const hour = new Date().getHours();
  return hour < 12 ? 'Good morning' : hour < 18 ? 'Good afternoon' : 'Good evening';
}

function firstName(name: string) {
  return name.trim().split(/\s+/)[0] || 'there';
}

function initials(name: string) {
  return name.trim().split(/\s+/).slice(0, 2).map(word => word[0]).join('').toUpperCase();
}

function shortTime(value: string) {
  return value?.slice(0, 5) ?? '—';
}

function formatDate(value: string) {
  const date = new Date(value.length === 10 ? `${value}T12:00:00` : value);
  return Number.isNaN(date.valueOf()) ? value : date.toLocaleDateString(undefined, { weekday: 'short', day: 'numeric', month: 'short' });
}

function eventDate(value: string, part: 'day' | 'month') {
  const date = new Date(value.length === 10 ? `${value}T12:00:00` : value);
  if (Number.isNaN(date.valueOf())) return '—';
  return part === 'day' ? `${date.getDate()}` : date.toLocaleDateString(undefined, { month: 'short' }).toUpperCase();
}

function nextLessonCopy(lesson: Lesson | undefined, now: number) {
  if (!lesson) return 'No more lessons scheduled today';
  const day = new Date();
  const [startHour, startMinute] = lesson.starts_at.slice(0, 5).split(':').map(Number);
  const [endHour, endMinute] = lesson.ends_at.slice(0, 5).split(':').map(Number);
  const start = new Date(day.getFullYear(), day.getMonth(), day.getDate(), startHour, startMinute).getTime();
  const end = new Date(day.getFullYear(), day.getMonth(), day.getDate(), endHour, endMinute).getTime();
  const name = lesson.subject ?? lesson.label ?? 'Next lesson';
  if (now >= start && now < end) return `${name} is going on · ends ${shortTime(lesson.ends_at)}`;
  const minutes = Math.max(0, Math.ceil((start - now) / 60_000));
  const countdown = minutes >= 60 ? `${Math.floor(minutes / 60)}h ${minutes % 60}m` : `${minutes} min`;
  return `${name} starts in ${countdown}`;
}

/* ------------------------------------------------------------------ */
/* Styles                                                              */
/* ------------------------------------------------------------------ */

const styles = StyleSheet.create({
  pendingRow: { flexDirection: 'row', alignItems: 'center', gap: 12, paddingVertical: 14, borderBottomWidth: 1, borderBottomColor: '#E2E7F0' },
  lessonFocus: { borderColor: colors.secondary, borderWidth: 1, gap: 8 },
  countdownBox: { backgroundColor: colors.primary, borderRadius: radius.md, padding: 16, marginTop: 6 },
  countdownLabel: { color: '#D8E2F2', fontSize: 11, fontWeight: '600' },
  countdownValue: { color: colors.secondary, fontSize: 26, fontWeight: '800' },
  screen: { flex: 1, backgroundColor: colors.background },
  content: { paddingHorizontal: GUTTER, paddingBottom: 56, alignItems: 'center' },
  inner: { width: '100%', maxWidth: MAX_WIDTH },
  pressed: { opacity: 0.78 },
  grow: { flex: 1, minWidth: 0 },

  // Top bar
  topBar: { minHeight: 75, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  topLogo: { width: 134, height: 58 },
  topActions: { flexDirection: 'row', alignItems: 'center', gap: 9 },
  iconButton: { width: 42, height: 42, borderRadius: 15, backgroundColor: colors.surface, borderWidth: 1, borderColor: colors.border, alignItems: 'center', justifyContent: 'center' },
  notificationBadge: { position: 'absolute', right: -3, top: -4, minWidth: 18, height: 18, borderRadius: 9, paddingHorizontal: 4, backgroundColor: '#DC2626', borderWidth: 2, borderColor: colors.surface, alignItems: 'center', justifyContent: 'center' },
  notificationBadgeText: { color: '#FFFFFF', fontSize: 9, fontWeight: '800' },

  // Avatar
  avatar: { backgroundColor: colors.secondary, alignItems: 'center', justifyContent: 'center', overflow: 'hidden', borderWidth: 2, borderColor: colors.surface },
  avatarImage: { resizeMode: 'cover' },
  avatarText: { color: colors.primary, fontWeight: '900', fontSize: 15 },

  // Welcome card intentionally preserved exactly from Edlink Premium V1.
  greetingCard: { minHeight: 198, borderRadius: 23, backgroundColor: colors.primaryCard, padding: 19, flexDirection: 'row', alignItems: 'center', overflow: 'hidden' },
  greetingGlow: { position: 'absolute', width: 200, height: 200, borderRadius: 100, backgroundColor: colors.primary, right: -90, top: -100, opacity: 0.55 },
  greetingCopy: { flex: 1, paddingRight: 8 },
  greetingEyebrow: { color: colors.secondary, fontSize: 10, fontWeight: '700', letterSpacing: 1.5 },
  greetingTitle: { color: colors.textLight, fontSize: 24, lineHeight: 31, fontWeight: '800', marginTop: 8 },
  greetingSubtitle: { color: '#DBE5F2', fontSize: 11, lineHeight: 18, marginTop: 6 },
  datePill: { alignSelf: 'flex-start', flexDirection: 'row', alignItems: 'center', gap: 6, backgroundColor: colors.secondary, borderRadius: radius.full, paddingHorizontal: 10, paddingVertical: 6, marginTop: 13 },
  dateText: { color: colors.primary, fontSize: 10, fontWeight: '800' },
  lessonPill: { alignSelf: 'flex-start', flexDirection: 'row', alignItems: 'center', gap: 6, marginTop: 10 },
  lessonText: { color: colors.secondary, fontSize: 10, lineHeight: 14, fontWeight: '800', flexShrink: 1 },

  // Section headers
  sectionHeader: { marginTop: 28, marginBottom: 12, flexDirection: 'row', alignItems: 'flex-end', justifyContent: 'space-between', gap: 12 },
  sectionHeaderText: { flex: 1, minWidth: 0 },
  sectionTitleRow: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  sectionMarker: { width: 4, height: 18, borderRadius: 2, backgroundColor: colors.secondary },
  sectionTitle: { flexShrink: 1, fontSize: 18, fontWeight: '800', color: colors.textDark, letterSpacing: -0.3 },
  sectionSubtitle: { fontSize: 11, lineHeight: 17, color: colors.textMuted, marginTop: 4, marginLeft: 12 },
  sectionAction: { flexDirection: 'row', alignItems: 'center', gap: 2, paddingBottom: 2 },
  sectionActionText: { color: colors.primary, fontSize: 12, fontWeight: '800' },

  // Stats
  statGrid: { flexDirection: 'row', gap: GAP, marginTop: 13 },
  statCard: { flex: 1, minWidth: 0, backgroundColor: colors.surface, borderRadius: 16, padding: 13, borderWidth: StyleSheet.hairlineWidth, borderColor: colors.border },
  statCardCompact: { padding: 10 },
  statIcon: { width: 32, height: 32, borderRadius: 11, alignItems: 'center', justifyContent: 'center' },
  statValue: { color: colors.textDark, fontSize: 26, lineHeight: 32, fontWeight: '800', marginTop: 12, fontVariant: ['tabular-nums'] },
  statLabel: { color: colors.textMuted, fontSize: 10, lineHeight: 14, fontWeight: '600', marginTop: 2 },

  // Action tiles
  actionGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: GAP },
  actionTile: { minHeight: 128, backgroundColor: colors.surface, borderRadius: 16, padding: 13, borderWidth: StyleSheet.hairlineWidth, borderColor: colors.border },
  actionTileTop: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: 12 },
  actionIcon: { width: 42, height: 42, borderRadius: 14, backgroundColor: '#FFF4D9', alignItems: 'center', justifyContent: 'center' },
  actionTitle: { fontSize: 14, fontWeight: '800', color: colors.textDark },
  actionSubtitle: { marginTop: 4, fontSize: 10, lineHeight: 15, color: colors.textMuted },
  actionBadge: { alignSelf: 'flex-start', marginTop: 9, backgroundColor: '#E8EDF8', borderRadius: radius.full, paddingHorizontal: 8, paddingVertical: 3 },
  actionBadgeText: { color: colors.primary, fontSize: 9, fontWeight: '800' },

  // Quick actions
  quickRow: { flexDirection: 'row', flexWrap: 'wrap', gap: GAP },
  quickAction: { width: '30%', flexGrow: 1, minHeight: 96, borderRadius: 16, backgroundColor: colors.surface, borderWidth: StyleSheet.hairlineWidth, borderColor: colors.border, alignItems: 'center', justifyContent: 'center', paddingHorizontal: 6 },
  quickIcon: { width: 40, height: 40, borderRadius: 14, backgroundColor: '#FFF4D9', alignItems: 'center', justifyContent: 'center' },
  quickLabel: { color: colors.textDark, fontSize: 10, fontWeight: '700', marginTop: 8, textAlign: 'center' },

  // Cards
  card: { backgroundColor: colors.surface, borderRadius: 18, paddingHorizontal: 18, paddingVertical: 18, borderWidth: StyleSheet.hairlineWidth, borderColor: colors.border },
  assignmentCard: { marginTop: 14 },
  cardHeading: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: 10 },
  cardTitle: { color: colors.textDark, fontSize: 15, fontWeight: '800' },
  cardCaption: { color: colors.textMuted, fontSize: 10, marginTop: 4 },
  cardIcon: { width: 36, height: 36, borderRadius: 12, backgroundColor: '#FFF4D9', alignItems: 'center', justifyContent: 'center' },
  ratePill: { backgroundColor: '#E8EDF8', borderRadius: radius.full, paddingHorizontal: 12, paddingVertical: 6 },
  rateValue: { color: colors.primary, fontSize: 18, fontWeight: '800' },
  bodyCopy: { color: colors.textMuted, fontSize: 12, lineHeight: 20, marginTop: 8 },
  chipWrap: { flexDirection: 'row', flexWrap: 'wrap', gap: 7, marginTop: 12 },
  chip: { maxWidth: '100%', backgroundColor: '#F0F3FA', borderRadius: radius.full, paddingHorizontal: 11, paddingVertical: 6 },
  chipText: { color: colors.primary, fontSize: 11, fontWeight: '700' },

  // Attendance chart
  legend: { flexDirection: 'row', flexWrap: 'wrap', gap: 16, marginTop: 16 },
  legendItem: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  legendDot: { width: 8, height: 8, borderRadius: 4 },
  legendText: { color: colors.textMuted, fontSize: 10 },
  legendValue: { color: colors.textDark, fontSize: 11, fontWeight: '800' },
  barChart: { height: CHART_HEIGHT + 40, flexDirection: 'row', alignItems: 'flex-end', justifyContent: 'space-between', marginTop: 8, position: 'relative' },
  chartGuide: { position: 'absolute', left: 0, right: 0, borderTopWidth: StyleSheet.hairlineWidth, borderTopColor: '#E3E7EF' },
  barColumn: { flex: 1, alignItems: 'center', zIndex: 1 },
  bars: { height: CHART_HEIGHT, flexDirection: 'row', alignItems: 'flex-end', gap: 3 },
  bar: { width: 9, borderTopLeftRadius: 5, borderTopRightRadius: 5 },
  presentBar: { backgroundColor: colors.status.present.text },
  absentBar: { backgroundColor: colors.status.absent.text },
  axisLabel: { color: colors.textMuted, fontSize: 10, marginTop: 9, height: 14 },

  // Performance
  performanceRow: { marginTop: 16 },
  performanceLabelRow: { flexDirection: 'row', justifyContent: 'space-between', gap: 12 },
  performanceLabel: { flex: 1, color: colors.textDark, fontSize: 12, fontWeight: '600' },
  performanceValue: { fontSize: 12, fontWeight: '800' },
  performanceTrack: { height: 8, borderRadius: 5, backgroundColor: '#EEF1F6', marginTop: 8, overflow: 'hidden' },
  performanceFill: { height: '100%', borderRadius: 5 },

  // Lists
  listCard: { borderRadius: 18, backgroundColor: colors.surface, paddingHorizontal: 16, borderWidth: StyleSheet.hairlineWidth, borderColor: colors.border, overflow: 'hidden' },
  rowBorder: { borderTopWidth: StyleSheet.hairlineWidth, borderTopColor: colors.border },
  rowDone: { opacity: 0.6 },
  rowTitle: { color: colors.textDark, fontSize: 13, fontWeight: '700' },
  rowMeta: { color: colors.textMuted, fontSize: 11, marginTop: 4 },

  scheduleRow: { minHeight: 76, flexDirection: 'row', alignItems: 'center', gap: 12 },
  timeBlock: { width: 46, alignItems: 'flex-start' },
  time: { color: colors.primary, fontSize: 13, fontWeight: '800', fontVariant: ['tabular-nums'] },
  timeLive: { color: colors.secondaryDark },
  timeEnd: { color: colors.textMuted, fontSize: 10, marginTop: 3, fontVariant: ['tabular-nums'] },
  timelineBar: { width: 3, alignSelf: 'stretch', marginVertical: 14, borderRadius: 2, backgroundColor: '#E9EDF4' },
  timelineBarLive: { backgroundColor: colors.secondary },

  homeworkRow: { minHeight: 70, flexDirection: 'row', alignItems: 'center', gap: 12 },
  homeworkIcon: { width: 42, height: 42, borderRadius: 13, backgroundColor: '#FFF4D9', alignItems: 'center', justifyContent: 'center' },
  duePill: { flexDirection: 'row', alignItems: 'center', gap: 4, backgroundColor: '#FFF4D9', borderRadius: radius.full, paddingHorizontal: 8, paddingVertical: 4 },
  dueText: { color: colors.secondaryDark, fontSize: 10, fontWeight: '800' },
  cardButton: { minHeight: 52, borderTopWidth: StyleSheet.hairlineWidth, borderTopColor: colors.border, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 7 },
  cardButtonText: { color: colors.primary, fontSize: 12, fontWeight: '800' },

  // Events (horizontal carousel)
  eventScroll: { marginHorizontal: -GUTTER },
  eventRow: { paddingHorizontal: GUTTER, gap: GAP, paddingBottom: 4 },
  eventCard: { minHeight: 132, backgroundColor: colors.surface, borderRadius: 18, padding: 14, gap: 12, borderWidth: StyleSheet.hairlineWidth, borderColor: colors.border },
  eventDateBlock: { height: 52, width: 56, borderRadius: 13, backgroundColor: colors.primaryCard, justifyContent: 'center', alignItems: 'center' },
  eventDay: { color: colors.secondary, fontSize: 21, lineHeight: 24, fontWeight: '800' },
  eventMonth: { color: colors.secondary, fontSize: 9, fontWeight: '700' },
  eventTitle: { color: colors.textDark, fontSize: 13, fontWeight: '700', lineHeight: 18 },

  // Empty states
  emptyInline: { minHeight: 96, alignItems: 'center', justifyContent: 'center', gap: 8, paddingVertical: 8 },
  emptyIcon: { width: 40, height: 40, borderRadius: 20, backgroundColor: '#F0F3FA', alignItems: 'center', justifyContent: 'center' },
  emptyText: { color: colors.textMuted, fontSize: 11, textAlign: 'center' },

  // Parent learner strip + live pill
  learnerStrip: { marginTop: 13, minHeight: 76, flexDirection: 'row', alignItems: 'center', gap: 12, borderRadius: 18, backgroundColor: colors.surface, padding: 12, borderWidth: StyleSheet.hairlineWidth, borderColor: colors.border },
  learnerName: { color: colors.textDark, fontSize: 15, fontWeight: '800' },
  livePill: { flexDirection: 'row', alignItems: 'center', gap: 5, backgroundColor: colors.status.present.bg, borderRadius: radius.full, paddingHorizontal: 9, paddingVertical: 6 },
  liveDot: { width: 6, height: 6, borderRadius: 3, backgroundColor: colors.status.present.text },
  liveText: { color: colors.status.present.text, fontSize: 9, fontWeight: '800' },
});
