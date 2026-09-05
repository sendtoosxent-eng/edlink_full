import { useState, type ReactNode } from 'react';
import { Image, StyleSheet, View } from 'react-native';
import Animated, { interpolate, useAnimatedStyle } from 'react-native-reanimated';
import { KeyboardAwareScrollView, useReanimatedKeyboardAnimation } from 'react-native-keyboard-controller';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Text } from '../../components/Typography';
import { BrandLogo } from '../../components/BrandLogo';
import { colors } from '../../theme';
import type { AccountIdentity, SchoolIdentity } from '../../types';

export function AuthLayout({ children, navigation, school, account, eyebrow, heroTitle, heroSubtitle }: { children: ReactNode; navigation?: ReactNode; school?: SchoolIdentity; account?: AccountIdentity; eyebrow: string; heroTitle?: string; heroSubtitle?: string }) {
  const insets = useSafeAreaInsets();
  const { progress } = useReanimatedKeyboardAnimation();
  const expandedHeight = (school || heroTitle ? 342 : 255) + insets.top;
  const heroMotion = useAnimatedStyle(() => ({ height: interpolate(progress.value, [0, 1], [expandedHeight, insets.top + (school ? 156 : 106)]) }));
  const logoMotion = useAnimatedStyle(() => ({ transform: [{ scale: interpolate(progress.value, [0, 1], [1, 0.72]) }], height: interpolate(progress.value, [0, 1], [118, 88]) }));
  const copyMotion = useAnimatedStyle(() => ({ opacity: interpolate(progress.value, [0, 0.55, 1], [1, 0, 0]), transform: [{ translateY: -18 * progress.value }] }));
  return <View style={styles.page}>
    <Animated.View style={[styles.hero, { paddingTop: insets.top }, heroMotion]}>
      <View style={styles.glowLarge} /><View style={styles.glowSmall} />
      {school ? <SchoolHeader school={school} account={account} greeting={eyebrow} /> : <>
        <Animated.View style={[styles.identity, logoMotion]}><BrandLogo /></Animated.View>
        <Animated.View style={[styles.heroCopy, copyMotion]}>
          <View style={styles.kicker}><Text style={styles.eyebrow}>{eyebrow}</Text></View>
          {!!heroTitle && <Text style={styles.heroTitle}>{heroTitle}</Text>}
          {!!heroSubtitle && <Text style={styles.heroSubtitle}>{heroSubtitle}</Text>}
        </Animated.View>
      </>}
    </Animated.View>
    <View style={styles.sheet}>{navigation && <View style={styles.sheetNavigation}>{navigation}</View>}<KeyboardAwareScrollView bottomOffset={24} keyboardShouldPersistTaps="handled" keyboardDismissMode="on-drag" showsVerticalScrollIndicator={false} contentContainerStyle={[styles.sheetScroll, !!navigation && styles.scrollWithNavigation, { paddingBottom: Math.max(insets.bottom, 24) }]}><View style={styles.sheetContent}>{children}</View><AuthFooter /></KeyboardAwareScrollView></View>
  </View>;
}

function SchoolHeader({ school, account, greeting }: { school: SchoolIdentity; account?: AccountIdentity; greeting: string }) {
  const name = account?.name ?? school.name;
  const photo = account ? account.avatar_url : school.logo_url;
  const subtitle = account ? 'Welcome to your workspace' : school.number;
  const [width, setWidth] = useState(320);
  const { progress } = useReanimatedKeyboardAnimation();
  const greetingMotion = useAnimatedStyle(() => ({ opacity: interpolate(progress.value, [0, 0.65, 1], [1, 0, 0]), transform: [{ translateY: -8 * progress.value }] }));
  const badgeMotion = useAnimatedStyle(() => ({
    top: interpolate(progress.value, [0, 1], [56, 28]),
    left: interpolate(progress.value, [0, 1], [(width - 112) / 2, 0]),
    width: interpolate(progress.value, [0, 1], [112, 68]),
    height: interpolate(progress.value, [0, 1], [112, 68]),
    borderRadius: interpolate(progress.value, [0, 1], [56, 34]),
  }));
  const stackedMotion = useAnimatedStyle(() => ({ opacity: interpolate(progress.value, [0, 0.45, 1], [1, 0, 0]), transform: [{ translateY: -16 * progress.value }] }));
  const rowMotion = useAnimatedStyle(() => ({ opacity: interpolate(progress.value, [0, 0.4, 1], [0, 0, 1]), transform: [{ translateY: 10 * (1 - progress.value) }] }));
  return <View style={styles.schoolHeader} onLayout={event => setWidth(event.nativeEvent.layout.width)} accessible accessibilityLabel={`${greeting}. ${name}. ${subtitle}`}>
    <Animated.View style={[styles.schoolGreeting, greetingMotion]}><Text style={styles.greetingText}>{greeting}</Text></Animated.View>
    <Animated.View style={[styles.schoolBadge, badgeMotion]}>
      {photo ? <Image source={{ uri: photo }} style={account ? styles.avatarImage : styles.badgeImage} resizeMode={account ? "cover" : "contain"} /> : <Text style={styles.badgeInitial}>{name.charAt(0)}</Text>}
    </Animated.View>
    <Animated.View style={[styles.stackedSchool, stackedMotion]}>
      <Text numberOfLines={3} style={styles.largeSchoolName}>{name}</Text><Text style={styles.schoolCode}>{subtitle}</Text>
    </Animated.View>
    <Animated.View style={[styles.horizontalSchool, rowMotion]}>
      <Text numberOfLines={2} style={styles.compactSchoolName}>{name}</Text><Text style={styles.schoolCode}>{subtitle}</Text>
    </Animated.View>
  </View>;
}

