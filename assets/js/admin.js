
;/* feng-admin */
if(window.polarEnabledAdminScripts.includes("feng-admin")){
/* WordPress media selection; settings still submit through the native Settings API. */
document.addEventListener('click',event=>{
 const button=event.target.closest('[data-feng-media],[data-feng-clear]'); if(!button)return;
 if(button.dataset.fengClear){const input=document.getElementById(button.dataset.fengClear);input.value=input.type==='number'?'0':'';input.dispatchEvent(new Event('change',{bubbles:true}));return;}
 const input=document.getElementById(button.dataset.fengTarget), type=button.dataset.fengMedia;
 const picker=wp.media({title:type==='video'?'选择背景视频':'选择图片',library:{type},multiple:false,button:{text:'使用此媒体'}});
 picker.on('select',()=>{const item=picker.state().get('selection').first().toJSON();input.value=type==='video'?item.id:item.url;input.dispatchEvent(new Event('change',{bubbles:true}));}); picker.open();
});

// All sections share the native form, including fields outside the active panel.
(() => {
 const links=[...document.querySelectorAll('.feng-admin-tabs a[href^="#feng-"]')];
 const sections=links.map(link=>document.querySelector(link.hash));
 function activate(hash){
  if(!links.length)return;
  const index=Math.max(0,links.findIndex(link=>link.hash===hash));
  sections.forEach((section,i)=>{if(section)section.hidden=i!==index;links[i].classList.toggle('nav-tab-active',i===index);if(i===index)links[i].setAttribute('aria-current','page');else links[i].removeAttribute('aria-current');});
 }
 activate(location.hash);
 links.forEach(link=>link.addEventListener('click',event=>{event.preventDefault();history.replaceState(null,'',link.hash);activate(link.hash);}));
 window.addEventListener('hashchange',()=>activate(location.hash));
 document.querySelectorAll('.feng-settings-form').forEach(form=>{
  const state=form.querySelector('[data-feng-save-state]');
  const dirty=()=>{if(state){state.textContent='有更改待保存';state.dataset.dirty='';}};
  form.addEventListener('input',dirty);form.addEventListener('change',dirty);
  form.addEventListener('click',event=>{if(event.target.closest('[data-music-add],[data-music-remove],[data-music-up],[data-music-down]'))dirty();});
  form.addEventListener('invalid',event=>{const section=event.target.closest('section[id]');if(section?.hidden){history.replaceState(null,'','#'+section.id);activate('#'+section.id);}},true);
  form.addEventListener('submit',()=>{const referer=form.querySelector('[name="_wp_http_referer"]');if(referer)referer.value=location.pathname+location.search+location.hash;if(state)state.textContent='正在保存…';});
 });
})();

// Keep Settings API notices native; dismiss only a successful save via WordPress.
(() => {
 const url=new URL(location.href);
 if(url.searchParams.get('settings-updated')!=='true')return;
 url.searchParams.delete('settings-updated');
 history.replaceState(history.state,'',url.pathname+url.search+url.hash);
 window.setTimeout(()=>{
  document.querySelectorAll('.feng-admin #setting-error-settings_updated.notice-success .notice-dismiss').forEach(button=>button.click());
 },5000);
})();

}

