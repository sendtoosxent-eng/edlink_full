import { NativeStudentProfile } from './NativeStudentProfile';
import { useCallback, useEffect, useRef, useState } from 'react';
import { Pressable, ScrollView, StyleSheet, View } from 'react-native';
import { Text, TextInput } from '../../components/Typography';
import { api } from '../../api';
import { colors } from '../../theme/index';

type Learner = { id: number; name: string; admission_no: string; status: string; school_class?: { name: string }; stream?: { name: string } };
export function TeacherDirectoryScreen({ token, onBack }: { token: string; onBack: () => void }) {
  const [selected, setSelected] = useState<number>();
  const requestId = useRef(0);
  const [items, setItems] = useState<Learner[]>([]); const [search, setSearch] = useState('');
  const [page, setPage] = useState(1); const [last, setLast] = useState(1); const [busy, setBusy] = useState(false); const [error, setError] = useState('');
  const load = useCallback(async (next = 1) => { const id = ++requestId.current; setBusy(true); setError(''); try {
    const result = await api.get<{ data: Learner[]; last_page: number }>(`/teacher/students?page=${next}&search=${encodeURIComponent(search.trim())}`, token);
    if (id !== requestId.current) return;
    setItems(result.data.data); setPage(next); setLast(result.data.last_page);
  } catch (e) { if (id !== requestId.current) return; setError(e instanceof Error ? e.message : 'Unable to load learners.'); } finally { if (id === requestId.current) setBusy(false); } }, [token, search]);
  useEffect(() => { const timer = setTimeout(() => void load(), 300); return () => { clearTimeout(timer); requestId.current++; }; }, [load]);
  if (selected) return <NativeStudentProfile token={token} id={selected} onBack={() => setSelected(undefined)} />;
  return <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
    <Pressable onPress={onBack}><Text style={styles.back}>← My Classes</Text></Pressable><Text style={styles.title}>My class students</Text>
    <TextInput accessibilityLabel="Search learners" value={search} onChangeText={setSearch} placeholder="Search name or admission number" style={styles.input} />
    {!!error && <Text>{error}</Text>}{busy && <Text>Loading learners…</Text>}
    {items.map(item => <Pressable key={item.id} onPress={() => setSelected(item.id)} style={styles.card}><Text style={styles.name}>{item.name}</Text><Text style={styles.meta}>{item.admission_no} · {item.school_class?.name}{item.stream ? ` · ${item.stream.name}` : ''}</Text><Text style={styles.meta}>{item.status}</Text></Pressable>)}
    {!busy && !error && !items.length && <Text>No matching learners in your assigned classes.</Text>}
    <View style={styles.row}><Pressable disabled={busy || page <= 1} onPress={() => void load(page - 1)}><Text style={styles.back}>Previous</Text></Pressable><Text>{page} / {last}</Text><Pressable disabled={busy || page >= last} onPress={() => void load(page + 1)}><Text style={styles.back}>Next</Text></Pressable></View>
    <Pressable disabled={busy} onPress={() => void load(page)}><Text style={styles.back}>Refresh</Text></Pressable>
  </ScrollView>;
}
const styles = StyleSheet.create({ content: { padding: 20, gap: 16 }, title: { fontSize: 27, fontWeight: '800', color: colors.primary }, back: { color: colors.primary, fontWeight: '700', paddingVertical: 12 }, input: { backgroundColor: 'white', borderRadius: 15, padding: 15, color: colors.primary }, card: { backgroundColor: 'white', borderRadius: 17, padding: 18, gap: 5 }, name: { color: colors.primary, fontSize: 16, fontWeight: '700' }, meta: { color: colors.textMuted, fontSize: 12 }, row: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' } });
