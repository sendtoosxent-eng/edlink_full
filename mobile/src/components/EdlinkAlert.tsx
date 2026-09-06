import { useSyncExternalStore } from 'react';
import { Ionicons } from '@expo/vector-icons';
import { Image, Keyboard, Modal, Pressable, ScrollView, StyleSheet, View } from 'react-native';
import { Text } from './Typography';
import { colors } from '../theme/index';

type Button = { text?: string; style?: 'default' | 'cancel' | 'destructive'; onPress?: () => void | Promise<void> };
type Prompt = { id: number; title: string; message?: string; buttons: Button[] };
let sequence = 0;
let queue: Prompt[] = [];
const listeners = new Set<() => void>();
const emit = () => listeners.forEach(listener => listener());
const subscribe = (listener: () => void) => { listeners.add(listener); return () => { listeners.delete(listener); }; };
const snapshot = () => queue[0];

/** App-owned dialogs. Device permissions and biometric prompts remain system-owned. */
export const EdlinkAlert = {
  alert(title: string, message?: string, buttons?: Button[]) {
    Keyboard.dismiss();
    queue = [...queue, { id: ++sequence, title, message, buttons: buttons?.length ? buttons : [{ text: 'Got it' }] }];
    emit();
  },
};

export function EdlinkAlertHost() {
  const prompt = useSyncExternalStore(subscribe, snapshot, snapshot);
  const choose = (button: Button) => {
    if (!prompt || queue[0]?.id !== prompt.id) return;
    queue = queue.slice(1); emit();
    try {
      Promise.resolve(button.onPress?.()).catch(() => EdlinkAlert.alert('Unable to complete this', 'Please try again.'));
    } catch { EdlinkAlert.alert('Unable to complete this', 'Please try again.'); }
  };
  const cancel = () => {
    const button = prompt?.buttons.find(item => item.style === 'cancel');
    if (button) choose(button);
    else if (prompt?.buttons.length === 1 && !prompt.buttons[0].onPress) choose(prompt.buttons[0]);
  };
  const confirming = (prompt?.buttons.length ?? 0) > 1;
  const success = /saved|updated|submitted|published|success/i.test(prompt?.title ?? '');
  const destructive = prompt?.buttons.some(button => button.style === 'destructive');
  return <Modal visible={!!prompt} transparent animationType="fade" statusBarTranslucent onRequestClose={cancel}>
    <View style={styles.backdrop}>
      <View accessibilityViewIsModal style={styles.card} key={prompt?.id}>
        <View style={styles.header}><Image source={require('../../assets/img/edlink-logo.png')} resizeMode="contain" style={styles.logo} /></View>
        <ScrollView bounces={false} contentContainerStyle={styles.content}>
          <View style={styles.symbol}><Ionicons name={destructive ? 'log-out-outline' : confirming ? 'help-circle-outline' : success ? 'checkmark-done-outline' : 'information-circle-outline'} size={38} color={colors.secondary} /></View>
          <Text accessibilityRole="header" style={styles.title}>{prompt?.title}</Text>
          {!!prompt?.message && <Text style={styles.message}>{prompt.message}</Text>}
          <View style={styles.actions}>{prompt?.buttons.slice().sort((a, b) => Number(a.style === 'cancel') - Number(b.style === 'cancel')).map((button, index) => <Pressable key={index} accessibilityRole="button" onPress={() => choose(button)} style={({ pressed }) => [styles.button, button.style === 'cancel' && styles.cancelButton, pressed && styles.pressed]}><Text style={[styles.buttonText, button.style === 'cancel' && styles.cancelText]}>{button.text ?? 'Continue'}</Text>{button.style !== 'cancel' && <Ionicons name={destructive ? 'arrow-forward-outline' : success && !confirming ? 'checkmark-outline' : 'arrow-forward-outline'} size={18} color={colors.secondary} />}</Pressable>)}</View>
        </ScrollView>
      </View>
    </View>
  </Modal>;
}
const styles = StyleSheet.create({
  backdrop: { flex: 1, justifyContent: 'center', alignItems: 'center', padding: 24, backgroundColor: 'rgba(7, 15, 38, 0.65)' },
  card: { width: '100%', maxWidth: 390, maxHeight: '85%', borderRadius: 28, backgroundColor: 'white', overflow: 'hidden' },
  header: { paddingHorizontal: 24, paddingVertical: 18, backgroundColor: '#F3F5FA', borderBottomWidth: 1, borderBottomColor: '#E8ECF4' },
  logo: { width: 112, height: 34 }, content: { padding: 24, alignItems: 'center' },
  symbol: { width: 76, height: 76, borderRadius: 25, backgroundColor: colors.primary, alignItems: 'center', justifyContent: 'center', marginBottom: 20 },
  title: { color: colors.primary, fontSize: 22, fontWeight: '800', textAlign: 'center' },
  message: { color: colors.textMuted, fontSize: 13, lineHeight: 22, marginTop: 12, textAlign: 'center' },
  actions: { width: '100%', gap: 10, marginTop: 26 },
  button: { backgroundColor: colors.primary, minHeight: 52, borderRadius: 16, paddingHorizontal: 18, paddingVertical: 13, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 10 },
  buttonText: { color: 'white', fontWeight: '700', fontSize: 14, flexShrink: 1, textAlign: 'center' },
  cancelButton: { backgroundColor: '#F0F3F8', borderWidth: 1, borderColor: '#E1E6EF' }, cancelText: { color: colors.primary }, pressed: { opacity: 0.7 },
});
