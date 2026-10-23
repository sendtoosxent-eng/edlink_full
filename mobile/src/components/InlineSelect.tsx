import { useThemedStyles } from '../theme/ThemeProvider';
import { useState } from 'react';
import { Pressable, ScrollView, StyleSheet, View } from 'react-native';
import Svg, { Path } from 'react-native-svg';
import { Text, TextInput } from './Typography';
import { colors } from '../theme/index';

export function InlineSelect({ label, options, value, onChange }: { label: string; options: Array<{ value: string; label: string }>; value: string; onChange: (value: string) => void }) {
  const styles = useThemedStyles(baseStyles);
  const [open, setOpen] = useState(false);
  const [search, setSearch] = useState('');
  const selected = options.find(option => option.value === value);
  const filtered = options.filter(option => option.label.toLowerCase().includes(search.trim().toLowerCase()));
  return <View>
    <Pressable accessibilityRole="button" accessibilityLabel={`${label}: ${selected?.label ?? 'Choose an option'}`} accessibilityState={{ expanded: open }} onPress={() => { setSearch(''); setOpen(current => !current); }} style={[styles.field, open && styles.focused]}>
      <Text style={styles.value}>{selected?.label ?? `Choose ${label.toLowerCase()}`}</Text>
      <Svg width={18} height={18} viewBox="0 0 24 24" fill="none" style={{ transform: [{ rotate: open ? '180deg' : '0deg' }] }}><Path d="m6 9 6 6 6-6" stroke={colors.primary} strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round" /></Svg>
    </Pressable>
    {open && <View style={styles.dropdown}>
      {options.length > 6 && <TextInput accessibilityLabel={`Search ${label.toLowerCase()}`} placeholder="Search options…" placeholderTextColor={colors.textMuted} value={search} onChangeText={setSearch} style={styles.search} />}
      <ScrollView nestedScrollEnabled keyboardShouldPersistTaps="handled" style={styles.options}>
        {filtered.map(option => <Pressable key={option.value} accessibilityRole="radio" accessibilityState={{ selected: value === option.value }} onPress={() => { onChange(option.value); setOpen(false); }} style={[styles.option, value === option.value && styles.selected]}>
          <Text style={styles.optionText}>{option.label}</Text>
          {value === option.value && <Svg width={18} height={18} viewBox="0 0 24 24"><Path d="m5 12 4 4L19 6" fill="none" stroke={colors.primary} strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" /></Svg>}
        </Pressable>)}
        {!filtered.length && <Text style={styles.empty}>No matching options.</Text>}
      </ScrollView>
    </View>}
  </View>;
}
const baseStyles = StyleSheet.create({
  field: { minHeight: 54, borderRadius: 14, borderWidth: 1, borderColor: colors.border, paddingHorizontal: 14, paddingVertical: 13, backgroundColor: colors.surface, flexDirection: 'row', alignItems: 'center', gap: 10 },
  focused: { borderColor: colors.secondary }, value: { flex: 1, color: colors.primary, fontSize: 13, lineHeight: 20 },
  dropdown: { marginTop: 6, borderRadius: 14, borderWidth: 1, borderColor: colors.border, backgroundColor: colors.surface, overflow: 'hidden' },
  options: { maxHeight: 240 }, option: { minHeight: 48, paddingHorizontal: 14, paddingVertical: 12, flexDirection: 'row', alignItems: 'center', gap: 10 },
  selected: { backgroundColor: '#FFF5D5' }, optionText: { flex: 1, color: colors.primary, fontSize: 13, lineHeight: 20 },
  search: { minHeight: 44, paddingHorizontal: 14, color: colors.primary, fontSize: 13, borderBottomWidth: 1, borderBottomColor: colors.border },
  empty: { padding: 16, color: colors.textMuted, fontSize: 12 },
});

const styles = baseStyles;
