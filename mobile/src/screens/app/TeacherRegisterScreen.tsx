import { useThemedStyles } from '../../theme/ThemeProvider';
import { TeacherAttendancePerformance } from './TeacherAttendancePerformance';
import { useEffect, useState } from 'react';
import { BackHandler, Image, Pressable, ScrollView, StyleSheet, View } from 'react-native';
import { StatArtwork } from '../../components/StatArtwork';
import { AppSvg } from '../../components/AppSvg';
import { api, ApiError } from '../../api';
import { Text, TextInput } from '../../components/Typography';
import { InlineDatePicker } from '../../components/InlineDatePicker';
import { InlineSelect } from '../../components/InlineSelect';
import { EdlinkAlert as Alert } from '../../components/EdlinkAlert';
import { BrandLoader } from '../../components/BrandLoader';
import { colors, radius } from '../../theme/index';
import type { Assignment, AttendanceRow, AttendanceStatus } from '../../types';

export type RegisterTarget = { school_class_id: number; subject_id: number | null; attendance_type: 'daily' | 'subject'; session_key?: string };
const statuses: AttendanceStatus[] = ['present', 'absent', 'late', 'excused'];
const statusColors = {
  present: { background: '#E3F3E8', text: '#246B41' },
  absent: { background: '#FBE5E5', text: '#AD3030' },
  late: { background: '#E4EDFC', text: '#2E5DA7' },
  excused: { background: '#FFF4CE', text: '#876600' },
};
type Register = Assignment & { session_key: string; stream_id?: number | null; stream_name?: string; starts_at?: string; ends_at?: string };
type Registers = { registers: Register[]; streams: Array<{ id: number; name: string; school_class_id: number }>; can_record: boolean };
const keyFor = (item: Register) => `${item.school_class_id}:${item.session_key}`;
const keyForTarget = (items: Register[], target?: RegisterTarget) => { const match = target && items.find(item => item.school_class_id === target.school_class_id && item.subject_id === target.subject_id && (!target.session_key || item.session_key === target.session_key)); return match ? keyFor(match) : items[0] ? keyFor(items[0]) : ''; };
const localDate = () => { const date = new Date(); return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`; };

export function TeacherRegisterScreen({ token, mode, target, onBack }: { token: string; mode?: 'daily' | 'subject'; target?: RegisterTarget; onBack: () => void }) {
  const styles = useThemedStyles(baseStyles);
  const [assignments, setAssignments] = useState<Register[]>([]);
  const [streams, setStreams] = useState<Registers['streams']>([]);
  const [streamId, setStreamId] = useState('');
  const [canRecord, setCanRecord] = useState(false);
  const [search, setSearch] = useState('');
  const [searchFocused, setSearchFocused] = useState(false);
  const [showPhotos, setShowPhotos] = useState(false);
  const [performance, setPerformance] = useState(false);
  const [selectedKey, setSelectedKey] = useState('');
  const [date, setDate] = useState(localDate);
  const [draftDate, setDraftDate] = useState(date);
  const [rows, setRows] = useState<Array<AttendanceRow & { note?: string }>>([]);
  const [version, setVersion] = useState<string>();
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [reload, setReload] = useState(0);
  const selected = assignments.find(item => keyFor(item) === selectedKey);
  useEffect(() => {
    let active = true;
    setLoading(true); setError(''); setRows([]); setAssignments([]);
    void api.get<Registers>(`/attendance/registers?attendance_date=${date}`, token).then(({ data }) => {
      if (!active) return;
      setStreams(data.streams); setCanRecord(data.can_record);
      const available = data.registers.filter(item => !mode || item.attendance_type === mode);
      setAssignments(available);
      setSelectedKey(previous => available.some(item => keyFor(item) === previous) ? previous : keyForTarget(available, target));
      if (!available.length) setLoading(false);
    }).catch(e => { if (active) { setError(e.message); setLoading(false); } });
    return () => { active = false; };
  }, [token, mode, target, date, reload]);
  useEffect(() => {
    if (!selected) return;
    let active = true;
    setLoading(true); setRows([]); setVersion(undefined); setError('');
    const session = selected.session_key;
    const query = `school_class_id=${selected.school_class_id}&attendance_date=${date}&session_key=${encodeURIComponent(session)}${selected.subject_id ? `&subject_id=${selected.subject_id}` : ''}${selected.attendance_type === 'daily' && streamId ? `&stream_id=${streamId}` : ''}`;
    void api.get<Array<AttendanceRow & { note?: string }>>(`/attendance?${query}`, token).then(response => { if (active) { setRows(response.data); setVersion(response.meta?.version as string | undefined); } }).catch(e => { if (active) setError(e.message); }).finally(() => { if (active) setLoading(false); });
    return () => { active = false; };
  }, [selected, date, streamId, token, reload]);
  const save = async () => {
    if (!selected || loading || saving || error || !canRecord) return;
    if (rows.some(row => row.status !== 'present' && !row.note?.trim())) { Alert.alert('Add attendance details', 'Enter a reason for absent or excused learners and minutes for late learners.'); return; }
    if (rows.some(row => row.status === 'late' && (!/^\d+$/.test(row.note ?? '') || Number(row.note) < 1 || Number(row.note) > 1440))) { Alert.alert('Check minutes late', 'Enter a whole number from 1 to 1440.'); return; }
    setSaving(true);
    try {
      const response = await api.post<{ saved: number; version: string }>('/attendance', token, { school_class_id: selected.school_class_id, subject_id: selected.subject_id, attendance_date: date, session_key: selected.session_key, stream_id: selected.attendance_type === 'daily' ? (streamId ? Number(streamId) : null) : selected.stream_id, base_version: version, records: rows.map(row => ({ student_id: row.student.id, status: row.status, note: row.note })) });
      setVersion(response.data.version); Alert.alert('Attendance saved', `${response.data.saved} learner records were saved.`);
    } catch (e) { Alert.alert(e instanceof ApiError && e.status === 409 ? 'Register changed — reload before saving' : 'Could not save', e instanceof Error ? e.message : 'Please try again.'); }
    finally { setSaving(false); }
  };
  useEffect(() => {
    const handler = BackHandler.addEventListener('hardwareBackPress', () => {
      if (performance) setPerformance(false); else onBack();
      return true;
    });
    return () => handler.remove();
  }, [performance, onBack]);

  if (performance && selected) return <TeacherAttendancePerformance token={token} classId={selected.school_class_id} streamId={streamId} onBack={() => setPerformance(false)} />;
  return <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
    <View style={styles.hero}><View pointerEvents="none" accessible={false} accessibilityElementsHidden importantForAccessibility="no-hide-descendants" style={styles.headerArtwork}><StatArtwork name="attendance" color={colors.secondary} /></View><View style={styles.headerCopy}><Text style={styles.title}>{mode === 'daily' ? 'Daily class register' : mode === 'subject' ? 'Subject attendance' : 'Take attendance'}</Text><Text style={styles.heroText}>Your assigned classes and registers</Text></View></View>
    <View style={styles.panel} pointerEvents={saving ? 'none' : 'auto'}>
      <Text style={styles.bold}>Register</Text><InlineSelect label="Register" value={selectedKey} options={assignments.map(item => ({ value: keyFor(item), label: `${item.class_name} · ${item.subject_name}${item.stream_name ? ` · ${item.stream_name}` : ''}${item.starts_at ? ` · ${item.starts_at.slice(0, 5)}–${item.ends_at?.slice(0, 5)}` : ''}` }))} onChange={value => { setSelectedKey(String(value)); setStreamId(''); }} />
      {selected?.attendance_type === 'daily' && <InlineSelect label="Stream" value={streamId} options={[{ value: '', label: 'All streams' }, ...streams.filter(stream => stream.school_class_id === selected.school_class_id).map(stream => ({ value: String(stream.id), label: stream.name }))]} onChange={value => setStreamId(String(value))} />}
      <Text style={styles.bold}>Attendance date</Text><InlineDatePicker value={draftDate} onChange={setDraftDate} />
      <Pressable accessibilityRole="button" onPress={() => { if (!/^\d{4}-\d{2}-\d{2}$/.test(draftDate) || Number.isNaN(new Date(`${draftDate}T12:00:00`).getTime())) { Alert.alert('Invalid date', 'Use YYYY-MM-DD.'); return; } setDate(draftDate); setReload(value => value + 1); }} style={({ pressed }) => [styles.button, pressed && styles.pressed]}><Text style={styles.buttonText}>Load register</Text></Pressable>
    </View>
    {!!error && <View style={styles.panel}><Text style={styles.error}>{error}</Text><Pressable onPress={() => setReload(value => value + 1)} style={({ pressed }) => [styles.outline, pressed && styles.pressed]}><Text style={styles.bold}>Retry</Text></Pressable></View>}
    {loading ? <BrandLoader /> : !error && !rows.length ? <Text style={styles.muted}>{assignments.length ? 'No active learners in this register.' : 'No registers are assigned to you for this attendance type.'}</Text> : null}
    {!loading && !error && rows.length > 0 && <>
      {!canRecord && <Text style={styles.error}>Attendance can only be saved in an open term.</Text>}
      <View style={[styles.searchField, searchFocused && styles.searchFocused]}><View pointerEvents="none" style={styles.searchIcon}><AppSvg name="search" size={21} color={colors.primary} /></View><TextInput accessibilityLabel="Find learner" placeholder="Search name or admission number" placeholderTextColor={colors.textMuted} value={search} onChangeText={setSearch} onFocus={() => setSearchFocused(true)} onBlur={() => setSearchFocused(false)} style={styles.searchInput} /></View>
      <Pressable accessibilityRole="button" onPress={() => setShowPhotos(value => !value)} style={({ pressed }) => [styles.outline, pressed && styles.pressed]}><Text style={styles.bold}>{showPhotos ? 'Hide' : 'Show'} learner photos</Text></Pressable>
      <Pressable accessibilityRole="button" disabled={saving || !canRecord} onPress={() => setRows(current => current.map(row => ({ ...row, status: 'present', note: '' })))} style={({ pressed }) => [styles.outline, pressed && styles.pressed]}><Text style={styles.bold}>Mark all present</Text></Pressable>
      <View style={styles.summary}>{statuses.map(status => <View key={status} style={styles.summaryItem}><Text style={styles.summaryCount}>{rows.filter(row => row.status === status).length}</Text><Text style={styles.small}>{status.charAt(0).toUpperCase() + status.slice(1)}</Text></View>)}</View>
      {rows.filter(row => `${row.student.name} ${row.student.admission_no}`.toLowerCase().includes(search.trim().toLowerCase())).map(row => <View key={row.student.id} style={styles.panel} pointerEvents={saving ? 'none' : 'auto'}>
        {showPhotos && row.student.photo_url && <Image source={{ uri: row.student.photo_url }} accessibilityLabel={`${row.student.name} photo`} style={{ width: 56, height: 56, borderRadius: 28 }} />}
        <Text style={styles.bold}>{row.student.name}</Text><Text style={styles.muted}>{row.student.admission_no}</Text>
        <View style={styles.statusRow}>{statuses.map(status => <Pressable key={status} accessibilityRole="button" accessibilityState={{ selected: row.status === status }} onPress={() => setRows(current => current.map(item => item.student.id === row.student.id ? { ...item, status, note: '' } : item))} style={({ pressed }) => [styles.status, { backgroundColor: statusColors[status].background, borderColor: row.status === status ? statusColors[status].text : 'transparent' }, pressed && styles.pressed]}><Text style={[styles.small, { color: statusColors[status].text, fontWeight: row.status === status ? '700' : '500' }]}>{status.charAt(0).toUpperCase() + status.slice(1)}</Text></Pressable>)}</View>
        {row.status !== 'present' && <TextInput accessibilityLabel={`${row.student.name} attendance details`} placeholder={row.status === 'late' ? 'Minutes late (e.g. 15)' : 'Reason for absence / excuse'} keyboardType={row.status === 'late' ? 'number-pad' : 'default'} value={row.note ?? ''} onChangeText={note => setRows(current => current.map(item => item.student.id === row.student.id ? { ...item, note } : item))} style={styles.input} />}
      </View>)}
      <Pressable accessibilityRole="button" disabled={saving || !canRecord || draftDate !== date} onPress={() => void save()} style={[styles.button, (saving || !canRecord || draftDate !== date) && { opacity: 0.5 }]}><Text style={styles.buttonText}>{saving ? 'Saving…' : `Save ${rows.length} records`}</Text></Pressable>
    </>}
  </ScrollView>;
}
const baseStyles = StyleSheet.create({
  headerArtwork: { position: 'absolute', right: 12, top: 12, width: 86, height: 92, opacity: 0.25 },
  headerCopy: { paddingRight: 70, gap: 8 },
  searchField: { minHeight: 54, flexDirection: 'row', alignItems: 'center', gap: 10, paddingHorizontal: 15, borderWidth: 1, borderColor: colors.border, borderRadius: 16, backgroundColor: colors.surface },
  searchFocused: { borderColor: colors.secondary, boxShadow: '0 0 0 3px rgba(239,176,0,0.15)' },
  searchIcon: { opacity: 0.35 },
  searchInput: { flex: 1, minWidth: 0, paddingVertical: 14, color: colors.primary, fontSize: 13 },
  pressed: { opacity: 0.65 },
  content: { padding: 18, gap: 14, paddingBottom: 40 }, back: { flexDirection: 'row', alignItems: 'center', gap: 8, minHeight: 44 }, bold: { color: colors.primary, fontWeight: '700', fontSize: 14 },
  hero: { backgroundColor: colors.primary, borderRadius: radius.lg, padding: 22, minHeight: 118, overflow: 'hidden' }, title: { color: colors.accent, fontSize: 23, fontWeight: '800' }, heroText: { color: '#D8E2F2', fontSize: 12 },
  panel: { backgroundColor: colors.surface, borderRadius: radius.md, borderWidth: 1, borderColor: '#DDE4EF', padding: 16, gap: 10 }, input: { borderWidth: 1, borderColor: '#DDE4EF', borderRadius: radius.sm, padding: 12, color: colors.primary, backgroundColor: colors.surface },
  outline: { minHeight: 52, backgroundColor: colors.secondary, borderRadius: 999, paddingHorizontal: 18, paddingVertical: 13, alignItems: 'center', justifyContent: 'center' }, button: { minHeight: 54, backgroundColor: colors.primary, paddingHorizontal: 18, paddingVertical: 15, borderRadius: 999, alignItems: 'center', justifyContent: 'center' }, buttonText: { color: colors.secondary, fontWeight: '600', fontSize: 14, textAlign: 'center' },
  muted: { color: colors.textSecondary, fontSize: 12 }, error: { color: colors.status.absent.text }, summary: { flexDirection: 'row', backgroundColor: colors.surface, borderRadius: radius.md, padding: 14 }, summaryItem: { flex: 1, alignItems: 'center' }, summaryCount: { color: colors.primary, fontSize: 22, fontWeight: '800' }, small: { fontSize: 10, fontWeight: '600', color: colors.primary }, statusRow: { flexDirection: 'row', gap: 5 }, status: { flex: 1, minHeight: 44, paddingVertical: 12, alignItems: 'center', justifyContent: 'center', borderRadius: 999, borderWidth: 1.5 },
});

const styles = baseStyles;
