(() => {
 const endpoint=JSON.parse(document.getElementById('feng-config')?.textContent||'{}').endpoint;if(!endpoint)return;
 const uuid=()=>crypto.randomUUID?crypto.randomUUID():'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g,c=>{const n=crypto.getRandomValues(new Uint8Array(1))[0]&15;return (c==='x'?n:(n&3)|8).toString(16);});
 let visitor;try{visitor=localStorage.getItem('feng-visitor-id');if(!/^[a-f0-9-]{36}$/.test(visitor||'')){visitor=uuid();localStorage.setItem('feng-visitor-id',visitor);}}catch{visitor=uuid();}
 let sequence=Promise.resolve();
 function ping(event=''){
  sequence=sequence.catch(()=>{}).then(async()=>{
   if(!document.querySelector('[data-footer-stats]'))return;
   const controller=new AbortController(),timer=setTimeout(()=>controller.abort(),12000);
   try{const response=await fetch(endpoint,{method:'POST',credentials:'same-origin',cache:'no-store',signal:controller.signal,body:new URLSearchParams({action:'feng_visitor_stats',visitor,event})});const result=await response.json();if(!result.success)throw Error();
    for(const el of document.querySelectorAll('[data-footer-online]'))el.textContent=Number(result.data.online).toLocaleString();
    for(const el of document.querySelectorAll('[data-footer-views]'))el.textContent=(Number(result.data.views)>=10000?(Number(result.data.views)/10000).toLocaleString('zh-CN',{maximumFractionDigits:1})+'万':Number(result.data.views).toLocaleString());
    for(const el of document.querySelectorAll('[data-footer-location]')){
     const location=result.data.location,code=location?.code?.toUpperCase();
     el.textContent=location?.label||'暂未获取';
     if(location?.label&&/^[A-Z]{2}$/.test(code||'')){
      const flag=document.createElement('img');flag.src='https://flagcdn.io/flags/4x3/'+code.toLowerCase()+'.svg';
      flag.alt='';flag.width=18;flag.height=14;flag.className='feng-footer-country-flag';
      flag.addEventListener('error',()=>flag.remove(),{once:true});el.prepend(flag);
     }
    }
   }catch{for(const el of document.querySelectorAll('[data-footer-online]'))el.textContent='—';}finally{clearTimeout(timer);}
  });
 }
 document.addEventListener('xf:mounted',()=>ping(uuid()));
 document.addEventListener('visibilitychange',()=>{if(!document.hidden)ping();});
 setInterval(()=>{if(!document.hidden)ping();},60000);ping(uuid());
})();
