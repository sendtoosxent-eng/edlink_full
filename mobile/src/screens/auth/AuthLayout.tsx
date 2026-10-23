import { useThemedStyles } from '../../theme/ThemeProvider';
import { useEffect, useState, type ReactNode } from 'react';
import { Image, Keyboard, StyleSheet, View, useWindowDimensions } from 'react-native';
import Animated, { interpolate, useAnimatedStyle } from 'react-native-reanimated';
import { KeyboardAwareScrollView, useReanimatedKeyboardAnimation } from 'react-native-keyboard-controller';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Text } from '../../components/Typography';
import Svg, { Path } from 'react-native-svg';
import { colors } from './authTheme';
import type { SchoolIdentity } from '../../types';

export function AuthLayout({ children, navigation, school, eyebrow, heroTitle, heroSubtitle, showFooter = true, fitContentHeight, fitContent = true, showSchoolBrand = false, headerContent }: { children: ReactNode; headerContent?: ReactNode; showSchoolBrand?: boolean; fitContent?: boolean; fitContentHeight?: number; showFooter?: boolean; navigation?: ReactNode; school?: SchoolIdentity; eyebrow: string; heroTitle?: string; heroSubtitle?: string }) {
  const styles = useThemedStyles(baseStyles);
  const insets = useSafeAreaInsets();
  const { width, height, fontScale } = useWindowDimensions();
  const [measuredContentHeight, setMeasuredContentHeight] = useState(340);
  const [keyboardOpen, setKeyboardOpen] = useState(false);
  useEffect(() => {
    const show = Keyboard.addListener('keyboardDidShow', () => setKeyboardOpen(true));
    const hide = Keyboard.addListener('keyboardDidHide', () => setKeyboardOpen(false));
    return () => { show.remove(); hide.remove(); };
  }, []);
  const { progress } = useReanimatedKeyboardAnimation();
  const fitting = fitContent;
  const contentHeight = fitContentHeight ?? measuredContentHeight;
  const expandedHeight = fitting
    ? Math.max(insets.top + 100, Math.min(382 + insets.top, height - contentHeight - Math.max(insets.bottom, 24) - 20))
    : (school ? 382 : heroTitle ? 342 : 255) + insets.top;
  const identityScale = fitting ? Math.min(1, (expandedHeight - insets.top - 16) / 300) : 1;
  const schoolScale = fitting ? Math.min(1, (expandedHeight - insets.top - 16) / (showSchoolBrand ? 402 : 358)) : 1;
  const heroMotion = useAnimatedStyle(() => ({ height: interpolate(progress.value, [0, 1], [expandedHeight, insets.top + (school ? 156 : 106)]) }));
  const logoMotion = useAnimatedStyle(() => ({ transform: [{ scale: interpolate(progress.value, [0, 1], [1, 0.72]) }], height: interpolate(progress.value, [0, 1], [118, 88]) }));
  const copyMotion = useAnimatedStyle(() => ({ opacity: interpolate(progress.value, [0, 0.55, 1], [1, 0, 0]), transform: [{ translateY: -18 * progress.value }] }));
  return <View style={styles.page}>
    <Animated.View style={[styles.hero, { paddingTop: insets.top }, heroMotion]}>
      <View style={styles.glowLarge} /><View style={styles.glowSmall} />
      {headerContent ? <View style={{ width: '100%', transform: [{ scale: fitting ? Math.min(1, (expandedHeight - insets.top - 16) / 358) : 1 }], transformOrigin: 'top center' }}><Animated.View style={copyMotion}>{headerContent}</Animated.View></View> : school ? <View style={{ width: '100%', transform: [{ scale: schoolScale }], transformOrigin: 'top center' }}><SchoolHeader school={school} greeting={eyebrow} showBrand={showSchoolBrand} /></View> : <View style={{ width: '100%', transform: [{ scale: identityScale }], transformOrigin: 'top center' }}>
        <Animated.View style={[styles.identity, logoMotion]}><Image accessibilityLabel="Edlink" source={require('../../../assets/img/logo.png')} resizeMode="contain" style={{ width: 170, height: 56 }} /></Animated.View>
        <Animated.View style={[styles.heroCopy, copyMotion]}>
          <View style={styles.kicker}><Text style={styles.eyebrow}>{eyebrow}</Text></View>
          {!!heroTitle && <Text style={styles.heroTitle}>{heroTitle}</Text>}
          {!!heroSubtitle && <Text style={styles.heroSubtitle}>{heroSubtitle}</Text>}
        </Animated.View>
      </View>}
    </Animated.View>
    <Svg pointerEvents="none" width={width} height={56} viewBox="0 0 400 56" preserveAspectRatio="none" style={styles.curve}><Path d="M0 48 Q200 -40 400 48 L400 56 L0 56 Z" fill={colors.surface} /></Svg>
    <View style={styles.sheet}>{navigation && <View style={styles.sheetNavigation}>{navigation}</View>}<KeyboardAwareScrollView scrollEnabled={!fitting || keyboardOpen || fontScale > 1.4} bounces={!fitting} bottomOffset={24} keyboardShouldPersistTaps="handled" keyboardDismissMode="on-drag" showsVerticalScrollIndicator={false} contentContainerStyle={[styles.sheetScroll, fitting && { paddingTop: 12 }, !!navigation && styles.scrollWithNavigation, { paddingBottom: Math.max(insets.bottom, 24) }]}><View onLayout={event => setMeasuredContentHeight(event.nativeEvent.layout.height)} style={[styles.sheetContent, fitting && { flexGrow: 0 }]}>{children}{showFooter && <AuthFooter />}</View></KeyboardAwareScrollView></View>
  </View>;
}

