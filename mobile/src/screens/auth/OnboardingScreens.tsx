import { useThemedStyles } from '../../theme/ThemeProvider';
import React from 'react';
import { AccessibilityInfo, Animated, AppState, Easing, Image, type ImageSourcePropType, Pressable, ScrollView, StyleSheet, View, useWindowDimensions } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import Svg, { Path } from 'react-native-svg';
import { OnboardingIllustration } from '../../components/OnboardingIllustration';
import { Text } from '../../components/Typography';

type Controls = { onNext: () => void; onBack?: () => void; onSkip?: () => void };
const BRAND = { navy: '#0B132B', gold: '#EFB000', white: '#FFFFFF', muted: '#707785' };

// Increase to move the white curve lower; decrease to move it higher.
const CURVE_OFFSET = 26;

type OnboardingProps = Controls & {
  index: number;
  source: ImageSourcePropType;
  imageLabel: string;
  title: string;
  description: string;
};

function useBreathingMotion() {
  const motion = React.useRef(new Animated.Value(0)).current;
  React.useEffect(() => {
    let reduced = true;
    let disposed = false;
    let loop: Animated.CompositeAnimation | undefined;
    const update = () => {
      loop?.stop();
      motion.setValue(0);
      if (disposed || reduced || AppState.currentState !== 'active') return;
      loop = Animated.loop(Animated.sequence([
        Animated.timing(motion, { toValue: 1, duration: 2600, easing: Easing.inOut(Easing.sin), useNativeDriver: true }),
        Animated.timing(motion, { toValue: 0, duration: 2600, easing: Easing.inOut(Easing.sin), useNativeDriver: true }),
      ]));
      loop.start();
    };
    const preference = AccessibilityInfo.addEventListener('reduceMotionChanged', value => { reduced = value; update(); });
    const activity = AppState.addEventListener('change', update);
    void AccessibilityInfo.isReduceMotionEnabled().then(value => { if (!disposed) { reduced = value; update(); } });
    return () => { disposed = true; loop?.stop(); preference.remove(); activity.remove(); };
  }, [motion]);
  return motion;
}

