import { Text, TextInput } from '../../components/Typography';
import { Ionicons } from '@expo/vector-icons';
import { useCallback, useEffect, useState } from 'react';
import { Alert, Linking, Pressable, RefreshControl, ScrollView, StyleSheet, View } from 'react-native';
import { api, ApiError, API_URL } from '../../api';
import { PageIntro } from '../../components/PageIntro';
import { colors, radius, shadows } from '../../theme/index';

type Notice = { id: number; title?: string; subject?: string; message?: string; body?: string; sent_at?: string };

export function NotificationsScreen({ token, onBack }: { token: string; onBack: () => void }) {
  const [items, setItems] = useState<Notice[]>([]); const [loading, setLoading] = useState(true); const [error, setError] = useState('');
  const load = useCallback(async () => { setLoading(true); try { const response = await api.get<{ data: Notice[] }>('/announcements', token); setItems(response.data.data ?? []); setError(''); } catch (reason) { setError(message(reason)); } finally { setLoading(false); } }, [token]);
  useEffect(() => { void load(); }, [load]);
  return <ScrollView contentContainerStyle={styles.content} refreshControl={<RefreshControl refreshing={loading} onRefresh={load} tintColor={colors.primary} />}><Back onPress={onBack} /><PageIntro title="Notifications" description="School notices and updates for your account." />{error ? <Empty text={error} /> : items.map(item => <View key={item.id} style={[styles.card, shadows.card]}><View style={styles.icon}><Ionicons name="notifications-outline" size={20} color={colors.primary} /></View><View style={styles.grow}><Text style={styles.cardTitle}>{item.title ?? item.subject ?? 'School update'}</Text><Text style={styles.body}>{item.message ?? item.body ?? ''}</Text></View></View>)}{!loading && !error && !items.length && <Empty text="You’re all caught up." />}</ScrollView>;
}

export function LeaveRequestScreen({ token, onBack }: { token: string; onBack: () => void }) {
  const [history, setHistory] = useState<Array<{ id: number; type: string; starts_on: string; ends_on: string; status: string }>>([]);
  const [historyError, setHistoryError] = useState('');
  const loadHistory = useCallback(async () => { try { setHistory((await api.get<typeof history>('/leave-requests', token)).data); setHistoryError(''); } catch (e) { setHistoryError(message(e)); } }, [token]);
  useEffect(() => { void loadHistory(); }, [loadHistory]);
  const [type, setType] = useState('Sick leave'); const [startsOn, setStartsOn] = useState(''); const [endsOn, setEndsOn] = useState(''); const [reason, setReason] = useState(''); const [saving, setSaving] = useState(false);
  const submit = async () => { setSaving(true); try { await api.post('/leave-requests', token, { type, starts_on: startsOn, ends_on: endsOn, reason }); Alert.alert('Request submitted', 'Your leave request is pending approval.'); setStartsOn(''); setEndsOn(''); setReason(''); void loadHistory(); } catch (error) { Alert.alert('Could not submit request', message(error)); } finally { setSaving(false); } };
  return <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled"><Back onPress={onBack} /><PageIntro title="Request leave" description="Send a leave request to your school administration for approval." /><Field label="Leave type" value={type} onChangeText={setType} placeholder="Sick leave" /><Field label="Start date" value={startsOn} onChangeText={setStartsOn} placeholder="YYYY-MM-DD" /><Field label="End date" value={endsOn} onChangeText={setEndsOn} placeholder="YYYY-MM-DD" /><Field label="Reason" value={reason} onChangeText={setReason} placeholder="Explain your request" multiline /><Pressable disabled={saving} onPress={() => void submit()} style={({ pressed }) => [styles.primaryButton, (pressed || saving) && styles.pressed]}><Ionicons name="send-outline" size={19} color={colors.primary} /><Text style={styles.primaryText}>{saving ? 'Submitting…' : 'Submit request'}</Text></Pressable><Text style={styles.cardTitle}>My leave history</Text>{!!historyError && <Text style={styles.body}>{historyError}</Text>}{history.map(item => <View key={item.id} style={styles.card}><View><Text style={styles.cardTitle}>{item.type} · {item.status}</Text><Text style={styles.body}>{item.starts_on.slice(0, 10)} – {item.ends_on.slice(0, 10)}</Text></View></View>)}<Pressable onPress={() => void loadHistory()}><Text style={styles.backText}>Refresh leave history</Text></Pressable><Pressable onPress={() => void Linking.openURL(`${API_URL.replace(/\/api\/v1$/, '')}/staff/leaves`).catch(() => setHistoryError('Unable to open website.'))}><Text style={styles.backText}>Open leave tools on website ↗</Text></Pressable></ScrollView>;
}

