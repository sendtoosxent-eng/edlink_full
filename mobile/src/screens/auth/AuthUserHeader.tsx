import { useThemedStyles } from '../../theme/ThemeProvider';
import { useEffect, useState } from 'react';
import { Image, Pressable, StyleSheet, View } from 'react-native';
import Svg, { Circle, Path } from 'react-native-svg';
import { Text } from '../../components/Typography';
import type { AccountPreview } from '../../types';
import { colors } from './authTheme';

export function AuthUserHeader({ account, email, onChangeEmail, disabled }: { account: AccountPreview | null; email: string; onChangeEmail?: () => void; disabled?: boolean }) {
  const styles = useThemedStyles(baseStyles);
  const [failed, setFailed] = useState(false);
  useEffect(() => { setFailed(false); }, [account?.avatar_url]);
  return <View style={styles.header}>
    <Image source={require('../../../assets/img/logo.png')} accessibilityLabel="Edlink" resizeMode="contain" style={styles.logo} />
    <View style={styles.avatar}>{account?.avatar_url && !failed ? <Image source={{ uri: account.avatar_url }} onError={() => setFailed(true)} accessibilityLabel={`${account.name} profile photo`} style={styles.photo} /> : <Svg width={110} height={110} viewBox="0 0 80 80" fill="none" accessibilityLabel="Default user portrait"><Circle cx={40} cy={28} r={14} fill={colors.gold} /><Path d="M14 70c0-16 10-26 26-26s26 10 26 26" fill={colors.navy} /><Path d="m31 47 9 10 9-10" stroke="#FFF5D5" strokeWidth={3} strokeLinecap="round" strokeLinejoin="round" /></Svg>}</View>
    <Text numberOfLines={1} ellipsizeMode="tail" style={styles.name}>{account?.name ?? 'Your account'}</Text>
    <Text style={styles.detail}>{email.trim()}</Text>
    {!!account?.staff_number && <Text style={styles.detail}>Staff number: {account.staff_number}</Text>}
    {!!onChangeEmail && <Pressable accessibilityRole="button" disabled={disabled} onPress={onChangeEmail} style={styles.change}><Text style={styles.changeText}>Not you? Change email</Text></Pressable>}
  </View>;
}
const baseStyles = StyleSheet.create({
  header: { alignItems: 'center', paddingTop: 8, paddingBottom: 24 },
  logo: { width: 156, height: 46, marginBottom: 20 },
  avatar: { width: 140, height: 140, borderRadius: 70, backgroundColor: '#FFF5D5', borderWidth: 2, borderColor: colors.gold, overflow: 'hidden', alignItems: 'center', justifyContent: 'center' },
  photo: { width: '100%', height: '100%' }, name: { color: colors.surface, fontSize: 20, fontWeight: '700', textAlign: 'center', marginTop: 10 },
  detail: { color: '#D1D7E2', fontSize: 12, textAlign: 'center', marginTop: 3 },
  change: { minHeight: 44, alignItems: 'center', justifyContent: 'center' }, changeText: { color: colors.gold, fontSize: 12, fontWeight: '600', textDecorationLine: 'underline' },
});

const styles = baseStyles;
