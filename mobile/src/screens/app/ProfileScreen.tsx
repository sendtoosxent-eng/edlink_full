import { Ionicons } from '@expo/vector-icons';
import { useCallback, useEffect, useState } from 'react';
import { Alert, Image, Pressable, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import { api, ApiError } from '../../api';
import { colors, radius, shadows } from '../../theme/index';
import type { User } from '../../types';
import type { AppTab } from './DashboardScreens';

type TimetableItem = { id: number; day_of_week: string; starts_at: string; ends_at: string; subject?: string | null; label?: string | null; class_name?: string | null };
type AnnouncementItem = { id: number; title?: string; subject?: string; message?: string; body?: string };

type Props = {
  token: string;
  user: User;
  studentId?: number;
  onSignOut: () => Promise<void>;
  navigate: (tab: AppTab) => void;
};

export function ProfileScreen({ token, user, studentId, onSignOut, navigate }: Props) {
  const [timetable, setTimetable] = useState<TimetableItem[]>([]);
  const [announcements, setAnnouncements] = useState<AnnouncementItem[]>([]);
  const [loading, setLoading] = useState(false);
  const [signingOut, setSigningOut] = useState(false);

  const load = useCallback(async () => {
    setLoading(true);
    const suffix = studentId ? `?student_id=${studentId}` : '';
    try {
      const [slots, notices] = await Promise.all([
        api.get<TimetableItem[]>(`/timetable${suffix}`, token),
        api.get<{ data: AnnouncementItem[] }>('/announcements', token),
      ]);
      setTimetable(slots.data);
      setAnnouncements(notices.data.data ?? []);
    } catch (error) {
      Alert.alert('Could not refresh profile', messageFor(error));
    } finally {
      setLoading(false);
    }
  }, [studentId, token]);

  useEffect(() => { void load(); }, [load]);

  const confirmSignOut = () => {
    Alert.alert('Log out of Edlink?', 'You will need to sign in again to access your school account.', [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Log out',
        style: 'destructive',
        onPress: () => {
          setSigningOut(true);
          void onSignOut().finally(() => setSigningOut(false));
        },
      },
    ]);
  };

  return (
    <ScrollView
      contentContainerStyle={styles.content}
      refreshControl={<RefreshControl refreshing={loading} onRefresh={load} tintColor={colors.primary} />}
    >
      <View style={[styles.hero, shadows.card]}>
        <View style={styles.heroGlow} />
        <View style={styles.avatarRing}>
          {user.avatar_url ? <Image source={{ uri: user.avatar_url }} style={styles.avatarImage} /> : <Text style={styles.initials}>{initials(user.name)}</Text>}
        </View>
        <Text style={styles.name}>{user.name}</Text>
        <View style={styles.rolePill}>
          <Ionicons name="shield-checkmark" size={14} color={colors.primary} />
          <Text style={styles.roleText}>{capitalize(user.role)}</Text>
        </View>
        <Text style={styles.school}>{user.school.name}</Text>
      </View>

      <Text style={styles.sectionTitle}>Account information</Text>
      <View style={[styles.card, shadows.card]}>
        <InfoRow icon="mail-outline" label="Email address" value={user.email} />
        <View style={styles.divider} />
        <InfoRow icon="business-outline" label="School number" value={user.school.number} />
        <View style={styles.divider} />
        <InfoRow icon="key-outline" label="Account ID" value={`EDL-${String(user.id).padStart(5, '0')}`} />
      </View>

      <View style={styles.sectionHeading}>
        <Text style={styles.sectionTitle}>This week</Text>
        <Text style={styles.sectionMeta}>{timetable.length} timetable {timetable.length === 1 ? 'item' : 'items'}</Text>
      </View>
      <View style={styles.card}>
        {timetable.slice(0, 3).map((slot, index) => (
          <View key={slot.id}>
            {index > 0 && <View style={styles.divider} />}
            <View style={styles.scheduleRow}>
              <View style={styles.scheduleIcon}><Ionicons name="calendar-outline" size={19} color={colors.secondaryDark} /></View>
              <View style={styles.grow}>
                <Text style={styles.rowValue}>{slot.subject ?? slot.label ?? 'Lesson'}</Text>
                <Text style={styles.rowLabel}>{slot.day_of_week} · {shortTime(slot.starts_at)}–{shortTime(slot.ends_at)}</Text>
              </View>
              {!!slot.class_name && <Text style={styles.classLabel}>{slot.class_name}</Text>}
            </View>
          </View>
        ))}
        {!timetable.length && <EmptyRow icon="calendar-clear-outline" text="No timetable items available." />}
      </View>

      <View style={styles.sectionHeading}>
        <Text style={styles.sectionTitle}>Latest notices</Text>
        <View style={styles.countBadge}><Text style={styles.countText}>{announcements.length}</Text></View>
      </View>
      <View style={styles.noticeStack}>
        {announcements.slice(0, 2).map(notice => (
          <View key={notice.id} style={styles.noticeCard}>
            <View style={styles.noticeIcon}><Ionicons name="megaphone-outline" size={19} color={colors.primary} /></View>
            <View style={styles.grow}>
              <Text style={styles.rowValue}>{notice.title ?? notice.subject ?? 'School notice'}</Text>
              <Text numberOfLines={2} style={styles.noticeBody}>{notice.message ?? notice.body ?? ''}</Text>
            </View>
          </View>
        ))}
        {!announcements.length && <View style={styles.card}><EmptyRow icon="notifications-off-outline" text="You're all caught up." /></View>}
      </View>

      {user.role === 'teacher' && <Pressable accessibilityRole="button" onPress={() => navigate('leave')} style={styles.leaveButton}><Ionicons name="calendar-number-outline" size={20} color={colors.primary} /><Text style={styles.leaveText}>Request leave</Text></Pressable>}

      <Pressable
        accessibilityRole="button"
        accessibilityLabel="Log out of Edlink"
        disabled={signingOut}
        onPress={confirmSignOut}
        style={({ pressed }) => [styles.logoutButton, (pressed || signingOut) && styles.pressed]}
      >
        <Ionicons name="log-out-outline" size={20} color="#B91C1C" />
        <Text style={styles.logoutText}>{signingOut ? 'Logging out…' : 'Log out'}</Text>
      </Pressable>
      <Text style={styles.version}>Edlink Mobile · Secure school access</Text>
    </ScrollView>
  );
}

