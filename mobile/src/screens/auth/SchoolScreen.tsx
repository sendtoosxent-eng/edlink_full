import { StyleSheet, Text, View } from 'react-native';
import { BrandLogo } from '../../components/BrandLogo';
import { AuthButton, AuthField, AuthHeader } from './AuthControls';
import { colors, radius } from '../../theme';

export function SchoolScreen({ school, error, onChange, onBack, onContinue }: { school: string; error: string; onChange: (value: string) => void; onBack: () => void; onContinue: () => void }) {
  return <View><AuthHeader onBack={onBack} /><BrandLogo /><Text style={styles.title}>Find your school</Text><Text style={styles.lead}>Enter the unique number your school gave you.</Text><View style={styles.card}><AuthField label="School number" value={school} onChangeText={onChange} placeholder="e.g. EDL-00001" autoCapitalize="characters" returnKeyType="next" onSubmitEditing={onContinue} />{!!error && <Text style={styles.error}>{error}</Text>}<AuthButton label="Continue" onPress={onContinue} /></View></View>;
}
const styles = StyleSheet.create({ title: { fontSize: 32, fontWeight: '800', color: colors.ink, marginTop: 22 }, lead: { color: colors.muted, fontSize: 16, lineHeight: 23, marginTop: 7, marginBottom: 26 }, card: { backgroundColor: colors.surface, borderRadius: radius.large, padding: 20, gap: 16, shadowColor: colors.navy, shadowOpacity: 0.1, shadowRadius: 20, elevation: 3 }, error: { color: colors.danger, lineHeight: 20 } });
