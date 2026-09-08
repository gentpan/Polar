function fengGameRankAvatar(row,score){const img=document.createElement('img');img.className='feng-rank-avatar';img.alt='';img.src=score.avatar||'https://www.gravatar.com/avatar/?d=mp&s=64';row.prepend(img);}
/* Capture also handles images added by subscription AJAX or PJAX. */
(() => {
 const recover=img=>{if(!img.matches?.('img[data-friend-fallback]'))return;const fallback=img.dataset.friendFallback;if(img.getAttribute('src')!==fallback){img.src=fallback;}else{img.hidden=true;const initial=img.nextElementSibling;if(initial?.classList.contains('feng-friend-avatar-initial'))initial.hidden=false;}};
 document.addEventListener('error',e=>recover(e.target),true);
 const check=()=>document.querySelectorAll('img[data-friend-fallback]').forEach(img=>{if(img.complete&&!img.naturalWidth)recover(img);});
 check();document.addEventListener('xf:mounted',check);
})();

/* Native dialog keeps keyboard focus within the application form. */
(() => {
 let opener=null;const copyTimers=new Map();
 const close=()=>{document.querySelectorAll('#friend-application[open],#friend-site-info[open],#friend-game-ranks[open]').forEach(dialog=>dialog.close());};
 document.addEventListener('click',async event=>{
  const mode=event.target.closest('[data-friend-mode]');
  if(mode){const dialog=mode.closest('dialog');const editing=mode.dataset.friendMode==='edit';dialog.querySelector('[data-friend-verification]').hidden=!editing;const code=dialog.querySelector('[name="email_code"]');code.required=editing;code.disabled=!editing;dialog.querySelector('[name="friend_mode"]').value=mode.dataset.friendMode;dialog.querySelectorAll('[data-friend-mode]').forEach(button=>button.setAttribute('aria-pressed',String(button===mode)));dialog.querySelector('[type="submit"]').textContent=mode.dataset.friendMode==='edit'?'提交修改申请':'提交申请';return;}
  const send=event.target.closest('[data-friend-send-code]');
  if(send){
   const form=send.closest('form'),email=form.elements.site_email;if(!email.reportValidity())return;
   send.disabled=true;const status=form.querySelector('[data-friend-code-status]');status.textContent='正在发送…';
   const body=new FormData();body.set('action','feng_friend_send_code');body.set('email',email.value);body.set('friend_nonce',form.elements.friend_nonce.value);
   try{const response=await fetch(send.dataset.endpoint,{method:'POST',body,credentials:'same-origin'});const result=await response.json();if(!result.success)throw new Error(result.data?.message||'发送失败');form.elements.email_token.value=result.data.token;status.textContent=result.data.message;let left=60;send.textContent=left+' 秒后重发';const timer=setInterval(()=>{if(!send.isConnected||--left<=0){clearInterval(timer);send.disabled=false;send.textContent='发送验证码';}else send.textContent=left+' 秒后重发';},1000);}catch(error){status.textContent=error.message;send.disabled=false;}
   return;
  }
  const info=event.target.closest('[data-friend-info-open]');if(info){opener=info;document.querySelector('#friend-site-info')?.showModal();return;}
  const open=event.target.closest('[data-friend-open]');
  if(open){opener=open;document.querySelector('#friend-application')?.showModal();return;}
  if(event.target.closest('[data-friend-close]')){close();opener?.focus();return;}
  if(event.target.matches('dialog#friend-application,dialog#friend-site-info,dialog#friend-game-ranks')){const r=event.target.getBoundingClientRect();if(event.clientX<r.left||event.clientX>r.right||event.clientY<r.top||event.clientY>r.bottom)close();}
  const copy=event.target.closest('[data-friend-copy]');if(!copy)return;
  const value=copy.dataset.friendCopy;
  let success=false;
  try{await navigator.clipboard.writeText(value);success=true;}catch{
   const field=document.createElement('textarea');field.value=value;field.style.cssText='position:fixed;top:0;left:-9999px';(copy.closest('dialog')||document.body).append(field);field.select();try{success=document.execCommand('copy');}catch{}field.remove();copy.focus();
  }
  window.fengToast?.(success?'复制成功':'复制失败，请手动选择复制',success?'success':'error');
  if(success){
   if(!copy.dataset.copyOriginal)copy.dataset.copyOriginal=copy.innerHTML;
   clearTimeout(copyTimers.get(copy));
   copy.innerHTML='<i class="fa-solid fa-check" aria-hidden="true"></i>';
   copy.classList.add('is-copied');
   copyTimers.set(copy,setTimeout(()=>{copy.innerHTML=copy.dataset.copyOriginal;copy.classList.remove('is-copied');copyTimers.delete(copy);},1800));
  }
 });
 document.addEventListener('xf:before-unmount',()=>{close();opener=null;copyTimers.forEach(clearTimeout);copyTimers.clear();});
})();