type Paper = { id: number; maximum_score: number; subject: { name: string }; exam: { name: string; school_class?: { name: string } } };
type MarkSheet = { paper: Paper; status: string; students: Array<{ student: { id: number; name: string; admission_no: string }; score: number | null }> };

export function TeacherMarksScreen({ token, onBack, readOnly = false }: { token: string; onBack: () => void; readOnly?: boolean }) {
  const [papers, setPapers] = useState<Paper[]>([]);
  const [sheet, setSheet] = useState<MarkSheet>();
  const [scores, setScores] = useState<Record<number, string>>({});
  const [version, setVersion] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');
  const load = useCallback(async () => {
    setBusy(true); setError('');
    try { setPapers((await api.get<Paper[]>('/exam-papers', token)).data); }
    catch (e) { setError(message(e)); } finally { setBusy(false); }
  }, [token]);
  useEffect(() => { void load(); }, [load]);
  const select = async (paper: Paper) => {
    setBusy(true); setError('');
    try {
      const response = await api.get<MarkSheet>(`/exam-papers/${paper.id}/marks`, token);
      setSheet(response.data); setVersion(response.meta?.version as string | null);
      setScores(Object.fromEntries(response.data.students.map(row => [row.student.id, row.score == null ? '' : String(row.score)])));
    } catch (e) { setError(message(e)); } finally { setBusy(false); }
  };
  const save = async (submit: boolean) => {
    if (!sheet || busy) return;
    const marks = sheet.students.map(row => ({ student_id: row.student.id, score: scores[row.student.id]?.trim() ? Number(scores[row.student.id]) : null }));
    if (marks.some(row => row.score !== null && (!Number.isFinite(row.score) || row.score < 0 || row.score > sheet.paper.maximum_score))) {
      Alert.alert('Check scores', `Enter scores between 0 and ${sheet.paper.maximum_score}.`); return;
    }
    if (submit && marks.some(row => row.score === null)) { Alert.alert('Missing scores', 'Enter a score for every learner before submitting.'); return; }
    setBusy(true); setError('');
    try {
      await api.put(`/exam-papers/${sheet.paper.id}/marks`, token, { marks, base_version: version });
      if (submit) await api.post(`/exam-papers/${sheet.paper.id}/submit`, token, {});
      await select(sheet.paper);
      Alert.alert(submit ? 'Marks submitted' : 'Draft saved', submit ? 'Your marks are ready for academic approval.' : 'You can continue editing this mark sheet.');
    } catch (e) { setError(message(e)); } finally { setBusy(false); }
  };
  const editable = !readOnly && sheet?.status === 'draft';
  return <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
    <Back onPress={onBack} /><PageIntro title={readOnly ? 'View marks' : 'Enter marks'} description="Exam papers for your assigned classes and subjects." />
    <Pressable onPress={() => void Linking.openURL(`${API_URL.replace(/\/api\/v1$/, '')}/exams/marks`).catch(() => setError('Unable to open website.'))}><Text style={styles.backText}>Open all mark tools on website ↗</Text></Pressable>
    {!!error && <Text style={styles.body}>{error}</Text>}
    {busy && <Text style={styles.body}>Loading…</Text>}
    {!sheet ? <>{papers.map(paper => <Pressable disabled={busy} key={paper.id} onPress={() => void select(paper)} style={styles.card}><View><Text style={styles.cardTitle}>{paper.subject.name}</Text><Text style={styles.body}>{paper.exam.name} · {paper.exam.school_class?.name} · /{paper.maximum_score}</Text></View></Pressable>)}{!busy && !papers.length && <Empty text="No assigned exam papers yet." />}<Pressable onPress={() => void load()}><Text style={styles.backText}>Refresh papers</Text></Pressable></> : <>
      <Pressable disabled={busy} onPress={() => setSheet(undefined)}><Text style={styles.backText}>← Choose another paper</Text></Pressable>
      <Text style={styles.cardTitle}>{sheet.paper.subject.name} · {sheet.paper.exam.name}</Text><Text style={styles.body}>Status: {sheet.status} · Maximum {sheet.paper.maximum_score}</Text>
      {sheet.students.map(row => <View key={row.student.id} style={styles.card}><View style={styles.grow}><Text style={styles.cardTitle}>{row.student.name}</Text><Text style={styles.body}>{row.student.admission_no}</Text></View><TextInput accessibilityLabel={`Score for ${row.student.name}`} editable={editable && !busy} keyboardType="decimal-pad" value={scores[row.student.id] ?? ''} onChangeText={value => setScores(old => ({ ...old, [row.student.id]: value }))} placeholder="—" style={[styles.input, { width: 80 }]} /></View>)}
      {editable && sheet.students.length > 0 && <><Pressable disabled={busy} style={styles.primaryButton} onPress={() => void save(false)}><Text style={styles.primaryText}>Save draft</Text></Pressable><Pressable disabled={busy} style={styles.primaryButton} onPress={() => Alert.alert('Submit marks?', 'Submit this complete mark sheet for academic approval?', [{ text: 'Cancel', style: 'cancel' }, { text: 'Submit', onPress: () => void save(true) }])}><Text style={styles.primaryText}>Submit for approval</Text></Pressable></>}
    </>}
  </ScrollView>;
}

