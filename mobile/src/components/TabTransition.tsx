import { useThemedStyles } from '../theme/ThemeProvider';
import { useEffect, useRef, type ReactNode } from 'react';
import { StyleSheet, View } from 'react-native';
import Animated, { Easing, FadeOut, ReduceMotion, withTiming } from 'react-native-reanimated';

export function TabTransition({ screen, order, children }: { screen: string; order: readonly string[]; children: ReactNode }) {
  const styles = useThemedStyles(baseStyles);
  const previous = useRef(screen);
  const from = order.indexOf(previous.current);
  const to = order.indexOf(screen);
  const direction = from >= 0 && to >= 0 && to < from ? -1 : 1;
  useEffect(() => { previous.current = screen; }, [screen]);
  const entering = () => {
    'worklet';
    const timing = { duration: 280, easing: Easing.out(Easing.cubic), reduceMotion: ReduceMotion.System };
    return {
      initialValues: { opacity: 0, transform: [{ translateX: direction * 24 }] },
      animations: { opacity: withTiming(1, timing), transform: [{ translateX: withTiming(0, timing) }] },
    };
  };
  return <View style={styles.viewport}>
    <Animated.View key={screen} entering={entering} exiting={FadeOut.duration(100).reduceMotion(ReduceMotion.System)} style={styles.screen}>
      {children}
    </Animated.View>
  </View>;
}
const baseStyles = StyleSheet.create({ viewport: { flex: 1, overflow: 'hidden' }, screen: { flex: 1 } });

const styles = baseStyles;
