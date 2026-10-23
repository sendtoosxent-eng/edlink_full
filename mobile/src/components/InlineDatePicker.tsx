import { useThemedStyles } from '../theme/ThemeProvider';
import { useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';
import Svg, { Path, Rect } from 'react-native-svg';
import { Text } from './Typography';
import { colors } from '../theme/index';

const dateKey = (date: Date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
const parseDate = (value: string) => {
  const [year, month, day] = value.split('-').map(Number);
  const date = new Date(year, month - 1, day);
  return Number.isNaN(date.getTime()) || dateKey(date) !== value ? new Date() : date;
};
export function InlineDatePicker({ value, onChange, label = 'Attendance date' }: { value: string; onChange: (value: string) => void; label?: string }) {
  const styles = useThemedStyles(baseStyles);
  const [open, setOpen] = useState(false);
  const [month, setMonth] = useState(() => { const date = parseDate(value); return new Date(date.getFullYear(), date.getMonth(), 1); });
  const selected = parseDate(value);
  const today = dateKey(new Date());
  const days = new Date(month.getFullYear(), month.getMonth() + 1, 0).getDate();
  const leading = (month.getDay() + 6) % 7;
  const cells = Array.from({ length: Math.ceil((leading + days) / 7) * 7 }, (_, index) => { const day = index - leading + 1; return day >= 1 && day <= days ? day : null; });
  const select = (date: Date) => { onChange(dateKey(date)); setOpen(false); };
  return <View>
    <Pressable accessibilityRole="button" accessibilityLabel={`${label}: ${selected.toDateString()}`} accessibilityState={{ expanded: open }} onPress={() => { if (!open) setMonth(new Date(selected.getFullYear(), selected.getMonth(), 1)); setOpen(current => !current); }} style={[styles.field, open && styles.focused]}>
      <View style={styles.icon}><Svg width={24} height={24} viewBox="0 0 24 24" fill="none"><Rect x={3} y={5} width={18} height={16} rx={3} stroke={colors.secondary} strokeWidth={1.7} /><Path d="M3 10h18M8 3v4m8-4v4m-9 7h3m4 0h3m-10 4h3" stroke={colors.secondary} strokeWidth={1.7} strokeLinecap="round" /></Svg></View>
      <Text style={styles.value}>{selected.toLocaleDateString(undefined, { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' })}</Text>
      <Chevron down={!open} up={open} />
    </Pressable>
    {open && <View style={styles.calendar}>
      <View style={styles.header}>
        <Pressable accessibilityRole="button" accessibilityLabel="Previous month" onPress={() => setMonth(new Date(month.getFullYear(), month.getMonth() - 1, 1))} style={styles.monthButton}><Chevron back /></Pressable>
        <Text accessibilityRole="header" accessibilityLiveRegion="polite" style={styles.month}>{month.toLocaleDateString(undefined, { month: 'long', year: 'numeric' })}</Text>
        <Pressable accessibilityRole="button" accessibilityLabel="Next month" onPress={() => setMonth(new Date(month.getFullYear(), month.getMonth() + 1, 1))} style={styles.monthButton}><Chevron /></Pressable>
      </View>
      <View style={styles.grid}>{['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].map(day => <View key={day} style={styles.cell}><Text style={styles.weekday}>{day}</Text></View>)}</View>
      <View style={styles.grid}>{cells.map((day, index) => {
        if (day === null) return <View key={`blank-${index}`} style={styles.cell} />;
        const date = new Date(month.getFullYear(), month.getMonth(), day);
        const key = dateKey(date);
        return <Pressable key={key} accessibilityRole="button" accessibilityLabel={date.toLocaleDateString(undefined, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })} accessibilityState={{ selected: key === value }} onPress={() => select(date)} style={styles.cell}><View style={[styles.day, key === today && styles.today, key === value && styles.selected]}><Text style={[styles.dayText, key === value && styles.selectedText]}>{day}</Text></View></Pressable>;
      })}</View>
      <Pressable accessibilityRole="button" onPress={() => select(new Date())} style={styles.todayButton}><Text style={styles.todayText}>Today</Text></Pressable>
    </View>}
  </View>;
}
function Chevron({ back, down, up }: { back?: boolean; down?: boolean; up?: boolean }) {
  return <Svg width={18} height={18} viewBox="0 0 24 24" fill="none"><Path d={back ? 'm14 6-6 6 6 6' : down ? 'm6 9 6 6 6-6' : up ? 'm6 15 6-6 6 6' : 'm10 6 6 6-6 6'} stroke={colors.primary} strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round" /></Svg>;
}
const baseStyles = StyleSheet.create({
  field: { minHeight: 56, flexDirection: 'row', alignItems: 'center', gap: 10, paddingHorizontal: 9, paddingVertical: 6, borderRadius: 14, borderWidth: 1, borderColor: colors.border, backgroundColor: colors.surface }, focused: { borderColor: colors.secondary },
  icon: { width: 40, height: 40, borderRadius: 12, backgroundColor: colors.primary, alignItems: 'center', justifyContent: 'center' }, value: { flex: 1, minWidth: 0, color: colors.primary, fontSize: 13, lineHeight: 20 },
  calendar: { padding: 10, marginTop: 8, borderRadius: 18, borderWidth: 1, borderColor: colors.border, backgroundColor: colors.surface }, header: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: 4 }, monthButton: { minWidth: 44, minHeight: 44, alignItems: 'center', justifyContent: 'center' }, month: { flex: 1, color: colors.primary, fontSize: 14, fontWeight: '600', textAlign: 'center' },
  grid: { flexDirection: 'row', flexWrap: 'wrap' }, cell: { width: '14.285714%', minHeight: 42, alignItems: 'center', justifyContent: 'center' }, weekday: { color: colors.textMuted, fontSize: 10 }, day: { width: 34, height: 34, borderRadius: 17, alignItems: 'center', justifyContent: 'center' }, today: { borderWidth: 1, borderColor: colors.secondary }, selected: { backgroundColor: colors.secondary }, dayText: { color: colors.primary, fontSize: 13 }, selectedText: { fontWeight: '700' },
  todayButton: { minHeight: 44, justifyContent: 'center', alignItems: 'center', marginTop: 6, borderTopWidth: 1, borderTopColor: colors.border }, todayText: { color: colors.primary, fontSize: 12, fontWeight: '600' },
});

const styles = baseStyles;
