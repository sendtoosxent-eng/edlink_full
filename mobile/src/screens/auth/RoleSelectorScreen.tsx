import { useEffect, useRef, useState } from 'react';
import { FlatList, Image, Pressable, ScrollView, StyleSheet, View, useWindowDimensions } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import Svg, { Path } from 'react-native-svg';
import { Text } from '../../components/Typography';
import { RoleIllustration } from '../../components/RoleIllustration';
import type { Role } from '../../types';

const colors = { navy: '#0B132B', gold: '#EFB000', white: '#FFFFFF', muted: '#707785' };
const ROLES: Array<{ key: Role; label: string; eyebrow: string; description: string }> = [
  { key: 'teacher', label: 'Teacher', eyebrow: 'Inspire. Teach. Make a difference.', description: 'Take attendance, share homework and enter marks. Keep every class moving forward, all from one place.' },
  { key: 'student', label: 'Student', eyebrow: 'Your learning journey starts here.', description: 'Find your timetable, keep up with homework and celebrate your results. Your school day, made simpler.' },
  { key: 'parent', label: 'Parent', eyebrow: 'Stay close to their school day.', description: 'Follow your child’s attendance, progress and school notices. Stay connected to every step of their learning.' },
];

export function RoleSelectorScreen({ onSelect }: { onSelect: (role: Role) => void }) {
  const insets = useSafeAreaInsets();
  const { width, height } = useWindowDimensions();
  const [active, setActive] = useState(0);
  const slider = useRef<FlatList<(typeof ROLES)[number]>>(null);
  const activeRef = useRef(active);
  activeRef.current = active;
  const compact = height < 720;
  const portraitSize = Math.max(180, Math.min(width * 0.66, height * 0.32, 290));
  useEffect(() => {
    slider.current?.scrollToOffset({ offset: activeRef.current * width, animated: false });
  }, [width]);
  const goTo = (index: number) => {
    setActive(index);
    slider.current?.scrollToOffset({ offset: index * width, animated: true });
  };

  return <View style={styles.screen}>
    <FlatList
      ref={slider}
      data={ROLES}
      keyExtractor={item => item.key}
      horizontal
      pagingEnabled
      bounces={false}
      showsHorizontalScrollIndicator={false}
      extraData={{ width, active, portraitSize }}
      getItemLayout={(_, index) => ({ length: width, offset: width * index, index })}
      onMomentumScrollEnd={event => setActive(Math.max(0, Math.min(2, Math.round(event.nativeEvent.contentOffset.x / width))))}
      renderItem={({ item, index }) => <ScrollView style={{ width }} contentContainerStyle={{ minHeight: height, flexGrow: 1 }} bounces={false} showsVerticalScrollIndicator={false}>
        <View style={[styles.hero, { paddingTop: insets.top + 14 }]}>
          <Image source={require('../../../assets/img/logo.png')} accessibilityLabel="Edlink" resizeMode="contain" style={styles.logo} />
          <Text style={styles.kicker}>CHOOSE YOUR WORKSPACE</Text>
          <View style={[styles.portrait, { marginTop: compact ? 14 : 22 }]}>
            <RoleIllustration role={item.key} size={portraitSize} />
          </View>
        </View>
        {/* The curve is part of the layout, spans the page, and overlaps the
            navy hero. Its filled base joins the white panel without a seam. */}
        <Svg pointerEvents="none" width={width} height={56} viewBox="0 0 400 56" preserveAspectRatio="none" style={styles.curve}><Path d="M0 48 Q200 -40 400 48 L400 56 L0 56 Z" fill={colors.white} /></Svg>
        <View style={[styles.sheet, { paddingBottom: Math.max(insets.bottom + 18, 28) }]}>
          <View style={styles.copy}>
            <Text style={styles.eyebrow}>{item.eyebrow}</Text>
            <Text accessibilityRole="header" style={styles.title}>I’m a {item.label.toLowerCase()}</Text>
            <Text style={styles.description}>{item.description}</Text>
          </View>
          <View style={styles.slideControls}>
            <Pressable accessibilityRole="button" accessibilityLabel="Previous role" disabled={index === 0} onPress={() => goTo(index - 1)} style={[styles.arrow, index === 0 && styles.hidden]}><Chevron backwards /></Pressable>
            <View style={styles.dots}>{ROLES.map((role, dot) => <Pressable key={role.key} accessibilityRole="button" accessibilityLabel={`Show ${role.label} role`} accessibilityState={{ selected: active === dot }} onPress={() => goTo(dot)} style={styles.dotTarget}><View style={[styles.dot, active === dot && styles.dotActive]} /></Pressable>)}</View>
            <Pressable accessibilityRole="button" accessibilityLabel="Next role" disabled={index === 2} onPress={() => goTo(index + 1)} style={[styles.arrow, index === 2 && styles.hidden]}><Chevron /></Pressable>
          </View>
          <Text style={styles.swipeHint}>Swipe to explore roles</Text>
          <View style={styles.footer}>
            <Pressable accessibilityRole="button" onPress={() => onSelect(item.key)} style={({ pressed }) => [styles.continueButton, pressed && styles.pressed]}><Text style={styles.continueText}>Continue as {item.label.toLowerCase()}</Text></Pressable>
            <Text style={styles.note}>Use the role linked to your school account.</Text>
          </View>
        </View>
      </ScrollView>}
    />
  </View>;
}

