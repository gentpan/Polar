/* One fixed music dock and audio instance survive PJAX. */
(() => {
 const root=document.querySelector('[data-feng-music]');if(!root)return;
 const $=s=>root.querySelector(s), tracks=JSON.parse($('[data-music-data]').textContent||'[]');
 const audio=new Audio();audio.preload='none';audio.volume=.65;
 let index=0,lyrics=[],activeLine=-1,loaded=false,expanded=false,seeking=false,generation=0;
 const time=n=>Number.isFinite(n)?`${Math.floor(n/60)}:${String(Math.floor(n%60)).padStart(2,'0')}`:'0:00';
 const finiteDuration=()=>Number.isFinite(audio.duration)&&audio.duration>0;
 function alignFooter(){root.classList.remove('is-beside-footer','is-above-footer');}

 window.addEventListener('scroll',alignFooter,{passive:true});window.addEventListener('resize',alignFooter);
 function place(){
  document.body.append(root);root.classList.add('is-floating');root.hidden=false;alignFooter();
 }
 function panel(open,playlist=false){
  expanded=open;root.classList.toggle('is-expanded',open);$('#feng-music-panel').hidden=!open;
  $('[data-music-expand]').setAttribute('aria-expanded',String(open));$('[data-music-list-toggle]').setAttribute('aria-expanded',String(open));
  if(open){const box=$('#feng-music-panel'),rect=root.getBoundingClientRect(),above=root.classList.contains('is-floating')||(rect.top>innerHeight-rect.bottom&&rect.bottom+box.scrollHeight>innerHeight-16);root.classList.toggle('opens-up',above);box.style.maxHeight=Math.min(530,Math.max(160,(above?rect.top:innerHeight-rect.bottom)-28))+'px';if(playlist)$('[data-music-playlist]').scrollTop=0;}
 }
 function parseLyrics(text){
  const rows=[];let offset=0;const offsetMatch=text.match(/\[offset:([+-]?\d+)\]/i);if(offsetMatch)offset=Number(offsetMatch[1])/1000;
  for(const line of text.split(/\r?\n/)){
   const stamps=[...line.matchAll(/\[(\d{1,3}):(\d{2})(?:[.:](\d{1,3}))?\]/g)];
   const words=line.replace(/\[[^\]]*\]/g,'').trim();
   for(const m of stamps)rows.push({at:Math.max(0,Number(m[1])*60+Number(m[2])+(m[3]?Number('0.'+m[3]):0)-offset),text:words});
  }
  return rows.sort((a,b)=>a.at-b.at);
 }
 function sync(){
  const playing=!audio.paused&&!audio.ended;
  document.querySelectorAll('[data-hero-music]').forEach(b=>{const active=playing&&b.dataset.heroMusic===tracks[index]?.url;b.setAttribute('aria-pressed',String(active));b.querySelector('[data-hero-music-state]').textContent=active?'暂停':'播放';});
  $('[data-music-play-icon]').hidden=playing;$('[data-music-pause-icon]').hidden=!playing;
  $('[data-music-play]').setAttribute('aria-label',playing?'暂停':'播放');$('[data-music-toggle]').textContent=playing?'暂停':'播放';root.classList.toggle('is-playing',playing);
  $('[data-music-seek]').disabled=!finiteDuration();
  $('[data-music-elapsed]').textContent=time(audio.currentTime);$('[data-music-duration]').textContent=time(audio.duration);
  const percent=finiteDuration()?audio.currentTime/audio.duration:0;
  if(!seeking)$('[data-music-seek]').value=String(Math.round(percent*1000));
  $('[data-music-seek]').setAttribute('aria-valuetext',`${time(audio.currentTime)} / ${time(audio.duration)}`);
  $('[data-music-mini-progress]').style.width=`${percent*100}%`;
  let line=-1;for(let i=0;i<lyrics.length&&lyrics[i].at<=audio.currentTime;i++)line=i;
  if(line!==activeLine){activeLine=line;const nodes=$('[data-music-lyrics]').children;Array.from(nodes).forEach((p,i)=>p.classList.toggle('is-current',i===line));
   $('[data-music-line]').textContent=line>=0?lyrics[line].text:(tracks[index]?.artist||'把日常放慢一点');
   if(expanded&&line>=0){const p=nodes[line],box=$('[data-music-lyrics]');box.scrollTo({top:p.offsetTop-box.offsetTop-box.clientHeight/2+p.offsetHeight/2,behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'instant':'smooth'});}
  }
 }
 document.addEventListener('click',e=>{const b=e.target.closest('[data-hero-music]');if(!b)return;const i=tracks.findIndex(t=>t.url===b.dataset.heroMusic);if(i<0)return;if(i!==index)choose(i,true);else if(audio.paused)start();else audio.pause();});
 document.addEventListener('xf:mounted',sync);
 let lyricRequest=null;const lyricCache=new Map();
 function renderLyrics(text){lyrics=parseLyrics(text||'');activeLine=-1;const box=$('[data-music-lyrics]');box.replaceChildren();box.scrollTop=0;const lines=lyrics.length?lyrics.map(x=>x.text):(text||'这首歌暂时没有歌词，静静听也很好。').split(/\r?\n/);lines.forEach(text=>{const p=document.createElement('p');p.textContent=text||'♪';box.append(p);});sync();}
 async function fetchLyrics(track,ticket){
  if(!track.lrc_url)return;
  if(lyricCache.has(track.lrc_url)){renderLyrics(lyricCache.get(track.lrc_url));return;}
  const controller=new AbortController();lyricRequest=controller;const timeout=setTimeout(()=>controller.abort(),10000);
  try{const r=await fetch(track.lrc_url,{signal:controller.signal,credentials:'omit'});if(!r.ok)throw Error();const reader=r.body.getReader();let size=0,text='';const decoder=new TextDecoder('utf-8');while(true){const chunk=await reader.read();if(chunk.done)break;size+=chunk.value.byteLength;if(size>300000){await reader.cancel();throw Error();}text+=decoder.decode(chunk.value,{stream:true});}text+=decoder.decode();lyricCache.set(track.lrc_url,text);if(ticket===generation)renderLyrics(text);}
  catch{if(ticket===generation&&!track.lrc)renderLyrics('歌词暂时无法加载，请稍后重试。');}
  finally{clearTimeout(timeout);if(lyricRequest===controller)lyricRequest=null;}
 }
 function choose(i,play=false){
  if(!tracks.length)return;index=(i+tracks.length)%tracks.length;const track=tracks[index];
  generation++;lyricRequest?.abort();audio.pause();audio.removeAttribute('src');audio.load();loaded=false;
  $('[data-music-title]').textContent=track.title;$('[data-music-line]').textContent=track.artist||'把日常放慢一点';
  const img=$('[data-music-cover]');img.hidden=!track.cover;$('[data-music-art-icon]').hidden=!!track.cover;if(track.cover)img.src=track.cover;else img.removeAttribute('src');
  renderLyrics(track.lrc||'');fetchLyrics(track,generation);
  $('[data-music-status]').textContent='';$('[data-music-playlist]').querySelectorAll('button').forEach((b,n)=>{b.setAttribute('aria-current',n===index?'true':'false');});
  $('[data-music-seek]').value='0';$('[data-music-mini-progress]').style.width='0%';sync();if(play)start();
 }
 async function start(){
  if(!tracks.length)return;
  if(!loaded){audio.src=tracks[index].url;loaded=true;}
  const requestGeneration=generation;
  $('[data-music-status]').textContent='正在加载音乐…';
  try{await audio.play();if(requestGeneration!==generation)return;$('[data-music-status]').textContent='';}
  catch(e){if(requestGeneration!==generation)return;if(e.name!=='AbortError')$('[data-music-status]').textContent='暂时无法播放，请重试或在歌单中选择另一首。';}
  sync();place();
 }
 const toggle=()=>audio.paused?start():audio.pause();
 $('[data-music-art-toggle]').addEventListener('click',toggle);
 tracks.forEach((track,i)=>{const li=document.createElement('li'),b=document.createElement('button'),title=document.createElement('strong'),artist=document.createElement('span');b.type='button';title.textContent=track.title;artist.textContent=track.artist||'本地音乐';b.append(title,artist);b.addEventListener('click',()=>choose(i,true));li.append(b);$('[data-music-playlist]').append(li);});
 $('[data-music-play]').addEventListener('click',toggle);$('[data-music-toggle]').addEventListener('click',toggle);
 $('[data-music-prev]').addEventListener('click',()=>choose(index-1,true));$('[data-music-next]').addEventListener('click',()=>choose(index+1,true));
 $('[data-music-expand]').addEventListener('click',()=>panel(!expanded));$('[data-music-list-toggle]').addEventListener('click',()=>panel(!expanded,true));
 $('[data-music-close]').addEventListener('click',()=>{panel(false);$('[data-music-expand]').focus();});
 document.addEventListener('click',e=>{if(expanded&&!root.contains(e.target))panel(false);});
 root.addEventListener('keydown',e=>{if(e.key==='Escape'){panel(false);$('[data-music-expand]').focus();}});
 $('[data-music-seek]').addEventListener('input',()=>{seeking=true;if(finiteDuration())$('[data-music-elapsed]').textContent=time(Number($('[data-music-seek]').value)/1000*audio.duration);});
 $('[data-music-seek]').addEventListener('change',()=>{if(finiteDuration())audio.currentTime=Number($('[data-music-seek]').value)/1000*audio.duration;seeking=false;sync();});
 $('[data-music-volume]').addEventListener('input',e=>{audio.volume=Number(e.target.value)/100;});
 $('[data-music-cover]').addEventListener('error',()=>{$('[data-music-cover]').hidden=true;$('[data-music-art-icon]').hidden=false;});
 for(const name of ['play','pause','timeupdate','loadedmetadata','durationchange','seeked','ended'])audio.addEventListener(name,sync);
 audio.addEventListener('playing',()=>{$('[data-music-status]').textContent='';});
 audio.addEventListener('waiting',()=>{$('[data-music-status]').textContent='正在缓冲…';});
 audio.addEventListener('error',()=>{$('[data-music-status]').textContent='音频未能加载，请检查 MP3 地址或选择另一首。';sync();});
 audio.addEventListener('ended',()=>{if(index<tracks.length-1)choose(index+1,true);});
 document.addEventListener('xf:before-unmount',()=>{panel(false);document.body.append(root);});
 document.addEventListener('xf:mounted',place);
 if(tracks.length)choose(0);else{
  $('[data-music-title]').textContent='给此刻一点音乐';$('[data-music-line]').textContent='歌单待添加';$('[data-music-lyrics]').textContent='还没有添加歌曲。';
  $('[data-music-status]').textContent='上传 MP3 后，这里就能播放你的歌单。';
  ['play','toggle','prev','next','seek','volume','art-toggle'].forEach(key=>$(`[data-music-${key}]`).disabled=true);
 }
 const menu=$('[data-music-menu]');let selection='',returnFocus=null,noticeTimer;
 const closeMenu=(focus=false)=>{menu.hidden=true;if(focus)returnFocus?.focus({preventScroll:true});};
 const notice=text=>{if(window.fengToast){window.fengToast(text,text.includes('失败')?'error':'success');return;}const n=$('[data-music-notice]');n.textContent=text;n.hidden=false;clearTimeout(noticeTimer);noticeTimer=setTimeout(()=>n.hidden=true,2200);};
 const copy=async text=>{try{await navigator.clipboard.writeText(text);notice('已复制');}catch{notice('复制失败，请使用浏览器复制功能');}};
 function openMenu(x,y,anchored=false){
  returnFocus=document.activeElement;selection=String(window.getSelection()||'').trim();
  $('[data-tool-selection]').hidden=true;
  menu.querySelector('.feng-music__menu-nav').hidden=true;
  $('[data-tool="quote"]').disabled=!document.querySelector('#comment');
  $('[data-tool-play-label]').textContent=audio.paused?'播放音乐':'暂停音乐';
  for(const key of ['play','prev','next','copy-title'])$(`[data-tool="${key}"]`).disabled=!tracks.length;
  menu.hidden=false;menu.style.left='10px';menu.style.top='10px';
  const r=menu.getBoundingClientRect();if(anchored){const anchor=root.getBoundingClientRect();x=anchor.right-r.width;y=anchor.top-r.height-12;}menu.style.left=Math.max(12,Math.min(x,document.documentElement.clientWidth-r.width-20))+'px';menu.style.top=Math.max(12,Math.min(y,innerHeight-r.height-12))+'px';
  menu.querySelector('button:not(:disabled)').focus({preventScroll:true});
 }
 document.addEventListener('contextmenu',e=>{
  if(e.shiftKey||!e.target.closest('.feng-music__capsule'))return;
  e.preventDefault();openMenu(e.clientX,e.clientY,root.contains(e.target));
 });
 root.addEventListener('keydown',e=>{if(e.key==='ContextMenu'||e.shiftKey&&e.key==='F10'){e.preventDefault();const r=root.getBoundingClientRect();openMenu(r.left,r.top);}});
 document.addEventListener('pointerdown',e=>{if(!menu.contains(e.target))closeMenu();});
 document.addEventListener('keydown',e=>{if(menu.hidden)return;if(e.key==='Escape'){e.preventDefault();closeMenu(true);}if(['ArrowDown','ArrowUp','Home','End'].includes(e.key)){e.preventDefault();const bs=[...menu.querySelectorAll('button:not(:disabled)')].filter(b=>b.getClientRects().length),i=bs.indexOf(document.activeElement);bs[e.key==='Home'?0:e.key==='End'?bs.length-1:(i+(e.key==='ArrowDown'?1:bs.length-1))%bs.length]?.focus();}if(e.key==='Tab')closeMenu();});
 window.addEventListener('resize',()=>closeMenu());document.addEventListener('xf:before-unmount',()=>closeMenu());
 menu.addEventListener('click',e=>{const b=e.target.closest('[data-tool]');if(!b||b.disabled)return;const action=b.dataset.tool;closeMenu(true);
  if(action==='play')toggle();if(action==='prev')choose(index-1,true);if(action==='next')choose(index+1,true);if(action==='playlist')panel(true,true);
  if(action==='back')history.back();if(action==='forward')history.forward();if(action==='reload')location.reload();if(action==='top')window.scrollTo({top:0,behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'instant':'smooth'});
  if(action==='copy-title')copy(tracks[index]?.title||'');if(action==='copy-selection')copy(selection);
  if(action==='search'){const form=document.querySelector('.feng-header-search form'),input=form?.querySelector('input[name="s"]');if(input){input.value=selection;form.requestSubmit();}else location.assign('/?s='+encodeURIComponent(selection));}
  if(action==='web-search')window.open('https://www.baidu.com/s?wd='+encodeURIComponent(selection),'_blank','noopener,noreferrer');
  if(action==='quote'){const field=document.querySelector('#comment');if(field){field.value+=(field.value?'\n\n':'')+selection.split('\n').map(t=>'> '+t).join('\n')+'\n\n';field.dispatchEvent(new Event('input',{bubbles:true}));field.scrollIntoView({block:'center',behavior:'smooth'});field.focus({preventScroll:true});}}
 });
 place();
})();
