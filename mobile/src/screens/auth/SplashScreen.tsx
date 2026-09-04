import { useEffect, useRef } from 'react';
import { Animated, Image, StyleSheet, Text, View } from 'react-native';

const LOGO_IMAGE = require('../../../assets/img/edlink-logo.png');

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
        <Image accessibilityLabel="Edlink" source={LOGO_IMAGE} resizeMode="contain" style={styles.logo} />
        <Text style={styles.tagline}>Your school day, simplified.</Text>
      </Animated.View>
    </View>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: '#efb000' },
  lockup: { alignItems: 'center' },
  logo: { width: 340, height: 250 },
  tagline: { marginTop: -54, color: '#0B132B', fontSize: 17, fontWeight: '800' },
});
