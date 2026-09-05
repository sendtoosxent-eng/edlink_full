import { Text } from './Typography';
import React from 'react';
import { BrandLoader } from './BrandLoader';
import { View, StyleSheet, Pressable } from 'react-native';
import { colors, radius, shadows, typography } from '../theme/index';
import type { AttendanceStatus } from '../types';

export function PrimaryButton({ label, onPress, disabled }: { label: string; onPress: () => void; disabled?: boolean }) {
  return (
    <Pressable
      onPress={onPress}
      disabled={disabled}
      style={({ pressed }) => [styles.button, (pressed || disabled) && styles.buttonDisabled]}
    >
      <Text style={styles.buttonText}>{label}</Text>
    </Pressable>
  );
}

export function StatusPill({ status }: { status: AttendanceStatus }) {
  const config = colors.status[status] || colors.status.excused;
  return (
    <View style={[styles.pill, { backgroundColor: config.bg }]}>
      <Text style={[styles.pillText, { color: config.text }]}>
        {status.charAt(0).toUpperCase() + status.slice(1)}
      </Text>
    </View>
  );
}

export function StatCard({ label, value }: { label: string; value: string | number }) {
  return (
    <View style={[styles.statCard, shadows.card]}>
      <Text style={styles.statValue}>{value}</Text>
      <Text style={styles.statLabel}>{label}</Text>
    </View>
  );
}

export function InlineLoading() { return <BrandLoader />; }

export function ErrorState({ message, retry }: { message: string; retry: () => void }) {
  return (
    <View style={styles.center}>
      <Text style={typography.titleSmall}>Something went wrong</Text>
      <Text style={styles.mutedText}>{message}</Text>
      <PrimaryButton label="Try Again" onPress={retry} />
    </View>
  );
}

const styles = StyleSheet.create({
  button: {
    minHeight: 52,
    borderRadius: radius.sm,
    backgroundColor: colors.primary,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 20,
    marginTop: 8,
  },
  buttonDisabled: {
    opacity: 0.6,
  },
  buttonText: {
    color: colors.surface,
    fontWeight: '800',
    fontSize: 15,
  },
  pill: {
    borderRadius: radius.full,
    paddingVertical: 6,
    paddingHorizontal: 12,
  },
  pillText: {
    fontSize: 12,
    fontWeight: '800',
  },
  statCard: {
    flex: 1,
    padding: 16,
    backgroundColor: colors.surface,
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: colors.border,
  },
  statValue: {
    color: colors.accent,
    fontWeight: '900',
    fontSize: 24,
  },
  statLabel: {
    color: colors.textSecondary,
    fontSize: 12,
    marginTop: 4,
  },
  center: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 12,
    padding: 24,
  },
  mutedText: {
    color: colors.textSecondary,
    textAlign: 'center',
  },
});
