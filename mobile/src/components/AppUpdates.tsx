import { useEffect, useRef, useState } from 'react';
import { AppState, Platform, Pressable, StyleSheet, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import * as Updates from 'expo-updates';
import { Text } from './Typography';
import { EdlinkAlert } from './EdlinkAlert';

const CHECK_INTERVAL = 4 * 60 * 60 * 1000;

/** Fetch compatible bundles quietly; never reload a teacher's unfinished form. */
export function AppUpdates() {
  const { isUpdatePending, isChecking, isDownloading } = Updates.useUpdates();
  const insets = useSafeAreaInsets();
  const checking = useRef(false);
  const lastCheck = useRef(0);
  const [dismissed, setDismissed] = useState(false);
  const nativeStatus = useRef({ isChecking, isDownloading, isUpdatePending });
  nativeStatus.current = { isChecking, isDownloading, isUpdatePending };
  useEffect(() => {
    if (__DEV__ || Platform.OS === 'web' || !Updates.isEnabled) return;
    let stopped = false;
    const check = async () => {
      const native = nativeStatus.current;
      if (stopped || checking.current || native.isChecking || native.isDownloading || native.isUpdatePending || AppState.currentState !== 'active' || Date.now() - lastCheck.current < CHECK_INTERVAL) return;
      checking.current = true;
      lastCheck.current = Date.now();
      try {
        const result = await Updates.checkForUpdateAsync();
        if (!stopped && (result.isAvailable || result.isRollBackToEmbedded)) await Updates.fetchUpdateAsync();
        // expo-updates activates the downloaded bundle at the next cold start.
      } catch {
        // A failed/offline check leaves the current version fully usable.
        lastCheck.current = Date.now() - CHECK_INTERVAL + 5 * 60 * 1000;
      } finally { checking.current = false; }
    };
    const initial = setTimeout(() => { void check(); }, 30_000);
    const periodic = setInterval(() => { void check(); }, 5 * 60 * 1000);
    const subscription = AppState.addEventListener('change', state => { if (state === 'active') void check(); });
    return () => { stopped = true; clearTimeout(initial); clearInterval(periodic); subscription.remove(); };
  }, []);

  if (__DEV__ || !Updates.isEnabled || !isUpdatePending || dismissed) return null;
  const restart = () => EdlinkAlert.alert('Restart to update Edlink?', 'Save any work before restarting. Your account and saved school records will stay in place.', [
    { text: 'Later', style: 'cancel' },
    { text: 'Restart', onPress: async () => {
      try { await Updates.reloadAsync(); }
      catch { EdlinkAlert.alert('Unable to restart', 'Close and reopen Edlink to use the downloaded update.'); }
    } },
  ]);
  return <View style={[styles.banner, { top: insets.top + 6 }]} accessibilityLiveRegion="polite">
    <Text style={styles.message}>Edlink update ready for your next launch.</Text>
    <Pressable accessibilityRole="button" onPress={restart} style={styles.button}><Text style={styles.buttonText}>Restart</Text></Pressable>
    <Pressable accessibilityRole="button" accessibilityLabel="Dismiss update notice" onPress={() => setDismissed(true)} style={styles.dismiss}><Text style={styles.close}>×</Text></Pressable>
  </View>;
}

const styles = StyleSheet.create({
  banner: { position: 'absolute', left: 12, right: 12, zIndex: 100, elevation: 10, borderRadius: 14, padding: 12, backgroundColor: '#252641', flexDirection: 'row', alignItems: 'center', gap: 12 },
  message: { flex: 1, color: '#FFFFFF', fontSize: 12 },
  button: { minHeight: 44, justifyContent: 'center', paddingHorizontal: 14, borderRadius: 10, backgroundColor: '#FACC15' },
  buttonText: { color: '#252641', fontSize: 12, fontWeight: '700' },
  dismiss: { minWidth: 44, minHeight: 44, alignItems: 'center', justifyContent: 'center' },
  close: { color: '#FFFFFF', fontSize: 24 },
});