;/* feng-ai-admin */
if(window.polarEnabledAdminScripts.includes("feng-ai-admin")){
/* AI requests stay in authenticated admin-ajax; API keys are never read by this script. */
(() => {
 'use strict';
 if(!window.fengAI)return;
 let busy=false;
 function status(scope,message,error=false){
  const node=scope.querySelector('.feng-ai-status');
  node.replaceChildren();node.className='feng-ai-status notice '+(error?'notice-error':'notice-info');node.textContent=message;
  return node;
 }
 async function request(scope,values){
  if(busy)throw new Error('已有生成任务正在处理，请稍后重试。');
  busy=true;document.querySelectorAll('[data-ai-generate],[data-ai-test]').forEach(b=>{b.disabled=true;});
  scope.querySelector('.spinner')?.classList.add('is-active');
  status(scope,values.kind==='image'||values.operation==='cover'?'正在生成封面，通常需要一至数分钟，请保持页面打开…':'正在处理，请稍候…');
  try {
   const response=await fetch(fengAI.url,{method:'POST',credentials:'same-origin',body:new URLSearchParams({action:'feng_ai',nonce:fengAI.nonce,...values})});
   let data;try{data=await response.json();}catch{throw new Error('服务器未返回有效结果，请刷新页面后重试。');}
   if(!data.success)throw new Error(data.data?.message||'请求失败，登录可能已过期，请刷新页面重试。');
   return data.data.result;
  } finally {
   busy=false;scope.querySelector('.spinner')?.classList.remove('is-active');
   document.querySelectorAll('[data-ai-generate],[data-ai-test]').forEach(b=>{b.disabled=!!b.closest('[data-kind]')?.dataset.dirty;});
  }
 }
 const settings=document.getElementById('feng-ai-settings-form');
 if(settings){
  settings.querySelectorAll('[data-kind]').forEach(section=>{
   const kind=section.dataset.kind;
   section.addEventListener('input',()=>{section.dataset.dirty='true';section.querySelector('[data-ai-test]').disabled=true;status(section,'配置已修改，请先保存 AI 设置，再测试连接。');});
   section.querySelector('[data-ai-field="provider"]').addEventListener('change',event=>{
    const preset=fengAI.presets[kind][event.target.value];
    ['protocol','endpoint'].forEach(field=>{section.querySelector('[data-ai-field="'+field+'"]').value=preset[field];});
    section.querySelector('[data-ai-field="model"]').value=preset.models[0]||'';
    section.querySelector('[data-ai-field="key"]').value='';
    section.dataset.dirty='true';section.querySelector('[data-ai-test]').disabled=true;
    status(section,'预设已填写。请填写对应密钥、保存设置后测试。');
   });
   section.querySelector('[data-ai-test]').addEventListener('click',async()=>{
    try{
     const result=await request(section,{operation:'test',kind});
     const node=status(section,kind==='text'?'连接成功，当前模型已返回文字。':'连接成功，测试封面已保存到媒体库。');
     if(kind==='image'){const img=document.createElement('img');img.src=result.url;img.alt='AI 测试封面';node.append(img);}
    }catch(error){status(section,error.message,true);}
   });
  });
  settings.addEventListener('submit',event=>{if(busy){event.preventDefault();status(settings,'请等待当前测试完成，再保存设置。',true);}});
 }
 const editor=document.getElementById('feng-ai-editor-tools');
 if(!editor)return;
 let cover=null,generatedSummary=false;
 function blockEditor(){const store=window.wp?.data?.select('core/editor');return store?.getCurrentPostId?.()===Number(editor.dataset.postId)?store:null;}
 function article(){
  const store=blockEditor();
  if(store)return {title:store.getEditedPostAttribute('title')||'',content:store.getEditedPostContent()||''};
  const rich=window.tinymce?.get('content');
  return {title:document.getElementById('title')?.value||'',content:rich&&!rich.isHidden()?rich.getContent():document.getElementById('content')?.value||''};
 }
 editor.querySelectorAll('[data-ai-generate]').forEach(button=>button.addEventListener('click',async()=>{
  const operation=button.dataset.aiGenerate;
  try{
   const data=await request(editor,{operation,post_id:editor.dataset.postId,...article(),style:document.getElementById('feng-ai-style')?.value||'photo',prompt:document.getElementById('feng-ai-prompt')?.value||''});
   const panel=editor.querySelector('[data-ai-result="'+operation+'"]');panel.hidden=false;
   if(operation==='summary'){document.getElementById('feng-ai-summary').value=data;generatedSummary=true;}
   if(operation==='keywords')document.getElementById('feng-ai-keywords').value=data.join('，');
   if(operation==='cover'){cover=data;panel.querySelector('img').src=data.url;panel.querySelector('[data-ai-media-link]').href=data.editUrl;if(blockEditor())await wp.data.dispatch('core/editor').editPost({featured_media:data.id});else if(window.wp?.media?.featuredImage)wp.media.featuredImage.set(data.id);}
   status(editor,operation==='cover'?'WebP 封面已保存，并自动设为特色图片。':'生成完成。可在预览中调整，再点击应用。');
  }catch(error){status(editor,error.message,true);}
 }));
 async function tagIds(names){
  const ids=[];
  for(const name of names){
   const existing=await wp.apiFetch({path:'/wp/v2/tags?search='+encodeURIComponent(name)+'&per_page=100'});
   const match=existing.find(term=>term.name===name);
   if(match){ids.push(match.id);continue;}
   try{const term=await wp.apiFetch({path:'/wp/v2/tags',method:'POST',data:{name}});ids.push(term.id);}
   catch(error){if(error.code==='term_exists'&&error.data?.term_id)ids.push(Number(error.data.term_id));else throw error;}
  }
  return ids;
 }
 editor.querySelectorAll('[data-ai-apply]').forEach(button=>button.addEventListener('click',async()=>{
  button.disabled=true;
  try{
   const kind=button.dataset.aiApply,store=blockEditor();
   if(kind==='summary'){
    const excerpt=document.getElementById('feng-ai-summary').value.trim();
    if(!excerpt)throw new Error('摘要为空，请先生成或填写摘要。');
    const summarySource=generatedSummary?excerpt:'';
    if(store)await wp.data.dispatch('core/editor').editPost({excerpt,meta:{...(store.getEditedPostAttribute('meta')||{}),_feng_ai_summary:summarySource}});
    else {const field=document.getElementById('excerpt');if(!field)throw new Error('请在编辑器「显示选项」中启用摘要。');field.value=excerpt;field.dispatchEvent(new Event('change',{bubbles:true}));}
    document.getElementById('feng-ai-summary-saved').value=summarySource;
   }else if(kind==='keywords'){
    const names=[...new Set(document.getElementById('feng-ai-keywords').value.split(/[,，、;；\n]+/).map(s=>s.trim()).filter(Boolean))].slice(0,8);
    if(!names.length)throw new Error('请先生成或填写关键词。');
    if(store){const ids=await tagIds(names);const current=wp.data.select('core/editor').getEditedPostAttribute('tags')||[];await wp.data.dispatch('core/editor').editPost({tags:[...new Set([...current,...ids])]});}
    else {const field=document.getElementById('new-tag-post_tag');if(!field||!window.tagBox)throw new Error('请在编辑器中启用标签面板。');field.value=[field.value,...names].filter(Boolean).join(',');window.tagBox.flushTags(window.jQuery('#tagsdiv-post_tag'));}
   }else if(kind==='cover'&&cover){
    if(store)await wp.data.dispatch('core/editor').editPost({featured_media:cover.id});
    else if(window.wp?.media?.featuredImage)wp.media.featuredImage.set(cover.id);
    else throw new Error('请从媒体库将生成的封面设为特色图片。');
   }
   status(editor,'已应用到编辑器。请保存草稿或更新文章以保留修改。');
  }catch(error){status(editor,error.message||'应用失败，请稍后重试。',true);}finally{button.disabled=false;}
 }));
})();

}

