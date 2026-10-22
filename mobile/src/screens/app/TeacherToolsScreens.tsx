import { NativeSelect } from '../../components/NativeSelect';
import { EdlinkAlert as Alert } from '../../components/EdlinkAlert';

import { NativeAttachment } from '../../components/NativeAttachment';

import * as DocumentPicker from 'expo-document-picker';

import { Text, TextInput } from '../../components/Typography';

import { Ionicons } from '@expo/vector-icons';

import { useCallback, useEffect, useState } from 'react';

import { AppState, Pressable, RefreshControl, ScrollView, StyleSheet, View } from 'react-native';

import { api, ApiError } from '../../api';


import { colors } from '../../theme/index';



type Notice = { id: number; title: string; message: string; type: string; category: string; attachment_name?: string | null; attachment_path?: string | null; created_at: string; read_at?: string | null };



export function NotificationsScreen({ token, studentId, onRead, onBack }: { token: string; studentId?: number; onRead?: () => void; onBack: () => void }) {

  const [items, setItems] = useState<Notice[]>([]); const [loading, setLoading] = useState(true); const [error, setError] = useState('');

  const load = useCallback(async (showSpinner = true) => {

    if (showSpinner) setLoading(true);

    try {

      const suffix = studentId ? `?student_id=${studentId}` : '';

      const response = await api.get<{ data: Notice[] }>(`/notifications${suffix}`, token);

      const notices = response.data.data ?? [];

      setItems(notices); setError('');

      const unread = notices.filter(item => !item.read_at).map(item => item.id);

      if (unread.length) { await api.post('/notifications/read', token, { notification_ids: unread }); onRead?.(); }

    } catch (reason) { setError(message(reason)); } finally { if (showSpinner) setLoading(false); }

  }, [onRead, studentId, token]);

  useEffect(() => {

    void load();

    const timer = setInterval(() => { if (AppState.currentState === 'active') void load(false); }, 15_000);

    const subscription = AppState.addEventListener('change', state => { if (state === 'active') void load(false); });

    return () => { clearInterval(timer); subscription.remove(); };

  }, [load]);

  return <ScrollView style={styles.screen} contentContainerStyle={styles.content} refreshControl={<RefreshControl refreshing={loading} onRefresh={() => void load()} tintColor={colors.primary} />}>
    <Back onPress={onBack} />
    <ScreenHero icon="notifications-outline" eyebrow="STAY CONNECTED" title="Notifications" subtitle="The latest updates from your school community." />
    <View style={styles.summaryRow}>
      <View style={styles.summaryText}><Text style={styles.summaryNumber}>{items.length}</Text><Text style={styles.summaryLabel}>Total updates</Text></View>
      <View style={styles.summaryDivider} />
      <View style={styles.summaryText}><Text style={styles.summaryNumber}>{items.filter(item => !item.read_at).length}</Text><Text style={styles.summaryLabel}>New updates</Text></View>
      <View style={styles.summaryIcon}><Ionicons name="mail-unread-outline" size={22} color={colors.primary} /></View>
    </View>
    <SectionTitle title="Your activity" caption="Attendance, results, homework and announcements" />
    {!!error && <Empty text={error} icon="cloud-offline-outline" />}
    {!error && items.map(item => <View key={item.id} style={[styles.noticeCard, !item.read_at && styles.noticeUnread]}>
      <View style={styles.noticeTop}><View style={styles.noticeIcon}><Ionicons name={notificationIcon(item.category)} size={21} color={colors.primary} /></View>
      <View style={styles.grow}><Text style={styles.noticeCategory}>{notificationLabel(item.category).toUpperCase()}</Text><Text style={styles.cardTitle}>{item.title}</Text></View>
      {!item.read_at && <View style={styles.unreadDot} />}
    </View>
    <Text style={styles.noticeBody}>{item.message}</Text>
    {item.attachment_path && item.attachment_name && <View style={styles.attachmentArea}><NativeAttachment token={token} path={item.attachment_path} name={item.attachment_name} /></View>}
    <View style={styles.noticeFooter}><Ionicons name="time-outline" size={13} color={colors.textMuted} /><Text style={styles.noticeMeta}>{new Date(item.created_at).toLocaleString()}</Text></View>
    </View>)}
    {!loading && !error && !items.length && <Empty text="No notifications yet. You're all caught up!" icon="checkmark-done-circle-outline" />}
  </ScrollView>;

}



