import { useThemedStyles } from '../../theme/ThemeProvider';
import { EdlinkAlert as Alert } from '../../components/EdlinkAlert';
import { useCallback, useEffect, useState } from 'react';
import { ScrollView, StyleSheet, View } from 'react-native';
import * as DocumentPicker from 'expo-document-picker';
import { api } from '../../api';
import { Text, TextInput } from '../../components/Typography';
import { StudentPhoto } from '../../components/StudentPhoto';
import { StatArtwork } from '../../components/StatArtwork';
import { InlineSelect } from '../../components/InlineSelect';
import { TeacherButton, useTeacherBack, teacherScreenStyles } from '../../components/TeacherScreenUI';
import { colors } from '../../theme/index';

const fieldLabel = (key: string) => key.replace(/_/g, ' ').replace(/\b\w/g, letter => letter.toUpperCase());
export function NativeStudentProfile({ token, id, onBack }: { token: string; id: number; onBack: () => void }) {
  const styles = useThemedStyles(baseStyles);
  const [fields, setFields] = useState<Record<string, string>>({});
  const [original, setOriginal] = useState<Record<string, string>>({});
  const [canEdit, setCanEdit] = useState(false);
  const [editing, setEditing] = useState(false);
  const [photo, setPhoto] = useState<string | null>();
  const [upload, setUpload] = useState<DocumentPicker.DocumentPickerAsset>();
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');
  const load = useCallback(async () => {
    setBusy(true); setError('');
    try {
      const response = await api.get<{ student: Record<string, string | null>; photo_url?: string | null; can_edit: boolean }>(`/teacher/students/${id}`, token);
      const values = Object.fromEntries(Object.entries(response.data.student).map(([key, value]) => [key, value ?? '']));
      setFields(values); setOriginal(values); setPhoto(response.data.photo_url); setCanEdit(response.data.can_edit);
    } catch (e) { setError(e instanceof Error ? e.message : 'Unable to load learner.'); }
    finally { setBusy(false); }
  }, [token, id]);
  useEffect(() => { void load(); }, [load]);
  const cancelEdit = () => { setFields(original); setUpload(undefined); setEditing(false); setError(''); };
  useTeacherBack(() => { if (editing) cancelEdit(); else onBack(); });
  const choosePhoto = async () => {
    try {
      const result = await DocumentPicker.getDocumentAsync({ type: ['image/jpeg', 'image/png', 'image/webp'], copyToCacheDirectory: true });
      if (!result.canceled) { setUpload(result.assets[0]); setError(''); }
    } catch { setError('Unable to choose photo.'); }
  };
  const save = async () => {
    if (busy || !canEdit) return;
    setBusy(true); setError('');
    try {
      const body = new FormData();
      Object.entries(fields).forEach(([key, value]) => body.append(key, value));
      if (upload) body.append('photo', { uri: upload.uri, name: upload.name, type: upload.mimeType ?? 'image/jpeg' } as unknown as Blob);
      await api.upload(`/teacher/students/${id}`, token, body);
      setUpload(undefined); setEditing(false); await load(); Alert.alert('Profile saved', 'The learner’s details have been updated.');
    } catch (e) { setError(e instanceof Error ? e.message : 'Unable to save learner.'); }
    finally { setBusy(false); }
  };
  const name = fields.name || 'Student profile';
  return <ScrollView contentContainerStyle={teacherScreenStyles.content} keyboardShouldPersistTaps="handled">
    <View style={styles.card}>
      <View style={styles.banner}><Text style={styles.bannerTitle}>Student profile</Text><Text style={styles.bannerSubtitle}>Learner details</Text><View pointerEvents="none" accessible={false} style={styles.artwork}><StatArtwork name="learners" color={colors.secondary} /></View></View>
      <View style={styles.body}>
        <View style={styles.identity}><StudentPhoto uri={upload?.uri ?? photo} name={name} size={128} /><Text style={styles.name}>{name}</Text>{!!fields.admission_no && <Text style={styles.admission}>{fields.admission_no}</Text>}</View>
        {busy && <Text style={styles.meta}>{editing ? 'Saving learner details…' : 'Loading learner details…'}</Text>}
        {!!error && <Text accessibilityLiveRegion="polite" style={styles.error}>{error}</Text>}
        {editing && <TeacherButton label="Change student photo" secondary disabled={busy} onPress={() => void choosePhoto()} />}
        <View style={styles.details}>{Object.entries(fields).filter(([key]) => editing || !['name', 'admission_no'].includes(key)).map(([key, value]) => <View key={key} style={styles.field}>
          <Text style={styles.label}>{fieldLabel(key)}</Text>
          {editing && key === 'gender' ? <InlineSelect label="Gender" options={[{ value: 'male', label: 'Male' }, { value: 'female', label: 'Female' }]} value={value} onChange={next => { setError(''); setFields(old => ({ ...old, [key]: next })); }} /> : editing ? <TextInput accessibilityLabel={fieldLabel(key)} editable={!busy} value={value} onChangeText={next => { setError(''); setFields(old => ({ ...old, [key]: next })); }} placeholder={key === 'date_of_birth' ? 'YYYY-MM-DD' : fieldLabel(key)} placeholderTextColor={colors.textMuted} multiline={['home_address', 'medical_notes'].includes(key)} style={teacherScreenStyles.input} /> : <Text style={styles.value}>{value || 'Not provided'}</Text>}
        </View>)}</View>
        {canEdit && (editing ? <><TeacherButton label={busy ? 'Saving…' : 'Save profile'} disabled={busy} onPress={() => void save()} /><TeacherButton label="Cancel" secondary disabled={busy} onPress={cancelEdit} /></> : <TeacherButton label="Edit student profile" secondary disabled={busy || !Object.keys(fields).length} onPress={() => { setEditing(true); setError(''); }} />)}
        {!editing && <TeacherButton label="Refresh profile" disabled={busy} onPress={() => void load()} />}
      </View>
    </View>
  </ScrollView>;
}
const baseStyles = StyleSheet.create({
  card: { backgroundColor: colors.surface, borderRadius: 24, borderWidth: 1, borderColor: colors.border, overflow: 'hidden' },
  banner: { minHeight: 116, backgroundColor: colors.primary, padding: 22, paddingBottom: 48, overflow: 'hidden' }, bannerTitle: { color: colors.secondary, fontSize: 22, fontWeight: '700' }, bannerSubtitle: { color: '#D8E2F2', fontSize: 11, marginTop: 5 }, artwork: { position: 'absolute', width: 90, height: 90, right: 12, top: 8, opacity: 0.2 },
  body: { paddingHorizontal: 18, paddingBottom: 20, gap: 12 }, identity: { alignItems: 'center', marginTop: -40, marginBottom: 8 }, name: { color: colors.primary, fontSize: 21, lineHeight: 28, fontWeight: '700', textAlign: 'center', marginTop: 12 }, admission: { color: '#8A6500', backgroundColor: '#FFF5D5', paddingHorizontal: 12, paddingVertical: 5, borderRadius: 999, fontSize: 11, fontWeight: '600', marginTop: 7 },
  details: { gap: 0 }, field: { gap: 7, paddingVertical: 13, borderBottomWidth: 1, borderBottomColor: colors.border }, label: { color: colors.textMuted, fontSize: 11, fontWeight: '600' }, value: { color: colors.primary, fontSize: 14, lineHeight: 21 }, meta: { color: colors.textMuted, fontSize: 12, textAlign: 'center' }, error: { color: '#B42318', fontSize: 12, lineHeight: 19 },
});

const styles = baseStyles;
