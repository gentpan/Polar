/* Decorative odometer overlay: the real statistic stays in the accessible DOM. */
(()=>{
 const selector='[data-footer-views],[data-footer-online],.feng-weekly-badges b,.feng-dash-metrics strong,.feng-dash-categories a>b,[data-talk-dock-count],[data-stat-roll]';
 const states=new Map(),reduced=matchMedia('(prefers-reduced-motion: reduce)');
 let scheduled=false;
 const value=el=>[...el.childNodes].filter(n=>!(n.nodeType===1&&n.classList.contains('feng-number-overlay'))).map(n=>n.textContent).join('');
 function finish(el,s){s.animations.forEach(a=>a.cancel());s.animations=[];s.overlay?.remove();s.overlay=null;el.classList.remove('is-number-rolling');}
 function play(el,s){
  const next=value(el);if(next===s.shown)return;
  const old=s.shown;s.shown=next;finish(el,s);
  if(reduced.matches||!/[0-9]/.test(next)||!el.getClientRects().length)return;
  const color=getComputedStyle(el).color,layer=document.createElement('span');layer.className='feng-number-overlay';layer.setAttribute('aria-hidden','true');layer.style.color=color;
  const previous=(old||'').replace(/[^0-9]/g,'');let digitIndex=0;const digits=next.replace(/[^0-9]/g,'').length;
  for(const char of next){
   if(!/[0-9]/.test(char)){layer.append(document.createTextNode(char));continue;}
   const slot=document.createElement('span');slot.className='feng-number-slot';
   const strip=document.createElement('span');strip.className='feng-number-strip';
   const before=previous[previous.length-digits+digitIndex],start=before===undefined?0:Number(before),end=Number(char);
   const steps=old===null?10+end:(end-start+10)%10;
   for(let i=0;i<=steps;i++){const row=document.createElement('span');row.textContent=String((start+i)%10);strip.append(row);}
   slot.append(strip);layer.append(slot);
   if(steps){const animation=strip.animate([{transform:'translateY(0)'},{transform:`translateY(-${steps*1.2}em)`}],{duration:old===null?850:550,delay:Math.min(digitIndex*35,140),easing:'cubic-bezier(.16,1,.3,1)',fill:'forwards'});s.animations.push(animation);}
   digitIndex++;
  }
  s.overlay=layer;el.append(layer);el.classList.add('is-number-rolling');
  const active=s.animations.slice();Promise.all(active.map(a=>a.finished.catch(()=>{}))).then(()=>{if(s.overlay===layer)finish(el,s);});
 }
 const io=new IntersectionObserver(entries=>{for(const e of entries){const s=states.get(e.target);if(!s)continue;s.visible=e.isIntersecting;if(s.visible)play(e.target,s);}},{threshold:.1});
 function scan(){scheduled=false;
  for(const [el,s] of states)if(!el.isConnected){finish(el,s);io.unobserve(el);states.delete(el);}
  document.querySelectorAll(selector).forEach(el=>{
   if(!states.has(el)){const s={shown:null,visible:false,animations:[],overlay:null};states.set(el,s);el.classList.add('feng-number');io.observe(el);}
   const s=states.get(el);if(s.visible)play(el,s);
  });
 }
 function schedule(){if(!scheduled){scheduled=true;requestAnimationFrame(scan);}}
 const observer=new MutationObserver(records=>{if(records.some(r=>!r.target.closest?.('.feng-number-overlay')))schedule();});
 observer.observe(document.documentElement,{childList:true,subtree:true,characterData:true});
 reduced.addEventListener('change',()=>{if(reduced.matches)states.forEach((s,el)=>finish(el,s));});
 document.addEventListener('xf:mounted',schedule);schedule();
})();
