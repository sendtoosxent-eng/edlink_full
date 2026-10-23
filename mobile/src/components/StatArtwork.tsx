import { useAppTheme } from '../theme/ThemeProvider';
import Svg, { Circle, G, Path, Rect, Text as SvgText } from 'react-native-svg';
export type StatArtworkName = 'learners' | 'classroom' | 'subjects' | 'lessons' | 'attendance' | 'marks' | 'reports' | 'school';

export function StatArtwork({ name, color: providedColor }: { name: StatArtworkName; color?: string }) {
  const { isDark } = useAppTheme();
  const color = providedColor ?? (isDark ? '#EFB000' : '#0B132B');
  return <Svg width="100%" height="100%" viewBox="0 0 96 96" fill="none">
    <G stroke={color} strokeWidth={2.4} strokeLinecap="round" strokeLinejoin="round">
      {name === 'learners' && <><Circle cx={48} cy={31} r={12} fill="#EFB000" fillOpacity={0.5} /><Circle cx={22} cy={37} r={9} /><Circle cx={74} cy={37} r={9} /><Path d="M29 80V65c0-12 8-19 19-19s19 7 19 19v15H29ZM8 76V63c0-9 6-15 14-15h5m42 0h5c8 0 14 6 14 15v13M42 47l6 8 6-8" /></>}
      {name === 'classroom' && <><Rect x={12} y={12} width={72} height={48} rx={5} fill="#EFB000" fillOpacity={0.3} /><Path d="M23 26h24m-24 11h35M65 24l7 8-7 8M30 60l-8 24m44-24 8 24M37 74h22" /><Circle cx={17} cy={75} r={5} /><Path d="M8 91v-3c0-5 4-8 9-8s9 3 9 8v3M72 55h7" /></>}
      {name === 'subjects' && <><Path d="M12 29c13-6 24-3 36 4 12-7 23-10 36-4v45c-13-6-24-3-36 4-12-7-23-10-36-4V29Z" fill="#EFB000" fillOpacity={0.3} /><Path d="M48 33v45M20 41c6-1 12 0 19 4m-19 7c6-1 12 0 19 4m18-11c7-4 13-5 19-4m-19 15c7-4 13-5 19-4M17 19h23M25 12h12M24 84h48" /></>}
      {name === 'lessons' && <><Rect x={14} y={16} width={58} height={62} rx={6} /><Path d="M14 31h58M28 9v14M58 9v14M25 43h8m10 0h8M25 55h8" /><Circle cx={66} cy={66} r={22} fill="#EFB000" fillOpacity={0.45} /><Path d="M66 52v14l9 6" /></>}
      {name === 'attendance' && <><Rect x={17} y={16} width={62} height={70} rx={6} /><Rect x={32} y={9} width={32} height={14} rx={4} fill="#EFB000" fillOpacity={0.5} /><Path d="m27 39 4 4 8-9m8 5h21M27 56l4 4 8-9m8 5h21M27 73l4 4 8-9m8 5h14" /></>}
      {name === 'reports' && <><Rect x={16} y={12} width={64} height={74} rx={6} /><Path d="M27 25h31M27 34h20M27 72h42" /><Rect x={29} y={56} width={8} height={16} rx={2} fill="#EFB000" fillOpacity={0.5} /><Rect x={43} y={47} width={8} height={25} rx={2} /><Rect x={57} y={39} width={8} height={33} rx={2} fill="#EFB000" fillOpacity={0.5} /></>}
      {name === 'school' && <><Path d="m9 39 39-25 39 25M17 39v43h62V39M38 82V59h20v23M9 82h78M48 14V4h19l-4 5H48" /><Circle cx={48} cy={40} r={8} fill="#EFB000" fillOpacity={0.5} /><Path d="M48 35v5h4M25 48h5v8h-5zm0 16h5v8h-5zm41-16h5v8h-5zm0 16h5v8h-5z" /></>}
      {name === 'marks' && <><Path d="M21 9h36l18 18v58H21V9Zm36 0v18h18M31 58h34M31 69h24M31 78h17" /><Circle cx={69} cy={69} r={17} fill="#EFB000" fillOpacity={0.6} /><Path d="m61 69 6 6 11-12" /></>}
    </G>
    {name === 'marks' && <SvgText x={31} y={49} fill={color} fontSize={20} fontWeight="600">A+</SvgText>}
  </Svg>;
}