function notificationIcon(category: string): React.ComponentProps<typeof Ionicons>['name'] { if (category.includes('attendance')) return 'checkmark-circle-outline'; if (category.includes('homework')) return 'book-outline'; if (category === 'results') return 'trophy-outline'; if (category === 'payment_receipt') return 'receipt-outline'; return 'notifications-outline'; }

function notificationLabel(category: string) { return ({ daily_attendance: 'Daily attendance', subject_attendance: 'Subject attendance', homework: 'Homework', homework_feedback: 'Homework results', results: 'Exam results', payment_receipt: 'Payment receipt' } as Record<string, string>)[category] ?? 'School update'; }



export function LeaveRequestScreen({ token, onBack }: { token: string; onBack: () => void }) {

  const [history, setHistory] = useState<Array<{ id: number; type: string; starts_on: string; ends_on: string; status: string }>>([]);

  const [historyError, setHistoryError] = useState('');

  const loadHistory = useCallback(async () => { try { setHistory((await api.get<typeof history>('/leave-requests', token)).data); setHistoryError(''); } catch (e) { setHistoryError(message(e)); } }, [token]);

  useEffect(() => { void loadHistory(); }, [loadHistory]);

  const [type, setType] = useState('Sick leave'); const [startsOn, setStartsOn] = useState(''); const [endsOn, setEndsOn] = useState(''); const [reason, setReason] = useState(''); const [saving, setSaving] = useState(false);

  const submit = async () => { setSaving(true); try { await api.post('/leave-requests', token, { type, starts_on: startsOn, ends_on: endsOn, reason }); Alert.alert('Request submitted', 'Your leave request is pending approval.'); setStartsOn(''); setEndsOn(''); setReason(''); void loadHistory(); } catch (error) { Alert.alert('Could not submit request', message(error)); } finally { setSaving(false); } };

  return <ScrollView style={styles.screen} contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
    <Back onPress={onBack} />
    <ScreenHero eyebrow="STAFF SELF SERVICE" title="Request leave" icon="calendar-clear-outline" subtitle="Plan time away and track your approvals." />
    <SectionTitle title="New leave request" caption="Share the details with your school administration" />
    <View style={styles.formCard}>
      <Field label="Leave type" value={type} onChangeText={setType} placeholder="e.g. Sick leave" icon="list-outline" />
      <View style={styles.formDivider} />
      <Field label="Start date" value={startsOn} onChangeText={setStartsOn} placeholder="YYYY-MM-DD" icon="calendar-outline" />
      <View style={styles.formDivider} />
      <Field label="End date" value={endsOn} onChangeText={setEndsOn} placeholder="YYYY-MM-DD" icon="calendar-outline" />
      <View style={styles.formDivider} />
      <Field label="Reason for leave" value={reason} onChangeText={setReason} placeholder="Briefly explain your request" multiline icon="create-outline" />
    </View>
    <PrimaryAction title={saving ? 'Submitting request…' : 'Submit leave request'} icon="paper-plane-outline" disabled={saving} onPress={() => void submit()} />
    <SectionTitle title="Request history" caption="Your previous applications" right={<Pressable onPress={() => void loadHistory()} style={styles.smallAction}><Ionicons name="refresh" size={17} color={colors.primary} /><Text style={styles.smallActionText}>Refresh</Text></Pressable>} />
    {!!historyError && <Empty text={historyError} icon="cloud-offline-outline" />}
    {history.map(item => <View key={item.id} style={styles.historyRow}>
      <View style={styles.historyIcon}><Ionicons name="calendar-outline" size={21} color={colors.primary} /></View>
      <View style={styles.grow}><Text style={styles.cardTitle}>{item.type}</Text><Text style={styles.body}>{item.starts_on.slice(0,10)} — {item.ends_on.slice(0,10)}</Text></View>
      <StatusChip value={item.status} />
    </View>)}
    {!historyError && !history.length && <Empty text="Your leave requests will appear here." icon="calendar-clear-outline" />}
  </ScrollView>;

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

  return <ScrollView style={styles.screen} contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
    <Back onPress={onBack} />
    <ScreenHero eyebrow="ACADEMIC WORKSPACE" title={readOnly ? 'View marks' : 'Enter marks'} subtitle="Manage assessment scores for your assigned classes." icon="school-outline" />
    {!!error && <Empty text={error} icon="alert-circle-outline" />}
    {busy && <Text style={styles.helperText}>Loading school records…</Text>}
    {!sheet ? <>
      <SectionTitle title="Exam papers" caption="Select a paper to open its mark sheet" right={<Pressable onPress={() => void load()} style={styles.smallAction}><Ionicons name="refresh" size={17} color={colors.primary} /><Text style={styles.smallActionText}>Refresh</Text></Pressable>} />
      {papers.map(paper => <Pressable key={paper.id} disabled={busy} onPress={() => void select(paper)} style={styles.linkRow}>
        <View style={styles.linkIcon}><Ionicons name="document-text-outline" size={22} color={colors.primary} /></View>
        <View style={styles.grow}><Text style={styles.cardTitle}>{paper.subject.name}</Text><Text style={styles.body}>{paper.exam.name} · {paper.exam.school_class?.name ?? 'Class'} · Max {paper.maximum_score}</Text></View>
        <Ionicons name="chevron-forward" size={19} color={colors.primary} />
      </Pressable>)}
      {!busy && !papers.length && <Empty text="No assigned exam papers yet." icon="documents-outline" />}
    </> : <>
      <Pressable onPress={() => setSheet(undefined)} style={styles.inlineBack}><Ionicons name="arrow-back" size={16} color={colors.primary} /><Text style={styles.inlineBackText}>All exam papers</Text></Pressable>
      <View style={styles.paperOverview}><Text style={styles.paperEyebrow}>SELECTED MARK SHEET</Text><Text style={styles.paperTitle}>{sheet.paper.subject.name}</Text><Text style={styles.paperMeta}>{sheet.paper.exam.name} · Out of {sheet.paper.maximum_score}</Text><View style={{ alignSelf: 'flex-start', marginTop: 12 }}><StatusChip value={sheet.status} /></View></View>
      <SectionTitle title="Learner scores" caption={`${sheet.students.length} learners · ${editable ? 'Enter marks below' : 'Read-only mark sheet'}`} />
      {sheet.students.map((row,index) => <View key={row.student.id} style={styles.scoreRow}>
        <View style={styles.studentAvatar}><Text style={styles.studentAvatarText}>{initials(row.student.name)}</Text></View>
        <View style={styles.grow}><Text style={styles.cardTitle}>{row.student.name}</Text><Text style={styles.body}>{row.student.admission_no}</Text></View>
        <View style={styles.scoreField}><TextInput accessibilityLabel={`Score for ${row.student.name}`} editable={editable && !busy} keyboardType="decimal-pad" value={scores[row.student.id] ?? ''} onChangeText={value => setScores(old => ({...old,[row.student.id]:value}))} placeholder="—" placeholderTextColor={colors.textMuted} style={styles.scoreInput} /><Text style={styles.scoreMax}>/{sheet.paper.maximum_score}</Text></View>
      </View>)}
      {editable && sheet.students.length > 0 && <>
        <PrimaryAction title="Save draft" icon="save-outline" secondary disabled={busy} onPress={() => void save(false)} />
        <PrimaryAction title="Submit for approval" icon="checkmark-circle-outline" disabled={busy} onPress={() => Alert.alert('Submit marks?', 'Submit this complete mark sheet for academic approval?', [{text:'Cancel',style:'cancel'},{text:'Submit',onPress:()=>void save(true)}])} />
      </>}
    </>}
  </ScrollView>;

}



