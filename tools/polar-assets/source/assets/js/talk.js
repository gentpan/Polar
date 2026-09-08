/* Front-end notes: progressive rendering, author-owned edits and slow paper transitions. */
(() => {
 'use strict';
 let cleanup=()=>{};
 function mount(){
  cleanup();cleanup=()=>{};const root=document.querySelector('[data-feng-talks]');if(!root)return;
  const life=new AbortController(),$=s=>root.querySelector(s),board=$('[data-talk-board]'),detail=$('[data-talk-detail]'),compose=$('[data-talk-compose]'),form=$('[data-talk-form]');
  const field=name=>form.elements.namedItem(name);
  let nonce='',canPublish=false,canManage=false,tags=[],page=1,tag=0,query='',listRequest=0,detailRequest=0,current=null,editing=null,files=[],kept=[],previewURLs=[],initial='',saving=false,saveKey='',undoId=0,noticeTimer;
  const on=(node,event,fn)=>node.addEventListener(event,fn,{signal:life.signal});
  const dock=root.querySelector('.feng-talk-dock');
  function syncFooterDock(){
   if(!dock)return;
   const footer=document.querySelector('.xf-footer');
   const near=!!footer&&window.scrollY>10&&footer.getBoundingClientRect().top<window.innerHeight;
   if(footer){footer.toggleAttribute('data-talk-footer-visible',near);footer.inert=!near;}
   dock.classList.toggle('is-near-footer',near);dock.inert=false;
   root.style.setProperty('--talk-footer-clearance',near?Math.max(0,window.innerHeight-footer.getBoundingClientRect().top)+24+'px':'0px');
  }
  on(window,'scroll',syncFooterDock);on(window,'resize',syncFooterDock);syncFooterDock();
  const uuid=()=>{
   if(typeof crypto.randomUUID==='function')return crypto.randomUUID();
   const bytes=crypto.getRandomValues(new Uint8Array(16));
   bytes[6]=(bytes[6]&15)|64;bytes[8]=(bytes[8]&63)|128;
   const hex=Array.from(bytes,b=>b.toString(16).padStart(2,'0')).join('');
   return [hex.slice(0,8),hex.slice(8,12),hex.slice(12,16),hex.slice(16,20),hex.slice(20)].join('-');
  };
  const reduced=()=>matchMedia('(prefers-reduced-motion: reduce)').matches;
  const controllers=new Set(),timers=new Set();
  const cardObserver=typeof ResizeObserver==='function'?new ResizeObserver(entries=>{for(const {target} of entries)target.style.gridRowEnd='span '+Math.ceil((target.offsetHeight+44)/36);}):null;
  function layoutCards(){if(!cardObserver)return;root.classList.add('is-masonry');cardObserver.disconnect();board.querySelectorAll('.feng-talk-card').forEach(card=>cardObserver.observe(card));}
  function later(fn,ms){const id=setTimeout(()=>{timers.delete(id);if(!life.signal.aborted)fn();},ms);timers.add(id);return id;}
  async function request(op,params={},write=false){
   const controller=new AbortController();controllers.add(controller);const timeout=setTimeout(()=>controller.abort(),write&&op==='save'?120000:20000);
   const url=new URL(root.dataset.endpoint,location.href);let body;
   if(write){url.searchParams.set('action','feng_talk_write');body=new FormData();body.set('action','feng_talk_write');body.set('nonce',nonce);body.set('op',op);for(const [key,value] of Object.entries(params))body.set(key,value);if(op==='save')files.forEach(file=>body.append('images[]',file));}
   else{url.search=new URLSearchParams({action:'feng_talk_read',op,...params}).toString();}
   try{
    const response=await fetch(url,{method:write?'POST':'GET',credentials:'same-origin',body,signal:controller.signal,cache:'no-store'});
    const data=await response.json();if(!data?.success)throw new Error(data?.data?.message||(response.status===403?'登录状态已过期，请刷新页面后重试。':'暂时没能完成，请稍后重试。'));return data.data;
   }catch(error){if(error.name==='AbortError')throw new Error('等待有些久，请稍后重试。');throw error;}
   finally{clearTimeout(timeout);controllers.delete(controller);}
  }
  function notify(text,undo=false){clearTimeout(noticeTimer);$('[data-talk-notice] span').textContent=text;$('[data-talk-notice]').hidden=false;$('[data-talk-undo]').hidden=!undo;if(!undo)noticeTimer=later(()=>{$('[data-talk-notice]').hidden=true;},6000);}
  function total(value){$('[data-talk-total]').textContent=value+' 条';$('[data-talk-dock-count]').textContent=value+' 条';}
  function renderTagPickers(){
   const area=$('[data-talk-tag-list]');area.replaceChildren();
   if(!tags.length){const p=document.createElement('p');p.textContent='发布时写下关键词，就能在这里找到它。';area.append(p);}
   for(const t of tags){const button=document.createElement('button');button.type='button';button.dataset.talkTag=t.id;button.style.setProperty('--tag-color',t.color);button.textContent='# '+t.name+' · '+t.count;button.setAttribute('aria-pressed',String(tag===t.id));area.append(button);}
   const suggestions=$('[data-talk-suggestions]');suggestions.replaceChildren();
   for(const t of tags.slice(0,8)){const button=document.createElement('button');button.type='button';button.dataset.talkSuggest=t.name;button.textContent=t.name;suggestions.append(button);}
  }
  async function session(){const data=await request('session');if(life.signal.aborted)return;canPublish=data.can_publish;canManage=!!data.can_manage;nonce=data.nonce;tags=data.tags;$('[data-talk-create]').hidden=!canPublish;$('[data-talk-login]').hidden=canPublish;renderTagPickers();}
  function filters(){const name=tags.find(t=>t.id===tag)?.name;$('[data-talk-filter]').hidden=!tag&&!query;$('[data-talk-filter-label]').textContent=[name?'# '+name:'',query?'搜索「'+query+'」':''].filter(Boolean).join(' · ');renderTagPickers();}
  async function load(append=false){
   const id=++listRequest,next=append?page+1:1;board.setAttribute('aria-busy','true');$('[data-talk-more]').disabled=true;
   try{const data=await request('list',{page:next,tag,q:query});if(id!==listRequest||life.signal.aborted)return;
    const template=document.createElement('template');template.innerHTML=data.html;
    if(append)board.append(template.content);else board.replaceChildren(template.content);
    layoutCards();page=next;total(data.total);$('[data-talk-empty]').hidden=data.total>0;
    $('[data-talk-empty] h2').textContent=tag||query?'这里还没有找到这个片刻':'留一张生活的便签';
    $('[data-talk-empty] p').textContent=tag||query?'试试其他关键词，或者查看全部说说。':'一些念头，一张照片，或者今天的小事。';
    $('[data-talk-more]').hidden=!data.more;filters();
   }catch(e){if(!life.signal.aborted)notify(e.message);}
   finally{if(id===listRequest){board.removeAttribute('aria-busy');$('[data-talk-more]').disabled=false;}}
  }
  function clearHash(){if(/^#talk-\d+$/.test(location.hash))history.replaceState(history.state,'',location.pathname+location.search);}
  const closeTimers=new Map();
  function cancelClose(dialog){const id=closeTimers.get(dialog);if(id!==undefined){clearTimeout(id);timers.delete(id);closeTimers.delete(dialog);}}
  function syncModalLock(){document.documentElement.classList.toggle('feng-talk-modal-open',detail.open||compose.open);}
  [detail,compose].forEach(dialog=>on(dialog,'close',()=>{
   // Native close calls (including other UI modules) must release our scroll lock.
   if(!dialog.open){cancelClose(dialog);dialog.classList.remove('is-closing');if(dialog===detail)clearHash();}
   syncModalLock();
  }));
  function openDialog(dialog){
   cancelClose(dialog);
   dialog.classList.remove('is-closing');dialog.scrollTop=0;
   if(!dialog.open)dialog.showModal();syncModalLock();
  }
  function closeDialog(dialog,immediate=false){
   cancelClose(dialog);
   if(!dialog.open){syncModalLock();return;}dialog.classList.add('is-closing');
   const finish=()=>{closeTimers.delete(dialog);dialog.close();dialog.classList.remove('is-closing');syncModalLock();};
   if(immediate||reduced())finish();else closeTimers.set(dialog,later(finish,180));
  }
  async function showDetail(id){
   await sessionReady;
   if(life.signal.aborted)return;
   if(!canManage){
    const card=board.querySelector('[data-talk-id="'+id+'"]');
    if(card)card.classList.toggle('is-straight');
    clearHash();return;
   }
   const sequence=++detailRequest;notify('正在展开这一刻…');
   try{const item=await request('detail',{id});if(sequence!==detailRequest||life.signal.aborted)return;current=item;
    $('[data-talk-notice]').hidden=true;$('#feng-talk-detail-title').textContent=item.date_label;$('[data-talk-detail-content]').textContent=item.content;
    const chips=$('[data-talk-detail-tags]');chips.replaceChildren();for(const t of item.tags){const span=document.createElement('span');span.textContent='# '+t.name;span.style.setProperty('--tag-color',t.color);chips.append(span);}
    const images=$('[data-talk-detail-images]');images.replaceChildren();for(const image of item.images){const a=document.createElement('a');a.href=image.full;a.target='_blank';a.rel='noopener noreferrer';a.setAttribute('aria-label','查看原图');const img=document.createElement('img');img.src=image.url;img.alt=image.alt||'说说配图';a.append(img);images.append(a);}
    $('[data-talk-detail-meta]').textContent=[item.author,item.location,'via 网页'].filter(Boolean).join(' · ');$('[data-talk-manage]').hidden=!item.can_edit;$('[data-talk-delete-confirm]').hidden=true;
    history.replaceState(history.state,'',location.pathname+location.search+'#talk-'+id);openDialog(detail);
   }catch(e){if(!life.signal.aborted)notify(e.message);}
  }
  function model(){return JSON.stringify([field('content').value,field('tags').value,field('location').value,kept.map(i=>i.id),files.map(f=>[f.name,f.size,f.lastModified])]);}
  function dirty(){return compose.open&&model()!==initial;}
  function revoke(){previewURLs.forEach(url=>URL.revokeObjectURL(url));previewURLs=[];}
  function previews(){
   revoke();const target=$('[data-talk-upload-preview]');target.replaceChildren();
   const all=[...kept.map((image,index)=>({url:image.url,index,kept:true})),...files.map((file,index)=>{const url=URL.createObjectURL(file);previewURLs.push(url);return {url,index,kept:false};})];
   all.forEach(image=>{const box=document.createElement('div'),img=document.createElement('img'),button=document.createElement('button');img.src=image.url;img.alt='待发布配图';button.type='button';button.textContent='移除';button.dataset.talkRemoveImage=image.index;button.dataset.kept=String(image.kept);button.setAttribute('aria-label','移除第 '+(all.indexOf(image)+1)+' 张图片');box.append(img,button);target.append(box);});
  }
  function composeNote(item=null){
   if(saving)return;
   if(!canPublish){notify('登录后才可以发布说说。');return;}
   editing=item;saveKey=uuid();files=[];kept=item?[...item.images]:[];form.reset();field('content').value=item?.content||'';field('tags').value=item?.tags.map(t=>t.name).join('，')||'';field('location').value=item?.location||'';
   $('#feng-talk-compose-title').textContent=item?'编辑这一刻':'写条说说';$('[data-talk-submit] span').textContent=item?'保存修改':'发布';$('[data-talk-form-error]').textContent='';$('[data-talk-discard]').hidden=true;$('[data-talk-words]').textContent=Array.from(field('content').value).length;previews();initial=model();closeDialog(detail,true);clearHash();openDialog(compose);field('content').focus({preventScroll:true});
  }
  function maybeClose(){if(saving)return;if(dirty()){$('[data-talk-discard]').hidden=false;$('[data-talk-continue]').focus();}else closeDialog(compose);}
  on(form,'input',()=>{$('[data-talk-words]').textContent=Array.from(field('content').value).length;});
  on(field('images[]'),'change',()=>{
   const selected=[...field('images[]').files];field('images[]').value='';
   if(selected.length+kept.length+files.length>4){$('[data-talk-form-error]').textContent='每条说说最多 4 张图片。';return;}
   if(selected.some(f=>f.size>Number(root.dataset.uploadLimit)||!['image/jpeg','image/png','image/webp','image/gif'].includes(f.type))){$('[data-talk-form-error]').textContent='请选择 '+root.dataset.uploadLabel+' 以内的 JPEG、PNG、WebP 或 GIF 图片。';return;}
   if([...files,...selected].reduce((size,f)=>size+f.size,0)>Number(root.dataset.uploadTotal)){$('[data-talk-form-error]').textContent='图片总大小超过服务器限制，请减少图片后重试。';return;}
   files.push(...selected);$('[data-talk-form-error]').textContent='';previews();
  });
  on(form,'submit',async e=>{
   e.preventDefault();if(saving)return;saving=true;form.querySelectorAll('input,textarea,button').forEach(control=>control.disabled=true);$('[data-talk-form-error]').textContent='正在保存这一刻…';
   try{await request('save',{id:editing?.id||0,version:editing?.version||'',content:field('content').value,tags:field('tags').value,location:field('location').value,keep_images:JSON.stringify(kept.map(i=>i.id)),request_id:saveKey},true);if(life.signal.aborted)return;
    initial=model();closeDialog(compose);if(!editing){tag=0;query='';}await session();await load();notify(editing?'修改已保存。':'这一刻，已经留下来了。');revoke();
   }catch(error){if(!life.signal.aborted)$('[data-talk-form-error]').textContent=error.message;}
   finally{saving=false;form.querySelectorAll('input,textarea,button').forEach(control=>control.disabled=false);}
  });
  async function remove(){if(saving||!current)return;const id=current.id;saving=true;$('[data-talk-delete-confirm-button]').disabled=true;
   try{await request('delete',{id,request_id:uuid()},true);undoId=id;if(current?.id===id){closeDialog(detail);clearHash();}await session();await load();notify('已移入回收站。',true);}catch(e){notify(e.message);}finally{saving=false;$('[data-talk-delete-confirm-button]').disabled=false;}
  }
  on(root,'click',e=>{
   const b=e.target.closest('button,a');if(!b)return;
   if(b.hasAttribute('data-talk-open')){e.preventDefault();showDetail(Number(b.dataset.talkOpen));}
   if(b.hasAttribute('data-talk-tag')){tag=Number(b.dataset.talkTag);$('[data-talk-tags]').hidden=true;$('[data-talk-filter-toggle]').setAttribute('aria-expanded','false');load();}
   if(b.hasAttribute('data-talk-clear')){tag=0;query='';load();}
   if(b.hasAttribute('data-talk-more'))load(true);
   if(b.hasAttribute('data-talk-notice-close'))$('[data-talk-notice]').hidden=true;
   if(b.hasAttribute('data-talk-filter-toggle')){$('[data-talk-tags]').hidden=!$('[data-talk-tags]').hidden;b.setAttribute('aria-expanded',String(!$('[data-talk-tags]').hidden));}
   if(b.hasAttribute('data-talk-filter-close')){$('[data-talk-tags]').hidden=true;$('[data-talk-filter-toggle]').setAttribute('aria-expanded','false');$('[data-talk-filter-toggle]').focus();}
   if(b.hasAttribute('data-talk-create'))composeNote();
   if(b.hasAttribute('data-talk-close')){if(b.closest('dialog')===compose)maybeClose();else{closeDialog(detail);clearHash();}}
   if(b.hasAttribute('data-talk-edit'))composeNote(current);
   if(b.hasAttribute('data-talk-delete'))$('[data-talk-delete-confirm]').hidden=false;
   if(b.hasAttribute('data-talk-delete-cancel'))$('[data-talk-delete-confirm]').hidden=true;
   if(b.hasAttribute('data-talk-delete-confirm-button'))remove();
   if(b.hasAttribute('data-talk-continue')){$('[data-talk-discard]').hidden=true;field('content').focus();}
   if(b.hasAttribute('data-talk-discard-confirm')){initial=model();closeDialog(compose);revoke();}
   if(b.hasAttribute('data-talk-remove-image')){const index=Number(b.dataset.talkRemoveImage);if(b.dataset.kept==='true')kept.splice(index,1);else files.splice(index,1);previews();}
   if(b.hasAttribute('data-talk-suggest')){const values=field('tags').value.split(/[,，\n]+/).map(v=>v.trim()).filter(Boolean);if(!values.includes(b.dataset.talkSuggest)&&values.length<4)values.push(b.dataset.talkSuggest);field('tags').value=values.join('，');}
   if(b.hasAttribute('data-talk-layout')){const neat=root.classList.toggle('is-neat');b.setAttribute('aria-pressed',String(neat));b.setAttribute('aria-label',neat?'切换为错落排列':'切换为整齐排列');try{localStorage.setItem('feng-talk-layout',neat?'neat':'scattered');}catch{}}
   if(b.hasAttribute('data-talk-undo')&&undoId){const id=undoId;undoId=0;b.disabled=true;request('restore',{id,request_id:uuid()},true).then(async()=>{await session();await load();notify('说说已恢复。');}).catch(e=>notify(e.message)).finally(()=>b.disabled=false);}
  });
  on(document,'click',e=>{if(!e.target.closest('[data-talk-tags],[data-talk-filter-toggle]')){$('[data-talk-tags]').hidden=true;$('[data-talk-filter-toggle]').setAttribute('aria-expanded','false');}});
  on(document,'keydown',e=>{if(e.key==='Escape'&&!$('[data-talk-tags]').hidden){$('[data-talk-tags]').hidden=true;$('[data-talk-filter-toggle]').setAttribute('aria-expanded','false');$('[data-talk-filter-toggle]').focus();}});
  [detail,compose].forEach(dialog=>{on(dialog,'cancel',e=>{e.preventDefault();if(dialog===compose)maybeClose();else{closeDialog(detail);clearHash();}});on(dialog,'click',e=>{if(e.target===dialog){const r=dialog.getBoundingClientRect();if(e.clientX<r.left||e.clientX>r.right||e.clientY<r.top||e.clientY>r.bottom){if(dialog===compose)maybeClose();else{closeDialog(detail);clearHash();}}}});});
  on(window,'beforeunload',e=>{if(dirty()){e.preventDefault();e.returnValue='';}});
  try{if(localStorage.getItem('feng-talk-layout')==='neat'){root.classList.add('is-neat');$('[data-talk-layout]').setAttribute('aria-pressed','true');$('[data-talk-layout]').setAttribute('aria-label','切换为错落排列');}}catch{}
  layoutCards();const sessionReady=session().catch(e=>notify(e.message));
  const match=location.hash.match(/^#talk-(\d+)$/);if(match)showDetail(Number(match[1]));
  cleanup=()=>{const footer=document.querySelector('.xf-footer');if(footer){footer.inert=false;footer.removeAttribute('data-talk-footer-visible');}life.abort();cardObserver?.disconnect();controllers.forEach(c=>c.abort());timers.forEach(clearTimeout);revoke();[detail,compose].forEach(d=>{if(d.open)d.close();});document.documentElement.classList.remove('feng-talk-modal-open');};
 }
 document.addEventListener('xf:before-unmount',()=>cleanup());document.addEventListener('xf:mounted',mount);mount();
})();
