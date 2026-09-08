/* Personal greetings are resolved after page load, outside shared HTML caches. */
(() => {
 const config=JSON.parse(document.getElementById('feng-config')?.textContent||'{}'),settings=config.greeting;
 if(!settings?.enabled)return;
 let generation=0,aborter=null,name='',weatherLine='';
 const period=()=>{const h=new Date().getHours();return h<6?'夜深了':h<11?'早上好':h<14?'中午好':h<18?'下午好':'晚上好';};
 function render(){const el=document.querySelector('[data-feng-greeting]');if(el)el.textContent=name?`${name}，${period()}，欢迎回来。`:`${period()}，欢迎来到${settings.site}的生活切片。`;}
 function notifyPet(){if(settings.pet)document.dispatchEvent(new CustomEvent('feng:greeting',{detail:{message:weatherLine||`${name?name+'，':''}${period()}！我是啾啾，很高兴陪你逛逛。`}}));}
 async function json(url,options={}){
  const controller=new AbortController(),outer=aborter?.signal,onAbort=()=>controller.abort();
  outer?.addEventListener('abort',onAbort,{once:true});if(outer?.aborted)controller.abort();
  const timer=setTimeout(()=>controller.abort(),8000);
  try{const response=await fetch(url,{...options,signal:controller.signal});if(!response.ok)throw Error('request');return await response.json();}
  finally{clearTimeout(timer);outer?.removeEventListener('abort',onAbort);}
 }
 async function mount(){
  const el=document.querySelector('[data-feng-greeting]');if(!el)return;
  const mine=++generation;aborter?.abort();aborter=new AbortController();name='';weatherLine='';render();
  try{const data=await json(config.endpoint,{method:'POST',credentials:'same-origin',cache:'no-store',body:new URLSearchParams({action:'feng_greeting_identity'})});if(mine!==generation)return;name=typeof data.data?.name==='string'?data.data.name:'';render();}catch{}
  if(mine!==generation)return;notifyPet();

 }
 document.addEventListener('xf:before-unmount',()=>{++generation;aborter?.abort();});document.addEventListener('xf:mounted',mount);
 document.addEventListener('visibilitychange',()=>{if(!document.hidden)render();});
 setInterval(render,60000);mount();
})();

/* Two-place weather is personalized outside the shared page cache. */
(() => {
 let controller;
 const describe=code=>code===0?'晴':code<=3?'多云':[45,48].includes(code)?'雾':code>=95?'雷雨':[71,73,75,77,85,86].includes(code)?'雪':code>=51?'雨':'多云';
 async function mountWeather(){
  controller?.abort();controller=new AbortController();const currentController=controller,signal=currentController.signal;
  const root=document.querySelector('[data-hero-weather]');if(!root)return;
  const dateRow=root.querySelector('[data-weather-date]');
  const updateDate=()=>{
   if(!dateRow)return;
   const now=new Date();
   const solar=new Intl.DateTimeFormat('zh-CN',{year:'numeric',month:'long',day:'numeric'}).format(now);
   const weekday=new Intl.DateTimeFormat('zh-CN',{weekday:'long'}).format(now);
   let lunar='';
   try{const parts=new Intl.DateTimeFormat('zh-CN-u-ca-chinese',{month:'long',day:'numeric'}).formatToParts(now);const month=parts.find(p=>p.type==='month')?.value||'';const day=Number(parts.find(p=>p.type==='day')?.value);const days=['初一','初二','初三','初四','初五','初六','初七','初八','初九','初十','十一','十二','十三','十四','十五','十六','十七','十八','十九','二十','廿一','廿二','廿三','廿四','廿五','廿六','廿七','廿八','廿九','三十'];if(month&&days[day-1])lunar=` · 农历${month}${days[day-1]}`;}catch{}
   dateRow.textContent=`${solar}${lunar} · ${weekday}`;
  };
  updateDate();root.addEventListener('pointerenter',updateDate,{signal});root.addEventListener('toggle',updateDate,{signal});
  const timer=setTimeout(()=>currentController.abort(),22000);
  let visitorReady=false;
  const load=async kind=>{
   try{
    const url=new URL(root.dataset.endpoint,location.href);url.searchParams.set('action','feng_weather');url.searchParams.set('kind',kind);
    const response=await fetch(url,{signal,cache:'no-store',credentials:'same-origin'});const result=await response.json();const w=result.data;
    if(signal.aborted||!root.isConnected||!result.success||!w||!Number.isFinite(w.temperature))return;
    const label=describe(w.code),row=root.querySelector('[data-weather-'+kind+']');row.textContent=`${w.city} · ${label} · ${w.temperature}°C`;
    if(kind==='visitor'||!visitorReady){root.querySelectorAll('[data-weather-host],[data-weather-visitor]').forEach(item=>{item.hidden=item!==row;});root.dataset.weather=label==='晴'?(w.day?'sun':'moon'):label==='雪'?'snow':['雨','雷雨'].includes(label)?'rain':'cloud';root.querySelector('summary').setAttribute('aria-label',`查看天气：${w.city}，${label}，${w.temperature}摄氏度`);}
    if(kind==='visitor'){visitorReady=true;document.dispatchEvent(new CustomEvent('polar:visitor-weather',{detail:w}));}root.hidden=false;
   }catch{/* Quietly retain the other location if this lookup is unavailable. */}
  };
  const enter=()=>{if(matchMedia('(hover:hover)').matches)root.open=true;};
  root.addEventListener('pointerenter',enter,{signal});root.addEventListener('pointerleave',()=>{if(!root.contains(document.activeElement))root.open=false;},{signal});
  document.addEventListener('pointerdown',e=>{if(!root.contains(e.target))root.open=false;},{signal});
  root.addEventListener('keydown',e=>{if(e.key==='Escape'){root.open=false;root.querySelector('summary').focus();}},{signal});
  await Promise.all([load('host'),...(root.dataset.visitor==='true'?[load('visitor')]:[])]);clearTimeout(timer);
 }
 document.addEventListener('xf:mounted',mountWeather);document.addEventListener('xf:before-unmount',()=>controller?.abort());
 setInterval(()=>{if(!document.hidden&&document.querySelector('[data-hero-weather]'))mountWeather();},1200000);mountWeather();
})();


