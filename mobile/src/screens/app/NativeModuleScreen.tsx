import { useThemedStyles } from '../../theme/ThemeProvider';
import { EdlinkAlert as Alert } from '../../components/EdlinkAlert';
import { ErrorScreen } from '../../components/ErrorScreen';
import { NativeRepeatFields } from '../../components/NativeRepeatFields';
import { exportNativeReport } from '../../components/NativeReportExport';
import { InlineSelect } from '../../components/InlineSelect';
import { TeacherHero, TeacherSearch, TeacherButton, useTeacherBack, teacherScreenStyles } from '../../components/TeacherScreenUI';
import { NativeSelect } from '../../components/NativeSelect';
import { useCallback, useEffect, useRef, useState } from 'react';
import { Pressable, ScrollView, StyleSheet, View } from 'react-native';
import { Text, TextInput } from '../../components/Typography';
import { api } from '../../api';
import { colors } from '../../theme/index';

type Field = { repeat?: Field[]; key: string; label: string; default?: string; hidden?: boolean; placeholder?: string; multiline?: boolean; secure?: boolean; multiple?: boolean; options?: Array<{ value: string; label: string }> };
type Form = { action: string; label: string; fields: Field[] };
type GroupMember = { id?: number; name: string; admission_no?: string; class_name?: string | null; stream_name?: string | null };
type Module = { title: string; rows: Array<{ members?: GroupMember[]; id: number | string; title: string; details: Record<string, string | number | boolean | null>; values?: Record<string, unknown> }>; forms: Form[]; filters?: Field[]; page: number; last_page: number };
export function NativeModuleScreen({ token, tool, onBack }: { token: string; tool: string; onBack: () => void }) {
  const styles = useThemedStyles(baseStyles);
  const [filters, setFilters] = useState<Record<string, string>>({});
  const [data, setData] = useState<Module>(); const [search, setSearch] = useState(''); const [values, setValues] = useState<Record<string, string | string[]>>({});
  const [editing, setEditing] = useState<Form>(); const [busy, setBusy] = useState(false); const [error, setError] = useState(''); const serial = useRef(0);
  const load = useCallback(async (page = 1) => { const id = ++serial.current; setBusy(true); setError(''); try { const response = await api.get<Module>(`/teacher/tools/${encodeURIComponent(tool)}?page=${page}&search=${encodeURIComponent(search)}${Object.entries(filters).filter(([,value]) => value !== '').map(([key,value]) => `&${key}=${encodeURIComponent(value)}`).join('')}`, token); if (id === serial.current) setData(response.data); } catch (e) { if (id === serial.current) setError(e instanceof Error ? e.message : 'Unable to load tool.'); } finally { if (id === serial.current) setBusy(false); } }, [token, tool, search, filters]);
  useEffect(() => { const timer = setTimeout(() => void load(), 250); return () => { clearTimeout(timer); serial.current++; }; }, [load]);
  const save = async () => { if (!editing || busy) return; setBusy(true); setError(''); try { await api.post(`/teacher/tools/${encodeURIComponent(tool)}`, token, { ...values, action: editing.action }); setEditing(undefined); setValues({}); await load(); Alert.alert('Saved', 'Your changes have been saved.'); } catch (e) { setError(e instanceof Error ? e.message : 'Unable to save.'); } finally { setBusy(false); } };
  useTeacherBack(() => { if (editing) { setEditing(undefined); setValues({}); } else onBack(); });
  if (error && !data && !editing) return <ErrorScreen message={error} retry={() => load()} onBack={onBack} />;
  return <ScrollView contentContainerStyle={teacherScreenStyles.content} keyboardShouldPersistTaps="handled"><TeacherHero title={data?.title ?? 'School workspace'} subtitle="Manage your assigned school records" artwork={tool.includes('report') ? 'reports' : tool.includes('class') || tool.includes('student') ? 'learners' : tool.includes('exam') ? 'marks' : 'school'} />{!!error && <Text style={styles.error}>{error}</Text>}{busy && <Text style={styles.meta}>Loading…</Text>}
    {editing ? <><Text style={styles.heading}>{values.id || values.editingId ? 'Edit record' : editing.label}</Text>{editing.fields.filter(field => !field.hidden).map(field => <View key={field.key} style={{ gap: 8 }}><Text style={styles.heading}>{field.label}</Text>{field.repeat ? <NativeRepeatFields fields={field.repeat} value={String(values[field.key] ?? '')} onChange={value => setValues(old => ({ ...old, [field.key]: value }))} /> : field.options ? (field.multiple ? <NativeSelect label={field.label} options={field.options} value={values[field.key] ?? (field.multiple ? [] : '')} multiple={field.multiple} onChange={value => setValues(old => ({ ...old, [field.key]: value }))} /> : <InlineSelect label={field.label} options={field.options} value={String(values[field.key] ?? '')} onChange={value => setValues(old => ({ ...old, [field.key]: value }))} />) : <TextInput accessibilityLabel={field.label} secureTextEntry={field.secure} value={typeof values[field.key] === 'string' ? values[field.key] as string : ''} onChangeText={text => setValues(old => ({ ...old, [field.key]: text }))} placeholder={field.placeholder ?? field.label} multiline={field.multiline} style={[styles.input, field.multiline && { minHeight: 110, textAlignVertical: 'top' }]} />}</View>)}<Pressable disabled={busy} onPress={() => Alert.alert(editing.label, 'Apply these changes to your school records?', [{ text: 'Cancel', style: 'cancel' }, { text: 'Confirm', onPress: () => void save() }])} style={styles.button}><Text style={styles.buttonText}>{editing.label}</Text></Pressable><TeacherButton label="Cancel" secondary disabled={busy} onPress={() => { setEditing(undefined); setValues({}); }} /></> : <>
      {data?.filters?.map(field => <View key={field.key} style={{ gap: 8 }}><Text style={styles.heading}>{field.label}</Text><InlineSelect label={field.label} options={field.options ?? []} value={filters[field.key] ?? field.default ?? ''} onChange={value => setFilters(old => ({ ...old, [field.key]: String(value), ...(field.key === 'termId' ? { studentId: '', examId: '' } : field.key === 'studentId' ? { examId: '' } : {}) }))} /></View>)}
      {data?.forms.map(form => <Pressable disabled={busy || !!error} key={form.action} onPress={() => { setValues(Object.fromEntries(form.fields.filter(field => field.default !== undefined).map(field => [field.key, field.default!]))); setEditing(form); }} style={styles.button}><Text style={styles.buttonText}>{form.label}</Text></Pressable>)}
      <TeacherSearch value={search} onChange={setSearch} />
      {data?.rows.filter(row => tool !== 'students.activities' || !search.trim() || `${row.title} ${row.details.Members ?? ''} ${(row.members ?? []).map(member => `${member.name} ${member.admission_no ?? ''} ${member.class_name ?? ''}`).join(' ')}`.toLowerCase().includes(search.trim().toLowerCase())).map(row => <View key={row.id} style={styles.card}><Text style={styles.heading}>{row.title}</Text>{Object.entries(row.details).filter(([label,value]) => value !== null && value !== '' && (tool !== 'students.activities' || label !== 'Members')).map(([label, value]) => <View key={label} style={styles.detail}><Text style={styles.meta}>{label}</Text><Text style={styles.detailValue}>{typeof value === 'boolean' ? value ? 'Yes' : 'No' : String(value)}</Text></View>)}{tool === 'students.activities' && <ActivityMembers members={row.members} legacy={row.details.Members} search={row.title.toLowerCase().includes(search.trim().toLowerCase()) ? '' : search} />}{row.values && data.forms[0] && <Pressable disabled={busy || !!error} onPress={() => { setValues(Object.fromEntries(Object.entries(row.values!).map(([key, value]) => [key, Array.isArray(value) ? value.map(String) : value == null ? '' : String(value)]))); setEditing(data.forms[0]); }} style={styles.secondaryButton}><Text style={styles.secondaryText}>Edit</Text></Pressable>}</View>)}
      {!busy && !error && !data?.rows.length && <Text style={styles.meta}>No records to show.</Text>}
      {data && data.last_page > 1 && <View style={styles.detail}><TeacherButton label="Previous" secondary disabled={busy || data.page <= 1} onPress={() => void load(data.page - 1)} style={{ flex: 1 }} /><Text style={styles.meta}>{data.page} / {data.last_page}</Text><TeacherButton label="Next" secondary disabled={busy || data.page >= data.last_page} onPress={() => void load(data.page + 1)} style={{ flex: 1 }} /></View>}
      {!!data?.rows.length && <Pressable disabled={busy} onPress={() => { setBusy(true); void exportNativeReport(data.title, data.rows).catch(() => setError('Unable to export records.')).finally(() => setBusy(false)); }} style={styles.secondaryButton}><Text style={styles.secondaryText}>Export displayed records as PDF</Text></Pressable>}
      <TeacherButton label={busy ? 'Refreshing…' : 'Refresh'} secondary disabled={busy} onPress={() => void load(data?.page ?? 1)} />
    </>}
  </ScrollView>;
}
function ActivityMembers({ members, legacy, search }: { members?: GroupMember[]; legacy: unknown; search: string }) {
  const styles = useThemedStyles(baseStyles);
  const [expanded, setExpanded] = useState(false);
  const students: GroupMember[] = members ?? (typeof legacy === 'string' && legacy !== 'No members' && legacy.trim() ? legacy.split(',').map(name => ({ name: name.trim() })).filter(member => member.name) : []);
  const matching = search.trim() ? students.filter(member => `${member.name} ${member.admission_no ?? ''} ${member.class_name ?? ''}`.toLowerCase().includes(search.trim().toLowerCase())) : students;
  const shown = expanded || search.trim() ? matching : matching.slice(0, 10);
  return <View style={styles.memberList}>
    <View style={styles.memberHeading}><Text style={styles.heading}>Students</Text><View style={styles.memberCount}><Text style={styles.memberCountText}>{students.length}</Text></View></View>
    {shown.map((member, index) => <View key={member.id ?? `${member.name}-${index}`} style={styles.memberRow}>
      <Text style={styles.memberNumber}>{index + 1}</Text>
      <View style={styles.memberCopy}><Text style={styles.memberName}>{member.name}</Text>{!!member.admission_no && <Text style={styles.memberMeta}>{member.admission_no}</Text>}</View>
      {!!member.class_name && <Text style={styles.memberClass}>{member.class_name}{member.stream_name ? ` · ${member.stream_name}` : ''}</Text>}
    </View>)}
    {!shown.length && <Text style={styles.meta}>{students.length ? 'No students match this search.' : 'No students assigned yet.'}</Text>}
    {!search.trim() && students.length > 10 && <TeacherButton label={expanded ? 'Show fewer students' : `View all ${students.length} students`} secondary onPress={() => setExpanded(value => !value)} />}
  </View>;
}
const baseStyles = StyleSheet.create({
  memberList: { borderTopWidth: 1, borderTopColor: colors.border, paddingTop: 14, gap: 4 },
  memberHeading: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: 6 },
  memberCount: { minWidth: 27, paddingHorizontal: 8, paddingVertical: 4, borderRadius: 999, backgroundColor: colors.secondary },
  memberCountText: { color: colors.primary, fontSize: 11, fontWeight: '600' },
  memberRow: { flexDirection: 'row', alignItems: 'center', gap: 10, minHeight: 56, paddingVertical: 10, borderBottomWidth: 1, borderBottomColor: colors.border },
  memberNumber: { width: 20, color: colors.textMuted, fontSize: 11 }, memberCopy: { flex: 1, minWidth: 0 },
  memberName: { color: colors.primary, fontSize: 13, lineHeight: 19, fontWeight: '600' }, memberMeta: { color: colors.textMuted, fontSize: 10, lineHeight: 16, marginTop: 3 },
  memberClass: { maxWidth: '32%', color: colors.primary, fontSize: 11, lineHeight: 17, textAlign: 'right' },
 secondaryButton: { minHeight: 52, borderRadius: 999, backgroundColor: colors.secondary, paddingHorizontal: 18, paddingVertical: 13, justifyContent: 'center', alignItems: 'center' }, secondaryText: { color: colors.primary, fontSize: 13, fontWeight: '600', textAlign: 'center' }, content: { padding: 20, gap: 16, paddingBottom: 40 }, back: { color: colors.primary, fontWeight: '700', paddingVertical: 12 }, title: { color: colors.primary, fontSize: 27, fontWeight: '800' }, heading: { color: colors.primary, fontSize: 15, fontWeight: '700' }, meta: { color: colors.textMuted, fontSize: 12 }, error: { color: '#B42318', lineHeight: 21 }, card: { backgroundColor: colors.surface, padding: 18, borderRadius: 18, gap: 12, borderWidth: 1, borderColor: colors.border }, detail: { flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between', gap: 15 }, detailValue: { flex: 1, textAlign: 'right', color: colors.primary, fontSize: 12 }, input: { backgroundColor: 'white', padding: 15, borderRadius: 14, color: colors.primary, borderWidth: 1, borderColor: '#DEE4EF' }, button: { backgroundColor: colors.primary, padding: 17, borderRadius: 999, minHeight: 54, alignItems: 'center' }, buttonText: { color: colors.secondary, fontWeight: '600', fontSize: 14, textAlign: 'center' }, options: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 }, option: { borderRadius: 12, backgroundColor: 'white', padding: 12 }, active: { backgroundColor: colors.primary } });

const styles = baseStyles;
