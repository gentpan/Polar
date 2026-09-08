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
