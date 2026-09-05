import { StyleSheet, View } from 'react-native';
import { colors } from '../../theme';

export function AuthProgress({ step }: { step: 1 | 2 | 3 }) {
  return <View style={styles.row} accessible accessibilityLabel={`Step ${step} of 3: ${['School', 'Email', 'Password'][step - 1]}`}>
    {[1, 2, 3].map(value => <View key={value} style={[styles.dot, value <= step && styles.complete, value === step && styles.active]} />)}
  </View>;
}
const styles = StyleSheet.create({ row: { flexDirection: 'row', gap: 6 }, dot: { width: 22, height: 6, borderRadius: 3, backgroundColor: colors.surfaceHigh }, complete: { backgroundColor: colors.gold }, active: { width: 30 } });