export function AddHomeworkScreen({ token, onBack }: { token: string; onBack: () => void }) {
  const [assignments, setAssignments] = useState<import('../../types').Assignment[]>([]);
  const [selected, setSelected] = useState<number>();
  const [title, setTitle] = useState(''); const [instructions, setInstructions] = useState('');
  const [due, setDue] = useState(''); const [maximum, setMaximum] = useState('100');
  const [busy, setBusy] = useState(false); const [error, setError] = useState('');
  const load = useCallback(async () => {
    setBusy(true); setError('');
    try { setAssignments((await api.get<import('../../types').Assignment[]>('/teaching-assignments', token)).data.filter(item => item.subject_id !== null)); }
    catch (e) { setError(message(e)); } finally { setBusy(false); }
  }, [token]);
  useEffect(() => { void load(); }, [load]);
  const publish = async () => {
    const assignment = selected === undefined ? undefined : assignments[selected];
    if (!assignment || busy) return;
    const date = new Date(due.replace(' ', 'T'));
    if (!title.trim() || !instructions.trim() || !Number.isFinite(date.valueOf()) || date <= new Date() || !Number.isInteger(Number(maximum)) || Number(maximum) < 1 || Number(maximum) > 1000) {
      Alert.alert('Check homework details', 'Enter a title, instructions, a future due date and time, and a maximum score from 1 to 1000.'); return;
    }
    setBusy(true); setError('');
    try { await api.post('/homework', token, { school_class_id: assignment.school_class_id, subject_id: assignment.subject_id, title: title.trim(), instructions: instructions.trim(), maximum_score: Number(maximum), due_at: date.toISOString() }); Alert.alert('Homework published', 'Your assigned learners can now see this homework.'); onBack(); }
    catch (e) { setError(message(e)); } finally { setBusy(false); }
  };
  return <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled"><Back onPress={onBack} /><PageIntro title="Add homework" description="Publish work for an assigned class and subject." />
    {!!error && <><Text style={styles.body}>{error}</Text><Pressable onPress={() => void load()}><Text style={styles.backText}>Retry</Text></Pressable></>}
    {busy && <Text style={styles.body}>Please wait…</Text>}
    {assignments.map((item, index) => <Pressable key={`${item.school_class_id}:${item.subject_id}`} onPress={() => setSelected(index)} style={[styles.card, selected === index && { borderColor: colors.primary, backgroundColor: colors.secondary }]}><Text style={styles.cardTitle}>{item.class_name} · {item.subject_name}</Text></Pressable>)}
    {!busy && !assignments.length && <Empty text="Your school needs to assign a class and subject before you can publish homework." />}
    <Field label="Title" value={title} onChangeText={setTitle} placeholder="Homework title" /><Field label="Instructions" value={instructions} onChangeText={setInstructions} placeholder="What should learners complete?" multiline /><Field label="Due date and time (local time)" value={due} onChangeText={setDue} placeholder="YYYY-MM-DD HH:mm" /><Field label="Maximum score" value={maximum} onChangeText={setMaximum} placeholder="100" />
    <Pressable disabled={busy || selected === undefined} onPress={() => void publish()} style={[styles.primaryButton, (busy || selected === undefined) && styles.pressed]}><Text style={styles.primaryText}>Publish homework</Text></Pressable>
  </ScrollView>;
}

