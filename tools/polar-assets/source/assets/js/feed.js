(() => {
 'use strict';
 const endpoint = JSON.parse(document.getElementById('feng-config')?.textContent || '{}').endpoint; if (!endpoint) return;
 let mounted, countBusy = false, midnightTimer;
 const json = async (params,signal) => {
  const url = new URL(endpoint, location.href); Object.entries(params).forEach(([k,v]) => url.searchParams.set(k,String(v)));
  const response = await fetch(url,{credentials:'same-origin',cache:'no-store',signal});
  const result = await response.json(); if (!result.success) throw new Error('暂时无法读取动态，请再试一次。'); return result.data;
 };
 const counts = async () => {
  if(countBusy || document.hidden) return; countBusy = true;
  try {
   const data = await json({action:'feng_feed_count'});
   document.querySelectorAll('.feng-feed-badge').forEach(badge => {
    if (Number(badge.dataset.feedCount) !== data.today) {
     badge.dataset.feedCount = String(data.today);
     badge.querySelector('.t-badge-dot').textContent = data.today > 99 ? '99+' : String(data.today);
     badge.querySelector('.screen-reader-text').textContent = `，今日更新 ${data.today} 篇`;
     badge.dataset.open = data.today ? 'true' : 'false';
    }
   });
   document.querySelectorAll('[data-feed-today-number]').forEach(n => n.textContent = String(data.today));
   clearTimeout(midnightTimer);
   midnightTimer = setTimeout(counts, Math.max(1000,Math.min(86400000,data.midnight*1000-Date.now()+1000)));
  } catch (_) { /* Keep the server-rendered count when offline. */ }
  finally { countBusy = false; }
 };
 const mount = () => {
  mounted?.abort(); mounted = new AbortController();
  document.querySelectorAll('.feng-feed-badge').forEach(badge => {
   if(badge.dataset.animated || Number(badge.dataset.feedCount)===0)return;
   badge.dataset.animated='true'; badge.dataset.open='false';
   requestAnimationFrame(() => requestAnimationFrame(() => {if(badge.isConnected)badge.dataset.open='true';}));
  }); counts();
  const root = document.querySelector('[data-feed-page]'); if (!root) return;
  const entries = root.querySelector('[data-feed-entries]'), status = root.querySelector('[data-feed-status]'), more = root.querySelector('[data-feed-more]'), empty = root.querySelector('[data-feed-empty]');
  let page=1, period='all', request, serial=0;
  mounted.signal.addEventListener('abort',() => request?.abort());
  const load = async (append=false) => {
   request?.abort(); request = new AbortController(); const version = ++serial;
   entries.setAttribute('aria-busy','true'); more.disabled = true; status.textContent = '正在整理朋友们的动态…';
   try {
    const target = append ? page+1 : 1;
    const data = await json({action:'feng_feed_list',page:target,today:period==='today'?1:0},request.signal);
    if (version !== serial) return;
    if (append) entries.insertAdjacentHTML('beforeend',data.html); else entries.innerHTML = data.html;
    page = target; status.textContent = `共 ${data.total} 篇文章`; more.hidden = !data.more; empty.hidden = !!data.total;
    if (!data.total) { empty.querySelector('h2').textContent = period==='today'?'今天暂时没有新文章':'暂时没有动态'; empty.querySelector('p').textContent = '可以查看全部动态，或稍后再来看看。'; }
   } catch(error) { if (error.name !== 'AbortError') status.textContent = error.message; }
   finally { if (version===serial) { entries.removeAttribute('aria-busy'); more.disabled=false; } }
  };
  root.querySelectorAll('[data-feed-period]').forEach(button => button.addEventListener('click',() => {
   period=button.dataset.feedPeriod;
   root.querySelectorAll('[data-feed-period]').forEach(b => b.setAttribute('aria-pressed',String(b===button))); load();
  },{signal:mounted.signal}));
  more.addEventListener('click',() => load(true),{signal:mounted.signal});
 };
 document.addEventListener('xf:mounted',mount);
 document.addEventListener('xf:before-unmount',() => mounted?.abort());
 document.addEventListener('visibilitychange',() => {if(!document.hidden)counts();});
 setInterval(counts,60000);
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',mount,{once:true});else mount();
})();
