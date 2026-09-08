(() => {
 let cleanup = () => {};
 const mount = () => {
  cleanup(); const root = document.querySelector('[data-feng-activity]'); if(!root)return;
  const life = new AbortController(), controls=root.querySelector('.feng-activity-controls'), list=root.querySelector('[data-activity-list]'), status=root.querySelector('[data-activity-status]');
  const previous=controls.querySelector('[data-activity-step="-1"]'), next=controls.querySelector('[data-activity-step="1"]');
  let page=1, pages=Number(root.dataset.pages), busy=false, request, animation;
  controls.hidden=pages<=1;
  const sync=()=>{previous.disabled=busy||page<=1;next.disabled=busy||page>=pages;};
  controls.addEventListener('click',async event=>{
   const button=event.target.closest('[data-activity-step]');if(!button||button.disabled||busy)return;
   const target=page+Number(button.dataset.activityStep);busy=true;sync();list.setAttribute('aria-busy','true');status.textContent='正在翻阅动态…';
   request=new AbortController();const timer=setTimeout(()=>request.abort(),12000);
   try {
    const url=new URL(root.dataset.endpoint,location.href);url.searchParams.set('action','feng_activity');url.searchParams.set('page',String(target));
    const response=await fetch(url,{signal:request.signal,credentials:'same-origin',cache:'no-store'});const result=await response.json();
    if(!response.ok||!result.success||typeof result.data.html!=='string')throw new Error(result.data?.message||'暂时无法加载，请再试一次。');
    if(life.signal.aborted)return;
    const height=list.getBoundingClientRect().height;list.style.minHeight=height+'px';list.innerHTML=result.data.html;
    page=result.data.page;pages=result.data.pages;
    if(!matchMedia('(prefers-reduced-motion: reduce)').matches){animation=list.animate([{opacity:.15,transform:'translateY(8px)'},{opacity:1,transform:'none'}],{duration:700,easing:'cubic-bezier(.4,0,.25,1)'});await animation.finished;}
    status.classList.add('screen-reader-text');status.textContent=`第 ${page} 页动态，共 ${pages} 页。`;
   } catch(error) {if(!life.signal.aborted){status.classList.remove('screen-reader-text');status.textContent=error.name==='AbortError'?'加载超时，请再试一次。':error.message;}}
   finally {clearTimeout(timer);if(!life.signal.aborted){list.style.minHeight='';list.removeAttribute('aria-busy');busy=false;sync();}}
  },{signal:life.signal});
  cleanup=()=>{life.abort();request?.abort();animation?.cancel();};
 };
 document.addEventListener('xf:mounted',mount);document.addEventListener('xf:before-unmount',()=>cleanup());
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',mount,{once:true});else mount();
})();
