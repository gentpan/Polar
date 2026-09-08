(() => {
 const menu=document.querySelector('[data-page-menu]');if(!menu)return;
 let focusBefore,linkUrl='';
 const targetFor=key=>key==='previous'?document.querySelector('.feng-entry-round[rel="prev"]'):key==='next'?document.querySelector('.feng-entry-round[rel="next"]'):null;
 function close(restore=false){menu.hidden=true;if(menu.parentElement!==document.body)document.body.append(menu);if(restore&&focusBefore?.isConnected)focusBefore.focus({preventScroll:true});}
 document.addEventListener('contextmenu',e=>{
  close();
  if(e.defaultPrevented||e.shiftKey)return;
  // Modal dialogs occupy the browser top layer; render the menu in that layer.
  const container=e.target.closest('dialog[open]')||document.body;
  if(menu.parentElement!==container)container.append(menu);
  e.preventDefault();focusBefore=document.activeElement;
  const anchor=e.target.closest('a[href]');
  linkUrl='';
  if(anchor){try{const url=new URL(anchor.href,location.href);if(['http:','https:','mailto:','tel:'].includes(url.protocol))linkUrl=url.href;}catch{}}
  for(const key of ['link-tab','link-window','link-copy'])menu.querySelector(`[data-page-tool="${key}"]`).hidden=!linkUrl;

  for(const key of ['previous','next'])menu.querySelector(`[data-page-tool="${key}"]`).hidden=!targetFor(key);
  menu.querySelector('[data-page-tool="theme"]').hidden=!document.querySelector('[data-xf-theme-toggle]');
  menu.querySelector('[data-page-tool="comment"]').hidden=!document.querySelector('#comment')&&!document.querySelector('.xf-header a[href*="guestbook"]');
  menu.hidden=false;menu.style.left='12px';menu.style.top='12px';const r=menu.getBoundingClientRect();
  menu.style.left=Math.max(12,Math.min(e.clientX,document.documentElement.clientWidth-r.width-12))+'px';menu.style.top=Math.max(12,Math.min(e.clientY,innerHeight-r.height-12))+'px';
  menu.querySelector('button').focus({preventScroll:true});
 });
 menu.addEventListener('click',async e=>{
  const b=e.target.closest('[data-page-tool]');if(!b)return;const key=b.dataset.pageTool,url=linkUrl;close(true);
  if(key==='link-tab'&&url)window.open(url,'_blank','noopener,noreferrer');
  if(key==='link-window'&&url)window.open(url,'_blank','popup,noopener,noreferrer');
  if(key==='previous'||key==='next')targetFor(key)?.click();
  if(key==='back')history.back();if(key==='forward')history.forward();if(key==='reload')location.reload();
  if(key==='top')scrollTo({top:0,behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'instant':'smooth'});
  if(key==='random')document.querySelector('[data-feng-random]')?.click();
  if(key==='theme')document.querySelector('[data-xf-theme-toggle]')?.click();
  if(key==='comment'){const field=document.querySelector('#comment');if(field){field.scrollIntoView({block:'center',behavior:'smooth'});field.focus({preventScroll:true});}else document.querySelector('.xf-header a[href*="guestbook"]')?.click();}
  if(key==='copy'||(key==='link-copy'&&url)){const message=key==='link-copy'?'链接地址已复制':'页面地址已复制';const status=document.querySelector('[data-page-menu-status]');try{await navigator.clipboard.writeText(key==='link-copy'?url:location.href);status.textContent=message;window.fengToast?.(message);}catch{status.textContent='复制失败，请按住 Shift 右键使用浏览器菜单复制';window.fengToast?.(status.textContent,'error');}}
 });
 document.addEventListener('pointerdown',e=>{if(!menu.contains(e.target))close();});
 document.addEventListener('keydown',e=>{if(menu.hidden)return;if(e.key==='Escape'){e.preventDefault();close(true);}if(e.key==='Tab')close();if(['ArrowDown','ArrowUp','Home','End'].includes(e.key)){e.preventDefault();const buttons=[...menu.querySelectorAll('button')].filter(b=>!b.hidden),i=buttons.indexOf(document.activeElement);buttons[e.key==='Home'?0:e.key==='End'?buttons.length-1:(i+(e.key==='ArrowDown'?1:buttons.length-1))%buttons.length].focus();}});
 window.addEventListener('resize',()=>close());window.addEventListener('scroll',()=>close(),{passive:true});document.addEventListener('xf:before-unmount',()=>close());
})();