(() => {
 let expanded=null;
 const position=cell=>{const grid=cell.closest('.feng-friends');if(!grid)return;cell.classList.toggle('opens-left',cell.getBoundingClientRect().left+280>Math.min(grid.getBoundingClientRect().right,innerWidth-12));};
 document.addEventListener('pointerover',event=>{const cell=event.target.closest('.feng-friend-cell');if(cell){position(cell);if(event.pointerType!=='touch'&&document.activeElement?.closest('.feng-friend-cell')!==cell)document.activeElement?.closest('.feng-friend-tile')?.blur();}});
 document.addEventListener('focusin',event=>{const cell=event.target.closest('.feng-friend-cell');if(cell)position(cell);});
 document.addEventListener('click',event=>{
  const cell=event.target.closest('.feng-friend-cell');
  if(!cell){expanded?.classList.remove('is-expanded');expanded=null;return;}
  if(!matchMedia('(hover: none)').matches&&event.pointerType!=='touch')return;
  if(expanded===cell)return;
  event.preventDefault();expanded?.classList.remove('is-expanded');position(cell);cell.classList.add('is-expanded');expanded=cell;
 });
 document.addEventListener('keydown',event=>{if(event.key==='Escape'){expanded?.classList.remove('is-expanded');expanded=null;document.activeElement?.closest('.feng-friend-tile')?.blur();}});
 document.addEventListener('xf:before-unmount',()=>{expanded=null;});
})();

document.addEventListener('pointerout',event=>{if(event.pointerType==='touch')return;const avatar=event.target.closest?.('.feng-friend-tile .feng-friend__avatar');if(avatar&&!avatar.contains(event.relatedTarget)){const tile=avatar.closest('.feng-friend-tile');if(tile===document.activeElement)tile.blur();}});

