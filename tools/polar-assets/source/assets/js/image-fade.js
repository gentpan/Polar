(() => {
 let cleanup=()=>{};
 function mount(){cleanup();if(matchMedia('(prefers-reduced-motion: reduce)').matches)return;const life=new AbortController();const observed=new WeakSet();
 const observer=new IntersectionObserver(entries=>entries.forEach(({target,isIntersecting})=>{if(!isIntersecting)return;observer.unobserve(target);const show=()=>{target.classList.add('is-visible');};if(target.complete)requestAnimationFrame(show);else{target.addEventListener('load',show,{once:true,signal:life.signal});target.addEventListener('error',show,{once:true,signal:life.signal});}}),{rootMargin:'40px'});
 const scan=()=>document.querySelectorAll('.feng-related-image img,.feng-profile__avatar img').forEach(img=>{if(observed.has(img))return;observed.add(img);img.classList.add('feng-image-fade');observer.observe(img);});scan();const changes=new MutationObserver(scan);const main=document.querySelector('main');if(main)changes.observe(main,{childList:true,subtree:true});
 cleanup=()=>{life.abort();observer.disconnect();changes.disconnect();document.querySelectorAll('.feng-image-fade').forEach(n=>n.classList.remove('feng-image-fade','is-visible'));};
 }
 document.addEventListener('xf:mounted',mount);document.addEventListener('xf:before-unmount',()=>cleanup());if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',mount,{once:true});else mount();
})();