function OnboardingScreen({ index, source, imageLabel, title, description, onNext, onBack, onSkip }: OnboardingProps) {
  const styles = useThemedStyles(baseStyles);
  const insets = useSafeAreaInsets();
  const { height, width, fontScale } = useWindowDimensions();
  const compact = height < 720;
  const heroHeight = Math.max(320, Math.min(height * 0.58, 620)) + CURVE_OFFSET;
  const availableImageHeight = Math.max(220, heroHeight - insets.top - 64);
  const asset = Image.resolveAssetSource(source);
  const imageWidth = Math.min(width * 1.12, 460, availableImageHeight * asset.width / asset.height);
  const imageHeight = imageWidth * asset.height / asset.width;
  const motion = useBreathingMotion();
  return (
    <ScrollView style={styles.screen} contentContainerStyle={{ minHeight: height, flexGrow: 1 }} bounces={false} showsVerticalScrollIndicator={false}>
      <View style={[styles.hero, { height: heroHeight }]}>
        <View pointerEvents="none" style={styles.halo} />
        <View style={[styles.topBar, { top: insets.top + 10 }]}>
          <View style={styles.headerSide}>{onBack && <Pressable accessibilityRole="button" accessibilityLabel="Previous introduction" onPress={onBack} style={styles.backButton}><Svg width={22} height={22} viewBox="0 0 24 24" fill="none"><Path d="m14 6-6 6 6 6M8 12h12" stroke={BRAND.white} strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round" /></Svg></Pressable>}</View>
          <Image source={require('../../../assets/img/logo.png')} accessibilityLabel="Edlink — Innovating paperless learning" resizeMode="contain" style={styles.brandLogo} />
          <View style={[styles.headerSide, styles.headerRight]}>{onSkip && <Pressable accessibilityRole="button" onPress={onSkip} hitSlop={8} style={styles.skipButton}><Text numberOfLines={1} style={styles.skipText}>Skip</Text></Pressable>}</View>
        </View>
        <Animated.Image source={source} accessibilityLabel={imageLabel} resizeMode="contain" style={[styles.heroImage, { width: imageWidth, height: imageHeight, transform: [{ scale: motion.interpolate({ inputRange: [0, 1], outputRange: [1, 1.025] }) }, { translateY: motion.interpolate({ inputRange: [0, 1], outputRange: [0, -4] }) }] }]} />
        <View pointerEvents="none" style={[styles.floatingIcon, styles.leftArtwork]}><OnboardingIllustration name={index === 0 ? 'learning' : index === 1 ? 'connection' : 'growth'} /></View>
        <View pointerEvents="none" style={[styles.floatingIcon, styles.rightArtwork]}><OnboardingIllustration name="orbit" size={50} /></View>
        <View pointerEvents="none" style={styles.goldDot} />
      </View>
      <View style={styles.sheet}>
        <Svg pointerEvents="none" width={width} height={56} viewBox="0 0 400 56" preserveAspectRatio="none" style={styles.curve}><Path d="M0 48 Q200 -40 400 48 L400 56 L0 56 Z" fill={BRAND.white} /></Svg>
        <View style={[styles.sheetContent, { paddingBottom: Math.max(insets.bottom + 16, 28), paddingTop: compact ? 12 : 20 }]}>
        <View style={styles.copy}>
          <Text accessibilityRole="header" style={[styles.title, compact && styles.titleCompact]}>{title}</Text>
          <Text style={styles.description}>{description}</Text>
        </View>
        <View accessible accessibilityLabel={`Introduction ${index + 1} of 3`} style={styles.dots}>
          {[0, 1, 2].map(dot => <View key={dot} style={[styles.dot, dot === index && styles.activeDot]} />)}
        </View>
        <View style={styles.buttonArea}>
        <Pressable accessibilityRole="button" onPress={onNext} style={({ pressed }) => [styles.nextButton, { marginTop: fontScale > 1.3 || compact ? 22 : 30 }, pressed && styles.pressed]}>
          <Text style={styles.nextText}>{index === 2 ? 'Get started' : 'Next'}</Text>
        </Pressable>
        </View>
        </View>
      </View>
    </ScrollView>
  );
}

export function ConnectOnboardingScreen(props: Controls) {
  return <OnboardingScreen {...props} index={0} source={require('../../../assets/img/onboad.png')} imageLabel="Smiling student holding school books" title={'Your school day,\nall in one place'} description="Connect with your school. Stay close to every moment of learning." />;
}

export function InformOnboardingScreen(props: Controls) {
  return <OnboardingScreen {...props} index={1} source={require('../../../assets/img/parent.png')} imageLabel="Students ready for their school day" title={'A little closer,\nevery school day'} description="Attendance, announcements and timetables. Everything you need to stay in the loop." />;
}

export function ProgressOnboardingScreen(props: Controls) {
  return <OnboardingScreen {...props} index={2} source={require('../../../assets/img/book.png')} imageLabel="Student holding a laptop and school books" title={'Every step forward,\nworth celebrating'} description="Follow homework, results and report cards as learning turns into progress." />;
}

const baseStyles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: BRAND.navy },
  hero: { flexShrink: 0, alignItems: 'center', justifyContent: 'flex-end', overflow: 'hidden' },
  topBar: { position: 'absolute', left: 24, right: 24, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', zIndex: 3 },
  headerSide: { width: 64 },
  headerRight: { alignItems: 'flex-end' },
  brandLogo: { width: 148, height: 46 },
  backButton: { width: 44, height: 44, alignItems: 'center', justifyContent: 'center', borderRadius: 22, backgroundColor: '#FFFFFF14' },
  skipButton: { minWidth: 56, minHeight: 44, paddingHorizontal: 8, justifyContent: 'center', alignItems: 'center' },
  skipText: { color: BRAND.white, fontSize: 12, fontWeight: '500' },
  halo: { position: 'absolute', width: 320, height: 320, borderRadius: 160, backgroundColor: '#EFB00012', bottom: 0 },
  heroImage: { marginBottom: -22 },
  floatingIcon: { position: 'absolute', width: 60, height: 60, alignItems: 'center', justifyContent: 'center' },
  leftArtwork: { left: 22, top: '35%', transform: [{ rotate: '-17deg' }] },
  rightArtwork: { right: 20, top: '53%', transform: [{ rotate: '16deg' }] },
  goldDot: { position: 'absolute', width: 9, height: 9, borderRadius: 5, backgroundColor: BRAND.gold, right: 42, top: '29%' },
  sheet: { flexGrow: 1, backgroundColor: BRAND.white, width: '100%' },
  sheetContent: { flexGrow: 1, paddingHorizontal: 28, alignItems: 'center' },
  buttonArea: { width: '100%', alignItems: 'center', marginTop: 'auto' },
  curve: { position: 'absolute', top: -55, left: 0, right: 0 },
  copy: { maxWidth: 380, width: '100%', alignItems: 'center' },
  title: { color: BRAND.navy, fontSize: 27, lineHeight: 34, fontWeight: '700', letterSpacing: -0.5, textAlign: 'center' },
  titleCompact: { fontSize: 24, lineHeight: 30 },
  description: { color: BRAND.muted, fontSize: 13, lineHeight: 20, textAlign: 'center', marginTop: 10, maxWidth: 310 },
  dots: { flexDirection: 'row', gap: 6, marginTop: 24, alignItems: 'center' },
  dot: { width: 5, height: 5, borderRadius: 3, backgroundColor: '#DDE0E6' },
  activeDot: { backgroundColor: BRAND.gold, width: 7, height: 7, borderRadius: 4 },
  nextButton: { width: '100%', maxWidth: 420, minHeight: 52, paddingVertical: 14, borderRadius: 999, backgroundColor: BRAND.navy, alignItems: 'center', justifyContent: 'center' },
  nextText: { color: BRAND.gold, fontSize: 14, fontWeight: '600' },
  pressed: { opacity: 0.85 },
});

const styles = baseStyles;