export function AuthFooter() {
  return <View style={styles.footer}><View style={styles.footerLine} /><Text style={styles.footerBrand}>Edlink <Text style={styles.footerVersion}>• v1.0.0</Text></Text><Text style={styles.footerCompany}>Powered by Sponet Technologies</Text></View>;
}

const styles = StyleSheet.create({
  page: { flex: 1, width: '100%', backgroundColor: colors.gold },
  hero: { flexShrink: 0, paddingHorizontal: 24, backgroundColor: colors.gold, alignItems: 'center', overflow: 'hidden' },
  identity: { alignItems: 'center', justifyContent: 'center', width: '100%' },
  glowLarge: { position: 'absolute', width: 190, height: 190, borderRadius: 95, backgroundColor: '#FFFFFF', opacity: 0.2, right: -70, top: -70 },
  glowSmall: { position: 'absolute', width: 54, height: 54, borderRadius: 27, borderWidth: 9, borderColor: '#FFFFFF', opacity: 0.16, left: -18, top: 115 },
  kicker: { borderRadius: 999, backgroundColor: colors.navy, paddingHorizontal: 13, paddingVertical: 7 },
  eyebrow: { color: '#FFFFFF', fontSize: 9, fontWeight: '700', letterSpacing: 1.1, textTransform: 'uppercase' },
  heroCopy: { alignItems: 'center', width: '100%', maxWidth: 350 },
  heroTitle: { color: colors.navy, fontSize: 28, lineHeight: 36, fontWeight: '800', letterSpacing: -0.5, textAlign: 'center', marginTop: 16 },
  heroSubtitle: { color: colors.navy, opacity: 0.78, fontSize: 12, lineHeight: 19, textAlign: 'center', marginTop: 8 },
  schoolHeader: { width: '100%', height: 318, position: 'relative' },
  schoolGreeting: { position: 'absolute', top: 10, width: '100%', alignItems: 'center' },
  greetingText: { color: colors.navy, fontSize: 18, lineHeight: 26, fontWeight: '600' },
  schoolBadge: { position: 'absolute', backgroundColor: colors.surface, borderWidth: 3, borderColor: colors.navy, alignItems: 'center', justifyContent: 'center', overflow: 'hidden' },
  badgeImage: { width: '78%', height: '78%' }, badgeInitial: { color: colors.navy, fontSize: 36, fontWeight: '800' },
  stackedSchool: { position: 'absolute', top: 184, width: '100%', alignItems: 'center' },
  largeSchoolName: { color: colors.navy, fontSize: 27, lineHeight: 34, fontWeight: '800', textAlign: 'center' },
  schoolCode: { color: colors.navy, fontSize: 11, lineHeight: 18, fontWeight: '600', letterSpacing: 1, marginTop: 6 },
  horizontalSchool: { position: 'absolute', top: 28, left: 84, right: 0, minHeight: 68, justifyContent: 'center' },
  compactSchoolName: { color: colors.navy, fontSize: 18, lineHeight: 24, fontWeight: '700' },
  sheetNavigation: { flexShrink: 0, paddingHorizontal: 20, paddingTop: 22, paddingBottom: 4, backgroundColor: colors.surface, zIndex: 1 },
  scrollWithNavigation: { paddingTop: 0 },
  sheet: { flex: 1, minHeight: 0, overflow: 'hidden', marginTop: -24, backgroundColor: colors.surface, borderTopLeftRadius: 32, borderTopRightRadius: 32, borderWidth: 2, borderBottomWidth: 0, borderColor: colors.navy, }, sheetScroll: { flexGrow: 1, paddingHorizontal: 20, paddingTop: 27 }, avatarImage: { width: '100%', height: '100%' }, sheetContent: { flexGrow: 1 },
  footer: { alignItems: 'center', marginTop: 28, paddingTop: 17 }, footerLine: { width: 36, height: 3, borderRadius: 2, backgroundColor: colors.gold, marginBottom: 10 }, footerBrand: { color: colors.navy, fontSize: 12, fontWeight: '800', letterSpacing: 0.4 }, footerVersion: { color: colors.muted, fontWeight: '500' }, footerCompany: { color: colors.muted, fontSize: 10, fontWeight: '500', marginTop: 3 },
});