/* Optional memory game, scoped to the current PJAX page. */
(() => {
 let dispose=()=>{};
 function mount(){
  dispose();const root=document.querySelector('[data-friend-game]');if(!root)return;
  const life=new AbortController(),q=s=>root.querySelector(s),wall=q('[data-game-wall]'),area=q('[data-game-area]'),board=q('[data-game-board]'),result=q('[data-game-result]'),start=q('[data-game-start]'),exit=q('[data-game-exit]');
  const friends=[...wall.querySelectorAll('[data-game-friend]')];let first=null,locked=false,pairs=0,moves=0,total=0,started=0,clock=0,delay=0,generation=0,roundToken="",flips=[],rankData={scores:[]};
  const shuffle=arr=>{for(let i=arr.length-1;i>0;i--){const j=Math.floor(Math.random()*(i+1));[arr[i],arr[j]]=[arr[j],arr[i]];}return arr;};
  const time=()=>{const seconds=started?Math.floor((Date.now()-started)/1000):0;q('[data-game-time]').textContent=String(Math.floor(seconds/60)).padStart(2,'0')+':'+String(seconds%60).padStart(2,'0');};
  const stop=()=>{clearInterval(clock);clearTimeout(delay);generation++;};
  const stats=()=>{q('[data-game-moves]').textContent='翻牌 '+moves+' 次';q('[data-game-score]').textContent='配对 '+pairs+' / '+total;};
  const reveal=(button,show)=>{button.classList.toggle('is-open',show);button.setAttribute('aria-label',show?button.dataset.name:'未翻开的卡片 '+button.dataset.position);button.setAttribute('aria-pressed',String(show));};
  const api=async(op,values={})=>{const body=new URLSearchParams({action:'feng_friend_game',nonce:root.dataset.nonce,op,size:q('[data-game-size]').value,...values});const response=await fetch(root.dataset.endpoint,{method:'POST',body,credentials:'same-origin',signal:life.signal});const data=await response.json();if(!data.success)throw new Error(data.data?.message||'成绩服务暂不可用');return data.data;};
  const ranks=data=>{rankData=data;const list=q('[data-game-ranking]');list.replaceChildren();[...data.scores].sort((a,b)=>q('[data-game-sort]').value==='time'?(a.seconds-b.seconds||a.moves-b.moves):(a.moves-b.moves||a.seconds-b.seconds)).slice(0,10).forEach((score,index)=>{const li=document.createElement('li');const name=document.createElement('strong');name.textContent=(index+1)+'. '+score.name;const value=document.createElement('span');value.textContent=score.moves+' 次翻牌 · '+score.seconds+' 秒';li.append(name,value);fengGameRankAvatar(li,score);list.append(li);});if(!data.scores.length)q('[data-game-rank-status]').textContent='还没有成绩，来成为第一位吧。';else q('[data-game-rank-status]').textContent='';};
  api('list').then(ranks).catch(()=>{q('[data-game-rank-status]').textContent='排行榜暂时无法加载。';});
  const celebrate=()=>{if(matchMedia('(prefers-reduced-motion: reduce)').matches)return;const host=document.createElement('div');host.className='feng-game-confetti';host.setAttribute('aria-hidden','true');for(let i=0;i<42;i++){const piece=document.createElement('i');piece.style.cssText='left:'+Math.random()*100+'%;--drift:'+(Math.random()*200-100)+'px;background:'+['#3979ee','#ffc857','#31bf84','#ed78a1'][i%4]+';animation-delay:'+Math.random()*.5+'s';host.append(piece);}root.append(host);host.addEventListener('animationend',()=>host.remove(),{once:true});};
  async function begin(){
   stop();root.querySelector('.feng-game-confetti')?.remove();result.classList.remove('is-win');roundToken='';flips=[];first=null;locked=false;pairs=0;moves=0;started=0;board.replaceChildren();
   q('[data-game-friends]').replaceChildren();const size=Number(q('[data-game-size]').value);board.style.setProperty('--game-columns',size===64?8:4);board.classList.toggle('is-large',size===64);
   const chosen=shuffle([...friends]).slice(0,size/2);total=chosen.length;if(total<2){result.textContent='至少需要两位朋友才能开始。';area.hidden=false;return;}
   wall.hidden=true;area.hidden=false;exit.hidden=false;start.textContent='再来一局';result.textContent='点击任意卡片开始计时。';time();stats();
   let deck=shuffle(chosen.flatMap((friend,id)=>[{friend,id},{friend,id}]));
   if(total===size/2){start.disabled=true;const round=generation;try{const data=await api('start');if(round!==generation)return;roundToken=data.token;deck=data.deck.map(id=>({friend:chosen[id],id}));}catch{result.textContent='本局可正常游玩，成绩暂不计入排行榜。';}finally{start.disabled=false;}}
   deck.forEach(({friend,id},index)=>{
    const button=document.createElement('button');button.type='button';button.className='feng-game-card';button.dataset.pair=id;button.dataset.name=friend.dataset.name;button.dataset.position=index+1;button.setAttribute('aria-label','未翻开的卡片 '+(index+1));button.setAttribute('aria-pressed','false');
    const back=document.createElement('span');back.className='feng-game-card-back';back.textContent='✦';back.setAttribute('aria-hidden','true');const face=document.createElement('span');face.className='feng-game-card-face';face.setAttribute('aria-hidden','true');face.innerHTML=friend.innerHTML;button.append(back,face);
    button.addEventListener('click',()=>{
     if(locked||button.classList.contains('is-open')||button.classList.contains('is-matched'))return;
     if(!started){started=Date.now();clock=setInterval(time,1000);}moves++;flips.push(index);stats();reveal(button,true);
     if(!first){first=button;return;}
     const prior=first;first=null;
     if(prior.dataset.pair===button.dataset.pair){
      const chip=document.createElement('a');chip.className='feng-friend-chip';chip.href=friend.href;chip.target='_blank';chip.rel='noopener noreferrer';const avatar=document.createElement('span');avatar.className='feng-friend-chip-avatar';avatar.innerHTML=friend.innerHTML;const label=document.createElement('strong');label.textContent=friend.dataset.name;chip.append(avatar,label);q('[data-game-friends]').append(chip);
      pairs++;[prior,button].forEach(b=>{b.classList.add('is-matched');b.setAttribute('aria-disabled','true');});stats();
      result.replaceChildren();const title=document.createElement('strong');title.textContent=friend.dataset.name;const desc=document.createElement('p');desc.textContent=friend.dataset.description||'这位朋友也在记录自己的生活。';const link=document.createElement('a');link.href=friend.href;link.target='_blank';link.rel='noopener noreferrer';link.textContent='访问博客 ↗';result.append(title,desc,link);
      if(pairs===total){clearInterval(clock);time();const done=document.createElement('p');done.textContent='全部配对完成！用时 '+q('[data-game-time]').textContent+'，翻牌 '+moves+' 次。';result.prepend(done);result.classList.add('is-win');celebrate();if(roundToken){api('score',{token:roundToken,flips:JSON.stringify(flips)}).then(ranks).catch(()=>{q('[data-game-rank-status]').textContent='本局已完成，但成绩未能保存。';});}}
     }else{locked=true;const round=generation;delay=setTimeout(()=>{if(round!==generation)return;reveal(prior,false);reveal(button,false);locked=false;},850);}
    },{signal:life.signal});board.append(button);
   });
  }
  q('[data-game-size] option[value="64"]').disabled=friends.length<32;
  q('[data-game-sort]').addEventListener('change',()=>ranks(rankData),{signal:life.signal});
  q('[data-game-size]').addEventListener('change',()=>{stop();board.replaceChildren();area.hidden=true;wall.hidden=false;exit.hidden=true;start.textContent='玩一下';api('list').then(ranks).catch(()=>{});},{signal:life.signal});
  start.disabled=friends.length<2;if(start.disabled)start.title='至少需要两位朋友才能开始';
  start.addEventListener('click',begin,{signal:life.signal});exit.addEventListener('click',()=>{stop();board.replaceChildren();area.hidden=true;wall.hidden=false;exit.hidden=true;start.textContent='玩一下';start.focus();},{signal:life.signal});
  dispose=()=>{stop();life.abort();};
 }
 document.addEventListener('xf:mounted',mount);document.addEventListener('xf:before-unmount',()=>dispose());if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',mount,{once:true});else mount();
})();