export function AddHomeworkScreen({ token, onBack }: { token: string; onBack: () => void }) {

  const [attachment, setAttachment] = useState<DocumentPicker.DocumentPickerAsset>();

  const [assignments, setAssignments] = useState<import('../../types').Assignment[]>([]);

  const [selected, setSelected] = useState<number>();
  const [streamId, setStreamId] = useState('');

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

    try { const body = new FormData(); Object.entries({ school_class_id: assignment.school_class_id, subject_id: assignment.subject_id, title: title.trim(), instructions: instructions.trim(), maximum_score: Number(maximum), due_at: date.toISOString() }).forEach(([key, value]) => body.append(key, String(value))); if (streamId) body.append('stream_id', streamId); if (attachment) body.append('attachment', { uri: attachment.uri, name: attachment.name, type: attachment.mimeType ?? 'application/octet-stream' } as unknown as Blob); await api.upload('/homework', token, body); Alert.alert('Homework published', 'Your assigned learners can now see this homework.'); onBack(); }

    catch (e) { setError(message(e)); } finally { setBusy(false); }

  };

  return <ScrollView style={styles.screen} contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
    <Back onPress={onBack} />
    <ScreenHero eyebrow="TEACHING TOOLS" title="New homework" icon="create-outline" subtitle="Create meaningful work for your learners." />
    {!!error && <Empty text={error} icon="alert-circle-outline" />}
    {busy && <Text style={styles.helperText}>Please wait…</Text>}
    <SectionTitle title="Assign to class" caption="Choose the subject and class for this task" />
    <View style={styles.chipList}>{assignments.map((item,index) => <Pressable key={`${item.school_class_id}:${item.subject_id}`} onPress={() => { setSelected(index); setStreamId(''); }} style={[styles.classChip,selected===index && styles.classChipSelected]}>
      <Ionicons name={selected===index?'checkmark-circle':'school-outline'} size={18} color={selected===index?'#FFFFFF':colors.primary} /><Text style={[styles.classChipText,selected===index && styles.classChipTextSelected]}>{item.class_name} · {item.subject_name}</Text>
    </Pressable>)}</View>
    {!busy && !assignments.length && <Empty text="Your school needs to assign a class and subject first." icon="school-outline" />}
    {selected !== undefined && <NativeSelect label="Stream" value={streamId} options={[{ value: '', label: 'All streams in this class' }, ...(assignments[selected]?.streams ?? []).map(stream => ({ value: String(stream.id), label: stream.name }))]} onChange={value => setStreamId(String(value))} />}
    <SectionTitle title="Assignment details" caption="Tell learners what they need to complete" />
    <View style={styles.formCard}>
      <Field label="Homework title" value={title} onChangeText={setTitle} placeholder="e.g. Mathematics revision" icon="book-outline" />
      <View style={styles.formDivider} />
      <Field label="Instructions" value={instructions} onChangeText={setInstructions} placeholder="What should learners complete?" multiline icon="reader-outline" />
      <View style={styles.formDivider} />
      <Field label="Due date and time" value={due} onChangeText={setDue} placeholder="YYYY-MM-DD HH:mm" icon="calendar-outline" />
      <View style={styles.formDivider} />
      <Field label="Maximum score" value={maximum} onChangeText={setMaximum} placeholder="100" icon="trophy-outline" />
    </View>
    <SectionTitle title="Supporting materials" caption="Attach a document (maximum 10 MB)" />
    <Pressable style={styles.uploadArea} onPress={() => void DocumentPicker.getDocumentAsync({ copyToCacheDirectory:true,multiple:false }).then(result=>{if(!result.canceled){if((result.assets[0].size??0)>10*1024*1024){Alert.alert('File too large','Choose a file smaller than 10 MB.');return;}setAttachment(result.assets[0]);}}).catch(()=>setError('Unable to choose attachment.'))}>
      <Ionicons name="cloud-upload-outline" size={26} color={colors.primary} /><View style={styles.grow}><Text style={styles.cardTitle}>{attachment?attachment.name:'Choose an attachment'}</Text><Text style={styles.body}>PDF, image or other supported document</Text></View><Ionicons name="add-circle-outline" size={22} color={colors.primary} />
    </Pressable>
    {attachment && <Pressable onPress={() => setAttachment(undefined)} style={styles.inlineBack}><Ionicons name="trash-outline" size={16} color={colors.primary} /><Text style={styles.inlineBackText}>Remove attachment</Text></Pressable>}
    <PrimaryAction title={busy?'Publishing…':'Publish homework'} icon="paper-plane-outline" disabled={busy||selected===undefined} onPress={() => void publish()} />
  </ScrollView>;

}



