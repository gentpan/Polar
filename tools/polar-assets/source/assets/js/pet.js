/* Persistent shared companion. Growth is awarded by WordPress, never from local counters. */
(() => {
 'use strict';
 const fengPet=JSON.parse(document.getElementById('feng-config')?.textContent || '{}').pet;
 const pet=document.querySelector('[data-feng-pet]');if(!pet||!fengPet)return;
 const panel=pet.querySelector('#feng-pet-panel'),toggle=pet.querySelector('[data-pet-toggle]'),status=pet.querySelector('[data-pet-status]'),bubble=pet.querySelector('[data-pet-bubble]');
 const wake=document.createElement('button');wake.type='button';wake.textContent='唤醒宠物';wake.dataset.petWake='';wake.hidden=true;pet.append(wake);
 let snapshot=null,busy=false,chatBusy=false,readTimer=null,epoch=0,popupTimer,refreshTimer;
 const motion=name=>pet.dispatchEvent(new CustomEvent('feng:pet-motion',{detail:name}));
 const chatHistory=[];
 let resting=false;try{resting=sessionStorage.getItem('feng-pet-rest')==='1';}catch{}
 let position=null,drag=null,suppressClick=false;
 try{const saved=JSON.parse(localStorage.getItem('feng-pet-screen-position'));if(saved&&Number.isFinite(saved.x)&&Number.isFinite(saved.y))position=saved;}catch{}
 function face(direction){pet.dataset.facing=direction;}
 function faceSide(){const r=pet.getBoundingClientRect();face(r.left+r.width/2<innerWidth/2?'right':'left');}
 function movePet(x,y){
  const r=pet.getBoundingClientRect();
  position={x:Math.max(8,Math.min(x,innerWidth-r.width-8)),y:Math.max(8,Math.min(y,innerHeight-r.height-8))};
  pet.style.left=position.x+'px';pet.style.top=position.y+'px';
 }
 function fitPanel(){
  if(!position||panel.hidden)return;
  const r=pet.getBoundingClientRect(),w=panel.offsetWidth,h=panel.offsetHeight;
  panel.style.left=Math.max(8,Math.min(r.left,innerWidth-w-8))+'px';
  panel.style.top=Math.max(8,Math.min(r.top-h-12>=8?r.top-h-12:r.bottom+12,innerHeight-h-8))+'px';
 }
 function place(){if(position){document.body.append(pet);pet.classList.remove('is-perched','is-on-music');pet.classList.add('is-free');movePet(position.x,position.y);faceSide();return;}const music=document.querySelector('[data-feng-music]'),slot=music||document.querySelector('[data-feng-pet-slot]');(slot||document.body).append(pet);pet.classList.toggle('is-perched',!!slot&&!music);pet.classList.toggle('is-on-music',!!music);faceSide();}
 const localHidden=()=>pet.hasAttribute('data-hide-mobile')&&matchMedia('(max-width:600px)').matches;
 async function api(op,extra={}){
  const abort=new AbortController(),timeout=setTimeout(()=>abort.abort(),op==='chat'?75000:12000);
  try{
   const response=await fetch(fengPet.url,{method:'POST',credentials:'same-origin',signal:abort.signal,body:new URLSearchParams({action:'feng_pet',nonce:fengPet.nonce,op,...extra})});
   let data;try{data=await response.json();}catch{throw new Error('暂时没连上小窝，稍后再试试。');}
   if(!data.success)throw new Error(data.data?.message||(response.status===403?'页面停留有些久了，请刷新后再来陪我。':'这次互动没有记上，稍后再试试。'));
   return data.data;
  }catch(error){if(error.name==='AbortError')throw new Error('这次等待有点久，稍后再试试吧。');throw error;}finally{clearTimeout(timeout);}
 }
 function render(s){
  snapshot=s;pet.dataset.level=s.level;pet.dataset.growthScale=String(s.form?.scale||1);const formLabel=pet.querySelector('[data-pet-form]');if(formLabel)formLabel.textContent=(s.form?.name||'成长中')+' · 体型随成长值变化';pet.toggleAttribute('data-sleeping',s.sleeping);
  pet.querySelector('[data-pet-stage]').textContent=s.stage+' · Lv.'+s.level;
  pet.querySelector('[data-pet-mood]').textContent=s.mood;
  pet.querySelector('[data-pet-age]').textContent='陪伴第 '+s.age+' 天 · 连续 '+s.streak+' 天有互动';
  pet.querySelector('[data-pet-satiety]').textContent=s.satiety+'%';pet.querySelector('[data-pet-food-meter]').value=s.satiety;pet.querySelector('[data-pet-food-meter]').textContent=s.satiety+'%';
  pet.querySelector('[data-pet-xp]').textContent=s.next?s.xp+' / '+s.next:s.xp+' · 伙伴';pet.querySelector('[data-pet-progress]').value=s.progress;pet.querySelector('[data-pet-progress]').textContent=s.progress+'%';
  const list=pet.querySelector('[data-pet-diary]');list.replaceChildren(...s.diary.map(line=>{const li=document.createElement('li');li.textContent=line;return li;}));
 }
 async function refresh(){const data=await api('state');render(data.state);pet.querySelector('[data-pet-chat-mode]').textContent=data.ai?'AI 陪伴':'日常问答';pet.querySelector('[data-pet-ai-note]').textContent=data.ai?'提问会发送问题、最近三轮对话和宠物公开日记给站长配置的 AI。本站不保存聊天记录。':'日常问答直接根据真实状态回答。';return data;}
 let bubbleFrame=0;
 function positionBubble(){
  if(bubble.hidden){bubbleFrame=0;return;}
  const art=toggle.querySelector('.feng-pet-art')||toggle,r=art.getBoundingClientRect(),host=pet.getBoundingClientRect();
  const mouthX=r.left+r.width*(pet.dataset.facing==='left'?.22:.78),mouthY=r.top+r.height*.48;
  const w=bubble.offsetWidth,h=bubble.offsetHeight;
  const left=Math.max(8,Math.min(mouthX-24,innerWidth-w-8));
  const above=mouthY-h-14>=8;
  const top=above?mouthY-h-14:mouthY+14;
  bubble.style.left=(left-host.left)+'px';bubble.style.top=(top-host.top)+'px';
  bubble.style.setProperty('--pet-tail-x',Math.max(12,Math.min(w-12,mouthX-left))+'px');
  bubble.dataset.direction=above?'above':'below';
  bubbleFrame=requestAnimationFrame(positionBubble);
 }
 function showBubble(message){if(!panel.hidden)return;clearTimeout(popupTimer);cancelAnimationFrame(bubbleFrame);bubble.textContent=message;bubble.hidden=false;positionBubble();popupTimer=setTimeout(()=>{bubble.hidden=true;cancelAnimationFrame(bubbleFrame);bubbleFrame=0;},4500);}
 async function open(){panel.hidden=false;fitPanel();motion('nod');bubble.hidden=true;toggle.setAttribute('aria-expanded','true');pet.querySelector('[data-pet-close]').focus({preventScroll:true});status.textContent='正在看看今天的小窝…';try{await refresh();status.textContent='';}catch(e){status.textContent=e.message;}}
 function close(){panel.hidden=true;toggle.setAttribute('aria-expanded','false');}
 toggle.title='点击互动，按住拖动';
 toggle.addEventListener('pointerdown',e=>{
  if(e.button!==0||!e.isPrimary)return;
  const r=pet.getBoundingClientRect();drag={id:e.pointerId,x:e.clientX,y:e.clientY,left:r.left,top:r.top,moved:false};
  suppressClick=false;toggle.setPointerCapture(e.pointerId);
 });
 toggle.addEventListener('pointermove',e=>{
  if(!drag||drag.id!==e.pointerId)return;
  if(!drag.moved&&Math.hypot(e.clientX-drag.x,e.clientY-drag.y)<6)return;
  if(!drag.moved){drag.moved=true;close();bubble.hidden=true;position={x:drag.left,y:drag.top};place();toggle.setPointerCapture(e.pointerId);pet.classList.add('is-dragging');}
  movePet(drag.left+e.clientX-drag.x,drag.top+e.clientY-drag.y);faceSide();
 });
 function endDrag(e){
  if(!drag||drag.id!==e.pointerId)return;
  suppressClick=drag.moved;drag=null;pet.classList.remove('is-dragging');
  if(toggle.hasPointerCapture(e.pointerId))toggle.releasePointerCapture(e.pointerId);
  if(position)try{localStorage.setItem('feng-pet-screen-position',JSON.stringify(position));}catch{}
  setTimeout(()=>{suppressClick=false;},0);
 }
 toggle.addEventListener('pointerup',endDrag);toggle.addEventListener('pointercancel',endDrag);toggle.addEventListener('lostpointercapture',endDrag);
 toggle.addEventListener('dragstart',e=>e.preventDefault());
 let clickTimer,jumpFrame=0;
 function stopJump(){cancelAnimationFrame(jumpFrame);jumpFrame=0;pet.dispatchEvent(new CustomEvent('feng:pet-flight',{detail:false}));}
 function hopTo(x,y){
  if(resting||drag||pet.hidden||localHidden())return;
  stopJump();close();bubble.hidden=true;
  const r=pet.getBoundingClientRect();position={x:r.left,y:r.top};place();
  const from={...position},to={x:Math.max(8,Math.min(x,innerWidth-pet.offsetWidth-8)),y:Math.max(8,Math.min(y,innerHeight-pet.offsetHeight-8))};
  if(Math.abs(to.x-from.x)>1)face(to.x<from.x?'left':'right');
  const finish=()=>{movePet(to.x,to.y);stopJump();faceSide();try{localStorage.setItem('feng-pet-screen-position',JSON.stringify(position));}catch{}};
  if(matchMedia('(prefers-reduced-motion: reduce)').matches){finish();return;}
  pet.dispatchEvent(new CustomEvent('feng:pet-flight',{detail:true}));
  const start=performance.now();
  function step(now){const t=Math.min(1,(now-start)/1100),ease=t*t*(3-2*t);movePet(from.x+(to.x-from.x)*ease,from.y+(to.y-from.y)*ease-Math.sin(Math.PI*t)*16+Math.sin(t*Math.PI*8)*3*Math.sin(Math.PI*t));if(t<1)jumpFrame=requestAnimationFrame(step);else finish();}
  jumpFrame=requestAnimationFrame(step);
 }
 toggle.addEventListener('click',e=>{if(suppressClick){e.preventDefault();return;}clearTimeout(clickTimer);if(e.detail===0){if(panel.hidden)open();else close();return;}clickTimer=setTimeout(()=>{if(panel.hidden)open();else close();},320);});
 toggle.addEventListener('dblclick',e=>{e.preventDefault();clearTimeout(clickTimer);close();motion('hop');});
 document.addEventListener('dblclick',e=>{
  if(e.defaultPrevented||pet.contains(e.target)||e.target.closest('a,button,input,textarea,select,label,dialog,[contenteditable],[role="button"]')||resting||pet.hidden||localHidden())return;
  const r=toggle.getBoundingClientRect(),cx=r.left+r.width/2,cy=r.top+r.height/2;
  if(!r.width||Math.hypot(e.clientX-cx,e.clientY-cy)>180)return;
  e.preventDefault();clearTimeout(clickTimer);hopTo(e.clientX-r.width/2,e.clientY-r.height/2);
 });
 toggle.addEventListener('pointerdown',()=>{clearTimeout(clickTimer);stopJump();});
 document.addEventListener('xf:before-unmount',()=>{clearTimeout(clickTimer);stopJump();});
 window.addEventListener('pagehide',()=>{clearTimeout(clickTimer);stopJump();});
 const resetPosition=document.createElement('button');resetPosition.type='button';resetPosition.textContent='回到音乐旁';
 panel.querySelector('footer').append(resetPosition);
 resetPosition.addEventListener('click',()=>{position=null;try{localStorage.removeItem('feng-pet-screen-position');}catch{}pet.classList.remove('is-free');pet.style.left='';pet.style.top='';panel.style.left='';panel.style.top='';close();place();});
 window.addEventListener('resize',()=>{if(position){movePet(position.x,position.y);fitPanel();}});
 new ResizeObserver(fitPanel).observe(panel);
 pet.querySelector('[data-pet-close]').addEventListener('click',()=>{close();toggle.focus({preventScroll:true});});
 document.addEventListener('keydown',e=>{if(e.key==='Escape'&&!panel.hidden){close();toggle.focus({preventScroll:true});}});
 document.addEventListener('pointerdown',e=>{if(!pet.contains(e.target))close();});
 function rest(value,focus=false){resting=value;pet.toggleAttribute('data-resting',value);wake.hidden=!value;close();bubble.hidden=true;try{sessionStorage.setItem('feng-pet-rest',value?'1':'0');}catch{}if(value){clearInterval(readTimer);epoch++;}else{startReading();if(focus)toggle.focus({preventScroll:true});}}
 pet.querySelector('[data-pet-hide]').addEventListener('click',()=>rest(true));wake.addEventListener('click',()=>rest(false,true));
 async function interact(action){
  if(busy)return;busy=true;
  pet.querySelectorAll('[data-pet-action]').forEach(b=>b.disabled=true);
  status.textContent='正在记下这次陪伴…';
  try{const data=await api(action);render(data.state);motion(action==='feed'?'feed':'pat');status.textContent=data.message;showBubble(data.message);}
  catch(e){status.textContent=e.message;showBubble(e.message);}
  finally{busy=false;pet.querySelectorAll('[data-pet-action]').forEach(b=>b.disabled=false);}
 }
 pet.querySelectorAll('[data-pet-action]').forEach(button=>button.addEventListener('click',()=>interact(button.dataset.petAction)));
 const petMenu=document.createElement('div');petMenu.className='feng-pet-menu';petMenu.hidden=true;petMenu.setAttribute('role','menu');petMenu.setAttribute('aria-label','小鸟互动');document.body.append(petMenu);
 let quiet=false,greetings=0,lastActivity=Date.now(),lastGreeting=Date.now();
 try{quiet=localStorage.getItem('feng-pet-quiet')==='1';greetings=Number(sessionStorage.getItem('feng-pet-greetings'))||0;}catch{}
 const menuItems=[['feed','喂食'],['pat','摸摸头'],['chat','聊聊天'],['home','回到音乐旁'],['quiet',''],['rest','让小鸟休息']];
 for(const [key,label] of menuItems){const b=document.createElement('button');b.type='button';b.dataset.petMenuAction=key;b.setAttribute('role','menuitem');b.textContent=label;petMenu.append(b);}
 const quietButton=petMenu.querySelector('[data-pet-menu-action="quiet"]');
 function closePetMenu(){petMenu.hidden=true;toggle.setAttribute('aria-expanded',String(!panel.hidden));}
 function openPetMenu(x,y){
  close();bubble.hidden=true;quietButton.textContent=quiet?'开启闲时问候':'关闭闲时问候';petMenu.hidden=false;
  petMenu.style.left=Math.max(8,Math.min(x,innerWidth-petMenu.offsetWidth-8))+'px';petMenu.style.top=Math.max(8,Math.min(y,innerHeight-petMenu.offsetHeight-8))+'px';
  toggle.setAttribute('aria-expanded','true');petMenu.querySelector('button').focus({preventScroll:true});
 }
 toggle.addEventListener('contextmenu',e=>{if(e.shiftKey)return;e.preventDefault();e.stopPropagation();const r=toggle.getBoundingClientRect();openPetMenu(e.clientX||r.left,e.clientY||r.top);});
 petMenu.addEventListener('click',e=>{
  const b=e.target.closest('[data-pet-menu-action]');if(!b)return;
  closePetMenu();toggle.focus({preventScroll:true});lastActivity=Date.now();
  const action=b.dataset.petMenuAction;
  if(action==='feed'||action==='pat')interact(action);
  if(action==='chat')open();
  if(action==='home')resetPosition.click();
  if(action==='rest')rest(true);
  if(action==='quiet'){quiet=!quiet;try{localStorage.setItem('feng-pet-quiet',quiet?'1':'0');}catch{}showBubble(quiet?'好，我安静地陪着你。':'好呀，闲下来时我们聊两句。');}
 });
 document.addEventListener('pointerdown',e=>{lastActivity=Date.now();if(!petMenu.contains(e.target))closePetMenu();});
 document.addEventListener('keydown',e=>{
  lastActivity=Date.now();if(petMenu.hidden)return;
  if(e.key==='Escape'){closePetMenu();toggle.focus({preventScroll:true});}
  if(e.key==='Tab')closePetMenu();
  if(['ArrowDown','ArrowUp','Home','End'].includes(e.key)){e.preventDefault();const buttons=[...petMenu.querySelectorAll('button')],i=buttons.indexOf(document.activeElement);buttons[e.key==='Home'?0:e.key==='End'?buttons.length-1:(i+(e.key==='ArrowDown'?1:buttons.length-1))%buttons.length].focus();}
 });
 window.addEventListener('scroll',()=>{lastActivity=Date.now();closePetMenu();if(position){close();bubble.hidden=true;}},{passive:true});window.addEventListener('resize',closePetMenu);
 document.addEventListener('xf:before-unmount',closePetMenu);
 document.addEventListener('visibilitychange',()=>{closePetMenu();lastActivity=Date.now();});
 let welcomeTimer;
 document.addEventListener('feng:greeting',e=>{
  const message=e.detail?.message;if(typeof message!=='string'||!message)return;
  clearTimeout(welcomeTimer);welcomeTimer=setTimeout(()=>{
   let greeted=false;try{greeted=sessionStorage.getItem('feng-pet-welcomed')==='1';}catch{}
   const r=toggle.getBoundingClientRect();
   if(greeted||quiet||resting||pet.hasAttribute('data-theme-sleeping')||document.hidden||localHidden()||drag||!panel.hidden||!petMenu.hidden||!bubble.hidden||r.width===0||r.bottom<0||r.top>innerHeight)return;
   showBubble(message.slice(0,160));motion('wave');lastGreeting=Date.now();
   try{sessionStorage.setItem('feng-pet-welcomed','1');}catch{}
  },1800);
 });
 document.addEventListener('xf:before-unmount',()=>clearTimeout(welcomeTimer));
 setInterval(()=>{
  const now=Date.now(),r=toggle.getBoundingClientRect();
  if(quiet||pet.hasAttribute('data-theme-sleeping')||greetings>=3||document.hidden||resting||localHidden()||drag||busy||chatBusy||!panel.hidden||!petMenu.hidden||!bubble.hidden||now-lastActivity<45000||now-lastGreeting<180000||r.width===0||r.bottom<0||r.top>innerHeight||document.activeElement?.closest('input,textarea,[contenteditable="true"]'))return;
  const hour=Number(new Intl.DateTimeFormat('en-GB',{hour:'2-digit',hourCycle:'h23'}).format(new Date()));
  const lines=['你那边今天天气怎么样？有没有适合散步的风？','看累了可以歇一会儿，我帮你守着这里。','今天有没有遇到一件让你开心的小事？'];
  if(hour<6||hour>=23)lines.unshift('夜深啦，记得早点休息。我会在这里等你。');
  else if(hour<11)lines.unshift('早上好呀，愿今天有个轻快的开始。');
  else if(hour>=18)lines.unshift('晚上好，忙完一天，来这里放松一下吧。');
  else lines.unshift('午后好呀，记得喝口水，活动一下肩膀。');
  if(snapshot?.satiety<35)lines.unshift('肚子有点空空的，右键点我可以喂一点小点心。');
  showBubble(lines[greetings%lines.length]);motion('wave');greetings++;lastGreeting=now;
  try{sessionStorage.setItem('feng-pet-greetings',String(greetings));}catch{}
 },15000);
 const conversation=pet.querySelector('[data-pet-conversation]'),question=pet.querySelector('#feng-pet-question'),form=pet.querySelector('[data-pet-chat-form]');
 function say(text,visitor=false){const p=document.createElement('p');p.textContent=text;if(visitor)p.className='is-visitor';conversation.append(p);while(conversation.children.length>12)conversation.firstElementChild.remove();conversation.scrollTop=conversation.scrollHeight;}
 async function chat(text){
  if(chatBusy||!text.trim())return;chatBusy=true;form.querySelector('button').disabled=true;say(text,true);question.value='';status.textContent='让我想一想…';motion('think');
  try{const data=await api('chat',{question:text.trim(),history:JSON.stringify(chatHistory.slice(-6))});render(data.state);say(data.reply);motion('talk');chatHistory.push({role:'user',content:text.trim()},{role:'assistant',content:data.reply});if(chatHistory.length>6)chatHistory.splice(0,chatHistory.length-6);pet.querySelector('[data-pet-chat-mode]').textContent=data.mode==='ai'?'AI 陪伴':'日常问答';status.textContent=data.note||'';}
  catch(e){status.textContent=e.message;}finally{chatBusy=false;form.querySelector('button').disabled=false;}
 }
 form.addEventListener('submit',e=>{e.preventDefault();chat(question.value);});pet.querySelectorAll('[data-pet-question]').forEach(b=>b.addEventListener('click',()=>chat(b.dataset.petQuestion)));
 async function startReading(){
  clearInterval(readTimer);const mine=++epoch;if(resting||localHidden())return;
  const article=document.querySelector('[data-feng-pet-post]'),id=Number(article?.dataset.fengPetPost||0);if(!id)return;
  let data;try{data=await api('state',{post_id:id});}catch{return;}if(mine!==epoch||!data.ticket)return;render(data.state);
  let visibleSeconds=0;
  readTimer=setInterval(async()=>{
   if(mine!==epoch||resting){clearInterval(readTimer);return;}
   if(document.hidden||localHidden())return;
   visibleSeconds++;
   const body=article.querySelector('#xf-article-text'),r=body?.getBoundingClientRect();
   if(visibleSeconds<25||!r||innerHeight<r.top+Math.min(240,r.height/3))return;
   clearInterval(readTimer);
   try{const result=await api('read',{post_id:id,ticket:data.ticket});render(result.state);showBubble(result.message);}catch{/* No interrupting a reader for duplicate or capped growth. */}
  },1000);
 }
 document.addEventListener('xf:before-unmount',()=>{clearInterval(readTimer);epoch++;close();bubble.hidden=true;document.body.append(pet);pet.classList.remove('is-perched');});document.addEventListener('xf:mounted',()=>{place();startReading();});
 document.addEventListener('feng:comment-added',()=>refresh().catch(()=>{}));
 document.addEventListener('visibilitychange',()=>{pet.toggleAttribute('data-page-hidden',document.hidden);if(!document.hidden&&!panel.hidden)refresh().catch(()=>{});});
 refreshTimer=setInterval(()=>{if(!panel.hidden&&!document.hidden&&!resting)refresh().catch(()=>{});},60000);
 window.addEventListener('pagehide',()=>{clearInterval(readTimer);clearInterval(refreshTimer);});
 window.addEventListener('pageshow',e=>{if(e.persisted){startReading();refreshTimer=setInterval(()=>{if(!panel.hidden&&!document.hidden&&!resting)refresh().catch(()=>{});},60000);}});
 pet.hidden=false;place();rest(resting);if(!resting&&!localHidden())refresh().catch(()=>{});
})();
