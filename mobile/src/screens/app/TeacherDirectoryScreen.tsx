import { useThemedStyles } from '../../theme/ThemeProvider';
import { StudentPhoto } from '../../components/StudentPhoto';
import { NativeStudentProfile } from './NativeStudentProfile';
import { useCallback, useEffect, useRef, useState } from 'react';
import { Pressable, ScrollView, StyleSheet, View } from 'react-native';
import { AppSvg } from '../../components/AppSvg';
import { TeacherHero, TeacherSearch, TeacherButton, useTeacherBack, teacherScreenStyles } from '../../components/TeacherScreenUI';
import { Text } from '../../components/Typography';
import { api } from '../../api';
import { colors } from '../../theme/index';

type Learner = { id: number; name: string; admission_no: string; photo_url?: string | null; status: string; school_class?: { name: string }; stream?: { name: string } };
export function TeacherDirectoryScreen({ token, onBack }: { token: string; onBack: () => void }) {
  const styles = useThemedStyles(baseStyles);
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
  useTeacherBack(() => { if (selected) setSelected(undefined); else onBack(); });
  if (selected) return <NativeStudentProfile token={token} id={selected} onBack={() => setSelected(undefined)} />;
  return <ScrollView contentContainerStyle={teacherScreenStyles.content} keyboardShouldPersistTaps="handled">
    <TeacherHero title="My class students" subtitle="Find learners, view profiles and keep your class details up to date" artwork="learners" />
    <TeacherSearch label="Search learners" value={search} onChange={setSearch} placeholder="Search name or admission number" />
    {!!error && <Text accessibilityLiveRegion="polite" style={styles.error}>{error}</Text>}{busy && <Text style={styles.meta}>Loading learners…</Text>}
    {items.map(item => <Pressable accessibilityRole="button" key={item.id} onPress={() => setSelected(item.id)} style={({ pressed }) => [styles.card, pressed && { opacity: 0.65 }]}>
      <StudentPhoto uri={item.photo_url} name={item.name} size={60} />
      <View style={styles.copy}><Text style={styles.name}>{item.name}</Text><Text style={styles.meta}>{item.admission_no} · {item.school_class?.name ?? 'Class'}{item.stream ? ` · ${item.stream.name}` : ''}</Text><Text style={[styles.status, item.status !== 'active' && styles.inactive]}>{item.status}</Text></View><AppSvg name="chevron" size={17} />
    </Pressable>)}
    {!busy && !error && !items.length && <View style={teacherScreenStyles.panel}><Text style={styles.meta}>No matching learners in your assigned classes.</Text></View>}
    <View style={styles.row}><TeacherButton label="Previous" secondary disabled={busy || page <= 1} onPress={() => void load(page - 1)} style={styles.pageButton} /><Text style={styles.meta}>{page} / {last}</Text><TeacherButton label="Next" secondary disabled={busy || page >= last} onPress={() => void load(page + 1)} style={styles.pageButton} /></View>
    <TeacherButton label={busy ? 'Refreshing…' : 'Refresh learners'} disabled={busy} onPress={() => void load(page)} />
  </ScrollView>;
}
const baseStyles = StyleSheet.create({
  card: { minHeight: 110, backgroundColor: colors.surface, borderRadius: 18, padding: 16, borderWidth: 1, borderColor: colors.border, flexDirection: 'row', alignItems: 'center', gap: 12 }, avatar: { width: 48, height: 48, borderRadius: 24, backgroundColor: '#FFF5D5', alignItems: 'center', justifyContent: 'center' }, initials: { color: colors.primary, fontSize: 15, fontWeight: '600' }, copy: { flex: 1, minWidth: 0 }, name: { color: colors.primary, fontSize: 14, lineHeight: 20, fontWeight: '700' }, meta: { color: colors.textMuted, fontSize: 11, lineHeight: 18 }, status: { alignSelf: 'flex-start', borderRadius: 999, paddingHorizontal: 8, paddingVertical: 3, marginTop: 6, backgroundColor: '#E3F3E8', color: '#246B41', fontSize: 10 }, inactive: { backgroundColor: '#FFF4CE', color: '#876600' }, row: { flexDirection: 'row', alignItems: 'center', gap: 12 }, pageButton: { flex: 1 }, error: { color: '#B42318', fontSize: 12, lineHeight: 19 },
});

const styles = baseStyles;