type TeacherHomework = { attachment_name?: string; id: number; title: string; instructions: string; due_at: string; maximum_score: number; subject?: { name: string }; school_class?: { name: string }; submissions?: Array<{ attachment_name?: string; id: number; student: { name: string }; answer?: string; score: number | null; feedback?: string; status: string }> };

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

  return <ScrollView style={styles.screen} contentContainerStyle={[styles.content,{paddingBottom:100}]} keyboardShouldPersistTaps="handled">
    <ScreenHero eyebrow="TEACHING TOOLS" title="Homework" subtitle="Follow assignments and review learner submissions." icon="book-outline" />
    {!!error && <Empty text={error} icon="alert-circle-outline" />}
    {busy && <Text style={styles.helperText}>Loading assignments…</Text>}
    {selected ? <>
      <Pressable onPress={() => setSelected(undefined)} style={styles.inlineBack}><Ionicons name="arrow-back" size={16} color={colors.primary} /><Text style={styles.inlineBackText}>All homework</Text></Pressable>
      <View style={styles.paperOverview}><Text style={styles.paperEyebrow}>ASSIGNMENT DETAILS</Text><Text style={styles.paperTitle}>{selected.title}</Text><Text style={styles.paperMeta}>Due {new Date(selected.due_at).toLocaleString()} · Out of {selected.maximum_score}</Text></View>
      <View style={styles.descriptionCard}><Text style={styles.cardTitle}>Instructions</Text><Text style={styles.descriptionText}>{selected.instructions}</Text><NativeAttachment token={token} path={`/homework/${selected.id}/attachment`} name={selected.attachment_name} /></View>
      <SectionTitle title="Learner submissions" caption={`${selected.submissions?.length??0} received`} right={<Pressable disabled={busy} onPress={() => void select(selected.id)} style={styles.smallAction}><Ionicons name="refresh" size={16} color={colors.primary} /><Text style={styles.smallActionText}>Refresh</Text></Pressable>} />
      {selected.submissions?.map(submission => <HomeworkReview key={submission.id} token={token} assignment={selected} submission={submission} />)}
      {!selected.submissions?.length && <Empty text="No learner submissions yet." icon="file-tray-outline" />}
    </> : <>
      <SectionTitle title="Assignments" caption="Tap an assignment to view submissions" right={<Pressable disabled={busy} onPress={() => void load()} style={styles.smallAction}><Ionicons name="refresh" size={16} color={colors.primary} /><Text style={styles.smallActionText}>Refresh</Text></Pressable>} />
      {items.map(item => <Pressable key={item.id} disabled={busy} style={styles.linkRow} onPress={() => void select(item.id)}><View style={styles.linkIcon}><Ionicons name="book-outline" size={22} color={colors.primary} /></View><View style={styles.grow}><Text style={styles.cardTitle}>{item.title}</Text><Text style={styles.body}>{item.subject?.name} · {item.school_class?.name}</Text><Text style={styles.dueCaption}>Due {new Date(item.due_at).toLocaleString()}</Text></View><Ionicons name="chevron-forward" size={19} color={colors.primary} /></Pressable>)}
      {!busy && !items.length && <Empty text="No homework posted yet. Create your first assignment to get started." icon="book-outline" />}
      {hasMore && <PrimaryAction title="Load more assignments" icon="chevron-down-outline" secondary disabled={busy} onPress={() => void load(page+1)} />}
    </>}
  </ScrollView>;

}

