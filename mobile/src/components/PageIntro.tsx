import { StyleSheet, Text, View } from 'react-native';
import { colors, radius, shadows } from '../theme/index';

export function PageIntro({ title, description, subtitle }: { title: string; description?: string; subtitle?: string; icon?: unknown; user?: unknown }) {
  return (
    <View style={[styles.card, shadows.card]}>
      <View style={styles.glow} />
      <View style={styles.copy}>
        <Text style={styles.eyebrow}>EDLINK</Text>
        <Text style={styles.title}>{title}</Text>
        <Text style={styles.description}>{description ?? subtitle}</Text>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  card: { minHeight: 150, borderRadius: radius.lg, backgroundColor: colors.primaryCard, padding: 20, justifyContent: 'center', overflow: 'hidden' },
  glow: { position: 'absolute', width: 190, height: 190, borderRadius: 95, right: -80, top: -92, backgroundColor: colors.primary, opacity: 0.72 },
  copy: { flex: 1 },
  eyebrow: { color: colors.secondary, fontSize: 10, fontWeight: '900', letterSpacing: 1.4 },
  title: { color: colors.secondary, fontSize: 25, lineHeight: 31, fontWeight: '900', marginTop: 5 },
  description: { color: colors.secondary, fontSize: 12, lineHeight: 18, marginTop: 5 },
});
