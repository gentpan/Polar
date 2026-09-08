(() => {
 let current=null,generation=0,cleanup=()=>{};const loaders={};
 // Official Mapbox Gallery templates: https://www.mapbox.com/gallery
 const mapStyles={"Faded": "mapbox://styles/mapbox-map-design/cmcl29tgn008r01p69cx43vvx", "Monochrome": "mapbox://styles/mapbox-map-design/cmcl1sypr006u01qv5l157uov", "Cool": "mapbox://styles/mapbox-map-design/cmclxnhzb008001sb6g3m49ko", "Dark 2D": "mapbox://styles/mapbox-map-design/cmf04nwfx00at01ple6dhenx3", "Light 2D": "mapbox://styles/mapbox-map-design/cmf04wyjp018e01sd3633800a", "Outdoors": "mapbox://styles/mapbox-map-design/cmh0wgofd00bu01srg2k73chv", "Streets": "mapbox://styles/mapbox/streets-v12"};
 const reduced=()=>matchMedia('(prefers-reduced-motion: reduce)').matches;
 function sdk(c){const p=c.provider;if((p==='mapbox'&&window.mapboxgl)||(p==='google'&&window.google?.maps)||(p==='amap'&&window.AMap))return Promise.resolve();if(loaders[p])return loaders[p];
  loaders[p]=new Promise((resolve,reject)=>{const s=document.createElement('script');let timer;const done=()=>{clearTimeout(timer);resolve();};const fail=()=>{clearTimeout(timer);s.remove();delete loaders[p];reject(Error('地图组件加载失败，请检查网络或稍后重试。'));};s.onerror=fail;
   if(p==='mapbox'){const css=document.createElement('link');css.rel='stylesheet';css.href='https://api.mapbox.com/mapbox-gl-js/v3.30.0/mapbox-gl.css';document.head.append(css);s.src='https://api.mapbox.com/mapbox-gl-js/v3.30.0/mapbox-gl.js';s.onload=done;}
   if(p==='google'){window.fengGoogleReady=done;s.src='https://maps.googleapis.com/maps/api/js?'+new URLSearchParams({key:c.key,loading:'async',callback:'fengGoogleReady',language:'zh-CN'});}
   if(p==='amap'){window._AMapSecurityConfig=c.proxy?{serviceHost:c.proxy}:{securityJsCode:c.security};window.fengAMapReady=done;s.src='https://webapi.amap.com/maps?'+new URLSearchParams({key:c.key,v:'2.0',callback:'fengAMapReady'});}
   timer=setTimeout(fail,20000);document.head.append(s);
  });return loaders[p];
 }
 function popup(point){const div=document.createElement('div'),p=document.createElement('p'),a=document.createElement('a');p.textContent=point.name;a.textContent=point.title;a.href=point.url;div.append(p,a);return div;}
 function dispose(){cleanup();cleanup=()=>{};generation++;current?.destroy();current=null;}
 async function mount(){dispose();const root=document.querySelector('[data-travel-page]');if(!root)return;const serial=generation,data=JSON.parse(root.querySelector('[data-travel-data]').textContent),c=data.config,canvas=root.querySelector('[data-travel-map]'),status=root.querySelector('[data-travel-status]'),cards=[...root.querySelectorAll('[data-travel-card]')];let markers=[],visible=new Set(cards.map(n=>Number(n.dataset.travelCard)));

 const controls=new AbortController(),settings=root.querySelector('[data-map-settings]'),mapButton=document.createElement('button');
 mapButton.type='button';mapButton.className='xf-icon-button feng-map-toggle';mapButton.title='地图设置';mapButton.setAttribute('aria-label','地图设置');mapButton.setAttribute('aria-expanded','false');settings.id='feng-map-settings';mapButton.setAttribute('aria-controls',settings.id);mapButton.innerHTML='<i class="fa-solid fa-map" aria-hidden="true"></i><span>地图</span>';
 const mapControl=document.createElement('div');mapControl.className='feng-map-control';
 if(c.provider==='mapbox'&&c.key){mapControl.append(mapButton,settings);root.querySelector('[data-travel-settings-slot]').append(mapControl);}
 const hideSettings=()=>{settings.hidden=true;mapButton.setAttribute('aria-expanded','false');};
 mapButton.addEventListener('click',()=>{settings.hidden=!settings.hidden;mapButton.setAttribute('aria-expanded',String(!settings.hidden));},{signal:controls.signal});
 document.addEventListener('click',e=>{if(!settings.contains(e.target)&&!mapButton.contains(e.target))hideSettings();},{signal:controls.signal});
 document.addEventListener('keydown',e=>{if(e.key==='Escape'&&!settings.hidden){hideSettings();mapButton.focus();}},{signal:controls.signal});
 const drawer=root.querySelector('[data-travel-drawer]'),heading=root.querySelector('#travel-drawer-title');
 let selected=null,trigger=null;
 function openArticles(ids,label,source){markers.forEach(m=>m.show(true));selected=ids?new Set(ids):null;trigger=source;drawer.hidden=false;root.classList.add('has-articles');heading.textContent=label;root.querySelector('input').value='';cards.forEach(card=>card.hidden=selected&&!selected.has(Number(card.dataset.travelCard)));root.querySelector('[data-travel-empty]').hidden=cards.some(card=>!card.hidden);heading.focus({preventScroll:true});}
 function closeArticles(){drawer.hidden=true;root.classList.remove('has-articles');trigger?.focus({preventScroll:true});}
 root.querySelector('[data-travel-all]').addEventListener('click',e=>openArticles(null,'全部旅行文章',e.currentTarget));
 root.querySelector('[data-travel-close]').addEventListener('click',closeArticles);
 root.querySelector('[data-travel-reset]').addEventListener('click',()=>{closeArticles();current?.reset?.();});
 const keys=e=>{if(e.key==='Escape'&&!drawer.hidden)closeArticles();};root.addEventListener('keydown',keys);
 const size=()=>{const top=Math.max(0,root.getBoundingClientRect().top+window.scrollY);root.style.setProperty('--travel-top',top+'px');root.style.setProperty('--travel-header-clearance',Math.max(80,document.querySelector('.xf-header').getBoundingClientRect().bottom-top+20)+'px');current?.resize?.();};
 const resizeObserver=new ResizeObserver(size);resizeObserver.observe(document.querySelector('.xf-header'));window.addEventListener('resize',size);size();
 cleanup=()=>{controls.abort();settings.hidden=true;root.append(settings);mapControl.remove();resizeObserver.disconnect();window.removeEventListener('resize',size);root.removeEventListener('keydown',keys);};
 root.querySelector('[data-travel-filter]').addEventListener('submit',e=>{e.preventDefault();const q=root.querySelector('input').value.trim().toLocaleLowerCase();visible=new Set();cards.forEach(card=>{card.hidden=(selected&&!selected.has(Number(card.dataset.travelCard)))||!card.dataset.travelText.toLocaleLowerCase().includes(q);if(!card.hidden)visible.add(Number(card.dataset.travelCard));});root.querySelector('[data-travel-empty]').hidden=visible.size>0;markers.forEach(m=>m.show((m.point.ids||[m.point.id]).some(id=>visible.has(id))));const first=markers.find(m=>(m.point.ids||[m.point.id]).some(id=>visible.has(id)));if(q&&first)current?.focus(first.coords);});
 if(!c.key){
  const frame=document.createElement('iframe');
  frame.title='旅行地图 · OpenStreetMap';
  frame.src='https://www.openstreetmap.org/export/embed.html?bbox=-180%2C-60%2C180%2C80&layer=mapnik';
  frame.loading='lazy';frame.style.cssText='width:100%;height:100%;border:0;display:block';
  canvas.hidden=false;canvas.replaceChildren(frame);
  current={destroy:()=>canvas.replaceChildren()};
  status.textContent='';return;
 }
 try{await sdk(c);if(serial!==generation||!root.isConnected)return;
  let add;
  if(c.provider==='mapbox'){
   if(!mapboxgl.supported())throw Error('当前浏览器不支持交互地图，仍可浏览文章。');
   const picker=settings.querySelector('[data-travel-style]');
   let chosen='Faded';try{const saved=localStorage.getItem('feng-travel-style');if(Object.hasOwn(mapStyles,saved))chosen=saved;}catch{}
   for(const name of Object.keys(mapStyles)){const option=document.createElement('option');option.value=name;option.textContent=name;picker.append(option);}
   const isDark=()=>document.documentElement.dataset.xfTheme==='dark';
   let activeStyle=isDark()?'Dark 2D':chosen;
   picker.value=activeStyle;picker.disabled=isDark();picker.title=isDark()?'深色模式自动使用 Dark 2D':'';picker.parentElement.hidden=false;root.dataset.mapStyle=activeStyle;
   const viewPicker=settings.querySelector('[data-travel-view]');let view='flat';try{const saved=localStorage.getItem('feng-travel-view');if(['flat','terrain','globe'].includes(saved))view=saved;}catch{}viewPicker.value=view;root.dataset.mapView=view;
   const map=new mapboxgl.Map({container:canvas,accessToken:c.key,style:mapStyles[activeStyle],pitch:0,bearing:0,maxPitch:0,projection:'mercator',renderWorldCopies:true,maxBounds:[[-30,-80],[330,84]],center:[150,15],zoom:1.4,bounds:[[-5,-55],[305,75]],fitBoundsOptions:{padding:0}});if(root.hasAttribute('data-travel-fullscreen'))map.scrollZoom.enable();else map.scrollZoom.disable();const reset=()=>{if(view==='globe'){map.flyTo({center:[105,20],zoom:1.3,pitch:0});return;}const points=data.points.filter(p=>Number.isFinite(p.lng)&&Number.isFinite(p.lat)&&p.crs!=='gcj02'&&['manual','mapbox'].includes(p.source||'manual'));if(points.length){const bounds=new mapboxgl.LngLatBounds();points.forEach(p=>bounds.extend([((p.lng+390)%360)-30,p.lat]));map.fitBounds(bounds,{padding:90,maxZoom:5,duration:reduced()?0:1000});}else map.fitBounds([[-5,-55],[305,75]],{padding:0,duration:reduced()?0:1000});};
   root.querySelectorAll('[data-travel-zoom]').forEach(button=>{button.disabled=false;button.addEventListener('click',()=>{const options={duration:reduced()?0:250};if(button.dataset.travelZoom==='in')map.zoomIn(options);else map.zoomOut(options);},{signal:controls.signal});});
   current={destroy:()=>map.remove(),resize:()=>map.resize(),reset,focus:xy=>map.flyTo({center:[((xy[0]+390)%360)-30,xy[1]],zoom:4,duration:reduced()?0:1200})};
   function syncStyle(){
    const name=isDark()?'Dark 2D':chosen;
    picker.value=name;picker.disabled=isDark();picker.title=isDark()?'深色模式自动使用 Dark 2D':'';
    if(activeStyle===name)return;
    activeStyle=name;root.dataset.mapStyle=name;status.textContent='正在加载 '+name+'…';
    try{map.setStyle(mapStyles[name]);}catch{status.textContent='地图样式切换失败，请重试。';}
   }
   picker.addEventListener('change',()=>{
    const name=picker.value;if(isDark()||!Object.hasOwn(mapStyles,name))return;
    chosen=name;try{localStorage.setItem('feng-travel-style',name);}catch{}
    syncStyle();
   });
   const backgroundPicker=settings.querySelector('[data-globe-background]');
   let background=['system','light','dark'].includes(c.globe_background)?c.globe_background:'system',originalFog=null;
   backgroundPicker.value=background;
   function applyBackground(){
    const dark=background==='dark'||(background==='system'&&document.documentElement.dataset.xfTheme==='dark');
    root.dataset.mapContrast=(view==='globe'?dark:picker.value==='Dark 2D')?'dark':'light';
    backgroundPicker.disabled=view!=='globe';
    if(view!=='globe'){map.setFog(originalFog);return;}
    map.setFog({range:[0.8,8],color:dark?'#bccce2':'#e6eef5','high-color':dark?'#24446b':'#dceaf5','space-color':dark?'#061629':'#f0f5fa','star-intensity':dark?0.35:0,'horizon-blend':0.08});
   }
   backgroundPicker.addEventListener('change',()=>{background=backgroundPicker.value;if(map.isStyleLoaded())applyBackground();});
   const themeObserver=new MutationObserver(()=>{syncStyle();if(map.isStyleLoaded())applyBackground();});
   themeObserver.observe(document.documentElement,{attributes:true,attributeFilter:['data-xf-theme']});
   controls.signal.addEventListener('abort',()=>themeObserver.disconnect(),{once:true});
   function applyView(reframe=false){
    root.dataset.mapView=view;
    map.setMaxBounds(null);map.setMaxPitch(view==='terrain'?75:0);map.setProjection(view==='globe'?'globe':'mercator');
    if(view==='terrain'){
     if(!map.getSource('feng-elevation'))map.addSource('feng-elevation',{type:'raster-dem',url:'mapbox://mapbox.mapbox-terrain-dem-v1',tileSize:512,maxzoom:14});
     map.setTerrain({source:'feng-elevation',exaggeration:1.3});
    }else map.setTerrain(null);
    map.setPitch(view==='terrain'?55:0);applyBackground();
    if(reframe&&view==='globe')map.flyTo({center:[105,20],zoom:1.3,pitch:0,duration:reduced()?0:900});
    else if(reframe&&view==='flat')map.fitBounds([[-5,-55],[305,75]],{padding:0,duration:reduced()?0:900});
   }
   viewPicker.addEventListener('change',()=>{view=viewPicker.value;try{localStorage.setItem('feng-travel-view',view);}catch{}if(map.isStyleLoaded())applyView(true);});
   let firstStyle=true;
   map.on('style.load',()=>{if(serial!==generation)return;originalFog=map.getFog()||null;applyView(firstStyle);firstStyle=false;status.textContent='';});
   map.on('error',()=>{if(serial===generation)status.textContent='Mapbox 地图加载失败，请检查 Token、权限及网络。';});
   add=(point,xy)=>{const el=document.createElement('button');el.className='feng-travel-marker';el.type='button';el.textContent=String(point.ids.length);el.setAttribute('aria-label',(point.name||'此地点')+'：'+point.ids.length+' 篇文章');new mapboxgl.Marker({element:el}).setLngLat(xy).addTo(map);el.addEventListener('click',()=>{openArticles(point.ids,point.name||'此地点的文章',el);current.focus(xy);});return {show:on=>{el.hidden=!on;}};};
  }else if(c.provider==='google'){
   const map=new google.maps.Map(canvas,{center:{lng:105,lat:28},zoom:2,gestureHandling:'cooperative',mapTypeControl:false,streetViewControl:false});const owned=[],info=new google.maps.InfoWindow();current={destroy:()=>{owned.forEach(m=>m.setMap(null));info.close();google.maps.event.clearInstanceListeners(map);canvas.replaceChildren();},focus:xy=>{map.panTo({lng:xy[0],lat:xy[1]});map.setZoom(5);}};
   add=(point,xy)=>{const marker=new google.maps.Marker({map,position:{lng:xy[0],lat:xy[1]},title:point.name+'：'+point.title});owned.push(marker);marker.addListener('click',()=>{info.setContent(popup(point));info.open({anchor:marker,map});});return {show:on=>marker.setVisible(on)};};
  }else{
   const map=new AMap.Map(canvas,{center:[105,35],zoom:3,scrollWheel:false,viewMode:'2D'});current={destroy:()=>map.destroy(),focus:xy=>map.setZoomAndCenter(6,xy)};
   add=(point,xy)=>{const marker=new AMap.Marker({position:xy,title:point.name+'：'+point.title});map.add(marker);const info=new AMap.InfoWindow({content:popup(point),offset:new AMap.Pixel(0,-24)});marker.on('click',()=>info.open(map,xy));return {show:on=>on?marker.show():marker.hide()};};
  }
  const grouped=new Map();for(const point of data.points){const key=c.provider==='mapbox'&&Number.isFinite(point.lat)&&Number.isFinite(point.lng)?[point.lat.toFixed(4),point.lng.toFixed(4),point.source,point.crs].join(':'):String(point.id);if(grouped.has(key))grouped.get(key).ids.push(point.id);else grouped.set(key,{...point,ids:[point.id]});}
  let skipped=0;for(const point of grouped.values()){if(serial!==generation)return;const source=point.source||'manual';if(source!=='manual'&&source!==c.provider){skipped++;continue;}let xy=[point.lng,point.lat];
   if(c.provider==='google'&&point.place_id){try{const result=await new google.maps.Geocoder().geocode({placeId:point.place_id});const pos=result.results[0]?.geometry.location;if(!pos){skipped++;continue;}xy=[pos.lng(),pos.lat()];}catch{skipped++;continue;}}
   else if(!xy.every(Number.isFinite)){skipped++;continue;}
   if(c.provider!=='amap'&&point.crs==='gcj02'){skipped++;continue;}
   if(c.provider==='amap'&&point.crs!=='gcj02'){try{xy=await new Promise((resolve,reject)=>AMap.convertFrom(xy,'gps',(s,r)=>s==='complete'&&r.locations?.length?resolve([r.locations[0].lng,r.locations[0].lat]):reject(Error())));}catch{skipped++;continue;}}
   if(serial!==generation)return;const handle=add(point,xy);handle.show(visible.has(point.id));markers.push({...handle,point,coords:xy});
  }
  root.querySelector('[data-travel-place-count]').textContent=String(markers.length);
  status.textContent=markers.length?'':'尚无可显示的地点，请在文章中搜索地点或填写坐标。';if(skipped)status.textContent+=` ${skipped} 条记录需要在当前服务重新选点或检查坐标。`;
 }catch(e){if(serial===generation){current?.destroy();current=null;canvas.hidden=false;status.textContent=e.message;}}
 }
 document.addEventListener('xf:mounted',mount);document.addEventListener('xf:before-unmount',dispose);if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',mount,{once:true});else mount();
})();
