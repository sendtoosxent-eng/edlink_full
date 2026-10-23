import { useEffect, useRef } from 'react';
import { AccessibilityInfo, Animated, AppState, Easing } from 'react-native';
import Svg, { Circle, Path } from 'react-native-svg';

export function AlarmClockArtwork() {
  const motion = useRef(new Animated.Value(0)).current;
  useEffect(() => {
    let reduced = true;
    let disposed = false;
    let animation: Animated.CompositeAnimation | undefined;
    const update = () => {
      animation?.stop(); motion.setValue(0);
      if (disposed || reduced || AppState.currentState !== 'active') return;
      const tilt = (toValue: number) => Animated.timing(motion, { toValue, duration: 110, easing: Easing.inOut(Easing.sin), useNativeDriver: true });
      animation = Animated.loop(Animated.sequence([tilt(1), tilt(-1), tilt(0.7), tilt(-0.7), tilt(0), Animated.delay(2200)]));
      animation.start();
    };
    const preference = AccessibilityInfo.addEventListener('reduceMotionChanged', value => { reduced = value; update(); });
    const activity = AppState.addEventListener('change', update);
    void AccessibilityInfo.isReduceMotionEnabled().then(value => { if (!disposed) { reduced = value; update(); } });
    return () => { disposed = true; animation?.stop(); preference.remove(); activity.remove(); };
  }, [motion]);
  return <Animated.View style={{ flex: 1, transform: [{ rotate: motion.interpolate({ inputRange: [-1, 1], outputRange: ['-7deg', '7deg'] }) }] }}>
    <Svg width="100%" height="100%" viewBox="0 0 128 128" fill="none">
      <Circle cx={64} cy={70} r={33} fill="#EFB000" fillOpacity={0.3} stroke="#0B132B" strokeWidth={3} />
      <Circle cx={64} cy={70} r={26} stroke="#0B132B" strokeWidth={1.5} />
      <Path d="M64 51v19l14 9M64 42v4m0 48v4M36 70h4m48 0h4M44 99l-8 12m48-12 8 12M64 37V24m-7 0h14M36 42l-6-7m62 7 6-7" stroke="#0B132B" strokeWidth={3} strokeLinecap="round" strokeLinejoin="round" />
      <Path d="M23 39c-9-9 5-24 15-15l-15 15Zm67-15c10-9 24 6 15 15L90 24Z" fill="#0B132B" />
      <Path d="M17 21c-7 5-10 12-9 20m17-12c-4 3-5 6-5 10m91-18c7 5 10 12 9 20m-17-12c4 3 5 6 5 10" stroke="#0B132B" strokeWidth={2.5} strokeLinecap="round" />
      <Circle cx={64} cy={70} r={3} fill="#0B132B" />
    </Svg>
  </Animated.View>;
}
