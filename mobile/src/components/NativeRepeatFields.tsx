import { Pressable, View } from 'react-native';
import { Text, TextInput } from './Typography';
import { NativeSelect } from './NativeSelect';
import { colors } from '../theme/index';
type ChildField = { key: string; label: string; multiple?: boolean; options?: Array<{ value: string; label: string }> };
export function NativeRepeatFields({ fields, value, onChange }: { fields: ChildField[]; value: string; onChange: (value: string) => void }) {
  let rows: Array<Record<string, string | string[]>> = [];
  try { const parsed = JSON.parse(value || '[]'); if (Array.isArray(parsed)) rows = parsed; } catch { /* Empty new form. */ }
  const update = (index: number, key: string, next: string | string[]) => onChange(JSON.stringify(rows.map((row, i) => i === index ? { ...row, [key]: next } : row)));
  return <View style={{ gap: 14 }}>{rows.map((row, index) => <View key={index} style={{ borderWidth: 1, borderColor: '#DDE4EF', borderRadius: 15, padding: 14, gap: 10 }}><Text style={{ fontWeight: '700', color: colors.primary }}>Entry {index + 1}</Text>{fields.map(field => <View key={field.key} style={{ gap: 6 }}><Text style={{ color: colors.primary }}>{field.label}</Text>{field.options ? <NativeSelect label={field.label} options={field.options} multiple={field.multiple} value={row[field.key] ?? (field.multiple ? [] : '')} onChange={next => update(index, field.key, next)} /> : <TextInput accessibilityLabel={field.label} value={String(row[field.key] ?? '')} onChangeText={next => update(index, field.key, next)} style={{ backgroundColor: 'white', padding: 12, borderRadius: 12 }} />}</View>)}<Pressable onPress={() => onChange(JSON.stringify(rows.filter((_, i) => i !== index)))}><Text style={{ color: colors.primary, paddingVertical: 8 }}>Remove entry</Text></Pressable></View>)}<Pressable onPress={() => onChange(JSON.stringify([...rows, {}]))}><Text style={{ color: colors.primary, paddingVertical: 10, fontWeight: '700' }}>+ Add entry</Text></Pressable></View>;
}
