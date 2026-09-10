const fs=require('node:fs'),vm=require('node:vm'),assert=require('node:assert/strict');
const SunCalc=require('../source/vendor/suncalc/suncalc.js');
const source=fs.readFileSync('tools/polar-assets/source/assets/js/greeting.js','utf8');
const fn=source.slice(source.indexOf('function polarMoonState('),source.indexOf('function polarPaintMoon('));
const state=vm.runInNewContext('('+fn+')',{SunCalc});
const place={latitude:41.3,longitude:69.2};
assert.equal(state(new Date(),null),null);
assert.equal(state(new Date(),{latitude:100,longitude:0}),null);
assert.ok(state(new Date('2024-04-08T18:21:00Z'),place).fraction<.002,'Solar eclipse new moon');
assert.ok(state(new Date('2024-03-25T07:00:00Z'),place).fraction>.998,'Lunar eclipse full moon');
const hours=Array.from({length:24},(_,h)=>state(new Date(Date.UTC(2026,8,9,h)),place));
assert.ok(hours.some(s=>s.visible)&&hours.some(s=>!s.visible),'Moonrise and moonset');
for(const s of hours){assert.ok(s.x>=0&&s.x<100);assert.ok(s.y>=3&&s.y<=32);}
const a=state(new Date('2026-09-09T12:00:00Z'),place),b=state(new Date('2026-09-09T12:00:00Z'),{latitude:-33.9,longitude:151.2});
assert.ok(Math.abs(a.altitude-b.altitude)>10);assert.notEqual(a.rotation,b.rotation);
console.log('Moon tests passed: known phases, horizon, coordinates and observer orientation.');

const paint=vm.runInNewContext('('+source.slice(source.indexOf('function polarPaintMoon('),source.indexOf('function polarHeroSceneState('))+')');
function disk(fraction){let pixels;const context={createImageData:(w,h)=>({data:new Uint8ClampedArray(w*h*4)}),putImageData:data=>pixels=data.data};paint({getContext:()=>context},{fraction,rotation:0});return pixels;}
function visiblePixels(data){let n=0;for(let i=3;i<data.length;i+=4)if(data[i])n++;return n;}
assert.equal(visiblePixels(disk(0)),0,'New moon must not paint a black disk');
assert.ok(visiblePixels(disk(1))>6000,'Full moon remains visible');
const crescent=disk(.05);assert.ok(visiblePixels(crescent)>0&&visiblePixels(crescent)<500,'Only the illuminated crescent is drawn');
assert.equal(crescent[(48*96+48)*4+3],0,'Dark center remains transparent');
console.log('Moon rendering passed: transparent dark side, crescent and full moon.');
