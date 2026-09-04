import type { ReactNode } from 'react';
import { Image, StyleSheet, Text, View } from 'react-native';
import { BrandLogo } from '../../components/BrandLogo';
import { colors } from '../../theme';
import type { SchoolIdentity } from '../../types';

export function AuthLayout({ children, school, eyebrow }: { children: ReactNode; school?: SchoolIdentity; eyebrow: string }) {
  return <View style={styles.page}>
    <View style={styles.hero}>
      <View style={styles.glowLarge} /><View style={styles.glowSmall} />
      <Text style={styles.eyebrow}>{eyebrow}</Text>
      {school ? <View style={styles.schoolIdentity}>
        {school.logo_url ? <Image source={{ uri: school.logo_url }} style={styles.schoolLogo} resizeMode="contain" /> : <View style={styles.fallback}><Text style={styles.fallbackText}>{school.name.charAt(0)}</Text></View>}
        <Text numberOfLines={2} style={styles.schoolName}>{school.name}</Text><Text style={styles.schoolNumber}>{school.number}</Text>
      </View> : <BrandLogo />}
    </View>
    <View style={styles.sheet}>{children}</View>
  </View>;
}

const styles = StyleSheet.create({
  page: { flex: 1, width: '100%', backgroundColor: colors.gold }, hero: { minHeight: 245, paddingHorizontal: 24, paddingTop: 12, alignItems: 'center', justifyContent: 'center', overflow: 'hidden' },
  glowLarge: { position: 'absolute', width: 170, height: 170, borderRadius: 85, backgroundColor: '#FFE69A', right: -58, top: -72 }, glowSmall: { position: 'absolute', width: 54, height: 54, borderRadius: 27, borderWidth: 9, borderColor: colors.navy, opacity: 0.08, left: 20, top: 28 },
  eyebrow: { color: colors.navy, fontSize: 13, fontWeight: '900', letterSpacing: 1.4, textTransform: 'uppercase', marginBottom: 4 }, schoolIdentity: { alignItems: 'center', maxWidth: 310 }, schoolLogo: { width: 92, height: 82, marginVertical: 5 }, fallback: { width: 76, height: 76, borderRadius: 38, backgroundColor: colors.navy, alignItems: 'center', justifyContent: 'center', marginVertical: 8, borderWidth: 4, borderColor: '#FFFFFF' }, fallbackText: { color: colors.gold, fontSize: 32, fontWeight: '900' }, schoolName: { color: colors.navy, fontSize: 22, lineHeight: 27, fontWeight: '900', textAlign: 'center' }, schoolNumber: { color: colors.goldDark, fontSize: 12, fontWeight: '800', letterSpacing: 1, marginTop: 3 },
  sheet: { flex: 1, minHeight: 520, backgroundColor: colors.surface, borderTopLeftRadius: 34, borderTopRightRadius: 34, borderWidth: 2, borderBottomWidth: 0, borderColor: colors.navy, paddingHorizontal: 24, paddingTop: 26, paddingBottom: 30 },
});
