import lottie from 'lottie-web/build/player/lottie_light';
import eye from './lordicon/eye-hover-pinch.json';
import location from './lordicon/location-hover-pinch.json';
import copy from './lordicon/copy-hover-pinch.json';
import copyright from './lordicon/copyright-hover-pinch.json';
import top from './lordicon/top-hover-pinch.json';
import pageprev from './lordicon/pageprev.json';
import pagenext from './lordicon/pagenext.json';
import relatedrefresh from './lordicon/related-refresh.json';
import articlelink from './lordicon/article-link.json';
import reply from './lordicon/reply-hover-pinch.json';
import github from './lordicon/github.json';
import twitter from './lordicon/twitter.json';
import siteactivity from './lordicon/site-activity.json';
const animations={siteactivity,github,twitter,reply,articlelink,relatedrefresh,pageprev,pagenext,eye,location,copy,copyright,top};
const instances=new Map<HTMLElement,()=>void>();
function mount(){
 for(const [el,dispose] of instances)if(!el.isConnected){dispose();instances.delete(el);}
 document.querySelectorAll<HTMLElement>('[data-lordicon-content],.comment-reply-link').forEach(button=>{
  if(instances.has(button))return;
  const kind=button.dataset.lordiconContent||(button.matches('.comment-reply-link')?'reply':'');
  if(!(kind in animations))return;
  const host=document.createElement('span');host.className='feng-lordicon-content';host.setAttribute('aria-hidden','true');button.prepend(host);
  const trigger=button.closest('button,a,.feng-footer-copyright,.feng-copyright-rule')||button;
  const reveal=['pageprev','pagenext','relatedrefresh','articlelink'].includes(kind);
  const restAtEnd=reveal||kind==='siteactivity';
  let player:any=null,hovered=false;
  const reduced=matchMedia('(prefers-reduced-motion: reduce)');
  const init=()=>{if(player)return;player=lottie.loadAnimation({container:host,renderer:'svg',loop:false,autoplay:false,animationData:JSON.parse(JSON.stringify(animations[kind as keyof typeof animations]))});player.addEventListener('DOMLoaded',()=>{button.classList.add('has-lordicon-content');if((hovered||reveal)&&!reduced.matches)player.goToAndPlay(0,true);else if(restAtEnd)player.goToAndStop(player.totalFrames-1,true);});};
  const enter=()=>{hovered=true;init();if(!reduced.matches)player?.goToAndPlay(0,true);};
  const leave=()=>{hovered=false;player?.goToAndStop(restAtEnd?player.totalFrames-1:0,true);};
  const observer=new IntersectionObserver(entries=>{if(entries.some(e=>e.isIntersecting)){init();observer.disconnect();}},{rootMargin:'100px'});observer.observe(button);
  trigger.addEventListener('pointerenter',enter);trigger.addEventListener('pointerleave',leave);trigger.addEventListener('focus',enter);trigger.addEventListener('blur',leave);reduced.addEventListener('change',leave);
  instances.set(button,()=>{observer.disconnect();player?.destroy();host.remove();button.classList.remove('has-lordicon-content');trigger.removeEventListener('pointerenter',enter);trigger.removeEventListener('pointerleave',leave);trigger.removeEventListener('focus',enter);trigger.removeEventListener('blur',leave);reduced.removeEventListener('change',leave);});
 });
}
let pending=false;
new MutationObserver(records=>{if(records.some(r=>[...r.addedNodes,...r.removedNodes].some(n=>n instanceof HTMLElement&&(n.matches('[data-lordicon-content],.comment-reply-link')||n.querySelector('[data-lordicon-content],.comment-reply-link'))))&&!pending){pending=true;requestAnimationFrame(()=>{pending=false;mount();});}}).observe(document.documentElement,{childList:true,subtree:true});
document.addEventListener('xf:mounted',mount);document.addEventListener('xf:before-unmount',()=>{instances.forEach(fn=>fn());instances.clear();});
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',mount,{once:true});else mount();
