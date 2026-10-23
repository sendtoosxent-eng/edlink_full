import { useThemedStyles } from '../theme/ThemeProvider';
import { StyleSheet, View } from 'react-native';
import { SvgXml } from 'react-native-svg';
import type { Role } from '../types';
import { roleArtwork } from './roleArtwork';

export function RoleIllustration({ role, size }: { role: Role; size: number }) {
  const styles = useThemedStyles(baseStyles);
  const illustrationSize = size * 0.76;
  return <View style={[styles.circle, { width: size, height: size, borderRadius: size / 2 }]}>
    <SvgXml xml={roleArtwork[role]} width={illustrationSize} height={illustrationSize} />
  </View>;
}
const baseStyles = StyleSheet.create({
  circle: { alignItems: 'center', justifyContent: 'center', backgroundColor: '#FFF5D5', borderWidth: 2, borderColor: '#EFB000', overflow: 'hidden' },
});

const styles = baseStyles;
