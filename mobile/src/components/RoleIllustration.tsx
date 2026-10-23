import Svg, { Circle, G, Path, Rect } from 'react-native-svg';
import type { Role } from '../types';

/** Original vector portraits for the account selection slides. */
export function RoleIllustration({ role, size }: { role: Role; size: number }) {
  return <Svg width={size} height={size} viewBox="0 0 260 260" fill="none">
    <Circle cx={130} cy={130} r={125} fill="#FFF5D5" />
    <Circle cx={130} cy={130} r={108} stroke="#EFB000" strokeOpacity={0.25} strokeDasharray="4 8" />
    {role === 'teacher' ? <>
      <Rect x={91} y={52} width={128} height={94} rx={10} fill="#1C2948" />
      <Path d="M109 76h41m-41 14h62m-14 33 15-16 12 5 17-26" stroke="#FFFFFF" strokeWidth={4} strokeLinecap="round" strokeLinejoin="round" />
      <Path d="M109 131h19" stroke="#EFB000" strokeWidth={4} strokeLinecap="round" />
      <Path d="m104 170 38-23 29-36" stroke="#BD7E58" strokeWidth={17} strokeLinecap="round" strokeLinejoin="round" />
      <Path d="M43 225v-42c0-28 18-44 43-44s44 16 44 44v42" fill="#EFB000" />
      <Path d="m75 140 11 24 12-24M86 166v55" stroke="#FFF7DC" strokeWidth={4} />
      <Rect x={75} y={121} width={23} height={27} rx={10} fill="#BD7E58" />
      <Circle cx={86} cy={103} r={29} fill="#CD946E" />
      <Path d="M57 104V91c0-20 15-31 31-31 20 0 32 15 29 32-18 1-30-5-39-13-1 11-8 20-21 25Z" fill="#18233C" />
      <Path d="M77 111c5 4 11 4 16 0" stroke="#754831" strokeWidth={2.5} strokeLinecap="round" />
      <Rect x={42} y={177} width={40} height={50} rx={5} fill="#1C2948" transform="rotate(-10 42 177)" />
      <Path d="m51 188 19-3m-18 13 14-2" stroke="#EFB000" strokeWidth={3} strokeLinecap="round" />
    </> : role === 'student' ? <>
      <Path d="M59 225v-42c0-31 28-48 70-48s72 17 72 48v42" fill="#EFB000" />
      <Path d="m91 147 10 67m62-67-9 67" stroke="#1C2948" strokeWidth={12} strokeLinecap="round" />
      <Rect x={116} y={119} width={27} height={32} rx={10} fill="#A96E4D" />
      <Circle cx={130} cy={99} r={37} fill="#BD825C" />
      <Path d="M93 101V84c0-25 17-39 38-39 24 0 40 18 37 43-21-1-40-9-48-19-3 17-13 27-27 32Z" fill="#18233C" />
      <Path d="M119 110c7 6 15 6 22 0" stroke="#74452E" strokeWidth={3} strokeLinecap="round" />
      <Path d="m82 53 48-23 49 23-49 22-48-22Z" fill="#1C2948" />
      <Path d="M103 63v14c18 11 37 11 54 0V63" fill="#1C2948" />
      <Path d="M176 55v32" stroke="#EFB000" strokeWidth={3} strokeLinecap="round" />
      <Rect x={90} y={170} width={75} height={54} rx={6} fill="#FFFFFF" transform="rotate(-8 90 170)" />
      <Path d="m106 183 39-6m-37 17 29-4" stroke="#EFB000" strokeWidth={4} strokeLinecap="round" />
    </> : <>
      <Path d="M36 225v-44c0-30 24-48 57-48s56 18 56 48v44" fill="#1C2948" />
      <Rect x={82} y={117} width={24} height={29} rx={10} fill="#A96E4D" />
      <Path d="M57 100c-8-41 13-61 38-61 32 0 48 25 37 65l-8 29H62l-5-33Z" fill="#18233C" />
      <Circle cx={94} cy={94} r={31} fill="#BD825C" />
      <Path d="M62 86c2-26 17-38 33-38 18 0 30 13 34 32-21 2-36-8-42-17-4 12-12 20-25 23Z" fill="#18233C" />
      <Path d="M85 104c6 5 12 5 18 0" stroke="#74452E" strokeWidth={2.5} strokeLinecap="round" />
      <Path d="M124 225v-25c0-24 19-39 43-39s42 15 42 39v25" fill="#EFB000" />
      <Rect x={157} y={151} width={20} height={22} rx={8} fill="#BD825C" />
      <Circle cx={167} cy={135} r={27} fill="#CD946E" />
      <Path d="M140 135v-12c0-19 12-29 28-29 18 0 30 13 28 31-18 1-29-4-37-13-1 12-7 18-19 23Z" fill="#18233C" />
      <Path d="M159 143c5 4 10 4 15 0" stroke="#74452E" strokeWidth={2.5} strokeLinecap="round" />
      <Path d="M69 177c8 21 29 36 60 35" stroke="#BD825C" strokeWidth={15} strokeLinecap="round" />
      <Path d="m158 185 9 9 10-9" stroke="#FFFFFF" strokeWidth={3} strokeLinecap="round" strokeLinejoin="round" />
    </>}
    <G fill="#EFB000"><Circle cx={36} cy={99} r={5} /><Circle cx={217} cy={177} r={4} /></G>
    <Path d="M199 35v14m-7-7h14" stroke="#EFB000" strokeWidth={3} strokeLinecap="round" />
  </Svg>;
}