/* Pure scene resolver: real solar times when available, local clock otherwise. */
// SunCalc 1.9 uses radians. Panorama: N at edges, E 25%, S 50%, W 75%.
function polarMoonState(now, weather) {
 const lat=weather?.latitude,lon=weather?.longitude;
 if(!Number.isFinite(lat)||!Number.isFinite(lon)||Math.abs(lat)>90||Math.abs(lon)>180)return null;
 const p=SunCalc.getMoonPosition(now,lat,lon),light=SunCalc.getMoonIllumination(now);
 const azimuth=(p.azimuth*180/Math.PI+180+360)%360,altitude=p.altitude*180/Math.PI;
 return {visible:altitude>0,azimuth,altitude,x:azimuth/360*100,y:32-Math.max(0,altitude)/90*29,
 fraction:light.fraction,phase:light.phase,rotation:light.angle-p.parallacticAngle};
}
function polarPaintMoon(canvas, moon) {
 const size=96,r=46,ctx=canvas.getContext('2d'),data=ctx.createImageData(size,size);
 // Bright-limb angle is anticlockwise from zenith in the observer's sky.
 const z=2*moon.fraction-1,t=Math.sqrt(Math.max(0,1-z*z));
 const lx=-Math.sin(moon.rotation)*t,ly=-Math.cos(moon.rotation)*t;
 for(let y=0;y<size;y++)for(let x=0;x<size;x++){
  const nx=(x+.5-size/2)/r,ny=(y+.5-size/2)/r,d=nx*nx+ny*ny;if(d>1)continue;
  const nz=Math.sqrt(1-d),lit=nx*lx+ny*ly+nz*z>0,i=(y*size+x)*4;
  data.data[i]=lit?237:28;data.data[i+1]=lit?244:36;data.data[i+2]=lit?255:49;
  data.data[i+3]=Math.round(Math.min(1,(1-Math.sqrt(d))*r)*255);
 }
 ctx.putImageData(data,0,0);
}

function polarHeroSceneState(now, weather, preview='auto') {
 const seconds=now.getTime()/1000;
 let hour=now.getHours()+now.getMinutes()/60;
 if(weather&&Number.isFinite(weather.utc_offset))hour=((seconds+weather.utc_offset)%86400+86400)%86400/3600;
 let sunrise=6,sunset=18,solar=false;
 const rises=weather?.sunrise||[],sets=weather?.sunset||[];
 const offset=weather?.utc_offset||0;
 const today=Math.floor((seconds+offset)/86400);
 for(let i=0;i<rises.length;i++){
  if(rises[i]>0&&sets[i]>rises[i]&&Math.floor((rises[i]+offset)/86400)===today){
   sunrise=((rises[i]+offset)%86400)/3600;sunset=((sets[i]+offset)%86400)/3600;solar=true;break;
  }
 }
 let night=hour<sunrise||hour>=sunset;
 if(weather&&!solar&&rises.length&&typeof weather.day==='boolean')night=!weather.day;
 let phase=night?'night':'day';
 if(Math.abs(hour-sunrise)<.65)phase='dawn';
 else if(Math.abs(hour-sunset)<.75)phase='sunset';
 const code=Number(weather?.code??0);
 let condition=[71,73,75,77,85,86].includes(code)?'snow':code>=51?'rain':code>=2?'cloud':'clear';
 let scene=condition==='clear'?phase:condition;
 if(['dawn','day','sunset','night','cloud','rain','snow'].includes(preview)){
  scene=preview;condition=['cloud','rain','snow'].includes(preview)?preview:'clear';phase=condition==='clear'?preview:'day';night=preview==='night';
  hour=preview==='dawn'?sunrise:preview==='sunset'?sunset:preview==='night'?0:12;
 }
 const progress=Math.max(0,Math.min(1,(hour-sunrise)/Math.max(1,sunset-sunrise)));
 return {scene,phase,condition,night,solar,x:night?74:12+progress*72,y:night?10:25-Math.sin(progress*Math.PI)*19};
}
window.polarVisitorNight=(weather)=>polarHeroSceneState(new Date(),weather).night;

