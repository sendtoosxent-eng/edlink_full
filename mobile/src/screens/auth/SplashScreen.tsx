import { useEffect, useRef } from 'react';
import { Animated, StyleSheet, Text, View } from 'react-native';
import { RoleIcon } from '../../components/RoleIcons';
import { colors, radius } from '../../theme';

export function SplashScreen() {
  const opacity = useRef(new Animated.Value(0)).current;
  const scale = useRef(new Animated.Value(0.94)).current;
  useEffect(() => {
    Animated.parallel([
      Animated.timing(opacity, { toValue: 1, duration: 600, useNativeDriver: true }),
      Animated.spring(scale, { toValue: 1, friction: 8, useNativeDriver: true }),
    ]).start();
  }, [opacity, scale]);

  return (
    <View style={styles.screen}>
      <Animated.View style={[styles.lockup, { opacity, transform: [{ scale }] }]}>
        <View style={styles.mark}><RoleIcon role="student" /></View>
        <Text style={styles.name}>Edlink</Text>
        <Text style={styles.tagline}>Your school day, simplified.</Text>
      </Animated.View>
    </View>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: colors.background },
  lockup: { alignItems: 'center' },
  mark: { width: 96, height: 96, borderRadius: radius.large, backgroundColor: colors.navy, alignItems: 'center', justifyContent: 'center', shadowColor: colors.navy, shadowOpacity: 0.14, shadowRadius: 16, elevation: 4 },
  name: { marginTop: 22, color: colors.navy, fontSize: 38, fontWeight: '800', letterSpacing: -1 },
  tagline: { marginTop: 24, color: colors.muted, fontSize: 17 },
});
