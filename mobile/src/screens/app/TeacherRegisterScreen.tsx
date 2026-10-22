import { useEffect, useState } from 'react';
import { Pressable, ScrollView, StyleSheet, View } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { api, ApiError } from '../../api';
import { Text, TextInput } from '../../components/Typography';
import { NativeSelect } from '../../components/NativeSelect';
import { EdlinkAlert as Alert } from '../../components/EdlinkAlert';
import { BrandLoader } from '../../components/BrandLoader';
import { colors, radius } from '../../theme/index';
import type { Assignment, AttendanceRow, AttendanceStatus } from '../../types';

export type RegisterTarget = { school_class_id: number; subject_id: number | null; attendance_type: 'daily' | 'subject' };
const statuses: AttendanceStatus[] = ['present', 'absent', 'late', 'excused'];
const keyFor = (item: RegisterTarget) => `${item.school_class_id}:${item.subject_id ?? 'daily'}`;
const localDate = () => { const date = new Date(); return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`; };

export function TeacherRegisterScreen({ token, mode, target, onBack }: { token: string; mode?: 'daily' | 'subject'; target?: RegisterTarget; onBack: () => void }) {
  const [assignments, setAssignments] = useState<Assignment[]>([]);
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
    void api.get<Assignment[]>('/teaching-assignments', token).then(({ data }) => {
      if (!active) return;
      const available = data.filter(item => !mode || item.attendance_type === mode);
      setAssignments(available);
      setSelectedKey(target && available.some(item => keyFor(item) === keyFor(target)) ? keyFor(target) : available[0] ? keyFor(available[0]) : '');
      if (!available.length) setLoading(false);
    }).catch(e => { if (active) { setError(e.message); setLoading(false); } });
    return () => { active = false; };
  }, [token, mode, target, reload]);
  useEffect(() => {
    if (!selected) return;
    let active = true;
    setLoading(true); setRows([]); setVersion(undefined); setError('');
    const session = selected.attendance_type === 'daily' ? 'daily' : `subject:${selected.subject_id}`;
    const query = `school_class_id=${selected.school_class_id}&attendance_date=${date}&session_key=${encodeURIComponent(session)}${selected.subject_id ? `&subject_id=${selected.subject_id}` : ''}`;
    void api.get<Array<AttendanceRow & { note?: string }>>(`/attendance?${query}`, token).then(response => { if (active) { setRows(response.data); setVersion(response.meta?.version as string | undefined); } }).catch(e => { if (active) setError(e.message); }).finally(() => { if (active) setLoading(false); });
    return () => { active = false; };
  }, [selected, date, token, reload]);
  const save = async () => {
    if (!selected || loading || error) return;
    if (rows.some(row => row.status !== 'present' && !row.note?.trim())) { Alert.alert('Add attendance details', 'Enter a reason for absent or excused learners and minutes for late learners.'); return; }
    setSaving(true);
    try {
      const response = await api.post<{ saved: number; version: string }>('/attendance', token, { school_class_id: selected.school_class_id, subject_id: selected.subject_id, attendance_date: date, session_key: selected.attendance_type === 'daily' ? 'daily' : `subject:${selected.subject_id}`, base_version: version, records: rows.map(row => ({ student_id: row.student.id, status: row.status, note: row.note })) });
      setVersion(response.data.version); Alert.alert('Attendance saved', `${response.data.saved} learner records were saved.`);
    } catch (e) { Alert.alert(e instanceof ApiError && e.status === 409 ? 'Register changed — reload before saving' : 'Could not save', e instanceof Error ? e.message : 'Please try again.'); }
    finally { setSaving(false); }
  };
  return <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
    <Pressable accessibilityRole="button" onPress={onBack} style={styles.back}><Ionicons name="arrow-back" size={20} color={colors.primary} /><Text style={styles.bold}>Dashboard</Text></Pressable>
    <View style={styles.hero}><Text style={styles.title}>{mode === 'daily' ? 'Daily class register' : mode === 'subject' ? 'Subject attendance' : 'Take attendance'}</Text><Text style={styles.heroText}>Your assigned classes and registers</Text></View>
    <View style={styles.panel} pointerEvents={saving ? 'none' : 'auto'}>
      <Text style={styles.bold}>Register</Text><NativeSelect label="Register" value={selectedKey} options={assignments.map(item => ({ value: keyFor(item), label: `${item.class_name} · ${item.subject_name}` }))} onChange={value => setSelectedKey(String(value))} />
      <Text style={styles.bold}>Attendance date (YYYY-MM-DD)</Text><TextInput accessibilityLabel="Attendance date" value={draftDate} onChangeText={setDraftDate} style={styles.input} />
      <Pressable accessibilityRole="button" onPress={() => { if (!/^\d{4}-\d{2}-\d{2}$/.test(draftDate) || Number.isNaN(new Date(`${draftDate}T12:00:00`).getTime())) { Alert.alert('Invalid date', 'Use YYYY-MM-DD.'); return; } setDate(draftDate); setReload(value => value + 1); }} style={styles.outline}><Text style={styles.bold}>Load register</Text></Pressable>
    </View>
    {!!error && <View style={styles.panel}><Text style={styles.error}>{error}</Text><Pressable onPress={() => setReload(value => value + 1)} style={styles.outline}><Text style={styles.bold}>Retry</Text></Pressable></View>}
    {loading ? <BrandLoader /> : !error && !rows.length ? <Text style={styles.muted}>{assignments.length ? 'No active learners in this register.' : 'No registers are assigned to you for this attendance type.'}</Text> : null}
    {!loading && !error && rows.length > 0 && <>
      <View style={styles.summary}>{statuses.map(status => <View key={status} style={styles.summaryItem}><Text style={styles.summaryCount}>{rows.filter(row => row.status === status).length}</Text><Text style={styles.small}>{status.charAt(0).toUpperCase() + status.slice(1)}</Text></View>)}</View>
      {rows.map(row => <View key={row.student.id} style={styles.panel} pointerEvents={saving ? 'none' : 'auto'}>
        <Text style={styles.bold}>{row.student.name}</Text><Text style={styles.muted}>{row.student.admission_no}</Text>
        <View style={styles.statusRow}>{statuses.map(status => <Pressable key={status} accessibilityRole="button" accessibilityState={{ selected: row.status === status }} onPress={() => setRows(current => current.map(item => item.student.id === row.student.id ? { ...item, status, note: '' } : item))} style={[styles.status, { backgroundColor: row.status === status ? colors.status[status].bg : colors.background }]}><Text style={[styles.small, { color: row.status === status ? colors.status[status].text : colors.textSecondary }]}>{status.charAt(0).toUpperCase() + status.slice(1)}</Text></Pressable>)}</View>
        {row.status !== 'present' && <TextInput accessibilityLabel={`${row.student.name} attendance details`} placeholder={row.status === 'late' ? 'Minutes late (e.g. 15)' : 'Reason for absence / excuse'} keyboardType={row.status === 'late' ? 'number-pad' : 'default'} value={row.note ?? ''} onChangeText={note => setRows(current => current.map(item => item.student.id === row.student.id ? { ...item, note } : item))} style={styles.input} />}
      </View>)}
      <Pressable accessibilityRole="button" disabled={saving || draftDate !== date} onPress={() => void save()} style={[styles.button, (saving || draftDate !== date) && { opacity: 0.5 }]}><Text style={styles.buttonText}>{saving ? 'Saving…' : `Save ${rows.length} records`}</Text></Pressable>
    </>}
  </ScrollView>;
}
const styles = StyleSheet.create({
  content: { padding: 18, gap: 14, paddingBottom: 40 }, back: { flexDirection: 'row', alignItems: 'center', gap: 8, minHeight: 44 }, bold: { color: colors.primary, fontWeight: '700', fontSize: 14 },
  hero: { backgroundColor: colors.primary, borderRadius: radius.lg, padding: 22, gap: 8 }, title: { color: colors.accent, fontSize: 23, fontWeight: '800' }, heroText: { color: '#D8E2F2', fontSize: 12 },
  panel: { backgroundColor: colors.surface, borderRadius: radius.md, borderWidth: 1, borderColor: '#DDE4EF', padding: 16, gap: 10 }, input: { borderWidth: 1, borderColor: '#DDE4EF', borderRadius: radius.sm, padding: 12, color: colors.primary, backgroundColor: colors.surface },
  outline: { borderWidth: 1, borderColor: colors.primary, borderRadius: radius.sm, padding: 12, alignItems: 'center' }, button: { backgroundColor: colors.primary, padding: 16, borderRadius: radius.md, alignItems: 'center' }, buttonText: { color: colors.accent, fontWeight: '800' },
  muted: { color: colors.textSecondary, fontSize: 12 }, error: { color: colors.status.absent.text }, summary: { flexDirection: 'row', backgroundColor: colors.surface, borderRadius: radius.md, padding: 14 }, summaryItem: { flex: 1, alignItems: 'center' }, summaryCount: { color: colors.primary, fontSize: 22, fontWeight: '800' }, small: { fontSize: 10, fontWeight: '600', color: colors.primary }, statusRow: { flexDirection: 'row', gap: 5 }, status: { flex: 1, paddingVertical: 12, alignItems: 'center', borderRadius: radius.sm },
});
