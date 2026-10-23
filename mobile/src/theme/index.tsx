export const colors = {
  primary: '#0B132B',
  primaryCard: '#0B132B',
  secondary: '#EFB000',
  secondaryDark: '#8A6500',
  accent: '#EFB000',
  accentMuted: '#FFF5D5',
  background: '#F7F8FC',
  surface: '#FFFFFF',
  textDark: '#0B132B',
  textLight: '#FFFFFF',
  textPrimary: '#0B132B',
  textSecondary: '#707785',
  textMuted: '#707785',
  border: '#E3E7EF',
  borderDark: '#0B132B',
  
  // Status Colors
  status: {
    present: { bg: '#DDF2E7', text: '#176B5B' },
    absent: { bg: '#F8DEDD', text: '#DE3B55' },
    late: { bg: '#F7EBCB', text: '#8A6D0B' },
    excused: { bg: '#DEE8F5', text: '#1E5180' },
  },
};

export const typography = {
  titleLarge: { fontSize: 32, fontWeight: '900' as const, lineHeight: 38 },
  titleMedium: { fontSize: 24, fontWeight: '800' as const, lineHeight: 30 },
  titleSmall: { fontSize: 18, fontWeight: '800' as const },
  bodyLarge: { fontSize: 16, lineHeight: 24 },
  bodyMedium: { fontSize: 14, lineHeight: 20 },
  caption: { fontSize: 12, lineHeight: 16 },
};

export const radius = {
  xs: 6,
  sm: 10,
  md: 16,
  lg: 24,
  full: 9999,
};

export const shadows = {
  card: {
    shadowColor: colors.primary,
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.06,
    shadowRadius: 12,
    elevation: 3,
  },
};