type IconName = React.ComponentProps<typeof Ionicons>['name'];

function InfoRow({ icon, label, value }: { icon: IconName; label: string; value: string }) {
  return <View style={styles.infoRow}><View style={styles.infoIcon}><Ionicons name={icon} size={19} color={colors.primary} /></View><View style={styles.grow}><Text style={styles.rowLabel}>{label}</Text><Text style={styles.rowValue}>{value}</Text></View></View>;
}

function EmptyRow({ icon, text }: { icon: IconName; text: string }) {
  return <View style={styles.emptyRow}><Ionicons name={icon} size={22} color={colors.textMuted} /><Text style={styles.emptyText}>{text}</Text></View>;
}

function messageFor(error: unknown) { return error instanceof ApiError ? error.message : 'Check your connection and try again.'; }
function capitalize(value: string) { return value.charAt(0).toUpperCase() + value.slice(1); }
function shortTime(value: string) { return value?.slice(0, 5) ?? ''; }
function initials(name: string) { return name.trim().split(/\s+/).slice(0, 2).map(word => word[0]).join('').toUpperCase(); }

const styles = StyleSheet.create({
  content: { paddingHorizontal: 20, paddingTop: 16, paddingBottom: 40 },
  hero: { minHeight: 258, borderRadius: radius.lg, backgroundColor: colors.primaryCard, alignItems: 'center', padding: 24, overflow: 'hidden' },
  heroGlow: { position: 'absolute', width: 260, height: 260, borderRadius: 130, backgroundColor: colors.primary, opacity: 0.45, top: -130, right: -80 },
  avatarRing: { width: 88, height: 88, borderRadius: 44, borderWidth: 4, borderColor: colors.secondary, backgroundColor: colors.surface, alignItems: 'center', justifyContent: 'center', overflow: 'hidden' },
  avatarImage: { width: '100%', height: '100%' },
  initials: { color: colors.primary, fontSize: 27, fontWeight: '900' },
  name: { color: colors.textLight, fontSize: 23, fontWeight: '900', marginTop: 14, textAlign: 'center' },
  rolePill: { flexDirection: 'row', alignItems: 'center', gap: 5, backgroundColor: colors.secondary, borderRadius: radius.full, paddingHorizontal: 11, paddingVertical: 6, marginTop: 9 },
  roleText: { color: colors.primary, fontSize: 12, fontWeight: '900' },
  school: { color: '#D7DDEC', fontSize: 13, marginTop: 10, textAlign: 'center' },
  sectionHeading: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginTop: 24, marginBottom: 10 },
  sectionTitle: { color: colors.textDark, fontSize: 17, fontWeight: '900', marginTop: 24, marginBottom: 10 },
  sectionMeta: { color: colors.textMuted, fontSize: 12, marginTop: 24 },
  card: { backgroundColor: colors.surface, borderRadius: radius.md, paddingHorizontal: 16, borderWidth: 1, borderColor: '#E3E8F2' },
  infoRow: { minHeight: 72, flexDirection: 'row', alignItems: 'center', gap: 13 },
  infoIcon: { width: 38, height: 38, borderRadius: 12, backgroundColor: '#EEF1F7', alignItems: 'center', justifyContent: 'center' },
  rowLabel: { color: colors.textMuted, fontSize: 12, lineHeight: 17 },
  rowValue: { color: colors.textDark, fontSize: 15, fontWeight: '800', marginTop: 2 },
  divider: { height: StyleSheet.hairlineWidth, backgroundColor: '#E3E8F2' },
  scheduleRow: { minHeight: 74, flexDirection: 'row', alignItems: 'center', gap: 12 },
  scheduleIcon: { width: 38, height: 38, borderRadius: 12, backgroundColor: '#FFF6E3', alignItems: 'center', justifyContent: 'center' },
  classLabel: { color: colors.secondaryDark, fontSize: 11, fontWeight: '800', maxWidth: 78 },
  grow: { flex: 1 },
  countBadge: { minWidth: 28, height: 28, borderRadius: 14, backgroundColor: colors.secondary, alignItems: 'center', justifyContent: 'center', marginTop: 14 },
  countText: { color: colors.primary, fontSize: 12, fontWeight: '900' },
  noticeStack: { gap: 10 },
  noticeCard: { minHeight: 88, flexDirection: 'row', alignItems: 'flex-start', gap: 12, backgroundColor: '#FFF8E8', borderRadius: radius.md, padding: 15, borderWidth: 1, borderColor: '#F2DFB6' },
  noticeIcon: { width: 38, height: 38, borderRadius: 12, backgroundColor: colors.secondary, alignItems: 'center', justifyContent: 'center' },
  noticeBody: { color: colors.textMuted, fontSize: 12, lineHeight: 18, marginTop: 4 },
  emptyRow: { minHeight: 76, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 9 },
  emptyText: { color: colors.textMuted, fontSize: 13 },
  logoutButton: { minHeight: 54, marginTop: 28, borderRadius: radius.md, borderWidth: 1, borderColor: '#F5B8B8', backgroundColor: '#FFF5F5', flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 8 },
  leaveButton: { minHeight: 54, marginTop: 28, borderRadius: radius.md, backgroundColor: colors.secondary, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 8 }, leaveText: { color: colors.primary, fontSize: 15, fontWeight: '900' },
  logoutText: { color: '#B91C1C', fontSize: 15, fontWeight: '900' },
  pressed: { opacity: 0.62 },
  version: { color: colors.textMuted, fontSize: 11, textAlign: 'center', marginTop: 16 },
});
