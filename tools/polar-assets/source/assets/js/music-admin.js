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
