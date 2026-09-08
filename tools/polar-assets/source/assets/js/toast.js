(() => {
 const toast=document.createElement('div');toast.className='feng-toast';toast.hidden=true;toast.setAttribute('role','status');toast.setAttribute('aria-live','polite');
 const icon=document.createElement('i');icon.setAttribute('aria-hidden','true');const message=document.createElement('span');toast.append(icon,message);document.body.append(toast);let timer;
 async function copy(text){
  try{if(navigator.clipboard){await navigator.clipboard.writeText(text);return;}}catch{}
  const previous=document.activeElement,field=document.createElement('textarea');field.value=text;field.setAttribute('readonly','');field.style.cssText='position:fixed;left:-9999px;top:0';document.body.append(field);field.select();let ok=false;
  try{ok=document.execCommand('copy');}finally{field.remove();previous?.focus({preventScroll:true});}if(!ok)throw Error('copy');
 }
 function show(text,type='success'){if(!text)return;clearTimeout(timer);toast.dataset.type=type;icon.className='fa-classic fa-regular fa-'+(type==='error'?'circle-exclamation':'circle-check');message.textContent=String(text);toast.hidden=false;timer=setTimeout(()=>toast.hidden=true,3200);}
 window.fengToast=show;
 document.addEventListener('feng:toast',e=>show(e.detail?.message,e.detail?.type));
 document.addEventListener('click',async e=>{
  const link=e.target.closest('a[data-feng-copy-rss],.xf-footer .feng-social a:is([aria-label="RSS 订阅"],.is-copied)');if(!link||e.ctrlKey||e.metaKey||e.shiftKey||e.altKey)return;
  e.preventDefault();e.stopPropagation();if(link.dataset.copying||link.classList.contains('is-copied'))return;link.dataset.copying='1';
  try{await copy(link.href);link.classList.add('is-copied');link.setAttribute('aria-label','RSS 链接复制成功');link.title='复制成功';const glyph=link.querySelector('i');if(glyph){glyph.classList.remove('fa-rss');glyph.classList.add('fa-check');}show('复制成功');
   setTimeout(()=>{link.classList.remove('is-copied');link.setAttribute('aria-label','RSS 订阅');link.title='复制 RSS 订阅链接';if(glyph){glyph.classList.remove('fa-check');glyph.classList.add('fa-rss');}},2500);
  }catch{show('复制失败，请右键复制链接地址','error');}finally{delete link.dataset.copying;}
 },true);
})();
