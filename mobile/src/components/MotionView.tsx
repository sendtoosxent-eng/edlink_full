import { useEffect, useRef, type ReactNode } from 'react';
import { Animated, Easing, type StyleProp, type ViewStyle } from 'react-native';

export function MotionView({ children, style, delay = 0, distance = 14, float = false }: { children: ReactNode; style?: StyleProp<ViewStyle>; delay?: number; distance?: number; float?: boolean }) {
  const opacity = useRef(new Animated.Value(float ? 1 : 0)).current;
  const translateY = useRef(new Animated.Value(float ? 1 : distance)).current;

  useEffect(() => {
    const animation = float
      ? Animated.loop(Animated.sequence([
        Animated.timing(translateY, { toValue: -3, duration: 1800, easing: Easing.inOut(Easing.sin), useNativeDriver: true }),
        Animated.timing(translateY, { toValue: 1, duration: 1800, easing: Easing.inOut(Easing.sin), useNativeDriver: true }),
      ]))
      : Animated.parallel([
        Animated.timing(opacity, { toValue: 1, duration: 440, delay, easing: Easing.out(Easing.cubic), useNativeDriver: true }),
        Animated.timing(translateY, { toValue: 0, duration: 440, delay, easing: Easing.out(Easing.cubic), useNativeDriver: true }),
      ]);
    animation.start();
    return () => animation.stop();
  }, [delay, float, opacity, translateY]);

  return <Animated.View style={[style, { opacity, transform: [{ translateY }] }]}>{children}</Animated.View>;
}