function Chevron({ backwards = false }: { backwards?: boolean }) {
  return <Svg width={20} height={20} viewBox="0 0 24 24" fill="none"><Path d={backwards ? 'm14 6-6 6 6 6' : 'm10 6 6 6-6 6'} stroke={colors.navy} strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round" /></Svg>;
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.navy },
  hero: { alignItems: 'center', paddingHorizontal: 24, paddingBottom: 60 },
  logo: { width: 150, height: 48 },
  kicker: { color: '#D1D7E2', fontSize: 9, fontWeight: '600', letterSpacing: 1.6, marginTop: 18 },
  portrait: { alignItems: 'center', justifyContent: 'center' },
  curve: { marginTop: -55, marginBottom: -1, zIndex: 1 },
  sheet: { flexGrow: 1, backgroundColor: colors.white, paddingHorizontal: 28, paddingTop: 12, alignItems: 'center' },
  copy: { width: '100%', maxWidth: 380, alignItems: 'center' },
  eyebrow: { color: '#8A6500', fontSize: 11, fontWeight: '500', textAlign: 'center' },
  title: { color: colors.navy, fontSize: 30, lineHeight: 39, fontWeight: '700', letterSpacing: -0.7, marginTop: 8, textAlign: 'center' },
  description: { color: colors.muted, fontSize: 13, lineHeight: 21, textAlign: 'center', marginTop: 10, maxWidth: 320 },
  slideControls: { flexDirection: 'row', gap: 24, alignItems: 'center', marginTop: 16 },
  arrow: { width: 44, height: 44, borderRadius: 22, backgroundColor: '#F4F6FA', alignItems: 'center', justifyContent: 'center' },
  hidden: { opacity: 0 },
  dots: { flexDirection: 'row', alignItems: 'center' },
  dotTarget: { width: 28, height: 44, alignItems: 'center', justifyContent: 'center' },
  dot: { width: 6, height: 6, borderRadius: 4, backgroundColor: '#DDE0E6' },
  dotActive: { width: 20, backgroundColor: colors.gold },
  swipeHint: { color: colors.muted, fontSize: 10, marginTop: 3 },
  footer: { width: '100%', maxWidth: 420, marginTop: 'auto', paddingTop: 24 },
  continueButton: { minHeight: 54, paddingHorizontal: 18, paddingVertical: 15, borderRadius: 999, backgroundColor: colors.navy, alignItems: 'center', justifyContent: 'center' },
  continueText: { color: colors.gold, fontSize: 14, fontWeight: '600', textAlign: 'center' },
  note: { color: colors.muted, fontSize: 10, lineHeight: 16, textAlign: 'center', marginTop: 12 },
  pressed: { opacity: 0.85 },
});
