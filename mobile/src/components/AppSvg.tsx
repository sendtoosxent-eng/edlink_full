import Svg, { Circle, Path } from 'react-native-svg';
export type AppSvgName = 'attendance' | 'exam' | 'bell' | 'settings' | 'search' | 'logout' | 'profile' | 'back' | 'chevron';
export function AppSvg({ name, size = 24, color = '#0B132B', solid = false }: { name: AppSvgName; size?: number; color?: string; solid?: boolean }) {
  return <Svg width={size} height={size} viewBox="0 0 24 24" fill="none">
    {name === 'attendance' && <><Path d="M8 4H5v17h14V4h-3M9 2h6v5H9zM8 11l1.5 1.5L12 10m2 2h2m-8 5 1.5 1.5L12 16m2 2h2" stroke={color} strokeWidth={1.6} strokeLinecap="round" strokeLinejoin="round" /></>}
    {name === 'exam' && <><Path d="M14 2H5v20h14V7l-5-5Zm0 0v5h5M8 11h8m-8 4h3m-3 3h3m3-2 1.5 1.5L18 15" stroke={color} strokeWidth={1.6} strokeLinecap="round" strokeLinejoin="round" /></>}
    {name === 'bell' && <><Path d="M5 16h14l-2-3V9a5 5 0 0 0-10 0v4l-2 3Z" fill={color} fillOpacity={solid ? 1 : 0.12} stroke={color} strokeWidth={1.7} strokeLinejoin="round" /><Path d="M10 19a2 2 0 0 0 4 0M12 2v2" stroke={color} strokeWidth={1.7} strokeLinecap="round" /></>}
    {name === 'settings' && <><Path d="m9 3-.7 2.3-2 .9L4 5.8 2.5 8.4l1.6 1.8-.2 2.2-1.4 1.9L4 17l2.4-.5 1.9 1L9 20h3l.8-2.5 1.9-1 2.3.5 1.5-2.7-1.5-1.9-.1-2.2 1.6-1.8L17 5.8l-2.3.4-2-.9L12 3H9Z" transform="translate(1.5 .5)" fill={color} fillOpacity={solid ? 1 : 0.1} stroke={color} strokeWidth={1.5} strokeLinejoin="round" /><Circle cx={12} cy={12} r={3.2} fill={solid ? '#FFFFFF' : 'none'} stroke={solid ? '#FFFFFF' : color} strokeWidth={1.7} /></>}
    {name === 'search' && <><Circle cx={10.5} cy={10.5} r={6.5} stroke={color} strokeWidth={1.8} /><Path d="m16 16 4 4" stroke={color} strokeWidth={1.8} strokeLinecap="round" /></>}
    {name === 'logout' && <><Path d="M10 4H5v16h5m3-12 4 4-4 4m-4-4h12" stroke={color} strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round" /></>}
    {name === 'profile' && <><Circle cx={12} cy={8} r={4} stroke={color} strokeWidth={1.7} /><Path d="M4 21v-2a8 8 0 0 1 16 0v2" stroke={color} strokeWidth={1.7} strokeLinecap="round" /></>}
    {name === 'back' && <Path d="m10 5-7 7 7 7M3 12h18" stroke={color} strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round" />}
    {name === 'chevron' && <Path d="m9 5 7 7-7 7" stroke={color} strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round" />}
  </Svg>;
}
