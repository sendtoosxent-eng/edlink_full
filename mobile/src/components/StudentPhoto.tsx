import { useThemedStyles } from '../theme/ThemeProvider';
import { useEffect, useState } from 'react';
import { Image, StyleSheet, View } from 'react-native';
import Svg, { Circle, Path } from 'react-native-svg';
import { colors } from '../theme/index';

export function StudentPhoto({ uri, name, size = 58 }: { uri?: string | null; name: string; size?: number }) {
  const styles = useThemedStyles(baseStyles);
  const [failed, setFailed] = useState(false);
  useEffect(() => { setFailed(false); }, [uri]);
  return <View style={[styles.frame, { width: size, height: size, borderRadius: size / 2 }]}>
    {uri && !failed ? <Image source={{ uri }} accessibilityLabel={`${name} photo`} resizeMode="cover" onError={() => setFailed(true)} style={styles.photo} /> : <Svg accessibilityLabel={`${name} default portrait`} width={size * 0.74} height={size * 0.74} viewBox="0 0 80 80" fill="none"><Circle cx={40} cy={26} r={15} fill={colors.secondary} /><Path d="M12 76V66c0-17 11-26 28-26s28 9 28 26v10" fill={colors.primary} /><Path d="m29 44 11 12 11-12" stroke="#FFF5D5" strokeWidth={3} strokeLinecap="round" strokeLinejoin="round" /></Svg>}
  </View>;
}
const baseStyles = StyleSheet.create({
  frame: { overflow: 'hidden', alignItems: 'center', justifyContent: 'center', backgroundColor: '#FFF5D5', borderWidth: 2, borderColor: colors.secondary, flexShrink: 0 },
  photo: { width: '100%', height: '100%' },
});

const styles = baseStyles;
