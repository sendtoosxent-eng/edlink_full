import { useEffect, useState } from 'react';
import { Pressable, RefreshControl, ScrollView, StyleSheet, View } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import * as FileSystem from 'expo-file-system/legacy';
import * as Sharing from 'expo-sharing';
import { api, API_URL } from '../../api';
import { Text, TextInput } from '../../components/Typography';
import { NativeSelect } from '../../components/NativeSelect';
import { EdlinkAlert as Alert } from '../../components/EdlinkAlert';
import { BrandLoader } from '../../components/BrandLoader';
import { colors, radius } from '../../theme/index';

const statuses = ['present', 'late', 'absent', 'excused', 'total'] as const;
type Summary = { class: string; groups: Record<'male' | 'female', Record<typeof statuses[number], number>>; unspecified: number; total: number };
type Option = { id: number; name: string; school_class_id?: number };
type Report = { classes: Summary[]; categories: Summary[]; counts: Record<string, number>; options: { classes: Option[]; streams: Option[]; subjects: Option[] }; records: { data: Array<{ id: number; name: string; date: string; subject: string; status: string; class_name?: string }>; current_page: number; last_page: number } };
const dateValue = (date: Date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;

export function TeacherAttendanceReportScreen({ token, onBack }: { token: string; onBack: () => void }) {
  const [from, setFrom] = useState(() => dateValue(new Date(new Date().getFullYear(), new Date().getMonth(), 1)));
  const [to, setTo] = useState(() => dateValue(new Date()));
  const [classId, setClassId] = useState(''); const [streamId, setStreamId] = useState('');
  const [subject, setSubject] = useState('daily'); const [status, setStatus] = useState('all'); const [search, setSearch] = useState('');
  const buildQuery = () => `from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}&subject=${encodeURIComponent(subject)}&status=${status}&search=${encodeURIComponent(search)}${classId ? `&school_class_id=${classId}` : ''}${streamId ? `&stream_id=${streamId}` : ''}`;
  const [query, setQuery] = useState(buildQuery);
  const [page, setPage] = useState(1); const [reload, setReload] = useState(0);
  const [data, setData] = useState<Report>(); const [options, setOptions] = useState<Report['options']>();
  const [loading, setLoading] = useState(true); const [exporting, setExporting] = useState(false); const [error, setError] = useState('');
  useEffect(() => {
    let active = true; setLoading(true); setError(''); setData(undefined);
    void api.get<Report>(`/teacher/attendance-summary?${query}&page=${page}`, token).then(({ data }) => { if (active) { setData(data); setOptions(data.options); } }).catch(e => { if (active) setError(e.message); }).finally(() => { if (active) setLoading(false); });
    return () => { active = false; };
  }, [query, page, token, reload]);
  const apply = () => {
    if (![from, to].every(value => /^\d{4}-\d{2}-\d{2}$/.test(value) && !Number.isNaN(new Date(`${value}T12:00:00`).getTime())) || to < from) { Alert.alert('Check the reporting dates', 'Use YYYY-MM-DD and an end date on or after the start date.'); return; }
    setQuery(buildQuery()); setPage(1); setReload(value => value + 1);
  };
  const download = async () => {
    if (!data || loading) return;
    setExporting(true);
    const path = `${FileSystem.cacheDirectory}attendance-summary-${Date.now()}.xlsx`;
    try {
      if (!await Sharing.isAvailableAsync()) throw new Error('File sharing is not available on this device.');
      const file = await FileSystem.downloadAsync(`${API_URL}/teacher/attendance-summary/excel?${query}`, path, { headers: { Authorization: `Bearer ${token}`, Accept: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' } });
      if (file.status !== 200) throw new Error('Unable to download the report. Please refresh and try again.');
      await Sharing.shareAsync(file.uri, { mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', UTI: 'org.openxmlformats.spreadsheetml.sheet', dialogTitle: 'Attendance summary' });
    } catch (e) { Alert.alert('Excel unavailable', e instanceof Error ? e.message : 'Please try again.'); }
    finally { await FileSystem.deleteAsync(path, { idempotent: true }).catch(() => undefined); setExporting(false); }
  };
  const selectOptions = (values: Option[] = [], label: string) => [{ value: '', label }, ...values.map(item => ({ value: String(item.id), label: item.name }))];
  return <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled" refreshControl={<RefreshControl refreshing={loading} onRefresh={() => setReload(value => value + 1)} tintColor={colors.primary} />}>
    <Pressable accessibilityRole="button" onPress={onBack} style={styles.back}><Ionicons name="arrow-back" size={20} color={colors.primary} /><Text style={styles.bold}>Dashboard</Text></Pressable>
    <View style={styles.hero}><Text style={styles.heroTitle}>Attendance reports</Text><Text style={styles.heroCopy}>Your assigned classes · class and category summaries</Text></View>
    <View style={styles.panel}>
      <Text style={styles.bold}>Reporting period (YYYY-MM-DD)</Text>
      <TextInput accessibilityLabel="From date" value={from} onChangeText={setFrom} style={styles.input} placeholder="From date" /><TextInput accessibilityLabel="To date" value={to} onChangeText={setTo} style={styles.input} placeholder="To date" />
      <NativeSelect label="Class" value={classId} options={selectOptions(options?.classes, 'All my classes')} onChange={value => { setClassId(String(value)); setStreamId(''); }} />
      <NativeSelect label="Stream" value={streamId} options={selectOptions(options?.streams.filter(item => !classId || String(item.school_class_id) === classId), 'All streams')} onChange={value => setStreamId(String(value))} />
      <NativeSelect label="Session" value={subject} options={[{ value: 'all', label: 'All sessions' }, { value: 'daily', label: 'Daily register' }, ...(options?.subjects ?? []).map(item => ({ value: String(item.id), label: item.name }))]} onChange={value => setSubject(String(value))} />
      <NativeSelect label="Status" value={status} options={[{ value: 'all', label: 'All statuses' }, ...statuses.filter(value => value !== 'total').map(value => ({ value, label: value.charAt(0).toUpperCase() + value.slice(1) }))]} onChange={value => setStatus(String(value))} />
      <TextInput accessibilityLabel="Find learner" value={search} onChangeText={setSearch} style={styles.input} placeholder="Learner name or admission number" />
      <Pressable accessibilityRole="button" onPress={apply} style={styles.button}><Text style={styles.buttonText}>Apply filters</Text></Pressable>
    </View>
    {buildQuery() !== query && <Text style={styles.muted}>Apply your changed filters to update the report and Excel download.</Text>}
    {!!error && <View style={styles.panel}><Text style={styles.error}>{error}</Text><Pressable onPress={() => setReload(value => value + 1)} style={styles.button}><Text style={styles.buttonText}>Retry</Text></Pressable></View>}
    {loading && <BrandLoader />}
    {data && <>
      <Pressable accessibilityRole="button" disabled={exporting || buildQuery() !== query} onPress={() => void download()} style={[styles.button, (exporting || buildQuery() !== query) && { opacity: 0.5 }]}><Text style={styles.buttonText}>{exporting ? 'Preparing Excel…' : 'Download / share Excel (.xlsx)'}</Text></Pressable>
      <Text style={styles.muted}>Counts are attendance records, not unique learners. Select one date and Daily register for a daily headcount. Unmarked learners are not counted as absent. Swipe tables sideways to view all columns.</Text>
      <SummaryTable title="Attendance by class" rows={data.classes} />
      <SummaryTable title="Attendance by current student category" rows={data.categories} />
      <View style={styles.panel}><Text style={styles.bold}>Attendance records</Text>{data.records.data.map(row => <View key={row.id} style={styles.record}><Text style={styles.bold}>{row.name}</Text><Text style={styles.muted}>{row.date} · {row.class_name} · {row.subject}</Text><Text style={styles.muted}>{row.status.charAt(0).toUpperCase() + row.status.slice(1)}</Text></View>)}{!data.records.data.length && <Text style={styles.muted}>No records match these filters.</Text>}
        <View style={styles.pagination}><Pressable accessibilityRole="button" disabled={page <= 1} onPress={() => setPage(value => value - 1)}><Text style={[styles.bold, page <= 1 && styles.disabled]}>Previous</Text></Pressable><Text style={styles.muted}>{data.records.current_page} / {data.records.last_page}</Text><Pressable accessibilityRole="button" disabled={page >= data.records.last_page} onPress={() => setPage(value => value + 1)}><Text style={[styles.bold, page >= data.records.last_page && styles.disabled]}>Next</Text></Pressable></View>
      </View>
    </>}
  </ScrollView>;
}
function SummaryTable({ title, rows }: { title: string; rows: Summary[] }) {
  const flatten = (row: Summary) => [row.class, ...(['male', 'female'] as const).flatMap(gender => statuses.map(status => row.groups[gender][status])), row.unspecified, row.total];
  const values = rows.map(flatten);
  const totals = ['Grand total', ...Array.from({ length: 12 }, (_, index) => values.reduce((sum, row) => sum + Number(row[index + 1]), 0))];
  const headings = ['Class / category', ...['Boys', 'Girls'].flatMap(gender => statuses.map(status => `${gender}\n${status.charAt(0).toUpperCase() + status.slice(1)}`)), 'Unspecified\ngender', 'Total'];
  return <View style={styles.tablePanel}><Text style={[styles.bold, { padding: 16 }]}>{title}</Text><ScrollView horizontal showsHorizontalScrollIndicator><View>
    <View style={styles.tableRow}>{headings.map((heading, index) => <Text key={index} style={[styles.cell, styles.tableHeader, index === 0 && styles.labelCell]}>{heading}</Text>)}</View>
    {values.map((row, rowIndex) => <View key={rowIndex} style={[styles.tableRow, rowIndex % 2 === 1 && { backgroundColor: colors.background }]}>{row.map((value, index) => <Text key={index} style={[styles.cell, index === 0 && styles.labelCell]}>{value}</Text>)}</View>)}
    <View style={styles.tableRow}>{totals.map((value, index) => <Text key={index} style={[styles.cell, styles.totalCell, index === 0 && styles.labelCell]}>{value}</Text>)}</View>
  </View></ScrollView>{!rows.length && <Text style={[styles.muted, { padding: 16 }]}>No matching records.</Text>}</View>;
}
const styles = StyleSheet.create({
  content: { padding: 18, gap: 16, paddingBottom: 40 }, back: { flexDirection: 'row', alignItems: 'center', gap: 8, minHeight: 44 }, bold: { color: colors.primary, fontWeight: '700', fontSize: 14 },
  hero: { backgroundColor: colors.primary, borderRadius: radius.lg, padding: 22, gap: 8 }, heroTitle: { color: colors.accent, fontSize: 24, fontWeight: '800' }, heroCopy: { color: '#D8E2F2', fontSize: 12 },
  panel: { backgroundColor: colors.surface, borderRadius: radius.md, padding: 16, gap: 12, borderWidth: 1, borderColor: '#DDE4EF' }, input: { borderWidth: 1, borderColor: '#DDE4EF', padding: 14, borderRadius: radius.sm, color: colors.primary },
  button: { backgroundColor: colors.primary, padding: 15, borderRadius: radius.sm, alignItems: 'center' }, buttonText: { color: colors.accent, fontWeight: '800', fontSize: 13 }, muted: { color: colors.textSecondary, fontSize: 11, lineHeight: 19 }, error: { color: colors.status.absent.text },
  tablePanel: { backgroundColor: colors.surface, borderRadius: radius.md, borderWidth: 1, borderColor: '#DDE4EF', overflow: 'hidden' }, tableRow: { flexDirection: 'row' }, cell: { width: 95, padding: 12, color: colors.primary, textAlign: 'center', fontSize: 11, borderRightWidth: 1, borderBottomWidth: 1, borderColor: '#DDE4EF' }, labelCell: { width: 175, textAlign: 'left', fontWeight: '700' }, tableHeader: { backgroundColor: colors.primary, color: colors.accent, fontWeight: '700' }, totalCell: { backgroundColor: '#FFF4D9', fontWeight: '800' }, record: { paddingVertical: 12, borderBottomWidth: 1, borderColor: '#DDE4EF', gap: 4 }, pagination: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', minHeight: 44 }, disabled: { opacity: 0.35 },
});
