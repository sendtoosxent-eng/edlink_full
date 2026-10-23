import Svg, { Circle, G, Path, Rect } from 'react-native-svg';

type Artwork = 'learning' | 'connection' | 'growth' | 'orbit';

/** Small vector compositions drawn in Edlink's palette, without icon fonts. */
export function OnboardingIllustration({ name, size = 60 }: { name: Artwork; size?: number }) {
  return (
    <Svg width={size} height={size} viewBox="0 0 80 80" fill="none">
      <Circle cx={40} cy={40} r={35} fill="#EFB000" fillOpacity={0.07} />
      <G stroke="#EFB000" strokeWidth={1.8} strokeLinecap="round" strokeLinejoin="round">
        {name === 'learning' && <>
          <Rect x={20} y={18} width={39} height={48} rx={6} fill="#16213D" />
          <Path d="M29 18v48M37 31h14M37 39h10M37 47h14" />
          <Circle cx={60} cy={20} r={8} fill="#0B132B" />
          <Path d="m57 20 2 2 4-4M14 39h-4M16 48l-4 3" />
        </>}
        {name === 'connection' && <>
          <Circle cx={40} cy={38} r={16} strokeDasharray="3 5" />
          <Path d="m20 26 11 7m20 1 10-8M40 55v9" />
          <Circle cx={16} cy={23} r={9} fill="#16213D" />
          <Circle cx={64} cy={23} r={9} fill="#16213D" />
          <Circle cx={40} cy={66} r={8} fill="#16213D" />
          <Path d="m35 39 4 4 7-9" />
        </>}
        {name === 'growth' && <>
          <Path d="M17 62h47" />
          <Rect x={22} y={47} width={9} height={15} rx={2} fill="#EFB000" fillOpacity={0.12} />
          <Rect x={37} y={36} width={9} height={26} rx={2} fill="#EFB000" fillOpacity={0.2} />
          <Rect x={52} y={23} width={9} height={39} rx={2} fill="#EFB000" fillOpacity={0.3} />
          <Path d="m20 36 13-11 10 1 16-13m-9 0h9v9" />
          <Circle cx={18} cy={19} r={3} />
        </>}
        {name === 'orbit' && <>
          <Circle cx={40} cy={40} r={17} strokeOpacity={0.5} />
          <Path d="M10 48c-4-7 6-19 23-27s34-9 37-2-6 19-23 27-34 9-37 2Z" />
          <Circle cx={57} cy={23} r={5} fill="#EFB000" />
          <Circle cx={24} cy={62} r={3} fill="#EFB000" />
        </>}
      </G>
    </Svg>
  );
}
