// Copies the existing app's artwork; rasterization preserves the original SVG drawings.
// Usage: NODE_PATH=<directory containing @resvg/resvg-js> node ios-native/Scripts/sync-artwork.cjs
const fs = require('fs');
const path = require('path');
const { Resvg } = require('@resvg/resvg-js');
const repo = path.resolve(__dirname, '../..');
const assets = path.join(repo, 'ios-native/Edlink/Assets.xcassets');
function save(name, svg) {
  const dir = path.join(assets, name + '.imageset'); fs.mkdirSync(dir, {recursive:true});
  fs.writeFileSync(path.join(dir,'artwork.png'), new Resvg(svg, {fitTo:{mode:'width',value:900}}).render().asPng());
  fs.writeFileSync(path.join(dir,'Contents.json'), JSON.stringify({images:[{filename:'artwork.png',idiom:'universal'}], info:{author:'xcode',version:1}}));
}
function convert(fragment) {
  return fragment.replace(/<\/?(?:SvgText|Path|Circle|Rect|G|Ellipse|Line|Polygon|Polyline)\b/g, x=>x.replace('SvgText','text').toLowerCase())
    .replace(/([\w]+)=\{([^{}]+)\}/g, (_,key,val)=>key+'="'+(val==='color' ? '#0B132B' : val.replace(/^['"]|['"]$/g,''))+'"')
    .replace(/\b(strokeWidth|strokeLinecap|strokeLinejoin|strokeDasharray|strokeOpacity|fillOpacity|fontSize|fontWeight|textAnchor)\b/g, x=>x.replace(/[A-Z]/g,l=>'-'+l.toLowerCase()));
}
for (const role of ['teacher','student','parent']) save('Role'+role[0].toUpperCase()+role.slice(1),fs.readFileSync(path.join(repo,'mobile/assets/roles',role+'.svg'),'utf8'));
for (const [file,prefix,box,stroke] of [['StatArtwork.tsx','Stat',96,2.4],['OnboardingIllustration.tsx','Intro',80,1.8]]) {
 const text=fs.readFileSync(path.join(repo,'mobile/src/components',file),'utf8');
 for (const match of text.matchAll(/\{name === '(\w+)' && <>([\s\S]*?)<\/>\}/g)) {
  const name=match[1]; const color=prefix==='Intro'?'#EFB000':'#0B132B';
  const extra = name==='marks' ? '<text x="31" y="49" fill="#0B132B" font-size="20" font-weight="600">A+</text>' : '';
  const halo=prefix==='Intro'?'<circle cx="40" cy="40" r="35" fill="#EFB000" fill-opacity=".07"/>':'';
  save(prefix+name[0].toUpperCase()+name.slice(1),`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${box} ${box}" fill="none">${halo}<g stroke="${color}" stroke-width="${stroke}" stroke-linecap="round" stroke-linejoin="round">${convert(match[2])}</g>${extra}</svg>`);
 }
}
for (const [file,name] of [['AlarmClockArtwork.tsx','LessonClock'],['LessonArtwork.tsx','LessonArtwork'],['SchoolIllustration.tsx','SchoolGlyph']]) {
 const text=fs.readFileSync(path.join(repo,'mobile/src/components',file),'utf8');
 const match=text.match(/<Svg[^>]*viewBox="([^"]+)"[^>]*>([\s\S]*?)<\/Svg>/);
 if (match) save(name,`<svg xmlns="http://www.w3.org/2000/svg" viewBox="${match[1]}" fill="none">${convert(match[2])}</svg>`);
}
for (const [name,content] of [
 ['AuthEmailGlyph','<rect x="4" y="7" width="24" height="18" rx="3"/><path d="m5 9 11 8L27 9"/>'],
 ['AuthLockGlyph','<path d="M10 14V9a6 6 0 0 1 12 0v5"/><rect x="6" y="14" width="20" height="15" rx="3"/><circle cx="16" cy="21" r="2" fill="#EFB000"/><path d="M16 23v2"/>'],
 ['AuthTokenGlyph','<circle cx="11" cy="11" r="6"/><path d="m15 16 11 11m-4-4 3-3m-7 0 3-3"/>']
]) save(name,`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" fill="none"><g stroke="#EFB000" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">${content}</g></svg>`);
const appSvg = fs.readFileSync(path.join(repo,'mobile/src/components/AppSvg.tsx'),'utf8');
for (const match of appSvg.matchAll(/\{name === '(\w+)' && <>([\s\S]*?)<\/>\}/g)) {
 const raw=match[2].replace(/solid \? 1 : 0\.\d+/g,'1').replace(/solid \? '#FFFFFF' : 'none'/g,"'#FFFFFF'").replace(/solid \? '#FFFFFF' : color/g,"'#FFFFFF'");
 const svg=`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none">${convert(raw)}</svg>`;
 save('App'+match[1][0].toUpperCase()+match[1].slice(1),svg);
 save('App'+match[1][0].toUpperCase()+match[1].slice(1)+'Dark',svg.replaceAll('#0B132B','#EFB000'));
}