function SchoolHeader({ school, greeting, showBrand = false }: { school: SchoolIdentity; greeting: string; showBrand?: boolean }) {
  const styles = useThemedStyles(baseStyles);
  const name = school.name;
  const photo = school.logo_url;
  const subtitle = school.number;
  const [width, setWidth] = useState(320);
  const { progress } = useReanimatedKeyboardAnimation();
  const greetingMotion = useAnimatedStyle(() => ({ opacity: interpolate(progress.value, [0, 0.65, 1], [1, 0, 0]), transform: [{ translateY: -8 * progress.value }] }));
  const badgeMotion = useAnimatedStyle(() => ({
    top: interpolate(progress.value, [0, 1], [showBrand ? 100 : 56, 28]),
    left: interpolate(progress.value, [0, 1], [(width - 140) / 2, 0]),
    width: interpolate(progress.value, [0, 1], [140, 68]),
    height: interpolate(progress.value, [0, 1], [140, 68]),
    borderRadius: interpolate(progress.value, [0, 1], [70, 34]),
  }));
  const stackedMotion = useAnimatedStyle(() => ({ opacity: interpolate(progress.value, [0, 0.45, 1], [1, 0, 0]), transform: [{ translateY: -16 * progress.value }] }));
  const rowMotion = useAnimatedStyle(() => ({ opacity: interpolate(progress.value, [0, 0.4, 1], [0, 0, 1]), transform: [{ translateY: 10 * (1 - progress.value) }] }));
  return <View style={[styles.schoolHeader, showBrand && { height: 402 }]} onLayout={event => setWidth(event.nativeEvent.layout.width)} accessible accessibilityLabel={`${greeting}. ${name}. ${subtitle}`}>
    {showBrand && <Animated.View style={[styles.schoolBrand, greetingMotion]}><Image source={require('../../../assets/img/logo.png')} accessibilityLabel="Edlink" resizeMode="contain" style={styles.schoolBrandImage} /></Animated.View>}
    {!!greeting && <Animated.View style={[styles.schoolGreeting, showBrand && { top: 54 }, greetingMotion]}><Text style={styles.greetingText}>{greeting}</Text></Animated.View>}
    <Animated.View style={[styles.schoolBadge, badgeMotion]}>
      {photo ? <Image source={{ uri: photo }} style={styles.badgeImage} resizeMode="contain" /> : <Text style={styles.badgeInitial}>{name.charAt(0)}</Text>}
    </Animated.View>
    <Animated.View style={[styles.stackedSchool, showBrand && { top: 254 }, stackedMotion]}>
      <Text numberOfLines={3} style={styles.largeSchoolName}>{name}</Text><Text style={styles.schoolCode}>{subtitle}</Text>
    </Animated.View>
    <Animated.View style={[styles.horizontalSchool, rowMotion]}>
      <Text numberOfLines={2} style={styles.compactSchoolName}>{name}</Text><Text style={styles.schoolCode}>{subtitle}</Text>
    </Animated.View>
  </View>;
}

