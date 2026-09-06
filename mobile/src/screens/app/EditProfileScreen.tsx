import { useState } from 'react';
import { Alert, Image, Pressable, StyleSheet, View } from 'react-native';
import { KeyboardAwareScrollView } from 'react-native-keyboard-controller';
import { Ionicons } from '@expo/vector-icons';
import * as DocumentPicker from 'expo-document-picker';
import { Text, TextInput } from '../../components/Typography';
import { ErrorScreen } from '../../components/ErrorScreen';
import { api, ApiError } from '../../api';
import { colors } from '../../theme/index';
import type { User } from '../../types';

export function EditProfileScreen({ token, user, onSaved, onBack }: { token: string; user: User; onSaved: (user: User) => void; onBack: () => void }) {
  const [name, setName] = useState(user.name);
  const [email, setEmail] = useState(user.email);
  const [phone, setPhone] = useState(user.phone ?? '');
  const [photo, setPhoto] = useState<DocumentPicker.DocumentPickerAsset>();
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');
  const [failure, setFailure] = useState<ApiError>();
  const pickPhoto = async () => {
    try {
      const result = await DocumentPicker.getDocumentAsync({ type: ['image/jpeg', 'image/png', 'image/webp'], copyToCacheDirectory: true });
      if (result.canceled) return;
      const asset = result.assets[0];
      if (asset.size && asset.size > 2 * 1024 * 1024) { setError('Choose a photo smaller than 2 MB.'); return; }
      setPhoto(asset); setError('');
    } catch { setError('Unable to choose a photo. Please try again.'); }
  };
  const save = async () => {
    if (busy) return;
    if (!name.trim() || !email.trim()) { setError('Enter your name and email address.'); return; }
    setBusy(true); setError('');
    try {
      const body = new FormData();
      body.append('name', name.trim()); body.append('email', email.trim().toLowerCase()); body.append('phone', phone.trim());
      if (photo) body.append('photo', { uri: photo.uri, name: photo.name, type: photo.mimeType ?? 'image/jpeg' } as unknown as Blob);
      const updated = (await api.upload<User>('/auth/profile', token, body)).data;
      onSaved(updated);
      Alert.alert('Profile updated', updated.email !== user.email ? 'Your changes are saved. Check your new email address for a verification link.' : 'Your changes are saved.');
    } catch (e) {
      if (e instanceof ApiError && (e.status === 0 || e.status === 408 || e.status >= 500)) setFailure(e);
      else setError(e instanceof Error ? e.message : 'Unable to save your profile.');
    } finally { setBusy(false); }
  };
  if (failure) return <ErrorScreen message={failure.message} status={failure.status} retry={async () => { setFailure(undefined); await save(); }} onBack={() => setFailure(undefined)} />;
  const avatar = photo?.uri ?? user.avatar_url;
  return <KeyboardAwareScrollView bottomOffset={28} keyboardShouldPersistTaps="handled" contentContainerStyle={styles.content}>
    <Pressable accessibilityRole="button" disabled={busy} onPress={onBack} style={styles.back}><Ionicons name="arrow-back" size={22} color={colors.primary} /><Text style={styles.backText}>My profile</Text></Pressable>
    <Text accessibilityRole="header" style={styles.title}>Edit profile</Text><Text style={styles.lead}>Keep your school account up to date.</Text>
    <View style={styles.photoPanel}><View style={styles.avatar}>{avatar ? <Image source={{ uri: avatar }} style={styles.image} /> : <Ionicons name="person" size={50} color={colors.secondary} />}</View><Pressable accessibilityRole="button" disabled={busy} onPress={() => void pickPhoto()} style={styles.photoButton}><Ionicons name="camera-outline" size={20} color={colors.secondary} /><Text style={styles.photoText}>Change photo</Text></Pressable><Text style={styles.photoHint}>JPG, PNG or WebP · Up to 2 MB</Text></View>
    <View style={styles.form}>
      <Text style={styles.label}>Full name</Text><TextInput accessibilityLabel="Full name" editable={!busy} value={name} onChangeText={setName} autoComplete="name" style={styles.input} />
      <Text style={styles.label}>Email address</Text><TextInput accessibilityLabel="Email address" editable={!busy} value={email} onChangeText={setEmail} keyboardType="email-address" autoCapitalize="none" autoCorrect={false} autoComplete="email" style={styles.input} />
      <Text style={styles.label}>Phone number</Text><TextInput accessibilityLabel="Phone number" editable={!busy} value={phone} onChangeText={setPhone} keyboardType="phone-pad" autoComplete="tel" placeholder="Add your phone number" style={styles.input} />
      <Text style={styles.note}>Changing your email also changes the address you use to sign in.</Text>
      {!!error && <Text accessibilityLiveRegion="polite" style={styles.error}>{error}</Text>}
      <Pressable accessibilityRole="button" disabled={busy} onPress={() => void save()} style={[styles.save, busy && { opacity: 0.6 }]}><Text style={styles.saveText}>{busy ? 'Saving…' : 'Save changes'}</Text></Pressable>
    </View>
  </KeyboardAwareScrollView>;
}
const styles = StyleSheet.create({ content: { padding: 22, paddingBottom: 40, gap: 10 }, back: { flexDirection: 'row', alignItems: 'center', gap: 9, minHeight: 44 }, backText: { color: colors.primary, fontWeight: '600' }, title: { fontSize: 28, fontWeight: '800', color: colors.primary }, lead: { color: colors.textMuted, fontSize: 13 }, photoPanel: { backgroundColor: colors.primary, borderRadius: 24, alignItems: 'center', padding: 24, marginVertical: 12 }, avatar: { width: 104, height: 104, borderRadius: 52, borderWidth: 3, borderColor: colors.secondary, alignItems: 'center', justifyContent: 'center', overflow: 'hidden' }, image: { width: '100%', height: '100%' }, photoButton: { flexDirection: 'row', gap: 8, padding: 14, alignItems: 'center' }, photoText: { color: 'white', fontWeight: '600' }, photoHint: { color: '#CBD3E4', fontSize: 10 }, form: { gap: 10 }, label: { color: colors.primary, fontWeight: '600', fontSize: 13, marginTop: 8 }, input: { minHeight: 54, backgroundColor: 'white', borderRadius: 14, borderWidth: 1, borderColor: '#DDE3ED', padding: 14, color: colors.primary }, note: { color: colors.textMuted, fontSize: 11, lineHeight: 18, marginVertical: 8 }, error: { color: '#B42318', fontSize: 13 }, save: { backgroundColor: colors.primary, borderRadius: 16, minHeight: 56, justifyContent: 'center', alignItems: 'center' }, saveText: { color: 'white', fontWeight: '700', fontSize: 15 } });
