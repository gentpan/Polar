/* Article-only enhancements, mounted and cleaned with the site's PJAX lifecycle. */
(() => {
 let cleanup=()=>{};
 const mount=()=>{
  cleanup();
  const text=document.querySelector('[data-feng-reading]'),tools=document.querySelector('[data-reading-tools]');
  if(!text||!tools)return;
  const life=new AbortController(),options={signal:life.signal},reduced=matchMedia('(prefers-reduced-motion: reduce)');
  const toc=tools.querySelector('details'),list=toc.querySelector('ol'),nav=toc.querySelector('nav'),status=tools.querySelector('[data-reading-status]');
  const ids=new Set([...document.querySelectorAll('[id]')].map(e=>e.id));
  const headings=[...text.querySelectorAll('h2,h3,h4')].filter(e=>!e.closest('details,nav,aside'));
  const links=[];let frame=0,focusScroll=0,focus=false,timer,printDetails=[],previousActive=null;
  list.replaceChildren();
  headings.forEach((heading,index)=>{
   const label=heading.textContent.trim();
   if(!label)return;
   if(!heading.id){const base='section-'+(label.normalize('NFKC').toLowerCase().replace(/[^\p{L}\p{N}]+/gu,'-').replace(/^-|-$/g,'').slice(0,70)||String(index+1));let id=base,n=2;while(ids.has(id))id=base+'-'+n++;heading.id=id;ids.add(id);}
   const item=document.createElement('li'),link=document.createElement('a');link.href='#'+encodeURIComponent(heading.id);link.textContent=label;item.dataset.level=heading.tagName.slice(1);item.append(link);list.append(item);links.push({heading,link});
   if(!heading.querySelector('[data-reading-anchor]')){
    const button=document.createElement('button');button.type='button';button.className='feng-heading-anchor';button.dataset.readingAnchor='';button.textContent='#';button.setAttribute('aria-label','复制章节链接：'+label);button.title='复制章节链接';
    button.addEventListener('click',async()=>{const url=new URL(location.href);url.hash=heading.id;try{await navigator.clipboard.writeText(url.href);status.textContent='章节链接已复制。';button.textContent='✓';clearTimeout(timer);timer=setTimeout(()=>{button.textContent='#';},2000);}catch{status.textContent='无法自动复制，请复制地址栏中的章节链接。';location.hash=heading.id;}},options);heading.append(button);
   }
  });
  const toggle=document.querySelector('[data-reading-toggle]');
  const article=text.closest('article')||text;
  const setOpen=(open,returnFocus=false)=>{tools.hidden=!open;toc.open=true;toggle?.setAttribute('aria-expanded',String(open));toggle?.setAttribute('aria-label',open?'关闭文章目录':'打开文章目录');if(returnFocus)toggle?.focus();};
  if(toggle){toggle.hidden=!links.length;toggle.addEventListener('click',()=>setOpen(tools.hidden),options);}
  tools.querySelector('[data-reading-close]').addEventListener('click',()=>setOpen(false,true),options);
  nav.addEventListener('click',e=>{if(e.target.closest('a')&&tools.dataset.inset==='true')setOpen(false);},options);
  document.addEventListener('keydown',e=>{if(e.key==='Escape'&&!tools.hidden)setOpen(false,true);},options);
  const position=()=>{const box=article.getBoundingClientRect(),width=Math.min(190,innerWidth-32),outside=innerWidth-box.right>=width+24;tools.dataset.inset=String(!outside);tools.style.left=Math.max(16,Math.min(outside?box.right+12:box.right-width-12,innerWidth-width-16))+'px';tools.style.width=width+'px';};
  const update=()=>{
   frame=0;position();const offset=focus?32:(parseFloat(getComputedStyle(document.documentElement).scrollPaddingTop)||120)+20;
   let active=links[0];for(const entry of links){if(entry.heading.getBoundingClientRect().top<=offset+20)active=entry;}
   links.forEach(entry=>{if(entry===active)entry.link.setAttribute('aria-current','location');else entry.link.removeAttribute('aria-current');});
   if(toc.open&&active&&active!==previousActive){const a=active.link.getBoundingClientRect(),n=nav.getBoundingClientRect();if(a.top<n.top+42)nav.scrollTop+=a.top-n.top-42;else if(a.bottom>n.bottom-12)nav.scrollTop+=a.bottom-n.bottom+12;}
   previousActive=active;
  };
  const schedule=()=>{if(!frame)frame=requestAnimationFrame(update);};
  toc.addEventListener('toggle',()=>{previousActive=null;schedule();},options);
  window.addEventListener('scroll',schedule,{...options,passive:true});window.addEventListener('resize',schedule,options);
  const observer=typeof ResizeObserver!=='undefined'?new ResizeObserver(schedule):null;observer?.observe(text);
  tools.querySelector('[data-reading-focus]').addEventListener('click',e=>{
   const button=e.currentTarget;focus=!focus;if(focus)focusScroll=window.scrollY;
   document.documentElement.toggleAttribute('data-reading-focus',focus);button.setAttribute('aria-pressed',String(focus));button.setAttribute('aria-label',focus?'退出专注阅读':'开启专注阅读');
   if(focus)text.scrollIntoView({behavior:reduced.matches?'instant':'smooth',block:'start'});else window.scrollTo({top:focusScroll,behavior:'instant'});schedule();
  },options);
  tools.querySelector('[data-reading-print]').addEventListener('click',()=>{window.print();},options);
  window.addEventListener('beforeprint',()=>{printDetails=[...text.closest('article').querySelectorAll('details:not([open])')];printDetails.forEach(e=>{e.open=true;});},options);
  window.addEventListener('afterprint',()=>{printDetails.forEach(e=>{e.open=false;});printDetails=[];},options);
  // Give wide tables a keyboard-accessible scroll area without changing their cells.
  const wrapped=[];
  text.querySelectorAll('table').forEach(table=>{
   let host=table.closest('.wp-block-table');
   if(!host){host=document.createElement('div');host.className='feng-table-scroll';table.before(host);host.append(table);wrapped.push(host);}
   host.tabIndex=0;host.setAttribute('role','region');host.setAttribute('aria-label',table.caption?.textContent||'表格，可左右滚动');
  });
  text.querySelectorAll('pre > code').forEach(code=>{
   const language=[...code.classList].find(c=>c.startsWith('language-'))?.slice(9);
   if(language)code.parentElement.dataset.language=language;
  });
  setOpen(links.length>0&&innerWidth>=1400);update();
  // A pasted section URL can target headings that did not have editor anchors.
  let hash='';try{hash=decodeURIComponent(location.hash.slice(1));}catch{}
  const target=links.find(e=>e.heading.id===hash);if(target)requestAnimationFrame(()=>{if(!life.signal.aborted)target.heading.scrollIntoView({block:'start'});});
  cleanup=()=>{life.abort();observer?.disconnect();cancelAnimationFrame(frame);clearTimeout(timer);document.documentElement.removeAttribute('data-reading-focus');tools.hidden=true;text.querySelectorAll('[data-reading-anchor]').forEach(e=>e.remove());wrapped.forEach(e=>e.replaceWith(...e.childNodes));};
 };
 document.addEventListener('xf:before-unmount',()=>cleanup());document.addEventListener('xf:mounted',mount);
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',mount,{once:true});else mount();
})();
