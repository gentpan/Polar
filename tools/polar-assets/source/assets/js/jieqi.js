(() => {
 const key='feng:jieqi:disabled';let disabled=false,loading=false;
 try{disabled=localStorage.getItem(key)==='1';}catch{}
 function sync(){document.querySelectorAll('[data-jieqi-toggle]').forEach(button=>{button.setAttribute('aria-pressed',String(!disabled));button.title=disabled?'开启节气与节假日提醒':'关闭节气与节假日提醒';button.querySelector('[data-jieqi-state]').textContent=disabled?'已关闭':'已开启';});}
 function start(){
  if(disabled)return;
  if(window.Jieqi){window.Jieqi.start();return;}
  if(loading)return;loading=true;
  const script=document.createElement('script');script.src='https://api.jieqi.dev/v1/widget.js';script.defer=true;script.dataset.mode='popup';script.dataset.style='stamp';script.id='feng-jieqi-widget';
  script.onload=()=>{loading=false;if(disabled)window.Jieqi?.destroy();};
  script.onerror=()=>{loading=false;script.remove();};document.head.append(script);
 }
 function update(){sync();if(disabled)window.Jieqi?.destroy();else start();}
 document.addEventListener('click',event=>{if(!event.target.closest('[data-jieqi-toggle]'))return;disabled=!disabled;try{localStorage.setItem(key,disabled?'1':'0');}catch{}update();});
 window.addEventListener('storage',event=>{if(event.key===key||event.key===null){disabled=event.key===null?false:event.newValue==='1';update();}});
 document.addEventListener('xf:mounted',sync);
 update();
})();