;/* feng-category-badge */
if(window.polarEnabledAdminScripts.includes("feng-category-badge")){
(() => {
 const cover=document.querySelector('[name="feng_category_cover"]');let coverFrame;
 document.querySelector('[data-category-cover-choose]')?.addEventListener('click',()=>{if(!coverFrame){coverFrame=wp.media({title:'选择分类背景图',button:{text:'使用此图片'},library:{type:'image'},multiple:false});coverFrame.on('select',()=>{cover.value=coverFrame.state().get('selection').first().toJSON().url;});}coverFrame.open();});
 document.querySelector('[data-category-cover-clear]')?.addEventListener('click',()=>{cover.value='';});
 const field=document.querySelector('[data-category-badge]');if(!field)return;
 const input=field.querySelector('input'),preview=field.querySelector('[data-badge-preview]');let frame;
 const code=document.querySelector('[name="feng_category_icon"]'),iconPreview=document.querySelector('[data-icon-preview]');
 function renderIcon(){
  iconPreview.replaceChildren();code.setCustomValidity('');let value=code.value.trim();if(!value)return;
  if(value.startsWith('<svg')){const doc=new DOMParser().parseFromString(value,'image/svg+xml');if(doc.querySelector('parsererror')){code.setCustomValidity('SVG 格式不正确');return;}const img=document.createElement('img');img.src='data:image/svg+xml;charset=utf-8,'+encodeURIComponent(value);img.width=32;img.height=32;img.alt='SVG 预览';iconPreview.append(img);return;}
  if(value.includes('<')){const parsed=new DOMParser().parseFromString(value,'text/html');const i=parsed.body.firstElementChild;if(i?.tagName!=='I'||parsed.body.children.length!==1){code.setCustomValidity('请填写类名、i 标签或 SVG');return;}value=i.getAttribute('class')||'';}
  const classes=value.split(/\s+/);if(classes.length>12||classes.some(c=>! /^(?:fa[srlbtdk]?|fa-[a-z0-9-]+)$/.test(c))){code.setCustomValidity('图标类名格式不正确');return;}
  const icon=document.createElement('i');icon.className=classes.join(' ');icon.setAttribute('aria-hidden','true');iconPreview.append(icon);
 }
 code.addEventListener('input',renderIcon);
 const clear=()=>{input.value='';preview.replaceChildren();};
 field.querySelector('[data-badge-remove]').addEventListener('click',clear);
 field.querySelector('[data-badge-choose]').addEventListener('click',()=>{
  if(!frame){frame=wp.media({title:'选择分类徽章',button:{text:'使用此徽章'},library:{type:'image'},multiple:false});
   frame.on('select',()=>{const item=frame.state().get('selection').first().toJSON();input.value=item.id;const img=document.createElement('img');img.src=item.url;img.width=48;img.height=48;img.style.objectFit='contain';img.alt='分类徽章预览';preview.replaceChildren(img);});
  }frame.open();
 });
 jQuery(document).ajaxSuccess((event,xhr,settings)=>{if(typeof settings.data==='string'&&settings.data.includes('action=add-tag')&&xhr.responseText.includes('<term_id>')){clear();code.value='';if(cover)cover.value='';renderIcon();}});
})();

}