(() => {
 let request=null,opener=null;
 async function load(){
  request?.abort();const controller=new AbortController();request=controller;
  const dialog=document.querySelector('#friend-game-ranks'),game=document.querySelector('[data-friend-game]');if(!dialog||!game)return;
  const status=dialog.querySelector('[data-rank-modal-status]'),list=dialog.querySelector('ol');status.textContent='正在加载…';list.replaceChildren();
  try{const body=new URLSearchParams({action:'feng_friend_game',nonce:game.dataset.nonce,op:dialog.querySelector('[data-rank-size]').value==='match'?'match_list':'list',size:dialog.querySelector('[data-rank-size]').value});const response=await fetch(game.dataset.endpoint,{method:'POST',body,credentials:'same-origin',signal:controller.signal});const data=await response.json();if(!data.success)throw Error();const match=dialog.querySelector('[data-rank-size]').value==='match';dialog.querySelector('[data-rank-order]').disabled=match;const time=dialog.querySelector('[data-rank-order]').value==='time';data.data.scores.sort((a,b)=>match?(b.points-a.points||a.seconds-b.seconds):time?(a.seconds-b.seconds||a.moves-b.moves):(a.moves-b.moves||a.seconds-b.seconds)).slice(0,10).forEach((score,index)=>{const row=document.createElement('li'),name=document.createElement('strong'),value=document.createElement('span');name.textContent=(index+1)+'. '+score.name;value.textContent=(match?score.points+' 分':score.moves+' 次翻牌')+' · '+score.seconds+' 秒';row.append(name,value);fengGameRankAvatar(row,score);list.append(row);});status.textContent=list.children.length?'休闲榜单 · 昵称来自登录信息或评论记录':'还没有成绩，来挑战一下吧。';}catch(error){if(error.name!=='AbortError')status.textContent='排行榜加载失败，请稍后重试。';}
 }
 document.addEventListener('click',event=>{const button=event.target.closest('[data-game-rank-open]');if(button){opener=button;const dialog=document.querySelector('#friend-game-ranks');dialog.showModal();dialog.addEventListener('close',()=>opener?.focus(),{once:true});load();}});
 document.addEventListener('change',event=>{if(event.target.matches('[data-rank-size],[data-rank-order]'))load();});
 document.addEventListener('xf:before-unmount',()=>{request?.abort();opener=null;});
})();

