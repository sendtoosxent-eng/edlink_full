import { useThemedStyles } from '../../theme/ThemeProvider';
import { useEffect, useState } from 'react';
import { Pressable, ScrollView, StyleSheet, View } from 'react-native';
import { api } from '../../api';
import { Text, TextInput } from '../../components/Typography';
import { NativeAttachment } from '../../components/NativeAttachment';
import { colors } from '../../theme/index';

type Row = { name: string; admission_no: string; class: string; total: number; present: number; absent: number; late: number; excused: number; rate: number };
export function TeacherAttendancePerformance({ token, classId, streamId, onBack }: { token: string; classId: number; streamId: string; onBack: () => void }) {
  const styles = useThemedStyles(baseStyles);
  const date = (value: Date) => `${value.getFullYear()}-${String(value.getMonth() + 1).padStart(2, '0')}-${String(value.getDate()).padStart(2, '0')}`;
  const [from, setFrom] = useState(() => date(new Date(new Date().getFullYear(), new Date().getMonth(), 1)));
  const [to, setTo] = useState(() => date(new Date()));
  const buildQuery = () => `from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}&school_class_id=${classId}${streamId ? `&stream_id=${streamId}` : ''}`;
  const [query, setQuery] = useState(buildQuery);
  const [rows, setRows] = useState<Row[]>();
  const [search, setSearch] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');
  const [reload, setReload] = useState(0);
  useEffect(() => {
    let active = true;
    setBusy(true); setError(''); setRows(undefined);
    void api.get<Row[]>(`/attendance/performance?${query}`, token).then(response => { if (active) setRows(response.data); }).catch(e => { if (active) setError(e instanceof Error ? e.message : 'Unable to load attendance performance.'); }).finally(() => { if (active) setBusy(false); });
    return () => { active = false; };
  }, [token, query, reload]);
  return <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
    <Pressable accessibilityRole="button" onPress={onBack}><Text style={styles.action}>← Back to register</Text></Pressable>
    <Text style={styles.title}>Learner attendance performance</Text>
    <Text style={styles.meta}>Selected class and stream · Present and late count toward attendance rate. Learners with the lowest rate appear first.</Text>
    <TextInput accessibilityLabel="Performance from date" value={from} onChangeText={setFrom} placeholder="From YYYY-MM-DD" style={styles.input} />
    <TextInput accessibilityLabel="Performance to date" value={to} onChangeText={setTo} placeholder="To YYYY-MM-DD" style={styles.input} />
    <Pressable accessibilityRole="button" disabled={busy} onPress={() => { setQuery(buildQuery()); setReload(value => value + 1); }}><Text style={styles.action}>Apply dates / refresh</Text></Pressable>
    {!!error && <Text style={styles.error}>{error}</Text>}
    {busy && <Text style={styles.meta}>Loading performance…</Text>}
    {rows && <>
      {query === buildQuery() ? <NativeAttachment token={token} path={`/attendance/performance/csv?${query}`} name="attendance-performance.csv" /> : <Text style={styles.meta}>Apply changed dates before exporting.</Text>}
      <TextInput accessibilityLabel="Find learner performance" value={search} onChangeText={setSearch} placeholder="Search name or admission number" style={styles.input} />
      {rows.filter(row => `${row.name} ${row.admission_no}`.toLowerCase().includes(search.trim().toLowerCase())).map(row => <View key={row.admission_no + row.name} style={styles.card}>
        <Text style={styles.name}>{row.name}</Text><Text style={styles.meta}>{row.admission_no} · {row.class}</Text>
        <Text style={styles.title}>{row.rate}%</Text>
        <Text style={styles.meta}>{row.total} recorded days · {row.present} present / late · {row.absent} absent · {row.excused} excused</Text>
      </View>)}
      {!rows.length && <Text style={styles.meta}>No active enrolled learners in this class and stream.</Text>}
    </>}
  </ScrollView>;
}
const baseStyles = StyleSheet.create({ content: { padding: 20, gap: 16, paddingBottom: 40 }, title: { fontSize: 24, fontWeight: '800', color: colors.primary }, name: { color: colors.primary, fontWeight: '700', fontSize: 15 }, action: { color: colors.primary, fontWeight: '700', paddingVertical: 14 }, meta: { color: colors.textMuted, lineHeight: 20, fontSize: 12 }, input: { borderWidth: 1, borderColor: colors.border, backgroundColor: colors.surface, borderRadius: 14, padding: 14, color: colors.primary }, card: { backgroundColor: colors.surface, padding: 18, borderRadius: 18, gap: 8 }, error: { color: colors.status.absent.text } });

const styles = baseStyles;
