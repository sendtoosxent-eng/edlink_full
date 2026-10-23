import Svg, { Circle, G, Path, Text as SvgText } from 'react-native-svg';

/** Decorative book and classroom calculations for lesson reminders. */
export function LessonArtwork() {
  return <Svg width="100%" height="100%" viewBox="0 0 140 128" fill="none">
    <G stroke="#0B132B" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round">
      <Path d="M27 65c15-6 29-3 43 5 14-8 28-11 43-5v43c-15-6-29-3-43 5-14-8-28-11-43-5V65Z" />
      <Path d="M70 70v43M36 77c9-1 17 1 25 5m-25 5c9-1 17 1 25 5m18-10c8-4 16-6 25-5m-25 15c8-4 16-6 25-5" />
      <Path d="m94 33 15-15 8 8-15 15-13 5 5-13Zm15-15 5-5a4 4 0 0 1 6 0l2 2a4 4 0 0 1 0 6l-5 5" />
      <Circle cx={21} cy={43} r={4} /><Path d="M121 77v10m-5-5h10" />
    </G>
    <SvgText x={12} y={24} fill="#0B132B" fontSize={18} fontWeight="500" transform="rotate(-9 12 24)">x² + y²</SvgText>
    <SvgText x={40} y={51} fill="#0B132B" fontSize={17} transform="rotate(7 40 51)">a + b</SvgText>
  </Svg>;
}
