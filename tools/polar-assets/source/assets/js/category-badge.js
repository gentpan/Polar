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