function HomeworkReview({ token, assignment, submission }: { token: string; assignment: TeacherHomework; submission: NonNullable<TeacherHomework['submissions']>[number] }) {

  const [score, setScore] = useState(submission.score == null ? '' : String(submission.score)); const [feedback, setFeedback] = useState(submission.feedback ?? ''); const [status, setStatus] = useState(submission.status); const [busy, setBusy] = useState(false);

  const save = async () => { if (busy) return; if (score.trim() && (!Number.isFinite(Number(score)) || Number(score) < 0 || Number(score) > assignment.maximum_score)) { Alert.alert('Check score', `Enter a score from 0 to ${assignment.maximum_score}.`); return; } setBusy(true); try { await api.post(`/homework/${assignment.id}/submissions/${submission.id}/review`, token, { score: score.trim() ? Number(score) : null, feedback }); setStatus('reviewed'); Alert.alert('Review saved'); } catch (e) { Alert.alert('Could not save review', message(e)); } finally { setBusy(false); } };

  return <View style={styles.reviewCard}>
    <View style={styles.reviewHeader}><View style={styles.studentAvatar}><Text style={styles.studentAvatarText}>{initials(submission.student.name)}</Text></View><View style={styles.grow}><Text style={styles.cardTitle}>{submission.student.name}</Text><Text style={styles.body}>Homework submission</Text></View><StatusChip value={status}/></View>
    <Text style={styles.descriptionText}>{submission.answer || 'No written answer submitted.'}</Text>
    <NativeAttachment token={token} path={`/homework/${assignment.id}/submissions/${submission.id}/attachment`} name={submission.attachment_name} />
    <View style={styles.reviewFields}><Field label={`Score / ${assignment.maximum_score}`} value={score} onChangeText={setScore} placeholder="Enter score" icon="trophy-outline" /><Field label="Teacher feedback" value={feedback} onChangeText={setFeedback} placeholder="Write helpful feedback" multiline icon="chatbubble-outline" /></View>
    <PrimaryAction title={busy?'Saving…':'Save learner review'} icon="checkmark-outline" disabled={busy} onPress={() => void save()} />
  </View>;
}