export function AuthFooter() {
  const styles = useThemedStyles(baseStyles);
  return <View style={styles.footer}><View style={styles.footerLine} /><Text style={styles.footerBrand}>Edlink <Text style={styles.footerVersion}>• v1.0.0</Text></Text><Text style={styles.footerCompany}>Powered by Sponet Technologies</Text></View>;
}

const baseStyles = StyleSheet.create({
  schoolBrand: { position: 'absolute', top: 0, width: '100%', alignItems: 'center' },
  schoolBrandImage: { width: 156, height: 44 },
  page: { flex: 1, width: '100%', backgroundColor: colors.navy },
  hero: { flexShrink: 0, paddingHorizontal: 24, backgroundColor: colors.navy, alignItems: 'center', overflow: 'hidden' },
  identity: { alignItems: 'center', justifyContent: 'center', width: '100%' },
  glowLarge: { position: 'absolute', width: 190, height: 190, borderRadius: 95, backgroundColor: colors.gold, opacity: 0.16, right: -70, top: -70 },
  glowSmall: { position: 'absolute', width: 54, height: 54, borderRadius: 27, borderWidth: 9, borderColor: colors.gold, opacity: 0.18, left: -18, top: 115 },
  kicker: { borderRadius: 999, backgroundColor: colors.navy, paddingHorizontal: 13, paddingVertical: 7 },
  eyebrow: { color: colors.gold, fontSize: 9, fontWeight: '700', letterSpacing: 1.1, textTransform: 'uppercase' },
  heroCopy: { alignItems: 'center', width: '100%', maxWidth: 350 },
  heroTitle: { color: colors.surface, fontSize: 28, lineHeight: 36, fontWeight: '800', letterSpacing: -0.5, textAlign: 'center', marginTop: 16 },
  heroSubtitle: { color: colors.surface, opacity: 0.78, fontSize: 12, lineHeight: 19, textAlign: 'center', marginTop: 8 },
  schoolHeader: { width: '100%', height: 358, position: 'relative' },
  schoolGreeting: { position: 'absolute', top: 10, width: '100%', alignItems: 'center' },
  greetingText: { color: colors.surface, fontSize: 18, lineHeight: 26, fontWeight: '600' },
  schoolBadge: { position: 'absolute', backgroundColor: colors.surface, borderWidth: 3, borderColor: colors.gold, alignItems: 'center', justifyContent: 'center', overflow: 'hidden' },
  badgeImage: { width: '86%', height: '86%' }, badgeInitial: { color: colors.navy, fontSize: 36, fontWeight: '800' },
  stackedSchool: { position: 'absolute', top: 210, width: '100%', alignItems: 'center' },
  largeSchoolName: { color: colors.surface, fontSize: 27, lineHeight: 34, fontWeight: '800', textAlign: 'center' },
  schoolCode: { color: colors.surface, fontSize: 11, lineHeight: 18, fontWeight: '600', letterSpacing: 1, marginTop: 6 },
  horizontalSchool: { position: 'absolute', top: 28, left: 84, right: 0, minHeight: 68, justifyContent: 'center' },
  compactSchoolName: { color: colors.surface, fontSize: 18, lineHeight: 24, fontWeight: '700' },
  sheetNavigation: { flexShrink: 0, paddingHorizontal: 20, paddingTop: 22, paddingBottom: 4, backgroundColor: colors.surface, zIndex: 1 },
  scrollWithNavigation: { paddingTop: 0 },
  curve: { flexShrink: 0, marginTop: -55, marginBottom: -1, zIndex: 1 },
  sheet: { flex: 1, minHeight: 0, backgroundColor: colors.surface }, sheetScroll: { flexGrow: 1, paddingHorizontal: 20, paddingTop: 27 }, avatarImage: { width: '100%', height: '100%' }, sheetContent: { flexGrow: 1 },
  footer: { alignItems: 'center', marginTop: 28, paddingTop: 17 }, footerLine: { width: 36, height: 3, borderRadius: 2, backgroundColor: colors.gold, marginBottom: 10 }, footerBrand: { color: colors.navy, fontSize: 12, fontWeight: '800', letterSpacing: 0.4 }, footerVersion: { color: colors.muted, fontWeight: '500' }, footerCompany: { color: colors.muted, fontSize: 10, fontWeight: '500', marginTop: 3 },
});

const styles = baseStyles;