;/* feng-feed-admin */
if(window.polarEnabledAdminScripts.includes("feng-feed-admin")){
(() => {
 const button = document.querySelector('[data-feed-refresh]'); if (!button) return;
 const status = document.querySelector('[data-feed-admin-status]');
 button.addEventListener('click', async () => {
  button.disabled = true; let start = true;
  try {
   do {
    status.textContent = start ? '正在连接订阅源…' : status.textContent;
    const response = await fetch(button.dataset.endpoint, {method:'POST', credentials:'same-origin', body:new URLSearchParams({action:'feng_feed_refresh',nonce:button.dataset.nonce,start:start?'1':'0'})});
    const json = await response.json(); if (!json.success) throw new Error(json.data?.message || '同步未完成，请稍后重试。');
    const d = json.data; start = false;
    status.textContent = d.pending ? `已同步 ${d.done} / ${d.total} 个源，新收录 ${d.added} 篇…` : `同步完成：新收录 ${d.added} 篇，${d.errors ? d.errors+' 个源暂时失败' : '全部成功'}。`;
    if (!d.pending) break;
   } while (true);
  } catch(error) { status.textContent = error.message + ' 后台会继续处理未完成的订阅。'; }
  finally { button.disabled = false; }
 });
})();

}

;/* feng-music-admin */
if(window.polarEnabledAdminScripts.includes("feng-music-admin")){
(() => {
 const rows=document.querySelector('[data-music-rows]'),template=document.querySelector('#feng-music-row-template');if(!rows||!template)return;
 let counter=rows.children.length;
 const renumber=()=>rows.querySelectorAll('[data-music-row]').forEach((row,i)=>{row.querySelectorAll('[name]').forEach(input=>{input.name=input.name.replace(/music_tracks\]\[[^\]]+\]/,'music_tracks]['+i+']');});});
 document.addEventListener('click',event=>{
  const button=event.target.closest('[data-music-add],[data-music-remove],[data-music-up],[data-music-down],[data-music-media]');if(!button)return;
  if(button.hasAttribute('data-music-add')){if(rows.children.length>=30)return;rows.insertAdjacentHTML('beforeend',template.innerHTML.replaceAll('__INDEX__','new'+counter++));renumber();rows.lastElementChild.querySelector('input').focus();return;}
  const row=button.closest('[data-music-row]');
  if(button.hasAttribute('data-music-remove'))row.remove();
  if(button.hasAttribute('data-music-up')&&row.previousElementSibling)row.previousElementSibling.before(row);
  if(button.hasAttribute('data-music-down')&&row.nextElementSibling)row.nextElementSibling.after(row);
  if(button.hasAttribute('data-music-media')){const picker=wp.media({title:'选择媒体',library:{type:button.dataset.musicMedia},multiple:false,button:{text:'使用此媒体'}});picker.on('select',()=>{const item=picker.state().get('selection').first().toJSON();const target=document.getElementById(button.dataset.target);target.value=item.url;target.dispatchEvent(new Event('change',{bubbles:true}));if(button.dataset.musicMedia==='audio'){const title=row.querySelector('input[name$="[title]"]');if(!title.value)title.value=item.title||item.filename||'';}});picker.open();}
  renumber();
 });
})();

}

