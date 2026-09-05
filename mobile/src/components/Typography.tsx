import { createContext, forwardRef, useContext } from 'react';
import { StyleSheet, Text as NativeText, TextInput as NativeTextInput, type TextProps, type TextInputProps, type TextStyle } from 'react-native';

const FontContext = createContext('Poppins_400Regular');
export type TextInput = NativeTextInput;
const fonts: Record<string, string> = {
  '100': 'Poppins_400Regular', '200': 'Poppins_400Regular', '300': 'Poppins_400Regular',
  normal: 'Poppins_400Regular', '400': 'Poppins_400Regular', '500': 'Poppins_500Medium',
  '600': 'Poppins_600SemiBold', bold: 'Poppins_700Bold', '700': 'Poppins_700Bold',
  '800': 'Poppins_800ExtraBold', '900': 'Poppins_800ExtraBold',
};
function fontStyle(style: TextStyle | undefined, inherited: string): TextStyle {
  return { fontFamily: style?.fontFamily ?? (style?.fontWeight ? fonts[style.fontWeight] : inherited), fontWeight: 'normal' };
}
export const Text = forwardRef<NativeText, TextProps>(function Text({ style, children, ...props }, ref) {
  const font = fontStyle(StyleSheet.flatten(style), useContext(FontContext));
  return <FontContext.Provider value={font.fontFamily!}><NativeText ref={ref} {...props} style={[style, font]}>{children}</NativeText></FontContext.Provider>;
});
export const TextInput = forwardRef<NativeTextInput, TextInputProps>(function TextInput({ style, ...props }, ref) {
  return <NativeTextInput ref={ref} {...props} style={[style, fontStyle(StyleSheet.flatten(style), 'Poppins_400Regular')]} />;
});