type TeacherHomework = { id: number; title: string; instructions: string; due_at: string; maximum_score: number; subject?: { name: string }; school_class?: { name: string }; submissions?: Array<{ id: number; student: { name: string }; answer?: string; score: number | null; feedback?: string; status: string }> };
export function TeacherHomeworkScreen({ token }: { token: string }) {
  const [items, setItems] = useState<TeacherHomework[]>([]); const [selected, setSelected] = useState<TeacherHomework>();
  const [page, setPage] = useState(1); const [hasMore, setHasMore] = useState(false);
  const [busy, setBusy] = useState(false); const [error, setError] = useState('');
  const load = useCallback(async (next = 1) => {
    setBusy(true); setError('');
    try { const response = await api.get<{ data: TeacherHomework[]; current_page: number; last_page: number }>(`/homework?page=${next}`, token); setItems(old => next === 1 ? response.data.data : [...old, ...response.data.data]); setPage(next); setHasMore(response.data.current_page < response.data.last_page); }
    catch (e) { setError(message(e)); } finally { setBusy(false); }
  }, [token]);
  useEffect(() => { void load(); }, [load]);
  const select = async (id: number) => { setBusy(true); setError(''); try { setSelected((await api.get<TeacherHomework>(`/homework/${id}`, token)).data); } catch (e) { setError(message(e)); } finally { setBusy(false); } };
  return <ScrollView contentContainerStyle={[styles.content, { paddingBottom: 100 }]} keyboardShouldPersistTaps="handled">
    <PageIntro title="Homework" description="Assigned work and learner submissions." />
    {!!error && <Text style={styles.body}>{error}</Text>}{busy && <Text style={styles.body}>Loading…</Text>}
    {selected ? <><Pressable onPress={() => setSelected(undefined)}><Text style={styles.backText}>← All homework</Text></Pressable><Text style={styles.cardTitle}>{selected.title}</Text><Text style={styles.body}>{selected.instructions}</Text><Text style={styles.body}>Due {new Date(selected.due_at).toLocaleString()} · /{selected.maximum_score}</Text>
      {selected.submissions?.map(submission => <HomeworkReview key={submission.id} token={token} assignment={selected} submission={submission} />)}
      {!selected.submissions?.length && <Empty text="No learner submissions yet." />}
      <Pressable disabled={busy} onPress={() => void select(selected.id)}><Text style={styles.backText}>Refresh submissions</Text></Pressable>
      <Pressable onPress={() => void Linking.openURL(`${API_URL.replace(/\/api\/v1$/, '')}/homework`).catch(() => setError('Unable to open website.'))}><Text style={styles.backText}>Open attachments and advanced tools on website ↗</Text></Pressable>
    </> : <>{items.map(item => <Pressable disabled={busy} key={item.id} style={styles.card} onPress={() => void select(item.id)}><View><Text style={styles.cardTitle}>{item.title}</Text><Text style={styles.body}>{item.subject?.name} · {item.school_class?.name}</Text><Text style={styles.body}>Due {new Date(item.due_at).toLocaleString()}</Text></View></Pressable>)}
      {!busy && !items.length && <Empty text="No homework posted yet. Use + to create your first assignment." />}
      <Pressable disabled={busy} onPress={() => void load()}><Text style={styles.backText}>Refresh</Text></Pressable>{hasMore && <Pressable disabled={busy} onPress={() => void load(page + 1)}><Text style={styles.backText}>Load more</Text></Pressable>}
    </>}
  </ScrollView>;
}
function HomeworkReview({ token, assignment, submission }: { token: string; assignment: TeacherHomework; submission: NonNullable<TeacherHomework['submissions']>[number] }) {
  const [score, setScore] = useState(submission.score == null ? '' : String(submission.score)); const [feedback, setFeedback] = useState(submission.feedback ?? ''); const [status, setStatus] = useState(submission.status); const [busy, setBusy] = useState(false);
  const save = async () => { if (busy) return; if (score.trim() && (!Number.isFinite(Number(score)) || Number(score) < 0 || Number(score) > assignment.maximum_score)) { Alert.alert('Check score', `Enter a score from 0 to ${assignment.maximum_score}.`); return; } setBusy(true); try { await api.post(`/homework/${assignment.id}/submissions/${submission.id}/review`, token, { score: score.trim() ? Number(score) : null, feedback }); setStatus('reviewed'); Alert.alert('Review saved'); } catch (e) { Alert.alert('Could not save review', message(e)); } finally { setBusy(false); } };
  return <View style={[styles.card, { flexDirection: 'column' }]}><Text style={styles.cardTitle}>{submission.student.name} · {status}</Text><Text style={styles.body}>{submission.answer || 'No written answer. Check the website for attachments.'}</Text><View style={{ width: '100%' }}><Field label={`Score / ${assignment.maximum_score}`} value={score} onChangeText={setScore} placeholder="Score" /><Field label="Feedback" value={feedback} onChangeText={setFeedback} placeholder="Your feedback" multiline /><Pressable disabled={busy} onPress={() => void save()} style={styles.primaryButton}><Text style={styles.primaryText}>{busy ? 'Saving…' : 'Save review'}</Text></Pressable></View></View>;
}

