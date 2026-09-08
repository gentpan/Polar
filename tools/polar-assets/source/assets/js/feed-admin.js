(() => {
 const button = document.querySelector('[data-feed-refresh]'); if (!button) return;
 const status = document.querySelector('[data-feed-admin-status]');
 button.addEventListener('click', async () => {
  button.disabled = true; let start = true;
  try {
   do {
    status.textContent = start ? '正在连接订阅源…' : status.textContent;
    const response = await fetch(button.dataset.endpoint, {method:'POST', credentials:'same-origin', body:new URLSearchParams({action:'feng_feed_refresh',nonce:button.dataset.nonce,start:start?'1':'0'})});
    const json = await response.json(); if (!json.success) throw new Error(json.data?.message || '同步未完成，请稍后重试。');
    const d = json.data; start = false;
    status.textContent = d.pending ? `已同步 ${d.done} / ${d.total} 个源，新收录 ${d.added} 篇…` : `同步完成：新收录 ${d.added} 篇，${d.errors ? d.errors+' 个源暂时失败' : '全部成功'}。`;
    if (!d.pending) break;
   } while (true);
  } catch(error) { status.textContent = error.message + ' 后台会继续处理未完成的订阅。'; }
  finally { button.disabled = false; }
 });
})();
