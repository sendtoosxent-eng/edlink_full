import { useState } from 'react';
import { Ionicons } from '@expo/vector-icons';
import { Pressable, ScrollView, StyleSheet, View } from 'react-native';
import { Text } from './Typography';
import { colors } from '../theme/index';

export function ErrorScreen({ message, retry, onBack, status }: { message: string; retry: () => void | Promise<void>; onBack?: () => void; status?: number }) {
  const [retrying, setRetrying] = useState(false);
  const offline = status === 0 || /cannot connect|internet|network/i.test(message);
  const timeout = status === 408 || /too long|timed out/i.test(message);
  const title = offline ? 'Let’s get you connected' : timeout ? 'Taking a little longer' : status === 403 ? 'This page is restricted' : 'Something went wrong';
  const description = offline ? 'We couldn’t reach Edlink. Check your Wi-Fi or mobile data, then try again.' : timeout ? 'Edlink is taking too long to respond. Give it a moment and try again.' : message;
  const tryAgain = async () => { if (retrying) return; setRetrying(true); try { await retry(); } finally { setRetrying(false); } };
  return <ScrollView contentContainerStyle={styles.page}>
    <View style={styles.art}><View style={styles.halo} /><View style={styles.icon}><Ionicons name={offline ? 'cloud-offline-outline' : timeout ? 'time-outline' : 'alert-circle-outline'} size={66} color={colors.secondary} /></View><View style={styles.badge}><Ionicons name="refresh" size={22} color={colors.primary} /></View></View>
    <Text style={styles.kicker}>{offline ? 'CONNECTION INTERRUPTED' : 'PLEASE TRY AGAIN'}</Text>
    <Text accessibilityRole="header" style={styles.title}>{title}</Text>
    <Text accessibilityLiveRegion="polite" style={styles.description}>{description}</Text>
    <Pressable accessibilityRole="button" disabled={retrying} onPress={() => void tryAgain()} style={[styles.button, retrying && { opacity: 0.6 }]}><Ionicons name="refresh-outline" size={20} color={colors.secondary} /><Text style={styles.buttonText}>{retrying ? 'Trying again…' : 'Try again'}</Text></Pressable>
    {onBack && <Pressable accessibilityRole="button" onPress={onBack} style={styles.back}><Text style={styles.backText}>Go back</Text></Pressable>}
    {offline && <Text style={styles.hint}>Your school account is still here.</Text>}
  </ScrollView>;
}
const styles = StyleSheet.create({
  page: { flexGrow: 1, justifyContent: 'center', alignItems: 'center', padding: 28, backgroundColor: colors.background },
  art: { width: 190, height: 182, alignItems: 'center', justifyContent: 'center', marginBottom: 25 },
  halo: { position: 'absolute', width: 180, height: 180, borderRadius: 90, backgroundColor: '#E8ECF4' },
  icon: { width: 136, height: 136, borderRadius: 44, backgroundColor: colors.primary, alignItems: 'center', justifyContent: 'center', transform: [{ rotate: '-7deg' }] },
  badge: { position: 'absolute', bottom: 8, right: 10, width: 48, height: 48, borderRadius: 24, backgroundColor: colors.secondary, borderWidth: 5, borderColor: colors.background, alignItems: 'center', justifyContent: 'center' },
  kicker: { color: colors.textMuted, fontSize: 10, fontWeight: '700', letterSpacing: 1.5, marginBottom: 12 },
  title: { color: colors.primary, fontSize: 26, fontWeight: '800', textAlign: 'center', maxWidth: 340 },
  description: { color: colors.textMuted, fontSize: 14, lineHeight: 24, textAlign: 'center', marginTop: 14, maxWidth: 330 },
  button: { flexDirection: 'row', gap: 10, minHeight: 56, borderRadius: 18, backgroundColor: colors.primary, alignItems: 'center', justifyContent: 'center', alignSelf: 'stretch', maxWidth: 330, width: '100%', marginTop: 30 },
  buttonText: { color: 'white', fontWeight: '700', fontSize: 15 }, back: { padding: 16 }, backText: { color: colors.primary, fontWeight: '600' }, hint: { color: colors.textMuted, fontSize: 11, marginTop: 22 },
});
