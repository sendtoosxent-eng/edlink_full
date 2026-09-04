# Edlink Mobile — Design System & Engineering Guidelines

This document defines the strict visual language, design constraints, and technical architecture for the Edlink React Native (Expo) codebase. **Do not modify or deviate from these core design patterns and color schemes when generating or refactoring UI components.**

---

## 1. Required Packages & Visual Dependencies

Always ensure visual dependencies (icons and SVG handling) are installed and utilized rather than text-glyph placeholders or custom shape mocks.

```bash
npx expo install @expo/vector-icons react-native-svg

export const colors = {
  // Brand Base
  primary: '#0B132B',       // Deep Edlink Navy
  primaryCard: '#1C2541',   // Dark Navy (Card Surface)
  secondary: '#efb000',     // Vibrant Edlink Yellow (Golds & Highlights)
  secondaryDark: '#E0A96D', // Deep Gold Accent
  
  // Surfaces & Backgrounds
  background: '#F4F6FA',    // Clean Light Neutral Background
  surface: '#FFFFFF',       // Card & Sheet Surfaces
  
  // Typography
  textDark: '#0B132B',      // High-Contrast Headings
  textMuted: '#6C7A9C',     // Secondary Body & Subtitles
  textLight: '#FFFFFF',     // Inverse Text
  
  // Semantic Indicators
  status: {
    present: { bg: '#DCFCE7', text: '#15803D' },
    absent: { bg: '#FEE2E2', text: '#B91C1C' },
    late: { bg: '#FEF3C7', text: '#B45309' },
    excused: { bg: '#E0F2FE', text: '#0369A1' },
  },
};
