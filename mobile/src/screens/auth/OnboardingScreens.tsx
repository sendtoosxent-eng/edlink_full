import { Text } from '../../components/Typography';
import React from 'react';
import { Animated, Easing, type ImageSourcePropType, Pressable, StyleSheet, View, Dimensions } from 'react-native';
import { Ionicons } from '@expo/vector-icons';

type Controls = { onNext: () => void; onBack?: () => void; onSkip?: () => void };

// --- Theme Tokens ---
const THEME = {
  navy: '#0B132B',
  navyCard: '#1C2541',
  yellow: '#fab001',
  yellowDark: '#efb000',
  background: '#F4F6FA',
  surface: '#FFFFFF',
  textDark: '#0B132B',
  textMuted: '#6C7A9C',
  fontRegular: 'Poppins_400Regular',
  fontMedium: 'Poppins_500Medium',
  fontSemiBold: 'Poppins_600SemiBold',
  fontBold: 'Poppins_700Bold',
};

// --- Reusable Sub-components ---

function Dots({ active }: { active: number }) {
  return (
    <View style={styles.dots}>
      {[0, 1, 2].map((index) => (
        <View
          key={index}
          style={[styles.dot, index === active && styles.dotActive]}
        />
      ))}
    </View>
  );
}

function PrimaryButton({
  label,
  onPress,
  isYellow = false,
}: {
  label: string;
  onPress: () => void;
  isYellow?: boolean;
}) {
  return (
    <Pressable
      onPress={onPress}
      style={({ pressed }) => [
        styles.primaryButton,
        isYellow ? styles.yellowBtn : styles.navyBtn,
        pressed && styles.pressed,
      ]}
    >
      <Text style={[styles.primaryButtonText, isYellow ? styles.yellowBtnText : styles.navyBtnText]}>
        {label}
      </Text>
      <Ionicons
        name={label === 'Get Started' ? 'rocket' : 'play-circle'}
        size={22}
        color={THEME.navy}
      />
    </Pressable>
  );
}

// --- Onboarding Screen 1: Connect ---

export function ConnectOnboardingScreen({ onNext, onSkip }: Controls) {
  return (
    <View style={styles.screen}>
      <Pressable onPress={onSkip} style={styles.skipButton}>
        <Text style={styles.skipText}>Skip</Text>
      </Pressable>

      <View style={styles.illustrationArea}>
        <MotionImage source={require('../../../assets/img/girl.png')} accessibilityLabel="Smiling student holding school books" style={styles.heroPhoto} />
      </View>

      <View style={styles.sheet}>
        <Text style={styles.title}>Everything school,{'\n'}in one place</Text>
        <Text style={styles.lead}>
          Edlink connects teachers, students, and parents seamlessly in real time.
        </Text>
        <View style={styles.footerRow}>
          <Dots active={0} />
          <PrimaryButton label="Next" onPress={onNext} />
        </View>
      </View>
    </View>
  );
}

function MotionImage({ source, accessibilityLabel, style }: { source: ImageSourcePropType; accessibilityLabel: string; style: object }) {
  const motion = React.useRef(new Animated.Value(0)).current;

  React.useEffect(() => {
    const animation = Animated.loop(
      Animated.sequence([
        Animated.timing(motion, { toValue: 1, duration: 1900, easing: Easing.inOut(Easing.sin), useNativeDriver: true }),
        Animated.timing(motion, { toValue: 0, duration: 1900, easing: Easing.inOut(Easing.sin), useNativeDriver: true }),
      ]),
    );
    animation.start();
    return () => animation.stop();
  }, [motion]);

  return (
    <Animated.Image
      accessibilityLabel={accessibilityLabel}
      source={source}
      resizeMode="contain"
      style={[
        style,
        {
          transform: [
            { scale: motion.interpolate({ inputRange: [0, 1], outputRange: [1, 1.025] }) },
            { translateY: motion.interpolate({ inputRange: [0, 1], outputRange: [0, 3] }) },
          ],
        },
      ]}
    />
  );
}

// --- Onboarding Screen 2: Inform ---

export function InformOnboardingScreen({ onNext, onBack }: Controls) {
  return (
    <View style={styles.screen}>
      <Pressable onPress={onBack} style={styles.backIconButton}>
        <Ionicons name="arrow-back" size={22} color={THEME.navy} />
      </Pressable>

      <View style={styles.illustrationArea}>
        <MotionImage source={require('../../../assets/img/onboad.png')} accessibilityLabel="Two students ready for their school day" style={styles.informPhoto} />
      </View>

      <View style={styles.sheet}>
        <Text style={styles.title}>Stay informed{'\n'}every school day</Text>
        <Text style={styles.lead}>
          Check attendance, class announcements, and timetables at a glance.
        </Text>
        <View style={styles.footerRow}>
          <Dots active={1} />
          <PrimaryButton label="Next" onPress={onNext} />
        </View>
      </View>
    </View>
  );
}

// --- Onboarding Screen 3: Progress ---

export function ProgressOnboardingScreen({ onNext, onBack }: Controls) {
  return (
    <View style={styles.screen}>
      <Pressable onPress={onBack} style={styles.backIconButton}>
        <Ionicons name="arrow-back" size={22} color={THEME.navy} />
      </Pressable>

      <View style={styles.illustrationArea}>
        <MotionImage source={require('../../../assets/img/onboad1.png')} accessibilityLabel="Student holding a laptop and school books" style={styles.progressPhoto} />
      </View>

      <View style={styles.sheet}>
        <Text style={styles.title}>Follow learning{'\n'}& academic growth</Text>
        <Text style={styles.lead}>
          View results, report cards, and homework feedback easily in real time.
        </Text>
        <View style={styles.singleActionFooter}>
          <Dots active={2} />
          <PrimaryButton label="Get Started" onPress={onNext} isYellow />
        </View>
      </View>
    </View>
  );
}