;/* feng-travel-editor */
if(window.polarEnabledAdminScripts.includes("feng-travel-editor")){
(() => {const box=document.querySelector('[data-travel-editor]');if(!box)return;const b=box.querySelector('[data-place-search]'),status=box.querySelector('[data-place-status]'),results=box.querySelector('[data-place-results]');box.querySelectorAll('[name="feng_travel[lat]"],[name="feng_travel[lng]"],[name="feng_travel[crs]"]').forEach(f=>f.addEventListener('input',()=>{box.querySelector('[name="feng_travel[source]"]').value='manual';box.querySelector('[name="feng_travel[place_id]"]').value='';}));b.addEventListener('click',async()=>{b.disabled=true;status.textContent='正在搜索地点…';results.replaceChildren();try{const r=await fetch(box.dataset.endpoint,{method:'POST',credentials:'same-origin',body:new URLSearchParams({action:'feng_travel_search',nonce:box.dataset.nonce,post:box.dataset.post,q:box.querySelector('[data-place-query]').value})});const data=await r.json();if(!data.success)throw Error(data.data?.message||'搜索失败');status.textContent=data.data.length?'选择一个地点，检查后保存文章。':'没有找到，请输入更完整的城市、国家名称。';data.data.forEach(item=>{const row=document.createElement('p'),button=document.createElement('button');button.type='button';button.className='button';button.textContent=item.label;button.onclick=()=>{for(const k of ['city','country','code','lat','lng','source','place_id','crs'])box.querySelector(`[name="feng_travel[${k}]"]`).value=item[k]??'';status.textContent='已填入地点，请保存文章。';};row.append(button);results.append(row);});}catch(e){status.textContent=e.message;}finally{b.disabled=false;}});})();

}
