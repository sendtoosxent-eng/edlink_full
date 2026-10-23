import { StyleSheet, View } from 'react-native';
import Svg, { Path } from 'react-native-svg';
import { Text } from '../../components/Typography';
import { colors } from './authTheme';

const STEPS = ['Role', 'School', 'Email', 'Password'];
export function AuthProgress({ step }: { step: 1 | 2 | 3 }) {
  return <View style={styles.progress} accessible accessibilityLabel={`Sign in step ${step + 1} of 4: ${STEPS[step]}. ${step} completed, ${4 - step} remaining.`}>
    <View style={styles.summary}><Text style={styles.summaryText}>{step} completed</Text><Text style={styles.remaining}>{4 - step} remaining</Text></View>
    <View style={styles.row}>{STEPS.map((label, index) => <View key={label} style={styles.item}>
      <View style={styles.markerRow}>
        <View style={[styles.marker, index < step && styles.complete, index === step && styles.current]}>
          {index < step ? <Svg width={12} height={12} viewBox="0 0 16 16"><Path d="m3 8 3 3 7-7" fill="none" stroke="#FFFFFF" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" /></Svg> : <Text style={[styles.number, index === step && styles.currentNumber]}>{index + 1}</Text>}
        </View>
        {index < 3 && <View style={[styles.connector, index < step && styles.connectorComplete]} />}
      </View>
      <Text style={[styles.label, index === step && styles.activeLabel]}>{label}</Text>
    </View>)}</View>
  </View>;
}
const styles = StyleSheet.create({
  progress: { flex: 1, minWidth: 0 },
  summary: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 10, gap: 6 },
  summaryText: { color: colors.navy, fontSize: 10, fontWeight: '600' },
  remaining: { color: colors.muted, fontSize: 10 },
  row: { flexDirection: 'row' }, item: { flex: 1 }, markerRow: { flexDirection: 'row', alignItems: 'center' },
  marker: { width: 23, height: 23, borderRadius: 12, backgroundColor: colors.surfaceLow, alignItems: 'center', justifyContent: 'center' },
  complete: { backgroundColor: colors.navy }, current: { backgroundColor: colors.gold },
  number: { color: colors.muted, fontSize: 10, fontWeight: '600' }, currentNumber: { color: colors.navy },
  connector: { flex: 1, height: 2, backgroundColor: colors.surfaceHigh, marginHorizontal: 4 }, connectorComplete: { backgroundColor: colors.gold },
  label: { color: colors.muted, fontSize: 9, marginTop: 5 }, activeLabel: { color: colors.navy, fontWeight: '700' },
});