// --- Styles ---

const { width } = Dimensions.get('window');

const styles = StyleSheet.create({
  screen: {
    flex: 1,
    backgroundColor: THEME.background,
    paddingTop: 50,
  },
  skipButton: {
    position: 'absolute',
    top: 54,
    right: 24,
    zIndex: 10,
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 20,
    backgroundColor: THEME.navy,
  },
  skipText: {
    fontFamily: THEME.fontSemiBold,
    fontSize: 13,
    color: THEME.yellow,
  },
  backIconButton: {
    position: 'absolute',
    top: 54,
    left: 24,
    zIndex: 10,
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: THEME.surface,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: THEME.navy,
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.06,
    shadowRadius: 8,
    elevation: 2,
  },
  illustrationArea: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 24,
  },
  heroPhoto: {
    position: 'absolute',
    bottom: -42,
    width: width * 1.24,
    height: '122%',
  },
  informPhoto: {
    position: 'absolute',
    bottom: -46,
    width: width * 1.2,
    height: '120%',
  },
  progressPhoto: {
    position: 'absolute',
    bottom: -48,
    width: width * 1.38,
    height: '138%',
  },
  mockPhone: {
    width: width * 0.65,
    padding: 20,
    borderRadius: 24,
    backgroundColor: THEME.surface,
    transform: [{ rotate: '-5deg' }],
    shadowColor: THEME.navy,
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.08,
    shadowRadius: 16,
    elevation: 4,
  },
  phoneHeader: {
    fontFamily: THEME.fontBold,
    fontSize: 11,
    color: THEME.navy,
    marginBottom: 14,
    letterSpacing: 0.5,
  },
  metricRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 10,
    borderBottomWidth: 1,
    borderColor: '#F1F5F9',
  },
  rowLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  metricLabel: {
    fontFamily: THEME.fontMedium,
    fontSize: 13,
    color: THEME.textDark,
  },
  metricValue: {
    fontFamily: THEME.fontBold,
    fontSize: 12,
    color: THEME.textMuted,
  },
  floatingNotification: {
    position: 'absolute',
    right: 20,
    bottom: 30,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    padding: 14,
    borderRadius: 18,
    backgroundColor: THEME.surface,
    shadowColor: THEME.navy,
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.1,
    shadowRadius: 12,
    elevation: 5,
  },
  bellIconBox: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: THEME.yellow,
    alignItems: 'center',
    justifyContent: 'center',
  },
  skeletonBox: {
    gap: 6,
  },
  skeletonLineLong: {
    width: 80,
    height: 8,
    borderRadius: 4,
    backgroundColor: '#E2E8F0',
  },
  skeletonLineShort: {
    width: 50,
    height: 8,
    borderRadius: 4,
    backgroundColor: '#F1F5F9',
  },
  progressCard: {
    width: '100%',
    padding: 24,
    borderRadius: 28,
    backgroundColor: THEME.navyCard,
    shadowColor: THEME.navy,
    shadowOffset: { width: 0, height: 12 },
    shadowOpacity: 0.15,
    shadowRadius: 24,
    elevation: 6,
  },
  cardHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    marginBottom: 16,
  },
  progressCardTitle: {
    fontFamily: THEME.fontBold,
    fontSize: 12,
    color: THEME.yellow,
    letterSpacing: 0.8,
  },
  checkItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    marginBottom: 8,
  },
  checkText: {
    fontFamily: THEME.fontRegular,
    fontSize: 13,
    color: '#E2E8F0',
  },
  chartSparkline: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    gap: 8,
    marginTop: 20,
    height: 60,
  },
  bar: {
    width: 12,
    borderRadius: 6,
    backgroundColor: '#334155',
  },
  trendIcon: {
    marginLeft: 'auto',
    marginBottom: 10,
  },
  sheet: {
    zIndex: 2,
    backgroundColor: THEME.navy,
    borderTopLeftRadius: 36,
    borderTopRightRadius: 36,
    paddingHorizontal: 28,
    paddingTop: 32,
    borderRadius:  20,
    paddingBottom: 40,
  },
  title: {
    fontFamily: THEME.fontBold,
    fontSize: 28,
    lineHeight: 35,
    fontWeight: '900',
    letterSpacing: -0.45,
    color: THEME.yellow,
    textAlign: 'center',
  },
  lead: {
    fontFamily: THEME.fontRegular,
    fontSize: 14,
    lineHeight: 22,
    color: THEME.textMuted,
    textAlign: 'center',
    marginTop: 10,
    marginBottom: 28,
  },
  footerRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  singleActionFooter: {
    gap: 20,
  },
  dots: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  dot: {
    width: 8,
    height: 8,
    borderRadius: 4,
    backgroundColor: '#CBD5E1',
  },
  dotActive: {
    width: 28,
    backgroundColor: THEME.yellow,
  },
  primaryButton: {
    height: 54,
    paddingHorizontal: 28,
    borderRadius: 18,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 10,
  },
  navyBtn: {
    backgroundColor: THEME.yellow,
  },
  yellowBtn: {
    backgroundColor: THEME.yellow,
    width: '100%',
  },
  pressed: {
    opacity: 0.88,
    transform: [{ scale: 0.98 }],
  },
  primaryButtonText: {
    fontFamily: THEME.fontSemiBold,
    fontSize: 16,
    fontWeight: '900',
    letterSpacing: 0.15,
  },
  navyBtnText: {
    color: 'navy',
  },
  yellowBtnText: {
    color: THEME.navy,
  },
});
