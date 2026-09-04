import { useEffect, useState, type ReactNode } from 'react';
import { Image, Keyboard, Platform, StyleSheet, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { BrandLogo } from '../../components/BrandLogo';
import { MotionView } from '../../components/MotionView';
import { colors } from '../../theme';
import type { SchoolIdentity } from '../../types';

export function AuthLayout({ children, school, eyebrow, heroTitle, heroSubtitle }: { children: ReactNode; school?: SchoolIdentity; eyebrow: string; heroTitle?: string; heroSubtitle?: string }) {
  const [keyboardVisible, setKeyboardVisible] = useState(false);
  const insets = useSafeAreaInsets();
  useEffect(() => {
    const show = Keyboard.addListener(Platform.OS === 'ios' ? 'keyboardWillShow' : 'keyboardDidShow', () => setKeyboardVisible(true));
    const hide = Keyboard.addListener(Platform.OS === 'ios' ? 'keyboardWillHide' : 'keyboardDidHide', () => setKeyboardVisible(false));
    return () => { show.remove(); hide.remove(); };
  }, []);
  return <View style={styles.page}>
    <MotionView distance={0} style={[styles.hero, !!heroTitle && styles.heroDetailed, { paddingTop: keyboardVisible ? 2 : insets.top + 8 }, keyboardVisible && styles.heroKeyboard]}>
      <View style={styles.glowLarge} /><View style={styles.glowSmall} />
      {!keyboardVisible && <View style={styles.kicker}><Text style={styles.eyebrow}>{eyebrow}</Text></View>}
      <MotionView distance={-8}>
      <MotionView float>
      {school ? <View style={[styles.schoolIdentity, keyboardVisible && styles.schoolIdentityKeyboard]}>
        {school.logo_url ? <Image source={{ uri: school.logo_url }} style={[styles.schoolLogo, keyboardVisible && styles.schoolLogoKeyboard]} resizeMode="contain" /> : <View style={[styles.fallback, keyboardVisible && styles.fallbackKeyboard]}><Text style={[styles.fallbackText, keyboardVisible && styles.fallbackTextKeyboard]}>{school.name.charAt(0)}</Text></View>}
        <Text numberOfLines={2} style={styles.schoolName}>{school.name}</Text><Text style={styles.schoolNumber}>{school.number}</Text>
      </View> : <BrandLogo compact={keyboardVisible} />}
      </MotionView>
      </MotionView>
      {!keyboardVisible && heroTitle ? <View style={styles.heroCopy}>
        <Text style={styles.heroTitle}>{heroTitle}</Text>
        {!!heroSubtitle && <Text style={styles.heroSubtitle}>{heroSubtitle}</Text>}
      </View> : null}
    </MotionView>
    <MotionView distance={22} style={[styles.sheet, keyboardVisible && styles.sheetKeyboard]}><View style={styles.sheetContent}>{children}</View>{!keyboardVisible && <AuthFooter />}</MotionView>
  </View>;
}

export function AuthFooter() {
  return <View style={styles.footer}><View style={styles.footerLine} /><Text style={styles.footerBrand}>Edlink <Text style={styles.footerVersion}>• v1.0.0</Text></Text><Text style={styles.footerCompany}>Powered by Sponet Technologies</Text></View>;
}

const styles = StyleSheet.create({
  page: { flex: 1, width: '100%', backgroundColor: colors.surface }, hero: { minHeight: 225, paddingHorizontal: 24, paddingTop: 8, backgroundColor: colors.gold, alignItems: 'center', justifyContent: 'center', overflow: 'hidden' }, heroDetailed: { minHeight: 325 },
  heroKeyboard: { minHeight: 160, paddingTop: 6 },
  glowLarge: { position: 'absolute', width: 170, height: 170, borderRadius: 85, backgroundColor: '#FFFFFF', opacity: 0.18, right: -58, top: -72 }, glowSmall: { position: 'absolute', width: 54, height: 54, borderRadius: 27, borderWidth: 9, borderColor: '#FFFFFF', opacity: 0.2, left: 20, top: 28 },
  kicker: { position: 'absolute', top: 52, borderRadius: 999, backgroundColor: colors.navy, paddingHorizontal: 13, paddingVertical: 7 }, eyebrow: { color: '#FFFFFF', fontSize: 9, fontWeight: '900', letterSpacing: 1.1, textTransform: 'uppercase' }, heroCopy: { alignItems: 'center', maxWidth: 340, marginTop: -8 }, heroTitle: { color: colors.navy, fontSize: 29, lineHeight: 35, fontWeight: '900', letterSpacing: -0.5, textAlign: 'center' }, heroSubtitle: { color: colors.navy, opacity: 0.78, fontSize: 13, lineHeight: 20, textAlign: 'center', marginTop: 7 }, schoolIdentity: { alignItems: 'center', maxWidth: 310 }, schoolLogo: { width: 92, height: 82, marginVertical: 5 }, fallback: { width: 76, height: 76, borderRadius: 38, backgroundColor: colors.navy, alignItems: 'center', justifyContent: 'center', marginVertical: 8, borderWidth: 4, borderColor: '#FFFFFF' }, fallbackText: { color: colors.gold, fontSize: 32, fontWeight: '900' }, schoolName: { color: colors.navy, fontSize: 22, lineHeight: 27, fontWeight: '900', textAlign: 'center' }, schoolNumber: { color: colors.goldDark, fontSize: 12, fontWeight: '800', letterSpacing: 1, marginTop: 3 },
  schoolIdentityKeyboard: { flexDirection: 'row', gap: 10 }, schoolLogoKeyboard: { width: 48, height: 48, marginVertical: 0 }, fallbackKeyboard: { width: 46, height: 46, borderRadius: 23, borderWidth: 2, marginVertical: 0 }, fallbackTextKeyboard: { fontSize: 20 },
  sheet: { flex: 1, minHeight: 535, marginTop: -18, backgroundColor: colors.surface, borderTopLeftRadius: 34, borderTopRightRadius: 34, borderWidth: 2, borderBottomWidth: 0, borderColor: colors.navy, paddingHorizontal: 24, paddingTop: 26, paddingBottom: 20, zIndex: 2 }, sheetContent: { flexGrow: 1 },
  sheetKeyboard: { minHeight: 0, borderTopLeftRadius: 28, borderTopRightRadius: 28, paddingTop: 22, paddingBottom: 16 },
  footer: { alignItems: 'center', marginTop: 34, paddingTop: 17 }, footerLine: { width: 36, height: 3, borderRadius: 2, backgroundColor: colors.gold, marginBottom: 10 }, footerBrand: { color: colors.navy, fontSize: 12, fontWeight: '900', letterSpacing: 0.4 }, footerVersion: { color: colors.muted, fontWeight: '700' }, footerCompany: { color: colors.muted, fontSize: 10, fontWeight: '600', marginTop: 3 },
});
