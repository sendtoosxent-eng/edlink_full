import { createContext, useContext, useEffect, useMemo, useRef, useState, type ReactNode } from 'react';
import { Appearance, StyleSheet, useColorScheme } from 'react-native';
import * as SecureStore from 'expo-secure-store';

export type ThemePreference = 'system' | 'light' | 'dark';
const KEY = 'edlink.mobile.appearance';
const ThemeContext = createContext({ preference: 'light' as ThemePreference, isDark: false, setPreference: (_value: ThemePreference) => {} });
export function ThemeProvider({ children }: { children: ReactNode }) {
  const system = useColorScheme();
  const [preference, setValue] = useState<ThemePreference>('light');
  const changed = useRef(false);
  const writes = useRef(Promise.resolve());
  useEffect(() => { void SecureStore.getItemAsync(KEY).then(value => { if (!changed.current && (value === 'light' || value === 'dark' || value === 'system')) setValue(value); }).catch(() => {}); }, []);
  const setPreference = (value: ThemePreference) => {
    changed.current = true; setValue(value);
    writes.current = writes.current.catch(() => {}).then(() => SecureStore.setItemAsync(KEY, value));
    void writes.current.catch(() => {});
  };
  const isDark = (preference === 'system' ? system : preference) === 'dark';
  useEffect(() => { Appearance.setColorScheme(preference === 'system' ? null : preference); }, [preference]);
  return <ThemeContext.Provider value={{ preference, isDark, setPreference }}>{children}</ThemeContext.Provider>;
}
export const useAppTheme = () => useContext(ThemeContext);

const surfaces = new Set(['white', '#ffffff', '#fff', '#f7f8fc', '#f9f9ff', '#f0f3ff', '#eef2f8', '#f8fafc', '#f8fafb']);
const inks = new Set(['#0b132b', '#09051c', '#151c27', '#231e34', '#211d35', '#17352f', '#1e293b']);
const muted = new Set(['#707785', '#777381', '#48464d', '#52635d', '#71807b', '#64748b']);
const borders = new Set(['#e3e7ef', '#e2e8f8', '#d9e1f1', '#dce2f3', '#c9c5cd', '#e2e7f0', '#e3e9e5']);
const creams = new Set(['#fff5d5', '#fff3d5', '#fff9e8']);
export function useThemedStyles<T extends Record<string, any>>(source: T): T {
  const { isDark } = useAppTheme();
  return useMemo(() => {
    if (!isDark) return source;
    return Object.fromEntries(Object.entries(source).map(([name, value]) => {
      const style = { ...StyleSheet.flatten(value) };
      for (const [property, color] of Object.entries(style)) {
        if (typeof color !== 'string') continue;
        const hex = color.toLowerCase();
        if (property === 'backgroundColor') {
          if (surfaces.has(hex)) style[property] = /screen|content|safe|outerContainer/i.test(name) ? '#080E1D' : '#172239';
          else if (creams.has(hex)) style[property] = '#302B1D';
        } else if (property === 'color') {
          if (inks.has(hex) && !/themeSelectedLabel|secondaryText|dateText|reportButtonText|termText|subjectText|chipTextActive|childNameActive/i.test(name) && style.backgroundColor !== '#EFB000') style[property] = '#EEF2FA';
          else if (muted.has(hex)) style[property] = '#A9B5C9';
          else if (hex === '#8a6500' || hex === '#765a00') style[property] = '#F4C44E';
        } else if (/border.*Color/.test(property) && borders.has(hex)) style[property] = '#2C3B55';
      }
      return [name, style];
    })) as T;
  }, [source, isDark]);
}