/* Emoji match-three: independent of the memory-game rankings. */
(() => {
 let cleanup=()=>{};
 function mount(){cleanup();const root=document.querySelector('[data-emoji-game]');if(!root)return;
 const life=new AbortController(),q=s=>root.querySelector(s),board=q('[data-emoji-board]'),status=q('[data-emoji-status]');
 const pool=[...document.querySelectorAll('[data-game-friend]')];let portraits=[],names=[];let cells=[],selected=-1,score=0,moves=30,busy=false,run=0,matchToken="";
 const game=document.querySelector('[data-friend-game]');
 const matchApi=async(op,extra={})=>{const response=await fetch(game.dataset.endpoint,{method:'POST',credentials:'same-origin',signal:life.signal,body:new URLSearchParams({action:'feng_friend_game',nonce:game.dataset.nonce,op,...extra})});const result=await response.json();if(!result.success)throw Error();return result.data;};
 const renderRanks=data=>{const list=q('[data-match-ranking]');list.replaceChildren();data.scores.forEach((entry,index)=>{const li=document.createElement('li'),name=document.createElement('strong'),value=document.createElement('span');name.textContent=(index+1)+'. '+entry.name;value.textContent=entry.points+' 分 · '+entry.seconds+' 秒';li.append(name,value);fengGameRankAvatar(li,entry);list.append(li);});q('[data-match-rank-status]').textContent=data.scores.length?'':'还没有成绩，完成一局即可参与。';};
 matchApi('match_list').then(renderRanks).catch(()=>{});
 matchApi('player').then(player=>{const host=document.querySelector('[data-game-player]');if(!host)return;const img=document.createElement('img');img.src=player.avatar;img.alt='';const name=document.createElement('span');name.textContent='玩家：'+player.name;host.replaceChildren(img,name);}).catch(()=>{});
 const random=()=>Math.floor(Math.random()*portraits.length),adjacent=(a,b)=>Math.abs(a%8-b%8)+Math.abs(Math.floor(a/8)-Math.floor(b/8))===1;
 const matches=()=>{const set=new Set();for(let r=0;r<8;r++)for(let c=0;c<8;c++){const i=r*8+c,v=cells[i];if(v<0)continue;if(c<6&&v===cells[i+1]&&v===cells[i+2]){let x=c;while(x<8&&cells[r*8+x]===v)set.add(r*8+x++);}if(r<6&&v===cells[i+8]&&v===cells[i+16]){let y=r;while(y<8&&cells[y*8+c]===v)set.add(y++*8+c);}}return set;};
 const swap=(a,b)=>{[cells[a],cells[b]]=[cells[b],cells[a]];};
 const possible=()=>{for(let i=0;i<64;i++)for(const j of [i%8<7?i+1:-1,i+8<64?i+8:-1]){if(j<0)continue;swap(i,j);const found=matches().size;swap(i,j);if(found)return true;}return false;};
 const fresh=()=>{do{cells=[];for(let i=0;i<64;i++){let v;do{v=random();}while(i%8>1&&cells[i-1]===v&&cells[i-2]===v||i>=16&&cells[i-8]===v&&cells[i-16]===v);cells.push(v);}}while(!possible());};
 const paint=()=>{[...board.children].forEach((button,i)=>{button.replaceChildren();if(cells[i]>=0){const portrait=portraits[cells[i]].cloneNode(true);portrait.setAttribute('aria-hidden','true');button.append(portrait);}button.classList.toggle('is-selected',i===selected);button.setAttribute('aria-pressed',String(i===selected));button.setAttribute('aria-label',(Math.floor(i/8)+1)+' 行 '+(i%8+1)+' 列 '+(names[cells[i]]||'消除中'));button.disabled=busy||moves===0;});q('[data-emoji-score]').textContent=score+' 分';q('[data-emoji-moves]').textContent='剩余 '+moves+' 步';};
 const pause=()=>new Promise(resolve=>setTimeout(resolve,matchMedia('(prefers-reduced-motion: reduce)').matches?0:220));
 async function choose(i){if(busy||moves===0)return;if(selected===i){selected=-1;paint();return;}if(selected<0||!adjacent(selected,i)){selected=i;paint();return;}
 const a=selected;selected=-1;swap(a,i);let hit=matches();if(!hit.size){swap(a,i);status.textContent='这次没有凑成三个，换一组试试。';paint();return;}
 busy=true;moves--;const id=run;let combo=0;
 while(hit.size){combo++;score+=hit.size*10*combo;hit.forEach(index=>cells[index]=-1);status.textContent=combo>1?'连消 ×'+combo+'！':'消除了 '+hit.size+' 个头像';paint();await pause();if(id!==run||life.signal.aborted)return;
 for(let c=0;c<8;c++){const column=[];for(let r=7;r>=0;r--)if(cells[r*8+c]>=0)column.push(cells[r*8+c]);for(let r=7;r>=0;r--)cells[r*8+c]=column[7-r]??random();}paint();await pause();if(id!==run||life.signal.aborted)return;hit=matches();}
 if(moves===0){if(matchToken){matchApi('match_score',{token:matchToken,points:String(score),moves:'30'}).then(renderRanks).catch(()=>{q('[data-match-rank-status]').textContent='本局已完成，成绩未能保存。';});matchToken='';}status.textContent=(score>=2000?'挑战成功！🎉':'本局完成！')+' 共获得 '+score+' 分。点击重新开始再挑战。';}else if(!possible()){fresh();status.textContent='没有可消除的组合，已自动换一盘，不扣步数。';}busy=false;paint();
 }
 for(let i=0;i<64;i++){const button=document.createElement('button');button.type='button';button.addEventListener('click',()=>choose(i),{signal:life.signal});button.addEventListener('keydown',event=>{const offsets={ArrowLeft:-1,ArrowRight:1,ArrowUp:-8,ArrowDown:8};const next=i+offsets[event.key];if(offsets[event.key]&&next>=0&&next<64&&adjacent(i,next)){event.preventDefault();board.children[next].focus();}},{signal:life.signal});board.append(button);}
 const restart=()=>{run++;matchToken="";const version=run;matchApi('match_start').then(data=>{if(version===run)matchToken=data.token;}).catch(()=>{});busy=false;score=0;moves=30;selected=-1;
 const candidates=[...pool];for(let i=candidates.length-1;i>0;i--){const j=Math.floor(Math.random()*(i+1));[candidates[i],candidates[j]]=[candidates[j],candidates[i]];}
 const chosen=candidates.slice(0,6);if(chosen.length<3){status.textContent='至少需要 3 位博友才能开始头像消消乐。';board.hidden=true;return;}
 board.hidden=false;names=chosen.map(friend=>friend.dataset.name);portraits=chosen.map(friend=>{const host=document.createElement('span');host.className='feng-match-avatar';host.innerHTML=friend.innerHTML;return host;});fresh();paint();status.textContent='本局使用 '+chosen.length+' 位博友的头像，选择两个相邻头像开始消除。';};restart();q('[data-emoji-restart]').addEventListener('click',restart,{signal:life.signal});
 document.querySelectorAll('[data-game-type]').forEach(button=>button.addEventListener('click',()=>{const emoji=button.dataset.gameType==='emoji';root.hidden=!emoji;document.querySelector('[data-friend-game]').hidden=emoji;document.querySelectorAll('[data-game-type]').forEach(tab=>tab.setAttribute('aria-pressed',String(tab===button)));},{signal:life.signal}));
 cleanup=()=>{run++;life.abort();board.replaceChildren();};
 }
 document.addEventListener('xf:mounted',mount);document.addEventListener('xf:before-unmount',()=>cleanup());if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',mount,{once:true});else mount();
})();
