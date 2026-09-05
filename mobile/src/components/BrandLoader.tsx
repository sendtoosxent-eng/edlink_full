import { useEffect, useRef } from 'react';
import { AccessibilityInfo, Animated, Easing, StyleSheet, View } from 'react-native';
import { BrandLogo } from './BrandLogo';
import { colors } from '../theme';

/** Shared loading indicator; uses the logo asset so it can appear before fonts load. */
export function BrandLoader() {
  const phase = useRef(new Animated.Value(0)).current;
  useEffect(() => {
    let disposed = false;
    let animation: Animated.CompositeAnimation | undefined;
    const update = (reduced: boolean) => {
      if (disposed) return;
      animation?.stop();
      phase.setValue(0);
      if (reduced) return;
      animation = Animated.loop(Animated.sequence([
        Animated.timing(phase, { toValue: 1, duration: 1100, easing: Easing.inOut(Easing.sin), useNativeDriver: true, isInteraction: false }),
        Animated.timing(phase, { toValue: 0, duration: 1100, easing: Easing.inOut(Easing.sin), useNativeDriver: true, isInteraction: false }),
      ]));
      animation.start();
    };
    void AccessibilityInfo.isReduceMotionEnabled().then(update);
    const listener = AccessibilityInfo.addEventListener('reduceMotionChanged', update);
    return () => { disposed = true; animation?.stop(); listener.remove(); };
  }, [phase]);
  return <View style={styles.container} accessible accessibilityRole="progressbar" accessibilityLabel="Loading Edlink" accessibilityState={{ busy: true }}>
    <View style={styles.mark} importantForAccessibility="no-hide-descendants" accessibilityElementsHidden>
      <Animated.View style={{ transform: [{ translateY: phase.interpolate({ inputRange: [0, 1], outputRange: [2, -4] }) }, { scale: phase.interpolate({ inputRange: [0, 1], outputRange: [0.98, 1.02] }) }] }}><BrandLogo /></Animated.View>
    </View>
    <View style={styles.dots}>
      {[0, 1, 2].map(index => <Animated.View key={index} style={[styles.dot, { backgroundColor: colors.navy, opacity: phase.interpolate({ inputRange: [0, 0.5, 1], outputRange: index === 0 ? [1, 0.45, 0.3] : index === 1 ? [0.45, 1, 0.45] : [0.3, 0.45, 1] }), transform: [{ translateY: phase.interpolate({ inputRange: [0, 0.5, 1], outputRange: index === 0 ? [-3, 0, 0] : index === 1 ? [0, -3, 0] : [0, 0, -3] }) }] }]} />)}
    </View>
  </View>;
}
const styles = StyleSheet.create({
  container: { flex: 1, minHeight: 220, alignItems: 'center', justifyContent: 'center', backgroundColor: colors.background, padding: 24 },
  mark: { width: 230, height: 150, alignItems: 'center', justifyContent: 'center' },
  dots: { flexDirection: 'row', gap: 8, marginTop: 16 },
  dot: { width: 7, height: 7, borderRadius: 4 },
});
