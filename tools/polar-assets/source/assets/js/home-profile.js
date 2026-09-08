(() => {
 let opener;
 let stackEpoch=0;
 const delay=ms=>new Promise(resolve=>setTimeout(resolve,ms));
 function loaded(img){return new Promise(resolve=>{
  if(img.complete&&img.naturalWidth){resolve(true);return;}
  let timer;const done=()=>{clearTimeout(timer);img.removeEventListener('load',done);img.removeEventListener('error',done);resolve(img.naturalWidth>0);};
  img.addEventListener('load',done);img.addEventListener('error',done);timer=setTimeout(done,12000);
 });}
 async function revealStack(){
  const epoch=++stackEpoch,stack=document.querySelector('[data-stack-loading]');if(!stack)return;
  const front=stack.querySelector('.feng-stack-front');await loaded(front);
  if(epoch!==stackEpoch||!stack.isConnected)return;
  try{await front.decode();}catch{}
  stack.classList.add('is-front-ready');
  const reduced=matchMedia('(prefers-reduced-motion:reduce)').matches;
  await delay(reduced?0:250);
  for(const corner of [0,1,3,2]){
   if(epoch!==stackEpoch||!stack.isConnected)return;
   const piece=stack.querySelector(`[data-corner="${corner}"]`),img=piece?.querySelector('img');if(!img)continue;
   img.src=img.dataset.stackSrc;const ok=await loaded(img);
   if(epoch!==stackEpoch||!stack.isConnected)return;
   if(ok){try{await img.decode();}catch{}piece.classList.add('is-photo-ready');await delay(reduced?0:200);}
  }
 }
 document.addEventListener('xf:before-unmount',()=>{stackEpoch++;});
 document.addEventListener('xf:mounted',revealStack);revealStack();
 document.addEventListener('click',e=>{
  const hero=e.target.closest('.feng-profile-hero');if(!hero)return;
  const open=e.target.closest('[data-profile-open]'), day=e.target.closest('[data-profile-day]'), photo=e.target.closest('[data-profile-photo]'), close=e.target.closest('[data-profile-close]');
  if(open||day){opener=open||day;const dialog=hero.querySelector(day?'[data-profile-calendar-dialog]':'[data-profile-dialog]');
   if(day){const date=day.dataset.profileDay;dialog.querySelector('[data-profile-date]').textContent=date;let found=false;dialog.querySelectorAll('[data-profile-records]').forEach(list=>{list.hidden=list.dataset.profileRecords!==date;if(!list.hidden)found=true;});dialog.querySelector('[data-profile-empty]').hidden=found;}
   dialog.showModal();
  }
  if(photo){const img=hero.querySelector('[data-profile-large]');img.src=photo.dataset.profilePhoto;img.hidden=false;}
  if(close){close.closest('dialog').close();opener?.focus({preventScroll:true});}
  if(e.target.matches('dialog')){const r=e.target.getBoundingClientRect();if(e.clientX<r.left||e.clientX>r.right||e.clientY<r.top||e.clientY>r.bottom)e.target.close();}
 });
 document.addEventListener('xf:before-unmount',()=>document.querySelectorAll('.feng-profile-dialog[open]').forEach(d=>d.close()));
})();

