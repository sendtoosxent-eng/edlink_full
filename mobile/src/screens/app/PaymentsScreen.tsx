import { Ionicons } from '@expo/vector-icons';
import { useCallback, useEffect, useState } from 'react';
import { RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import { api, ApiError } from '../../api';
import { PageIntro } from '../../components/PageIntro';
import { colors, radius, shadows } from '../../theme/index';

type PaymentData = {
  student: { name: string; class?: string | null };
  term?: string | null;
  summary: { due: number; paid: number; balance: number };
  payments: Array<{ id: number; amount: string | number; method: string; transaction_id?: string | null; bank_slip_number?: string | null; paid_at: string }>;
};

export function PaymentsScreen({ token, studentId, isParent }: { token: string; studentId?: number; isParent: boolean }) {
  const [data, setData] = useState<PaymentData>();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const load = useCallback(async () => {
    if (isParent && !studentId) { setLoading(false); return; }
    setLoading(true);
    try { setData((await api.get<PaymentData>(`/payments${studentId ? `?student_id=${studentId}` : ''}`, token)).data); setError(''); }
    catch (reason) { setError(reason instanceof ApiError ? reason.message : 'Could not load payment information.'); }
    finally { setLoading(false); }
  }, [isParent, studentId, token]);
  useEffect(() => { void load(); }, [load]);

  return <ScrollView contentContainerStyle={styles.content} refreshControl={<RefreshControl refreshing={loading} onRefresh={load} tintColor={colors.primary} />}>
    <PageIntro title="Payments" description="View the learner’s current fee position and confirmed payment history." />
    {error ? <View style={styles.message}><Text style={styles.error}>{error}</Text></View> : data ? <>
      <View style={styles.context}><Text style={styles.student}>{data.student.name}</Text><Text style={styles.meta}>{data.student.class ?? 'Class not assigned'} · {data.term ?? 'No current term'}</Text></View>
      <View style={styles.summaryRow}>
        <Summary label="Amount due" value={money(data.summary.due)} />
        <Summary label="Paid" value={money(data.summary.paid)} />
      </View>
      <View style={[styles.balanceCard, shadows.card]}><Text style={styles.balanceLabel}>Remaining balance</Text><Text style={styles.balance}>{money(data.summary.balance)}</Text></View>
      <Text style={styles.heading}>Payment history</Text>
      {data.payments.map(payment => <View key={payment.id} style={[styles.payment, shadows.card]}><View style={styles.paymentIcon}><Ionicons name="receipt-outline" size={20} color={colors.primary} /></View><View style={styles.grow}><Text style={styles.amount}>{money(Number(payment.amount))}</Text><Text style={styles.meta}>{label(payment.method)} · {date(payment.paid_at)}</Text><Text style={styles.reference}>{payment.transaction_id ?? payment.bank_slip_number ?? 'School receipt'}</Text></View><Ionicons name="checkmark-circle" size={20} color={colors.status.present.text} /></View>)}
      {!data.payments.length && <View style={styles.message}><Ionicons name="receipt-outline" size={25} color={colors.textMuted} /><Text style={styles.meta}>No posted payments for this term yet.</Text></View>}
    </> : <View style={styles.message}><Text style={styles.meta}>{loading ? 'Loading payments…' : 'Choose a learner to view payments.'}</Text></View>}
  </ScrollView>;
}

function Summary({ label: title, value }: { label: string; value: string }) { return <View style={[styles.summary, shadows.card]}><Text style={styles.summaryLabel}>{title}</Text><Text numberOfLines={1} adjustsFontSizeToFit style={styles.summaryValue}>{value}</Text></View>; }
function money(value: number) { return `UGX ${Math.round(value || 0).toLocaleString()}`; }
function label(value: string) { return value.replace(/_/g, ' ').replace(/\b\w/g, character => character.toUpperCase()); }
function date(value: string) { const parsed = new Date(value); return Number.isNaN(parsed.valueOf()) ? value : parsed.toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' }); }

const styles = StyleSheet.create({
  content: { padding: 20, paddingBottom: 34, gap: 12 }, context: { paddingVertical: 5 }, student: { color: colors.textDark, fontSize: 18, fontWeight: '900' }, meta: { color: colors.textMuted, fontSize: 12, lineHeight: 18, marginTop: 3 }, summaryRow: { flexDirection: 'row', gap: 10 }, summary: { flex: 1, minHeight: 92, borderRadius: radius.md, backgroundColor: colors.surface, borderWidth: 1, borderColor: colors.border, padding: 14, justifyContent: 'space-between' }, summaryLabel: { color: colors.textMuted, fontSize: 11, fontWeight: '700' }, summaryValue: { color: colors.textDark, fontSize: 17, fontWeight: '900' }, balanceCard: { minHeight: 112, borderRadius: radius.md, backgroundColor: colors.secondary, padding: 18, justifyContent: 'center' }, balanceLabel: { color: colors.primary, fontSize: 11, fontWeight: '800' }, balance: { color: colors.primary, fontSize: 27, fontWeight: '900', marginTop: 6 }, heading: { color: colors.textDark, fontSize: 19, fontWeight: '900', marginTop: 10 }, payment: { minHeight: 86, flexDirection: 'row', alignItems: 'center', gap: 12, borderRadius: radius.md, backgroundColor: colors.surface, borderWidth: 1, borderColor: colors.border, padding: 14 }, paymentIcon: { width: 42, height: 42, borderRadius: 13, backgroundColor: '#FFF2CF', alignItems: 'center', justifyContent: 'center' }, grow: { flex: 1 }, amount: { color: colors.textDark, fontSize: 15, fontWeight: '900' }, reference: { color: colors.primary, fontSize: 10, fontWeight: '700', marginTop: 3 }, message: { minHeight: 110, alignItems: 'center', justifyContent: 'center', gap: 8, padding: 18, borderRadius: radius.md, backgroundColor: colors.surface }, error: { color: colors.status.absent.text, textAlign: 'center' },
});