(() => {
 let cleanup;
 function mountScene(){
  cleanup?.();const hero=document.querySelector('[data-smart-scene]');if(!hero)return;
  const base=hero.querySelector('.polar-hero-landscape');if(!base)return;
  let images;try{images=JSON.parse(hero.dataset.sceneImages);}catch{return;}
  const controller=new AbortController(),signal=controller.signal;
  const next=base.cloneNode();next.removeAttribute('src');next.classList.add('polar-scene-image');base.classList.add('polar-scene-image');base.after(next);
  base.style.opacity='1';next.style.opacity='0';
  const atmosphere=document.createElement('div');atmosphere.className='polar-scene-atmosphere';atmosphere.setAttribute('aria-hidden','true');
  const orb=document.createElement('span');orb.className='polar-scene-orb';atmosphere.append(orb);
  const moon=document.createElement('canvas');moon.width=moon.height=96;moon.className='polar-scene-moon';moon.hidden=true;atmosphere.append(moon);
  const clouds=document.createElement('span');clouds.className='polar-scene-clouds';atmosphere.append(clouds);
  const particles=document.createElement('div');particles.className='polar-scene-particles';
  for(let i=0;i<36;i++){const drop=document.createElement('i');drop.style.setProperty('--x',`${(i*37)%100}%`);drop.style.setProperty('--delay',`${-i*.37}s`);drop.style.setProperty('--speed',`${.65+(i%7)*.12}s`);particles.append(drop);}atmosphere.append(particles);hero.prepend(atmosphere);
  let weather=null,current='',generation=0,front=base,visible=true;
  const preview=hero.dataset.scenePreview||'auto';
  function paintState(state){hero.dataset.scene=state.scene;hero.dataset.scenePhase=state.phase;hero.dataset.sceneNight=String(state.night);hero.dataset.sceneCondition=state.condition;orb.style.left=state.x+'%';orb.style.top=state.y+'%';orb.hidden=state.night;
   const lunar=polarMoonState(new Date(),weather);moon.hidden=!lunar?.visible;
   if(lunar){hero.dataset.moonAzimuth=lunar.azimuth.toFixed(1);hero.dataset.moonAltitude=lunar.altitude.toFixed(1);
    if(lunar.visible){moon.style.left=lunar.x+'%';moon.style.top=lunar.y+'%';polarPaintMoon(moon,lunar);}
   }
  }
  async function render(){
   const state=polarHeroSceneState(new Date(),weather,preview);
   if(state.scene===current){paintState(state);return;}
   const url=images[state.scene];if(!url)return;
   const version=++generation;
   try{
    const image=new Image();image.src=url;await image.decode();
    if(signal.aborted||version!==generation||!hero.isConnected)return;
    const back=front===base?next:base;back.src=url;back.style.opacity='1';front.style.opacity='0';front=back;current=state.scene;paintState(state);
   }catch{/* Keep the last successfully decoded landscape. */}
  }
  document.addEventListener('polar:visitor-weather',event=>{weather=event.detail;render();},{signal});
  document.addEventListener('visibilitychange',()=>{hero.classList.toggle('polar-scene-paused',document.hidden||!visible);if(!document.hidden)render();},{signal});
  const observer=new IntersectionObserver(entries=>{visible=entries[0].isIntersecting;hero.classList.toggle('polar-scene-paused',!visible||document.hidden);});observer.observe(hero);
  const timer=setInterval(()=>{if(!document.hidden&&visible)render();},60000);render();
  cleanup=()=>{controller.abort();generation++;clearInterval(timer);observer.disconnect();next.remove();atmosphere.remove();base.style.opacity='';};
 }
 document.addEventListener('xf:mounted',mountScene);document.addEventListener('xf:before-unmount',()=>cleanup?.());mountScene();
})();
