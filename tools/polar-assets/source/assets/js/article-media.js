(() => {
 let cleanup=()=>{};
 function mount(){
  cleanup();
  // Group only adjacent image-only blocks. Captions stay attached to their image.
  document.querySelectorAll('.xf-prose').forEach(prose=>{
   // Classic editor may put several images inside the same paragraph.
   prose.querySelectorAll('p').forEach(p=>{
    if(p.closest('.wp-block-gallery,.gallery,.feng-image-group')||p.querySelectorAll('img[data-article-lazy]').length<2)return;
    const copy=p.cloneNode(true);copy.querySelectorAll('img,picture,noscript,br').forEach(n=>n.remove());if(copy.textContent.trim())return;
    const children=[...p.children];if(children.some(n=>!n.matches('img,picture,a,noscript,br')||(n.matches('a')&&n.querySelectorAll('img').length!==1)))return;
    children.filter(n=>n.matches('img,picture,a')).forEach(n=>{const figure=document.createElement('figure');const fallback=n.nextElementSibling?.matches('noscript')?n.nextElementSibling:null;p.before(figure);figure.append(n);if(fallback)figure.append(fallback);});
    p.remove();
   });
   const isImageBlock=node=>{
    if(!node.matches('p,figure,div.wp-caption')||node.closest('.wp-block-gallery,.gallery,.feng-image-group'))return false;
    if(node.querySelectorAll('img[data-article-lazy]').length!==1)return false;
    const copy=node.cloneNode(true);copy.querySelectorAll('img,picture,noscript,figcaption,.wp-caption-text').forEach(n=>n.remove());
    return !copy.textContent.trim()&&!copy.querySelector('video,iframe,pre');
   };
   [prose,...prose.querySelectorAll('.wp-block-group__inner-container')].forEach(parent=>{
    let run=[];
    const flush=()=>{
     if(run.length>1){const group=document.createElement('div');group.className='feng-image-group';group.dataset.count=String(run.length);run[0].before(group);run.forEach(n=>group.append(n));}
     run=[];
    };
    [...parent.childNodes].forEach(node=>{
     if(node.nodeType===8||(node.nodeType===3&&!node.textContent.trim()))return;
     if(node.nodeType===1&&isImageBlock(node))run.push(node);else flush();
    });flush();
   });
   prose.querySelectorAll('pre > code:not([data-highlighted])').forEach(code=>{
    if(code.textContent.length<100000)window.hljs?.highlightElement(code);
   });
  });
  const life=new AbortController();const observer=new IntersectionObserver(entries=>entries.forEach(({target,isIntersecting})=>{if(isIntersecting){observer.unobserve(target);load(target);}}),{rootMargin:'100px 0px'});
  function load(img){
   img.closest('picture')?.querySelectorAll('source[data-article-srcset]').forEach(s=>s.srcset=s.dataset.articleSrcset);
   const host=img.closest('.feng-article-loading');
   const finish=()=>{host.classList.remove('is-loading');host.classList.add(img.naturalWidth?'is-loaded':'is-error');};
   img.addEventListener('load',async()=>{try{await img.decode();}catch{}if(img.isConnected)finish();},{once:true,signal:life.signal});img.addEventListener('error',finish,{once:true,signal:life.signal});
   for(const key of ['sizes','srcset','src'])if(img.dataset['article'+key[0].toUpperCase()+key.slice(1)])img.setAttribute(key,img.dataset['article'+key[0].toUpperCase()+key.slice(1)]);
   if(img.complete&&img.naturalWidth)finish();
  }
  document.querySelectorAll('img[data-article-lazy]').forEach(img=>{
   let host=img.closest('.feng-article-loading');if(!host){host=document.createElement('span');host.className='feng-article-loading is-loading';const media=img.closest('picture')||img;media.before(host);host.append(media);const w=Number(img.getAttribute('width')),h=Number(img.getAttribute('height'));if(w>0&&h>0){host.style.aspectRatio=`${w} / ${h}`;host.style.maxWidth=w+'px';}else host.style.minHeight='220px';}
   observer.observe(img);
  });
  document.querySelectorAll('.xf-prose a[href]').forEach(link=>{
   if(link.classList.contains('feng-external-link')||link.closest('pre,code')||link.querySelector('img,picture,svg')||!link.textContent.trim())return;
   let url;try{url=new URL(link.href,location.href);}catch{return;}
   if(!['https:','http:'].includes(url.protocol)||url.hostname===location.hostname)return;
   link.classList.add('feng-external-link');
   const icon=document.createElement('img');icon.className='feng-link-favicon';icon.src='https://favicon.la/'+encodeURIComponent(url.hostname);icon.alt='';icon.width=14;icon.height=14;icon.loading='lazy';icon.decoding='async';icon.setAttribute('aria-hidden','true');icon.addEventListener('error',()=>icon.remove(),{once:true});link.prepend(icon);
   const arrow=document.createElementNS('http://www.w3.org/2000/svg','svg');arrow.classList.add('feng-external-arrow');arrow.setAttribute('viewBox','0 0 1024 1024');arrow.setAttribute('aria-hidden','true');arrow.setAttribute('focusable','false');
   for(const d of ['M426.6496 128v85.3504H213.3504v597.2992h597.2992v-213.2992H896v256c0 23.552-19.0976 42.6496-42.6496 42.6496H170.6496a42.6496 42.6496 0 0 1-42.6496-42.6496V170.6496c0-23.552 19.0976-42.6496 42.6496-42.6496h256z m323.6864 85.3504h-195.6864V128H896v341.3504h-85.3504V273.664L512 572.3136 451.6864 512l298.6496-298.6496z']){const path=document.createElementNS('http://www.w3.org/2000/svg','path');path.setAttribute('d',d);path.setAttribute('fill','currentColor');arrow.append(path);}const animated=document.createElement('span');animated.className='feng-external-arrow';animated.dataset.lordiconContent='articlelink';animated.setAttribute('aria-hidden','true');arrow.classList.remove('feng-external-arrow');animated.append(arrow);link.append(animated);
  });
  window.LiteZoom?.bind('.xf-prose img:not(.feng-link-favicon), img[data-xf-zoom]',{mode:'full',group:img=>img.closest('.xf-prose')||img.parentElement,caption:img=>img.closest('figure')?.querySelector('figcaption')?.textContent||img.alt||''});
  window.LiteZoom?.refresh();
  cleanup=()=>{life.abort();observer.disconnect();window.LiteZoom?.close();};
 }
 document.addEventListener('xf:mounted',mount);document.addEventListener('xf:before-unmount',()=>cleanup());mount();
})();
