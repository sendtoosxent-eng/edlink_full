import { useState } from 'react';
import { Alert, Image, Modal, Pressable, ScrollView, View } from 'react-native';
import * as FileSystem from 'expo-file-system/legacy';
import * as Sharing from 'expo-sharing';
import * as Print from 'expo-print';
import { API_URL } from '../api';
import { Text } from './Typography';
import { colors } from '../theme/index';
export function NativeAttachment({ token, path, name }: { token: string; path: string; name?: string | null }) {
  const [busy, setBusy] = useState(false); const [preview, setPreview] = useState<string>(); const [text, setText] = useState<string>();
  if (!name) return null;
  const open = async () => {
    if (busy) return; setBusy(true);
    const safeName = name.replace(/[^a-zA-Z0-9._-]/g, '_'); const uri = `${FileSystem.cacheDirectory}edlink-${Date.now()}-${safeName}`;
    let keepPreview = false;
    try {
      const file = await FileSystem.downloadAsync(`${API_URL}${path}`, uri, { headers: { Authorization: `Bearer ${token}` } });
      if (file.status !== 200) throw new Error('Attachment unavailable. Refresh and try again.');
      if (/\.(png|jpe?g|webp)$/i.test(name)) { keepPreview = true; setPreview(uri); return; }
      if (/\.txt$/i.test(name)) { setText(await FileSystem.readAsStringAsync(uri)); return; }
      if (/\.pdf$/i.test(name)) await Print.printAsync({ uri });
      else if (await Sharing.isAvailableAsync()) await Sharing.shareAsync(uri, { dialogTitle: name });
      else throw new Error('This device cannot preview this file type.');
    } catch (e) { Alert.alert('Attachment', e instanceof Error ? e.message : 'Unable to open attachment.'); }
    finally { setBusy(false); if (!keepPreview) await FileSystem.deleteAsync(uri, { idempotent: true }).catch(() => undefined); }
  };
  const close = () => { if (preview) void FileSystem.deleteAsync(preview, { idempotent: true }).catch(() => undefined); setPreview(undefined); setText(undefined); };
  return <><Pressable disabled={busy} onPress={() => void open()} style={{ paddingVertical: 13 }}><Text style={{ color: colors.primary, fontWeight: '700' }}>{busy ? 'Opening attachment…' : `Attachment: ${name}`}</Text></Pressable><Modal visible={!!preview || text !== undefined} onRequestClose={close} animationType="slide"><View style={{ flex: 1, backgroundColor: colors.background, padding: 22, paddingTop: 55 }}><Pressable onPress={close}><Text style={{ paddingVertical: 16, color: colors.primary }}>Close attachment</Text></Pressable>{preview ? <Image source={{ uri: preview }} style={{ flex: 1 }} resizeMode="contain" /> : <ScrollView><Text>{text}</Text></ScrollView>}</View></Modal></>;
}
