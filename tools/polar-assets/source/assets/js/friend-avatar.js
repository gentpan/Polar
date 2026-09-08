/* Capture also handles images added by subscription AJAX or PJAX. */
(() => {
 const recover=img=>{if(!img.matches?.('img[data-friend-fallback]'))return;const fallback=img.dataset.friendFallback;if(img.getAttribute('src')!==fallback){img.src=fallback;}else{img.hidden=true;const initial=img.nextElementSibling;if(initial?.classList.contains('feng-friend-avatar-initial'))initial.hidden=false;}};
 document.addEventListener('error',e=>recover(e.target),true);
 const check=()=>document.querySelectorAll('img[data-friend-fallback]').forEach(img=>{if(img.complete&&!img.naturalWidth)recover(img);});
 check();document.addEventListener('xf:mounted',check);
})();