type UIIcon = React.ComponentProps<typeof Ionicons>['name'];

function initials(name: string) { return name.trim().split(/\s+/).slice(0,2).map(part=>part.charAt(0).toUpperCase()).join('') || '?'; }
function Back({onPress}:{onPress:()=>void}) { return <Pressable accessibilityRole="button" onPress={onPress} style={styles.back}><Ionicons name="arrow-back" size={19} color={colors.primary} /><Text style={styles.backText}>Back to home</Text></Pressable>; }
function ScreenHero({eyebrow,title,subtitle,icon}:{eyebrow:string;title:string;subtitle:string;icon:UIIcon}) { return <View style={styles.hero}><View style={styles.heroGlow}/><View style={styles.heroTop}><Text style={styles.heroEyebrow}>{eyebrow}</Text><View style={styles.heroIcon}><Ionicons name={icon} size={25} color={colors.secondary}/></View></View><Text style={styles.heroTitle}>{title}</Text><Text style={styles.heroSubtitle}>{subtitle}</Text></View>; }
function SectionTitle({title,caption,right}:{title:string;caption?:string;right?:React.ReactNode}) { return <View style={styles.sectionHeader}><View style={styles.grow}><Text style={styles.sectionTitle}>{title}</Text>{caption && <Text style={styles.sectionCaption}>{caption}</Text>}</View>{right}</View>; }
function PrimaryAction({title,icon,disabled,onPress,secondary=false}:{title:string;icon:UIIcon;disabled?:boolean;onPress:()=>void;secondary?:boolean}) { return <Pressable accessibilityRole="button" disabled={disabled} onPress={onPress} style={({pressed})=>[styles.primaryButton,secondary&&styles.secondaryButton,(pressed||disabled)&&styles.pressed]}><Ionicons name={icon} size={19} color={secondary?colors.primary:'#FFFFFF'}/><Text style={[styles.primaryText,secondary&&styles.secondaryText]}>{title}</Text></Pressable>; }
function StatusChip({value}:{value:string}) { const success=['approved','reviewed','published'].includes(value.toLowerCase()); const pending=['pending','draft','submitted'].includes(value.toLowerCase()); return <View style={[styles.statusChip,{backgroundColor:success?'#E8F6EE':pending?'#FFF3D7':'#F2F4F8'}]}><View style={[styles.statusDot,{backgroundColor:success?'#168451':pending?'#CA901D':colors.textMuted}]}/><Text style={[styles.statusText,{color:success?'#168451':pending?'#986600':colors.textDark}]}>{value.replace(/_/g,' ')}</Text></View>; }
function Field(props:{label:string;value:string;onChangeText:(value:string)=>void;placeholder:string;multiline?:boolean;icon?:UIIcon}) { return <View style={styles.field}><Text style={styles.label}>{props.label}</Text><View style={styles.fieldControl}>{props.icon&&<Ionicons name={props.icon} size={19} color={colors.textMuted} style={styles.fieldIcon}/>}<TextInput accessibilityLabel={props.label} value={props.value} onChangeText={props.onChangeText} placeholder={props.placeholder} multiline={props.multiline} style={[styles.input,props.multiline&&styles.multiline]} placeholderTextColor={colors.textMuted}/></View></View>; }
function Empty({text,icon='file-tray-outline'}:{text:string;icon?:UIIcon}) { return <View style={styles.placeholder}><View style={styles.placeholderIcon}><Ionicons name={icon} size={27} color={colors.primary}/></View><Text style={styles.placeholderTitle}>Nothing here yet</Text><Text style={styles.placeholderText}>{text}</Text></View>; }
function message(error:unknown) { return error instanceof ApiError ? error.message : 'Check your connection and try again.'; }

