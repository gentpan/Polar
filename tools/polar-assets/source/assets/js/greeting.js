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
  const timer=setTimeout(()=>currentController.abort(),22000);
  let hostReady=false;
  const load=async kind=>{
   try{
    const url=new URL(root.dataset.endpoint,location.href);url.searchParams.set('action','feng_weather');url.searchParams.set('kind',kind);
    const response=await fetch(url,{signal,cache:'no-store',credentials:'same-origin'});const result=await response.json();const w=result.data;
    if(signal.aborted||!root.isConnected||!result.success||!w||!Number.isFinite(w.temperature))return;
    const label=describe(w.code),row=root.querySelector('[data-weather-'+kind+']');row.textContent=`${kind==='host'?'我这里':'你那里'} · ${w.city} · ${label} · ${w.temperature}°C`;row.hidden=false;
    if(kind==='host'||!hostReady){root.dataset.weather=label==='晴'?(w.day?'sun':'moon'):label==='雪'?'snow':['雨','雷雨'].includes(label)?'rain':'cloud';root.querySelector('summary').setAttribute('aria-label',`查看天气：${w.city}，${label}，${w.temperature}摄氏度`);}
    if(kind==='host')hostReady=true;root.hidden=false;
   }catch{/* Quietly retain the other location if this lookup is unavailable. */}
  };
  const enter=()=>{if(matchMedia('(hover:hover)').matches)root.open=true;};
  root.addEventListener('pointerenter',enter,{signal});root.addEventListener('pointerleave',()=>{if(!root.contains(document.activeElement))root.open=false;},{signal});
  document.addEventListener('pointerdown',e=>{if(!root.contains(e.target))root.open=false;},{signal});
  root.addEventListener('keydown',e=>{if(e.key==='Escape'){root.open=false;root.querySelector('summary').focus();}},{signal});
  await Promise.all([load('host'),...(root.dataset.visitor==='true'?[load('visitor')]:[])]);clearTimeout(timer);
 }
 document.addEventListener('xf:mounted',mountWeather);document.addEventListener('xf:before-unmount',()=>controller?.abort());mountWeather();
})();
