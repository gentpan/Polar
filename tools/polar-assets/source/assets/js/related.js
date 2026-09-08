(() => {
 let cleanup=()=>{};
 function mount(){
  cleanup();const root=document.querySelector('[data-related]');if(!root)return;
  const life=new AbortController(),panel=root.querySelector('[data-related-panel]'),items=[...panel.querySelectorAll('.feng-related-item')],refresh=root.querySelector('[data-related-refresh]'),status=root.querySelector('[data-related-status]');let offset=0,timer,finishTimer;
  refresh.disabled=items.length<=3;refresh.title=refresh.disabled?'已显示全部推荐文章':'换一组文章';
  refresh.addEventListener('click',()=>{
   if(refresh.disabled)return;items.forEach(n=>n.classList.remove('is-entering'));refresh.disabled=true;refresh.classList.add('is-refreshing');panel.classList.add('is-refreshing');root.setAttribute('aria-busy','true');
   timer=setTimeout(()=>{
    offset=(offset+3)%items.length;const next=Array.from({length:Math.min(3,items.length)},(_,i)=>items[(offset+i)%items.length]);items.forEach(n=>n.hidden=!next.includes(n));next.forEach(n=>panel.append(n));
    panel.classList.remove('is-refreshing');
    next.forEach((n,i)=>{n.style.setProperty('--related-delay',i*80+'ms');n.classList.add('is-entering');});
    finishTimer=setTimeout(()=>{next.forEach(n=>n.classList.remove('is-entering'));refresh.classList.remove('is-refreshing');root.removeAttribute('aria-busy');refresh.disabled=false;status.textContent='已更新推荐文章。';},matchMedia('(prefers-reduced-motion: reduce)').matches?0:600);
   },matchMedia('(prefers-reduced-motion: reduce)').matches?0:200);
  },{signal:life.signal});
  cleanup=()=>{clearTimeout(timer);clearTimeout(finishTimer);life.abort();};
 }
 document.addEventListener('xf:mounted',mount);document.addEventListener('xf:before-unmount',()=>cleanup());if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',mount,{once:true});else mount();
})();
