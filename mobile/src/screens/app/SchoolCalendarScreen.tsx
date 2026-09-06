import { useCallback, useEffect, useState } from 'react';
import { Pressable, RefreshControl, ScrollView, StyleSheet, View } from 'react-native';
import { Text } from '../../components/Typography';
import { ErrorScreen } from '../../components/ErrorScreen';
import { api } from '../../api';
import { colors } from '../../theme/index';

type Event = { id: number; title: string; event_date: string; description?: string; event_type?: string };
type Page = { data: Event[]; current_page: number; last_page: number };
export function SchoolCalendarScreen({ token, onBack }: { token: string; onBack: () => void }) {
  const [page, setPage] = useState<Page>(); const [busy, setBusy] = useState(false); const [error, setError] = useState('');
  const load = useCallback(async (number = 1) => { setBusy(true); setError(''); try { setPage((await api.get<Page>(`/events?page=${number}`, token)).data); } catch (e) { setError(e instanceof Error ? e.message : 'Unable to load the school calendar.'); } finally { setBusy(false); } }, [token]);
  useEffect(() => { void load(); }, [load]);
  if (error) return <ErrorScreen message={error} retry={() => load(page?.current_page)} onBack={onBack} />;
  return <ScrollView contentContainerStyle={styles.content} refreshControl={<RefreshControl refreshing={busy} onRefresh={() => void load()} tintColor={colors.primary} />}><Pressable onPress={onBack}><Text style={styles.link}>← Dashboard</Text></Pressable><Text style={styles.title}>School calendar</Text><Text style={styles.meta}>Upcoming dates for your school.</Text>{page?.data.map(event => <View key={event.id} style={styles.card}><Text style={styles.date}>{new Date(`${event.event_date.slice(0, 10)}T12:00:00`).toLocaleDateString(undefined, { weekday: 'long', day: 'numeric', month: 'long' })}</Text><Text style={styles.heading}>{event.title}</Text>{!!event.description && <Text style={styles.meta}>{event.description}</Text>}</View>)}{!busy && !page?.data.length && <Text style={styles.meta}>No upcoming events yet.</Text>}{page && page.last_page > 1 && <View style={styles.row}><Pressable disabled={busy || page.current_page === 1} onPress={() => void load(page.current_page - 1)}><Text style={styles.link}>Previous</Text></Pressable><Text style={styles.meta}>{page.current_page} / {page.last_page}</Text><Pressable disabled={busy || page.current_page === page.last_page} onPress={() => void load(page.current_page + 1)}><Text style={styles.link}>Next</Text></Pressable></View>}</ScrollView>;
}
const styles = StyleSheet.create({ content: { padding: 22, gap: 16, paddingBottom: 40 }, title: { fontSize: 28, fontWeight: '800', color: colors.primary }, link: { color: colors.primary, paddingVertical: 10, fontWeight: '600' }, meta: { color: colors.textMuted, lineHeight: 22, fontSize: 13 }, card: { backgroundColor: 'white', borderRadius: 20, padding: 22, gap: 12 }, date: { color: colors.primary, fontWeight: '600', fontSize: 12 }, heading: { color: colors.primary, fontSize: 18, fontWeight: '700' }, row: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' } });
