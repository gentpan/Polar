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
