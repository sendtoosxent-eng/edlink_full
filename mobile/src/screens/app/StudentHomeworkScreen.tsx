import { useCallback, useEffect, useRef, useState } from 'react';
import { Alert, Pressable, RefreshControl, ScrollView, StyleSheet, View } from 'react-native';
import { KeyboardAwareScrollView } from 'react-native-keyboard-controller';
import * as DocumentPicker from 'expo-document-picker';
import { Ionicons } from '@expo/vector-icons';
import { Text, TextInput } from '../../components/Typography';
import { ErrorScreen } from '../../components/ErrorScreen';
import { BrandLoader } from '../../components/BrandLoader';
import { NativeAttachment } from '../../components/NativeAttachment';
import { api } from '../../api';
import { colors } from '../../theme/index';

type Submission = { id: number; answer?: string; status: string; updated_at: string; score?: number | null; feedback?: string | null; attachment_name?: string | null };
type Homework = { id: number; title: string; instructions?: string; due_at?: string; maximum_score: number; subject?: { name: string }; attachment_name?: string; submissions?: Submission[] };
type Page = { data: Homework[]; current_page: number; last_page: number };
const FILTERS = [{ key: 'pending', label: 'To do' }, { key: 'submitted', label: 'Submitted' }, { key: 'reviewed', label: 'Reviewed' }, { key: 'all', label: 'All' }];
export function StudentHomeworkScreen({ token }: { token: string }) {
  const [filter, setFilter] = useState('pending'); const [page, setPage] = useState<Page>(); const [selected, setSelected] = useState<number>();
  const [busy, setBusy] = useState(true); const [error, setError] = useState(''); const serial = useRef(0);
  const load = useCallback(async (number = 1) => { const id = ++serial.current; setBusy(true); setError(''); try { const response = await api.get<Page>(`/homework?status=${filter}&page=${number}`, token); if (id === serial.current) setPage(response.data); } catch (e) { if (id === serial.current) setError(e instanceof Error ? e.message : 'Unable to load homework.'); } finally { if (id === serial.current) setBusy(false); } }, [token, filter]);
  useEffect(() => { void load(); return () => { serial.current++; }; }, [load]);
  if (selected) return <HomeworkDetail token={token} id={selected} onBack={() => { setSelected(undefined); void load(); }} />;
  if (error) return <ErrorScreen message={error} retry={() => load()} />;
  if (busy && !page) return <BrandLoader />;
  return <ScrollView contentContainerStyle={styles.content} refreshControl={<RefreshControl refreshing={busy} onRefresh={() => void load()} tintColor={colors.primary} />}>
    <Text style={styles.title}>My homework</Text><Text style={styles.meta}>Your assignments, answers and teacher feedback.</Text>
    <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.filters}>{FILTERS.map(item => <Pressable accessibilityRole="button" accessibilityState={{ selected: filter === item.key }} key={item.key} onPress={() => { if (filter !== item.key) { setBusy(true); setPage(undefined); setFilter(item.key); } }} style={[styles.chip, filter === item.key && styles.active]}><Text style={{ color: filter === item.key ? 'white' : colors.primary, fontWeight: '600' }}>{item.label}</Text></Pressable>)}</ScrollView>
    {!busy && !page?.data.length && <View style={styles.empty}><Ionicons name="checkmark-done-circle-outline" size={46} color={colors.primary} /><Text style={styles.heading}>{filter === 'pending' ? 'You’re all caught up' : 'Nothing here yet'}</Text><Text style={styles.meta}>{filter === 'pending' ? 'New assignments will appear here when your teachers post them.' : 'Your work will appear here as it is submitted and reviewed.'}</Text></View>}
    {page?.data.map(item => <Pressable accessibilityRole="button" key={item.id} onPress={() => setSelected(item.id)} style={styles.card}><View style={styles.row}><Text style={styles.subject}>{item.subject?.name ?? 'Homework'}</Text><Ionicons name="arrow-forward" size={20} color={colors.primary} /></View><Text style={styles.heading}>{item.title}</Text><Text style={styles.meta}>{item.due_at ? `Due ${new Date(item.due_at).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' })}` : 'No deadline set'}</Text><Text style={styles.status}>{item.submissions?.[0]?.status ?? 'To do'}</Text></Pressable>)}
    {page && page.last_page > 1 && <View style={styles.row}><Pressable disabled={busy || page.current_page === 1} onPress={() => void load(page.current_page - 1)}><Text style={styles.link}>Previous</Text></Pressable><Text style={styles.meta}>{page.current_page} / {page.last_page}</Text><Pressable disabled={busy || page.current_page === page.last_page} onPress={() => void load(page.current_page + 1)}><Text style={styles.link}>Next</Text></Pressable></View>}
  </ScrollView>;
}
function HomeworkDetail({ token, id, onBack }: { token: string; id: number; onBack: () => void }) {
  const [item, setItem] = useState<Homework>(); const [answer, setAnswer] = useState(''); const [file, setFile] = useState<DocumentPicker.DocumentPickerAsset>();
  const [busy, setBusy] = useState(false); const [error, setError] = useState(''); const [loadError, setLoadError] = useState('');
  const load = useCallback(async () => { setBusy(true); setLoadError(''); try { const data = (await api.get<Homework>(`/homework/${id}`, token)).data; setItem(data); setAnswer(data.submissions?.[0]?.answer ?? ''); } catch (e) { setLoadError(e instanceof Error ? e.message : 'Unable to load assignment.'); } finally { setBusy(false); } }, [id, token]);
  useEffect(() => { void load(); }, [load]);
  const submission = item?.submissions?.[0];
  const pick = async () => { try { const result = await DocumentPicker.getDocumentAsync({ copyToCacheDirectory: true }); if (!result.canceled) { if ((result.assets[0].size ?? 0) > 10 * 1024 * 1024) { setError('Choose a file smaller than 10 MB.'); return; } setFile(result.assets[0]); setError(''); } } catch { setError('Unable to choose your file.'); } };
  const submit = async () => { if (busy) return; setBusy(true); setError(''); try { const body = new FormData(); body.append('answer', answer); if (submission?.updated_at) body.append('base_version', submission.updated_at); if (file) body.append('attachment', { uri: file.uri, name: file.name, type: file.mimeType ?? 'application/octet-stream' } as unknown as Blob); await api.upload(`/homework/${id}/submit`, token, body); setFile(undefined); await load(); Alert.alert('Work submitted', 'Your teacher can now review your answer.'); } catch (e) { setError(e instanceof Error ? e.message : 'Unable to submit. Your answer is still here.'); } finally { setBusy(false); } };
  if (loadError) return <ErrorScreen message={loadError} retry={load} onBack={onBack} />;
  if (!item) return <BrandLoader />;
  return <KeyboardAwareScrollView bottomOffset={24} keyboardShouldPersistTaps="handled" contentContainerStyle={styles.content}>
    <Pressable disabled={busy} onPress={onBack}><Text style={styles.link}>← My homework</Text></Pressable><Text style={styles.subject}>{item.subject?.name ?? 'Homework'}</Text><Text style={styles.title}>{item.title}</Text><Text style={styles.meta}>{item.due_at ? `Due ${new Date(item.due_at).toLocaleString()}` : 'No deadline set'}</Text>
    <View style={styles.card}><Text style={styles.heading}>Instructions</Text><Text style={styles.body}>{item.instructions || 'Follow your teacher’s instructions.'}</Text><NativeAttachment token={token} path={`/homework/${id}/attachment`} name={item.attachment_name} /></View>
    {submission?.status === 'reviewed' && <View style={styles.card}><Text style={styles.heading}>Teacher feedback</Text><Text style={styles.score}>{submission.score ?? '—'} / {item.maximum_score}</Text><Text style={styles.body}>{submission.feedback || 'Your teacher has reviewed your work.'}</Text></View>}
    <Text style={styles.heading}>Your answer</Text><TextInput accessibilityLabel="Your homework answer" editable={!busy} multiline value={answer} onChangeText={setAnswer} placeholder="Write your answer here…" style={styles.answer} />
    <NativeAttachment token={token} path={`/homework/${id}/submissions/${submission?.id}/attachment`} name={submission?.attachment_name} />
    <Pressable disabled={busy} onPress={() => void pick()} style={styles.card}><Text style={styles.link}>{file ? file.name : 'Attach your work'}</Text><Text style={styles.meta}>Documents, photos or ZIP · Up to 10 MB</Text></Pressable>
    {file && <Pressable disabled={busy} onPress={() => setFile(undefined)}><Text style={styles.link}>Remove selected file</Text></Pressable>}
    {!!error && <Text accessibilityLiveRegion="polite" style={styles.error}>{error}</Text>}
    <Pressable accessibilityRole="button" disabled={busy || (!answer.trim() && !file && !submission?.attachment_name)} onPress={() => Alert.alert(submission ? 'Resubmit your work?' : 'Submit your work?', submission ? 'This replaces your previous answer and clears its review so your teacher can mark it again.' : 'Send this answer to your teacher?', [{ text: 'Cancel', style: 'cancel' }, { text: 'Submit', onPress: () => void submit() }])} style={[styles.button, busy && { opacity: 0.6 }]}><Text style={styles.buttonText}>{busy ? 'Please wait…' : submission ? 'Resubmit work' : 'Submit work'}</Text></Pressable>
  </KeyboardAwareScrollView>;
}
const styles = StyleSheet.create({ content: { padding: 22, gap: 16, paddingBottom: 40 }, title: { color: colors.primary, fontSize: 28, fontWeight: '800' }, meta: { color: colors.textMuted, fontSize: 12, lineHeight: 20 }, heading: { color: colors.primary, fontSize: 17, fontWeight: '700' }, filters: { gap: 8 }, chip: { paddingHorizontal: 18, paddingVertical: 12, borderRadius: 15, backgroundColor: 'white' }, active: { backgroundColor: colors.primary }, card: { backgroundColor: 'white', borderRadius: 20, padding: 20, gap: 12 }, row: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' }, subject: { color: colors.primary, fontSize: 12, fontWeight: '700' }, status: { color: colors.primary, textTransform: 'capitalize', fontWeight: '600', fontSize: 12 }, empty: { padding: 28, alignItems: 'center', gap: 16, backgroundColor: 'white', borderRadius: 22 }, link: { color: colors.primary, fontWeight: '600', paddingVertical: 9 }, body: { color: colors.primary, lineHeight: 24, fontSize: 14 }, score: { color: colors.primary, fontSize: 30, fontWeight: '800' }, answer: { backgroundColor: 'white', borderRadius: 18, padding: 18, minHeight: 180, textAlignVertical: 'top', color: colors.primary }, error: { color: '#B42318', lineHeight: 22 }, button: { backgroundColor: colors.primary, padding: 18, borderRadius: 18, alignItems: 'center' }, buttonText: { color: 'white', fontWeight: '700' } });
