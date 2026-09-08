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