export function TeacherReportsScreen({ token, onBack }: { token: string; onBack: () => void }) {
  const [tools, setTools] = useState<import('../../types').TeacherWorkspace['tools']>([]);
  const [error, setError] = useState('');
  const load = useCallback(async () => {
    setError('');
    try { const response = await api.get<import('../../types').Dashboard>('/dashboard', token); setTools(response.data.teacher_workspace?.tools.filter(tool => tool.group === 'Reports') ?? []); }
    catch (e) { setError(message(e)); }
  }, [token]);
  useEffect(() => { void load(); }, [load]);
  return <ScrollView contentContainerStyle={styles.content}><Back onPress={onBack} /><PageIntro title="Results & reports" description="Open your school's report tools in the browser. You may need to sign in." />
    {!!error && <Text style={styles.body}>{error}</Text>}
    {tools.map(tool => <Pressable key={tool.id} style={styles.card} onPress={() => void Linking.openURL(`${API_URL.replace(/\/api\/v1$/, '')}${tool.path}`).catch(() => setError('Unable to open the website. Please try again.'))}><Text style={styles.cardTitle}>{tool.label}</Text><Ionicons name="open-outline" size={20} color={colors.primary} /></Pressable>)}
    <Pressable onPress={() => void load()}><Text style={styles.backText}>Refresh available reports</Text></Pressable>
  </ScrollView>;
}

function Back({ onPress }: { onPress: () => void }) { return <Pressable onPress={onPress} style={styles.back}><Ionicons name="arrow-back" size={20} color={colors.primary} /><Text style={styles.backText}>Home</Text></Pressable>; }
function Field(props: { label: string; value: string; onChangeText: (value: string) => void; placeholder: string; multiline?: boolean }) { return <View><Text style={styles.label}>{props.label}</Text><TextInput {...props} style={[styles.input, props.multiline && styles.multiline]} placeholderTextColor={colors.textMuted} /></View>; }
function Empty({ text }: { text: string }) { return <View style={styles.placeholder}><Ionicons name="notifications-off-outline" size={27} color={colors.textMuted} /><Text style={styles.placeholderText}>{text}</Text></View>; }
function message(error: unknown) { return error instanceof ApiError ? error.message : 'Check your connection and try again.'; }

const styles = StyleSheet.create({
  content: { padding: 20, paddingBottom: 40, gap: 12 }, back: { alignSelf: 'flex-start', minHeight: 42, flexDirection: 'row', alignItems: 'center', gap: 7, paddingHorizontal: 13, borderRadius: radius.full, backgroundColor: colors.secondary }, backText: { color: colors.primary, fontWeight: '900' }, card: { minHeight: 92, flexDirection: 'row', alignItems: 'flex-start', gap: 12, padding: 15, backgroundColor: colors.surface, borderWidth: 1, borderColor: colors.border, borderRadius: radius.md }, icon: { width: 40, height: 40, borderRadius: 13, backgroundColor: colors.secondary, alignItems: 'center', justifyContent: 'center' }, grow: { flex: 1 }, cardTitle: { color: colors.textDark, fontSize: 15, fontWeight: '900' }, body: { color: colors.textMuted, fontSize: 12, lineHeight: 18, marginTop: 5 }, label: { color: colors.textDark, fontSize: 12, fontWeight: '800', marginBottom: 7 }, input: { minHeight: 52, borderRadius: radius.md, borderWidth: 1, borderColor: colors.border, backgroundColor: colors.surface, color: colors.textDark, paddingHorizontal: 14 }, multiline: { minHeight: 110, paddingTop: 14, textAlignVertical: 'top' }, primaryButton: { minHeight: 54, borderRadius: radius.md, backgroundColor: colors.secondary, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 8 }, primaryText: { color: colors.primary, fontSize: 14, fontWeight: '900' }, pressed: { opacity: 0.62 }, placeholder: { minHeight: 190, borderRadius: radius.md, backgroundColor: colors.surface, alignItems: 'center', justifyContent: 'center', padding: 24, borderWidth: 1, borderColor: colors.border }, largeIcon: { width: 64, height: 64, borderRadius: 20, backgroundColor: colors.secondary, alignItems: 'center', justifyContent: 'center' }, placeholderTitle: { color: colors.textDark, fontSize: 20, fontWeight: '900', marginTop: 14 }, placeholderText: { color: colors.textMuted, textAlign: 'center', lineHeight: 20, marginTop: 7 },
});
