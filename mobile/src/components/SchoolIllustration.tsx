import Svg, { Circle, Path, Rect } from 'react-native-svg';
export function SchoolIllustration({ size = 30 }: { size?: number }) {
  return <Svg width={size} height={size} viewBox="0 0 40 40" fill="none">
    <Path d="M5 18h30v17H5z" fill="#FFFFFF" fillOpacity={0.12} />
    <Path d="m3 18 17-12 17 12M7 18v17h26V18M17 35V25h6v10M3 35h34M20 6V2h8l-2 3h-6" stroke="#EFB000" strokeWidth={1.7} strokeLinecap="round" strokeLinejoin="round" />
    <Circle cx={20} cy={16} r={3} stroke="#EFB000" strokeWidth={1.5} />
    <Path d="M20 14v2h1" stroke="#EFB000" strokeLinecap="round" />
    <Rect x={10} y={22} width={3} height={4} rx={0.5} fill="#EFB000" /><Rect x={27} y={22} width={3} height={4} rx={0.5} fill="#EFB000" />
  </Svg>;
}