const styles = StyleSheet.create({
 screen:{flex:1,backgroundColor:colors.background},content:{paddingHorizontal:18,paddingTop:12,paddingBottom:60,gap:13},
 grow:{flex:1,minWidth:0},
 back:{alignSelf:'flex-start',flexDirection:'row',alignItems:'center',gap:8,paddingVertical:9,paddingHorizontal:12,borderRadius:18,backgroundColor:colors.surface,borderWidth:1,borderColor:colors.border,marginBottom:2},backText:{fontSize:12,fontWeight:'700',color:colors.primary},
 hero:{minHeight:171,backgroundColor:colors.primaryCard,borderRadius:24,padding:21,overflow:'hidden',justifyContent:'center'},heroGlow:{position:'absolute',width:170,height:170,borderRadius:85,right:-48,top:-95,backgroundColor:colors.primary,opacity:0.65},heroTop:{flexDirection:'row',alignItems:'center',justifyContent:'space-between'},heroEyebrow:{fontSize:10,fontWeight:'800',letterSpacing:1.5,color:colors.secondary},heroIcon:{width:45,height:45,borderRadius:15,backgroundColor:colors.primary,alignItems:'center',justifyContent:'center'},heroTitle:{fontSize:28,lineHeight:34,fontWeight:'800',letterSpacing:-0.6,color:colors.textLight,marginTop:5},heroSubtitle:{fontSize:12,lineHeight:19,color:'#D9E3F3',marginTop:7,maxWidth:'90%'},
 sectionHeader:{flexDirection:'row',alignItems:'center',gap:9,marginTop:14,marginBottom:1},sectionTitle:{color:colors.textDark,fontSize:18,fontWeight:'800',letterSpacing:-0.3},sectionCaption:{color:colors.textMuted,fontSize:11,lineHeight:17,marginTop:4},
 smallAction:{flexDirection:'row',alignItems:'center',gap:5,padding:8,borderRadius:10,backgroundColor:colors.surface},smallActionText:{fontSize:11,color:colors.primary,fontWeight:'700'},
 summaryRow:{backgroundColor:colors.surface,borderRadius:20,padding:17,borderWidth:1,borderColor:colors.border,flexDirection:'row',alignItems:'center',gap:19},summaryText:{minWidth:62},summaryNumber:{color:colors.textDark,fontSize:27,fontWeight:'800'},summaryLabel:{color:colors.textMuted,fontSize:10,marginTop:2},summaryDivider:{height:33,width:1,backgroundColor:colors.border},summaryIcon:{marginLeft:'auto',width:44,height:44,borderRadius:15,backgroundColor:colors.secondary,alignItems:'center',justifyContent:'center'},
 noticeCard:{backgroundColor:colors.surface,borderRadius:18,padding:16,borderWidth:1,borderColor:colors.border},noticeUnread:{borderLeftWidth:4,borderLeftColor:colors.secondary},noticeTop:{flexDirection:'row',alignItems:'center',gap:12},noticeIcon:{height:44,width:44,borderRadius:15,backgroundColor:colors.secondary,alignItems:'center',justifyContent:'center'},noticeCategory:{fontSize:9,color:colors.primary,fontWeight:'800',letterSpacing:0.8,marginBottom:5},cardTitle:{fontSize:14,fontWeight:'800',lineHeight:20,color:colors.textDark},noticeBody:{fontSize:12,lineHeight:20,color:colors.textMuted,marginTop:12},noticeFooter:{flexDirection:'row',alignItems:'center',gap:5,marginTop:13,paddingTop:10,borderTopWidth:1,borderTopColor:colors.border},noticeMeta:{fontSize:10,color:colors.textMuted},unreadDot:{height:9,width:9,borderRadius:5,backgroundColor:colors.secondary},attachmentArea:{marginTop:10},
 formCard:{backgroundColor:colors.surface,borderRadius:20,padding:18,borderWidth:1,borderColor:colors.border,gap:14},formDivider:{height:1,backgroundColor:colors.border},field:{gap:7},label:{color:colors.textDark,fontSize:12,fontWeight:'700'},fieldControl:{flexDirection:'row',alignItems:'center',borderWidth:1,borderColor:colors.border,borderRadius:13,backgroundColor:colors.background,overflow:'hidden'},fieldIcon:{marginLeft:13},input:{flex:1,minHeight:48,color:colors.textDark,paddingVertical:11,paddingHorizontal:12,fontSize:13},multiline:{minHeight:90,textAlignVertical:'top'},
 primaryButton:{minHeight:53,flexDirection:'row',justifyContent:'center',alignItems:'center',gap:9,padding:12,borderRadius:15,backgroundColor:colors.primary,marginTop:4},primaryText:{fontSize:13,fontWeight:'800',color:'#FFFFFF'},secondaryButton:{backgroundColor:colors.surface,borderWidth:1,borderColor:colors.primary},secondaryText:{color:colors.primary},pressed:{opacity:0.55},
 historyRow:{backgroundColor:colors.surface,borderWidth:1,borderColor:colors.border,borderRadius:17,padding:14,flexDirection:'row',alignItems:'center',gap:11},historyIcon:{width:42,height:42,borderRadius:13,backgroundColor:colors.secondary,alignItems:'center',justifyContent:'center'},body:{fontSize:11,lineHeight:17,color:colors.textMuted,marginTop:4},
 statusChip:{flexDirection:'row',alignItems:'center',gap:5,borderRadius:50,paddingVertical:6,paddingHorizontal:9},statusDot:{height:6,width:6,borderRadius:3},statusText:{fontWeight:'800',fontSize:10,textTransform:'capitalize'},
 linkRow:{backgroundColor:colors.surface,flexDirection:'row',alignItems:'center',gap:12,borderRadius:18,borderWidth:1,borderColor:colors.border,padding:15,minHeight:85},linkIcon:{height:46,width:46,borderRadius:15,backgroundColor:colors.secondary,alignItems:'center',justifyContent:'center'},
 inlineBack:{flexDirection:'row',alignItems:'center',gap:6,alignSelf:'flex-start',paddingVertical:7},inlineBackText:{fontSize:12,fontWeight:'800',color:colors.primary},paperOverview:{backgroundColor:colors.primaryCard,borderRadius:20,padding:20},paperEyebrow:{color:colors.secondary,fontSize:10,fontWeight:'800',letterSpacing:1},paperTitle:{color:colors.textLight,fontSize:21,fontWeight:'800',marginTop:9},paperMeta:{color:'#D9E3F3',fontSize:12,marginTop:6},
 scoreRow:{flexDirection:'row',alignItems:'center',gap:10,backgroundColor:colors.surface,borderRadius:15,padding:12,borderWidth:1,borderColor:colors.border},studentAvatar:{height:42,width:42,borderRadius:15,backgroundColor:colors.secondary,alignItems:'center',justifyContent:'center'},studentAvatarText:{color:colors.primary,fontSize:12,fontWeight:'800'},scoreField:{flexDirection:'row',alignItems:'center',borderWidth:1,borderColor:colors.border,borderRadius:12,backgroundColor:colors.background,paddingHorizontal:9},scoreInput:{width:39,height:43,textAlign:'center',color:colors.textDark,fontSize:15,fontWeight:'800'},scoreMax:{fontSize:9,color:colors.textMuted},
 classChip:{borderWidth:1,borderColor:colors.border,borderRadius:13,padding:13,backgroundColor:colors.surface,flexDirection:'row',alignItems:'center',gap:8},classChipSelected:{backgroundColor:colors.primary,borderColor:colors.primary},classChipText:{fontSize:12,fontWeight:'700',color:colors.textDark,flexShrink:1},classChipTextSelected:{color:'#FFFFFF'},chipList:{gap:8},
 uploadArea:{minHeight:86,padding:15,borderWidth:1.5,borderStyle:'dashed',borderColor:colors.primary,borderRadius:18,backgroundColor:colors.surface,flexDirection:'row',alignItems:'center',gap:13},descriptionCard:{backgroundColor:colors.surface,padding:16,borderWidth:1,borderColor:colors.border,borderRadius:18,gap:8},descriptionText:{color:colors.textMuted,fontSize:13,lineHeight:20},dueCaption:{fontSize:10,color:colors.primary,marginTop:7,fontWeight:'700'},
 reviewCard:{backgroundColor:colors.surface,borderRadius:20,borderWidth:1,borderColor:colors.border,padding:16,gap:14},reviewHeader:{flexDirection:'row',alignItems:'center',gap:10},reviewFields:{gap:13},
 placeholder:{minHeight:190,alignItems:'center',justifyContent:'center',padding:24,borderRadius:20,borderWidth:1,borderColor:colors.border,backgroundColor:colors.surface},placeholderIcon:{height:58,width:58,borderRadius:18,backgroundColor:colors.secondary,alignItems:'center',justifyContent:'center'},placeholderTitle:{color:colors.textDark,fontWeight:'800',fontSize:16,marginTop:12},placeholderText:{color:colors.textMuted,fontSize:12,lineHeight:19,textAlign:'center',marginTop:6},helperText:{color:colors.textMuted,fontSize:12,paddingVertical:8},
});
