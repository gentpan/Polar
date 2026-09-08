/* Articulated SVG poses. No layout movement, image swapping, or growth writes. */
(() => {
 'use strict';
 const pet=document.querySelector('[data-feng-pet]');if(!pet||pet.dataset.petModel)return;
 const reduced=matchMedia('(prefers-reduced-motion: reduce)');
 const rigs=[...pet.querySelectorAll('[data-pet-rig]')];
 let running=[],timer,blinkTimer,finishTimer,inView=true,active=false,lastIdle='',hoverAt=0;
 const effects=[];
 const pose=(part,frames)=>({part,frames});
 const turn=(...angles)=>angles.map(a=>`rotate(${a}deg)`);
 const shift=(...points)=>points.map(([x,y])=>`translate(${x}px,${y}px)`);
 const poses={
  bird:{
   pat:[pose('head',['rotate(0)','translate(-3px,-2px) rotate(-14deg)','translate(3px,-1px) rotate(9deg)','translate(-2px,-2px) rotate(-10deg)','rotate(0)']),pose('tail',turn(0,-18,12,-18,0)),pose('arm-left',turn(0,25,8,20,0)),pose('torso',['scale(1)','scale(1.02,.98)','scale(.99,1.02)','scale(1)'])],
   nod:[pose('head',turn(0,16,-5,12,0)),pose('ear-left',turn(0,-15,10,0))],
   wave:[pose('arm-left',turn(0, 135,90,140,90,0)),pose('head',turn(0,-8,-8,0))],
   stretch:[pose('arm-left',turn(0,85,105,85,0)),pose('arm-right',turn(0,-85,-105,-85,0)),pose('torso',['scale(1)','scale(.96,1.06)','scale(1)']),pose('head',shift([0,0],[0,-5],[0,-5],[0,0]))],
   feed:[pose('head',['translate(0,0) rotate(0)','translate(2px,1px) rotate(5deg)','translate(1px,0) rotate(2deg)','translate(3px,2px) rotate(7deg)','translate(1px,0) rotate(2deg)','translate(0,0) rotate(0)']),pose('mouth',['scaleY(1)','scaleY(.85)','scaleY(1.08)','scaleY(.85)','scaleY(1)']),pose('tail',turn(0,-4,0,-4,0))],
   happy:[pose('arm-left',turn(0,95,20,95,0)),pose('arm-right',turn(0,-85,0,-85,0)),pose('head',turn(0,-9,9,0)),pose('torso',['scale(1)','scale(1.04,.96)','scale(.98,1.02)','scale(1)'])],
   groom:[pose('head',turn(0,-28,-20,-28,0)),pose('arm-left',turn(0,-24,-8,-24,0)),pose('tail',turn(0,12,-5,0))]
  },
  cat:{
   nod:[pose('head',turn(0,10,-4,10,0)),pose('ear-left',turn(0,-12,0))],
   wave:[pose('arm-right',turn(0,-125,-90,-130,-90,0)),pose('head',turn(0,-9,-9,0))],
   stretch:[pose('torso',['scale(1)','scale(.9,1.12)','scale(.9,1.12)','scale(1)']),pose('head',shift([0,0],[0,-7],[0,-7],[0,0])),pose('arm-left',turn(0,155,165,155,0)),pose('arm-right',turn(0,-155,-165,-155,0)),pose('tail',turn(0,-18,-25,0))],
   feed:[pose('head',shift([0,0],[0,5],[0,2],[0,5],[0,0])),pose('arm-left',turn(0, -35,-35,0)),pose('arm-right',turn(0,35,35,0)),pose('mouth',['scale(1)','scaleY(.4)','scaleY(1.25)','scaleY(.4)','scale(1)'])],
   happy:[pose('head',turn(0,-12,10,-8,0)),pose('tail',turn(0,-20,18,-20,18,0)),pose('arm-left',turn(0,20,-10,0))],
   groom:[pose('arm-left',['rotate(0)','translate(7px,-14px) rotate(25deg)','translate(7px,-8px) rotate(8deg)','translate(7px,-14px) rotate(25deg)','rotate(0)']),pose('head',turn(0,-13,-5,-13,0)),pose('ear-left',turn(0,-15,0))]
  },
  fox:{
   nod:[pose('head',turn(0,17,0,-10,0)),pose('ear-right',turn(0,18,0))],
   wave:[pose('arm-left',turn(0,115,80,120,80,0)),pose('tail',turn(0,-15,12,-15,0))],
   stretch:[pose('torso',['scale(1)','scale(1.12,.9)','scale(1.12,.9)','scale(1)']),pose('head',shift([0,0],[-4,6],[-4,6],[0,0])),pose('arm-left',turn(0,45,55,0)),pose('arm-right',turn(0,-45,-55,0)),pose('tail',turn(0,-35,-40,0))],
   feed:[pose('head',turn(0,12,3,12,0)),pose('arm-left',turn(0,-32,-32,0)),pose('arm-right',turn(0,32,32,0)),pose('mouth',['scale(1)','scaleY(.6)','scaleY(1.3)','scaleY(.6)','scale(1)']),pose('ear-right',turn(0,12,0))],
   happy:[pose('tail',turn(0,-30,15,-30,15,-20,0)),pose('head',turn(0,-12,10,0)),pose('ear-left',turn(0,-17,0)),pose('ear-right',turn(0,17,0))],
   groom:[pose('head',turn(0,25,18,25,0)),pose('tail',turn(0,-34,-25,-34,0)),pose('arm-right',turn(0,-50,-20,-50,0))]
  },
  rabbit:{
   nod:[pose('head',turn(0,10,-3,10,0)),pose('ear-left',turn(0,-22,8,0)),pose('ear-right',turn(0,15,-12,0))],
   wave:[pose('arm-right',turn(0,-125,-85,-125,-85,0)),pose('ear-left',turn(0,-18,0)),pose('ear-right',turn(0,18,0))],
   stretch:[pose('ear-left',turn(0,20,25,0)),pose('ear-right',turn(0,-20,-25,0)),pose('torso',['scale(1)','scale(.92,1.1)','scale(1)']),pose('head',shift([0,0],[0,-5],[0,-5],[0,0])),pose('arm-left',turn(0,135,145,0)),pose('arm-right',turn(0,-135,-145,0))],
   feed:[pose('arm-left',turn(0,-36,-36,0)),pose('arm-right',turn(0,36,36,0)),pose('head',shift([0,0],[0,4],[0,2],[0,4],[0,0])),pose('mouth',['scale(1)','scaleY(.4)','scaleY(1.3)','scaleY(.4)','scale(1)']),pose('ear-left',turn(0,-12,0,-12,0))],
   happy:[pose('ear-left',turn(0,-30,14,-22,0)),pose('ear-right',turn(0,30,-14,22,0)),pose('arm-left',turn(0,45,0,45,0)),pose('arm-right',turn(0,-45,0,-45,0)),pose('torso',['scale(1)','scale(1.07,.94)','scale(.97,1.04)','scale(1)'])],
   groom:[pose('arm-left',['rotate(0)','translate(11px,-18px) rotate(20deg)','translate(11px,-10px)','translate(11px,-18px) rotate(20deg)','rotate(0)']),pose('arm-right',['rotate(0)','translate(-11px,-18px) rotate(-20deg)','translate(-11px,-10px)','translate(-11px,-18px) rotate(-20deg)','rotate(0)']),pose('head',shift([0,0],[0,3],[0,0],[0,3],[0,0]))]
  }
 };
 // Each toy has its own path and a matching full-body response.
 const birdTricks={
  ball:{label:'顶小球',toy:'●',x:50,y:0,color:'#eeb34c',path:['translateY(0)','translateY(-25px)','translateY(0)','translateY(-18px)','translateY(0)'],body:['translateY(0)','translateY(-3px)','translateY(1px)','translateY(-2px)','translateY(0)']},
  kick:{label:'踢皮球',toy:'⚽',x:78,y:80,path:['translateX(0) rotate(0)','translateX(0) rotate(0)','translate(28px,-12px) rotate(160deg)','translate(38px,0) rotate(260deg)','translateX(0) rotate(360deg)'],body:['rotate(0)','rotate(-5deg)','rotate(7deg)','rotate(0)']},
  butterfly:{label:'追蝴蝶',toy:'🦋',x:75,y:10,path:['translate(0,0)','translate(-30px,-15px)','translate(15px,-8px)','translate(-15px,4px)','translate(0,0)'],body:['translateX(0) rotate(0)','translateX(-5px) rotate(-5deg)','translateX(5px) rotate(5deg)','translateX(0) rotate(0)']},
  hop:{label:'蹦蹦跳',body:['translateY(0) scale(1)','translateY(2px) scale(1.04,.96)','translateY(-16px) scale(.98,1.02)','translateY(0) scale(1.04,.96)','translateY(-9px) scale(1)','translateY(0) scale(1)']},
  turn:{label:'转个身',body:['scaleX(1)','scaleX(.25)','scaleX(-1)','scaleX(-1)','scaleX(.25)','scaleX(1)']},
  balance:{label:'单脚平衡',body:['translate(0,0) rotate(0)','translate(-3px,-2px) rotate(-9deg)','translate(3px,-2px) rotate(7deg)','translate(-2px,-2px) rotate(-5deg)','translate(0,0) rotate(0)']},
  bubble:{label:'吹泡泡',toy:'○',x:80,y:42,color:'#78bfc7',path:['translate(0,0) scale(.2)','translate(5px,-4px) scale(.8)','translate(16px,-16px) scale(1.4)','translate(25px,-28px) scale(1.6)'],body:['rotate(0)','rotate(3deg)','rotate(0)']},
  music:{label:'跟着节拍',toy:'♪',x:80,y:12,color:'#9b8cce',path:['translateY(0) rotate(-10deg)','translateY(-12px) rotate(12deg)','translateY(-24px) rotate(-10deg)'],body:['rotate(0)','rotate(-6deg)','rotate(6deg)','rotate(-6deg)','rotate(6deg)','rotate(0)']},
  umbrella:{label:'撑小伞',toy:'☂',x:46,y:-10,color:'#8bb9b3',path:['rotate(-8deg)','rotate(8deg)','rotate(-5deg)','rotate(0)'],body:['translateX(0)','translateX(-3px)','translateX(3px)','translateX(0)']},
  peek:{label:'害羞躲躲',toy:'♥',x:75,y:5,color:'#df869e',path:['translateY(0) scale(.5)','translateY(-8px) scale(1)','translateY(-18px) scale(.7)'],body:['scale(1)','translateY(5px) scale(.92)','translateY(5px) scale(.92)','scale(1)']}
 };
 function playBirdTrick(name,duration){
  const trick=birdTricks[name];if(!trick)return;
  rigs.forEach(rig=>running.push(rig.animate(trick.body.map(transform=>({transform})),{duration,easing:'ease-in-out',fill:'none'})));
  if(trick.toy)pet.querySelectorAll('.feng-pet-launcher .feng-pet-art,.feng-pet-intro .feng-pet-art').forEach(art=>{
   const toy=document.createElement('span');toy.className='feng-pet-toy';toy.textContent=trick.toy;toy.setAttribute('aria-hidden','true');toy.style.left=trick.x+'%';toy.style.top=trick.y+'%';toy.style.color=trick.color||'';art.append(toy);effects.push(toy);
   running.push(toy.animate(trick.path.map((transform,i,a)=>({transform,opacity:i===0||i===a.length-1?0:1})),{duration,easing:'ease-in-out',fill:'both'}));
  });
 }
 const available=()=>!document.hidden&&!pet.hidden&&!pet.hasAttribute('data-resting')&&(inView||!pet.querySelector('#feng-pet-panel').hidden)&&getComputedStyle(pet).display!=='none';
 function stop(){effects.splice(0).forEach(node=>node.remove());clearTimeout(finishTimer);running.forEach(a=>a.cancel());running=[];active=false;delete pet.dataset.motion;}
 function animate(part,frames,duration){
  rigs.forEach(rig=>rig.querySelectorAll(`.pet-${part}`).forEach(node=>{
   running.push(node.animate(frames,{duration,easing:'ease-in-out',fill:'none'}));
  }));
 }
 function play(name,interactive=false){
  if(pet.hasAttribute('data-theme-sleeping')||!available()||reduced.matches||!Element.prototype.animate)return;
  if(active&&!interactive)return;
  stop();active=true;pet.dataset.motion=name;
  const duration=birdTricks[name]?3200:({flutter:2600,stretch:2400,groom:2300,feed:3000,pat:2800,happy:1800,wave:1700,nod:1500,blink:350,think:2200,talk:1700}[name]||1800);
  const kind=pet.dataset.kind in poses?pet.dataset.kind:'bird';
  let tracks=poses[kind][name]||[];
  if(kind==='bird'&&birdTricks[name]){
   playBirdTrick(name,duration);
   if(name==='balance')tracks=[pose('leg-right',['translateY(0)','translateY(-6px) rotate(-15deg)','translateY(-6px) rotate(-15deg)','translateY(0)'])];
   if(name==='kick')tracks=[pose('leg-right',turn(0,0,-35,10,0))];
   if(name==='ball')tracks=[pose('head',shift([0,0],[0,-3],[0,0],[0,-2],[0,0]))];
   if(name==='bubble')tracks=[pose('mouth',['scale(1)','scale(.8,1.15)','scale(1)'])];
   if(name==='peek')tracks=[pose('head',turn(0,-12,-12,0)),pose('eyes',['scaleY(1)','scaleY(.25)','scaleY(.25)','scaleY(1)'])];
  }
  if(name==='flutter'&&kind==='bird'){
   tracks=[pose('arm-left',turn(0,65,15,85,10,85,15,75,10,0)),pose('arm-right',turn(0,-65,-15,-85,-10,-85,-15,-75,-10,0)),pose('tail',turn(0,-10,8,-8,4,0)),pose('head',turn(0,-5,4,-3,0))];
   rigs.forEach(rig=>running.push(rig.animate([
    {transform:'translate(0,0) rotate(0)',offset:0},
    {transform:'translate(0,2px) rotate(-2deg)',offset:.15},
    {transform:'translate(4px,-12px) rotate(3deg)',offset:.35},
    {transform:'translate(-4px,-18px) rotate(-3deg)',offset:.55},
    {transform:'translate(3px,-9px) rotate(2deg)',offset:.72},
    {transform:'translate(0,2px) rotate(0)',offset:.9},
    {transform:'translate(0,0) rotate(0)',offset:1}
   ],{duration,easing:'ease-in-out',fill:'none'})));
  }
  if(name==='think')tracks=[pose('head',turn(0,-12,-12,0)),pose('ear-right',turn(0,20,0))];
  if(name==='talk')tracks=[pose('head',turn(0,5,-3,5,0)),pose('mouth',['scale(1)','scaleY(.5)','scaleY(1.4)','scaleY(.5)','scale(1)'])];
  tracks.forEach(t=>animate(t.part,t.frames.map(transform=>({transform})),duration));
  if(['blink','happy','stretch','groom','pat'].includes(name))animate('eyes', [{transform:'scaleY(1)'},{transform:'scaleY(.08)'},{transform:'scaleY(.08)'},{transform:'scaleY(1)'}],name==='blink'?350:duration);
  if(name==='feed')animate('food',[{opacity:0,transform:'translateY(5px)'},{opacity:1,transform:'translateY(0)'},{opacity:1,transform:'translateY(-5px)'},{opacity:0,transform:'translateY(-5px)'}],duration);
  if(name==='feed'||name==='pat'){
   pet.querySelectorAll('.feng-pet-launcher .feng-pet-art,.feng-pet-intro .feng-pet-art').forEach(art=>{
    for(let i=0;i<4;i++){
     const particle=document.createElement('span');particle.className='feng-pet-interaction-effect';particle.textContent=name==='pat'?'♥':(i===0?'🌾':'✦');particle.setAttribute('aria-hidden','true');art.append(particle);effects.push(particle);
     particle.style.left=(name==='feed'&&i===0?80:30+i*13)+'%';particle.style.top=(name==='feed'&&i===0?48:20)+'%';
     const food=name==='feed'&&i===0;
     running.push(particle.animate(food?[{opacity:0,transform:'translate(16px,10px) scale(.7)'},{opacity:1,transform:'translate(0,0) scale(1)',offset:.35},{opacity:1,transform:'translate(-4px,2px) scale(.8)',offset:.7},{opacity:0,transform:'translate(-7px,3px) scale(.2)'}]:[{opacity:0,transform:'translateY(0) scale(.6)'},{opacity:.9,transform:'translateY(-8px) scale(1)',offset:.3},{opacity:0,transform:'translate('+((i-1.5)*8)+'px,-32px) scale(.7)'}],{duration:food?2100:1800,delay:i*220,easing:'ease-in-out',fill:'both'}));
    }
   });
  }
  finishTimer=setTimeout(()=>{stop();},duration+40);
 }
 function scheduleBlink(){
  if(blinkTimer!==undefined)return;
  blinkTimer=setTimeout(()=>{
   blinkTimer=undefined;
   if(available()&&!active&&!reduced.matches&&!pet.hasAttribute('data-theme-sleeping')&&!pet.hasAttribute('data-sleeping'))play('blink');
   if(!document.hidden&&!reduced.matches&&!pet.hasAttribute('data-theme-sleeping'))scheduleBlink();
  },3000+Math.random()*3000);
 }
 function clearIdleTimers(){clearTimeout(timer);clearTimeout(blinkTimer);blinkTimer=undefined;}
 function schedule(){
  if(!document.hidden&&!reduced.matches&&!pet.hasAttribute('data-theme-sleeping'))scheduleBlink();
  clearTimeout(timer);
  timer=setTimeout(()=>{
   if(available()&&!pet.hasAttribute('data-theme-sleeping')&&!pet.hasAttribute('data-sleeping')){
    const choices=['nod','groom','stretch',...(pet.dataset.kind==='bird'?['flutter',...Object.keys(birdTricks)]:[])].filter(x=>x!==lastIdle);
    lastIdle=choices[Math.floor(Math.random()*choices.length)];play(lastIdle);
   }
   schedule();
  },6000+Math.random()*7000);
 }
 pet.addEventListener('feng:pet-flight',e=>{
  if(!e.detail){if(pet.dataset.motion==='travel')stop();return;}
  stop();if(reduced.matches||!Element.prototype.animate)return;
  active=true;pet.dataset.motion='travel';
  rigs.forEach(rig=>{
   for(const [part,sign] of [['arm-left',1],['arm-right',-1]])rig.querySelectorAll('.pet-'+part).forEach(wing=>running.push(wing.animate([{transform:'rotate(0deg)'},{transform:'rotate('+sign*60+'deg)'},{transform:'rotate(0deg)'}],{duration:220,iterations:Infinity,easing:'ease-in-out'})));
   rig.querySelectorAll('.pet-tail').forEach(tail=>running.push(tail.animate([{transform:'rotate(-5deg)'},{transform:'rotate(5deg)'},{transform:'rotate(-5deg)'}],{duration:400,iterations:Infinity,easing:'ease-in-out'})));
  });
 });
 pet.addEventListener('feng:pet-motion',e=>play(e.detail,true));
 pet.querySelector('[data-pet-toggle]').addEventListener('pointerenter',()=>{if(Date.now()-hoverAt>5000){hoverAt=Date.now();play('wave');}});
 pet.querySelectorAll('[data-pet-preview]').forEach(button=>button.addEventListener('click',()=>play(button.dataset.petPreview,true)));
 const observer=new IntersectionObserver(entries=>{inView=entries[0].isIntersecting;if(!available())stop();});observer.observe(pet);
 document.addEventListener('visibilitychange',()=>{if(document.hidden){stop();clearIdleTimers();}else schedule();});
 document.addEventListener('xf:before-unmount',()=>{stop();clearIdleTimers();});
 document.addEventListener('xf:mounted',schedule);
 reduced.addEventListener('change',()=>{stop();schedule();});
 window.addEventListener('pagehide',()=>{stop();clearIdleTimers();});
 window.addEventListener('pageshow',schedule);
 new MutationObserver(()=>{if(pet.hasAttribute('data-resting'))stop();}).observe(pet,{attributes:true,attributeFilter:['data-resting']});
 const sleepMarks=document.createElement('span');sleepMarks.className='feng-pet-sleep-marks';sleepMarks.setAttribute('aria-hidden','true');
 for(let i=0;i<3;i++){const z=document.createElement('span');z.textContent='Z';sleepMarks.append(z);}
 pet.querySelector('[data-pet-toggle]').append(sleepMarks);
 const darkPreference=matchMedia('(prefers-color-scheme:dark)');
 function syncSleep(){const theme=document.documentElement.getAttribute('data-xf-theme');const sleeping=theme==='dark'||(theme!=='light'&&darkPreference.matches);pet.toggleAttribute('data-theme-sleeping',sleeping);if(sleeping){stop();clearIdleTimers();pet.querySelector('[data-pet-bubble]').hidden=true;}else schedule();}
 new MutationObserver(syncSleep).observe(document.documentElement,{attributes:true,attributeFilter:['data-xf-theme']});
 darkPreference.addEventListener('change',syncSleep);syncSleep();
})();
