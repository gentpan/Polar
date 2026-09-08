import {forwardRef,useEffect,useImperativeHandle,useRef} from 'react';
import lottie from 'lottie-web/build/player/lottie_light';

/** Shared lifecycle for the site owner's unmodified Lordicon exports. */
export function createLordicon(data:object,className:string,autoplay=false){
 return forwardRef(({size=20}:{size?:number},ref)=>{
  const host=useRef<HTMLDivElement>(null),player=useRef<any>(null),requested=useRef(false);
  useImperativeHandle(ref,()=>({
   startAnimation:()=>{requested.current=true;if(!matchMedia('(prefers-reduced-motion: reduce)').matches)player.current?.goToAndPlay(0,true);},
   stopAnimation:()=>{requested.current=false;player.current?.goToAndStop(0,true);}
  }));
  useEffect(()=>{
   const animation=lottie.loadAnimation({container:host.current!,renderer:'svg',loop:false,autoplay:false,animationData:JSON.parse(JSON.stringify(data)),rendererSettings:{preserveAspectRatio:'xMidYMid meet'}});
   player.current=animation;
   animation.addEventListener('DOMLoaded',()=>{if((autoplay||requested.current)&&!matchMedia('(prefers-reduced-motion: reduce)').matches)animation.goToAndPlay(0,true);});
   return()=>{player.current=null;animation.destroy();};
  },[]);
  return <div ref={host} className={className} style={{width:size,height:size}}/>;
 });
}
