<?php if(!defined('ABSPATH')) exit; $tracks=feng_music_sanitize_tracks(feng_setting('music_tracks',array())); ?>
<section class="feng-music" data-feng-music hidden aria-label="音乐播放器">
 <script type="application/json" data-music-data><?php echo wp_json_encode($tracks,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?></script>
 <div class="feng-music__capsule">
  <button class="feng-music__art" type="button" data-music-art-toggle aria-label="播放或暂停音乐"><img data-music-cover alt="" hidden><span data-music-art-icon><?php echo feng_icon('music'); ?></span></button>
  <button class="feng-music__copy" type="button" data-music-expand aria-controls="feng-music-panel" aria-expanded="false"><strong data-music-title>给此刻一点音乐</strong><span data-music-line>展开歌单与歌词</span></button>
  <button class="feng-music__play" type="button" data-music-play aria-label="播放"><span data-music-play-icon><?php echo feng_icon('play'); ?></span><span data-music-pause-icon hidden><?php echo feng_icon('pause'); ?></span></button>
  <button class="feng-music__list" type="button" data-music-list-toggle aria-label="打开歌单" aria-expanded="false" aria-controls="feng-music-panel"><?php echo feng_icon('playlist'); ?></button>
  <span class="feng-music__mini-progress" aria-hidden="true"><i data-music-mini-progress></i></span>
 </div>
 <div class="feng-music__panel" id="feng-music-panel" hidden>
  <header><strong>此刻正在听</strong><button type="button" data-music-close aria-label="收起播放器"><?php echo feng_icon('close'); ?></button></header>
  <div class="feng-music__lyrics" data-music-lyrics tabindex="0" aria-label="歌词"></div>
  <div class="feng-music__seek"><input data-music-seek type="range" min="0" max="1000" value="0" aria-label="播放进度"><div><time data-music-elapsed>0:00</time><time data-music-duration>0:00</time></div></div>
  <div class="feng-music__controls"><button type="button" data-music-prev aria-label="上一首"><?php echo feng_icon('previous'); ?></button><button type="button" data-music-toggle aria-label="播放或暂停">播放 / 暂停</button><button type="button" data-music-next aria-label="下一首"><?php echo feng_icon('next'); ?></button><label>音量<input type="range" data-music-volume min="0" max="100" value="65" aria-label="音量"></label></div>
  <ol class="feng-music__playlist" data-music-playlist aria-label="歌单"></ol>
  <p class="feng-music__status" data-music-status role="status"></p>
  <?php if(current_user_can('manage_options')): ?><a class="feng-music__settings" href="<?php echo esc_url(admin_url('themes.php?page=feng-settings#feng-music')); ?>">管理歌单</a><?php endif; ?>
 </div>
 <div class="feng-music__menu" data-music-menu role="menu" aria-label="快捷工具" hidden>
  <div class="feng-music__menu-nav"><?php foreach(array('back'=>array('arrow-left','后退'),'forward'=>array('arrow-right','前进'),'reload'=>array('refresh','刷新'),'top'=>array('arrow-up','回到顶部')) as $action=>$item): ?><button type="button" role="menuitem" data-tool="<?php echo esc_attr($action); ?>" aria-label="<?php echo esc_attr($item[1]); ?>"><?php echo feng_icon($item[0]); ?></button><?php endforeach; ?></div>
  <div data-tool-selection hidden><button role="menuitem" type="button" data-tool="copy-selection"><?php echo feng_icon('copy'); ?>复制选中文字</button><button role="menuitem" type="button" data-tool="quote"><?php echo feng_icon('comment'); ?>引用到评论</button><button role="menuitem" type="button" data-tool="search"><?php echo feng_icon('search'); ?>站内搜索</button><button role="menuitem" type="button" data-tool="web-search"><?php echo feng_icon('search'); ?>百度搜索</button></div>
  <button type="button" role="menuitem" data-tool="play"><?php echo feng_icon('play'); ?><span data-tool-play-label>播放音乐</span></button>
  <button type="button" role="menuitem" data-tool="prev"><?php echo feng_icon('previous'); ?>切换到上一首</button>
  <button type="button" role="menuitem" data-tool="next"><?php echo feng_icon('next'); ?>切换到下一首</button>
  <button type="button" role="menuitem" data-tool="copy-title"><?php echo feng_icon('copy'); ?>复制歌名</button>
  <button type="button" role="menuitem" data-tool="playlist"><?php echo feng_icon('playlist'); ?>打开歌单</button>
 </div>
 <span class="feng-music__notice" data-music-notice role="status" hidden></span>
</section>
