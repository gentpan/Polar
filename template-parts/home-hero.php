<?php
if(!defined('ABSPATH'))exit;
$portrait=feng_setting('hero_portrait','')?:get_avatar_url(get_option('admin_email'),array('size'=>320));
$photos=array();for($i=1;$i<=4;$i++){ $url=feng_setting('hero_photo_'.$i,'');if($url)$photos[]=$url; }
$name=feng_setting('profile_name','')?:get_bloginfo('name');
$now=new DateTimeImmutable('today',wp_timezone());$start=$now->modify('-89 days');
$records=get_posts(array('post_type'=>array('post','feng_talk'),'post_status'=>'publish','has_password'=>false,'posts_per_page'=>-1,'date_query'=>array(array('after'=>$start->format('Y-m-d').' 00:00:00','before'=>$now->format('Y-m-d').' 23:59:59','inclusive'=>true))));
$days=array();foreach($records as $record)$days[substr($record->post_date,0,10)][]=$record;
$notes=get_posts(array('post_type'=>'feng_talk','post_status'=>'publish','has_password'=>false,'posts_per_page'=>1));
// Keep five frequent commenters first, then five distinct recent commenters.
$recent_visitors=array();$visitor_counts=array();$visitor_users=array();
foreach(get_comments(array('status'=>'approve','type'=>'comment','number'=>0,'orderby'=>array('comment_date_gmt'=>'DESC','comment_ID'=>'DESC'),'post_status'=>'publish','post_password'=>'')) as $comment){
 $email=strtolower(trim($comment->comment_author_email));
 if(!$email && !$comment->user_id)continue;
 $lookup=$comment->user_id?'user:'.$comment->user_id:'email:'.$email;
 if(!array_key_exists($lookup,$visitor_users))$visitor_users[$lookup]=$comment->user_id?get_userdata((int)$comment->user_id):get_user_by('email',$email);
 $user=$visitor_users[$lookup];
 if($user && user_can($user,'manage_options'))continue;
 $key=$user?'user:'.$user->ID:'email:'.$email;
 if(!isset($recent_visitors[$key]))$recent_visitors[$key]=$comment;
 $visitor_counts[$key]=($visitor_counts[$key]??0)+1;
}
arsort($visitor_counts,SORT_NUMERIC);
$visitors=array();
foreach(array_slice($visitor_counts,0,5,true) as $key=>$count)$visitors[$key]=$recent_visitors[$key];
foreach($recent_visitors as $key=>$comment){
 if(count($visitors)>=10)break;
 if(!isset($visitors[$key]))$visitors[$key]=$comment;
}
?>
<noscript><style>.feng-photo-stack[data-stack-loading] .feng-stack-front{opacity:1}</style></noscript>
<?php
$footer_background=feng_setting('footer_background','');
if(feng_setting('footer_seasonal',true)){
 $month=(int)wp_date('n');
 $season=$month>=3&&$month<=5?'spring':($month>=6&&$month<=8?'summer':($month>=9&&$month<=11?'autumn':'winter'));
 $footer_background=feng_setting('footer_'.$season,'')?:($footer_background?:get_theme_file_uri('/assets/images/seasons/'.$season.'.webp'));
}
$smart_scene=feng_setting('hero_smart_scene',true);
$scene_images=array();
if($smart_scene){
 foreach(array('dawn','day','sunset','night','cloud','rain','snow') as $scene)$scene_images[$scene]=get_theme_file_uri('/assets/images/hero-fuji/'.$scene.'.webp');
 $footer_background=$scene_images['day'];
}
?>
<section class="xf-stage feng-profile-hero" aria-labelledby="xf-stage-title" data-xf-stage<?php if($smart_scene): ?> data-smart-scene data-scene-images="<?php echo esc_attr(wp_json_encode($scene_images)); ?>" data-scene-preview="<?php echo esc_attr(feng_setting('hero_scene_preview','auto')); ?>" data-scene-animation="<?php echo feng_setting('hero_scene_animation',true)?'true':'false'; ?>"<?php endif; ?>>
 <?php if($footer_background): ?><img class="polar-hero-landscape" src="<?php echo esc_url($footer_background); ?>" alt="" decoding="async" aria-hidden="true"><?php endif; ?>
 <?php if(feng_setting('greeting_weather',true)||$smart_scene): ?>
 <details class="feng-hero-weather" data-hero-weather data-watermark-enabled="<?php echo feng_setting('greeting_weather',true)?'true':'false'; ?>" data-animated="<?php echo feng_setting('weather_animation',true)?'true':'false'; ?>" data-visitor="<?php echo feng_setting('weather_visitor',true)?'true':'false'; ?>" data-endpoint="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" hidden>
 <summary aria-label="查看两地天气"><svg viewBox="0 0 80 80" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><g class="weather-sun"><circle cx="40" cy="32" r="13"/><path d="M40 10v-5m0 49v5M18 32h-5m49 0h5M24 16l-4-4m36 36 4 4M24 48l-4 4m36-36 4-4"/></g><path class="weather-cloud" d="M21 48a11 11 0 1 1 2-22 17 17 0 0 1 33 2 10 10 0 1 1 3 20Z"/><g class="weather-rain"><path d="m27 56-3 8m16-8-3 8m16-8-3 8"/></g><g class="weather-snow"><circle cx="25" cy="59" r="2"/><circle cx="40" cy="63" r="2"/><circle cx="55" cy="58" r="2"/></g><path class="weather-moon" d="M53 45A21 21 0 0 1 32 16a22 22 0 1 0 21 29Z"/></svg></summary>
 <div class="feng-weather-card"><p class="polar-weather-date" data-weather-date></p><p data-weather-host hidden></p><p data-weather-visitor hidden></p></div>
 </details>
 <?php endif; ?>
 
 <div class="feng-profile-top">
 <div class="xf-stage__identity"><div class="feng-hero-welcome-row"><p class="xf-welcome" data-feng-greeting><?php echo esc_html(feng_setting('xf_eyebrow','你好，欢迎来到我的生活切片')); ?></p><?php $hero_github=feng_weekly_github_stats(); ?>
 <div class="feng-mini-activity"><a href="https://x.com/gentpan" target="_blank" rel="noopener noreferrer" aria-label="X / Twitter" title="X / Twitter"><i class="feng-icon fa-brands fa-x-twitter" aria-hidden="true"></i></a><a href="https://github.com/gentpan" target="_blank" rel="noopener noreferrer" aria-label="GitHub" title="GitHub"><i class="feng-icon fa-brands fa-github" aria-hidden="true"></i></a>
 <?php if($hero_github!==null): $daily=array_slice($hero_github['daily'],-7,null,true);$peak=max(1,max($daily));$today_pushes=(int)end($daily); ?><span class="feng-mini-caption"><?php echo $today_pushes?'今日 '.$today_pushes.' 次推送':'今日暂无推送'; ?><em> · 近 7 天 <?php echo $hero_github['complete']?'':'至少 '; ?><?php echo (int)array_sum($daily); ?> 次</em></span><div class="feng-mini-bars" aria-label="GitHub 最近七个日期的公开推送，今天尚未结束<?php echo $hero_github['complete']?'':'，数据不完整'; ?>"><?php foreach($daily as $date=>$count): ?><span data-count="<?php echo (int)$count; ?>" aria-label="<?php echo esc_attr($date.' · '.$count.' 次推送'); ?>"><i style="--bar-height:<?php echo $count?max(8,round($count/$peak*100)):0; ?>%"></i></span><?php endforeach; ?></div><?php else: ?><small>GitHub 暂未同步</small><?php endif; ?>
 </div></div><h1 id="xf-stage-title"><?php echo esc_html(get_bloginfo('description')); ?></h1><div class="feng-hero-latest"><?php if($notes):$note=$notes[0]; ?><a class="feng-hero-note" href="<?php echo esc_url(feng_page_url('talks')); ?>"><small><?php echo feng_icon('comment'); ?><span>最新说说 · <?php echo esc_html(human_time_diff(get_post_time('U',true,$note),time()).'前'); ?></span></small><span><?php echo esc_html(wp_trim_words(wp_strip_all_tags($note->post_content),55,'…')); ?></span></a><?php endif; ?></div>



 <?php if(feng_setting('music_enabled',true)): foreach(feng_music_sanitize_tracks(feng_setting('music_tracks',array())) as $track): if($track['url']!==feng_setting('hero_music',''))continue; ?><button class="feng-hero-music" type="button" data-hero-music="<?php echo esc_attr($track['url']); ?>"><?php if($track['cover']): ?><img src="<?php echo esc_url($track['cover']); ?>" alt=""><?php endif; ?><span><small>最近分享的音乐</small><strong><?php echo esc_html($track['title']); ?></strong><small><?php echo esc_html($track['artist']); ?></small></span><span data-hero-music-state>播放</span></button><?php endforeach;endif; ?>
 </div>
 <div class="feng-profile-visual"><button class="feng-photo-stack" data-stack-loading type="button" data-profile-open aria-haspopup="dialog" aria-label="展开<?php echo esc_attr($name); ?>的个人相册"><img class="feng-stack-front" fetchpriority="high" loading="eager" src="<?php echo esc_url($portrait); ?>" alt="<?php echo esc_attr($name); ?>"><?php foreach($photos as $i=>$photo): ?><span class="feng-stack-piece" data-corner="<?php echo (int)$i; ?>"><img class="feng-stack-back" data-stack-src="<?php echo esc_url($photo); ?>" alt=""></span><?php endforeach; ?></button><?php if(feng_setting('pet_enabled',true)): ?><div class="feng-home-perch" data-feng-pet-slot></div><?php endif; ?></div>
 </div>
 <div class="feng-profile-bottom"><div class="feng-profile-calendar"><div class="feng-profile-calendar-heading"><strong><span class="feng-activity-icon" data-lordicon-content="siteactivity" aria-hidden="true"><i class="fa-solid fa-chart-column"></i></span>站点动态</strong><div class="feng-heatmap-legend" aria-label="每日发布数量：从少到多，依次为 0、1、2、3、4 条及以上"><span>少</span><?php for($level=0;$level<=4;$level++): ?><i data-level="<?php echo $level; ?>" title="<?php echo $level===4?'4 条及以上':$level.' 条'; ?>" aria-hidden="true"></i><?php endfor; ?><span>多</span></div><?php get_template_part('template-parts/hero-weekly',null,array('start'=>$start)); ?></div><div class="feng-heatmap" aria-label="本站发布记录热力图">
 <?php for($i=0;$i<90;$i++):$date=$start->modify('+'.$i.' days')->format('Y-m-d');$count=count($days[$date]??array()); ?><button type="button" data-profile-day="<?php echo esc_attr($date); ?>" data-level="<?php echo min(4,$count); ?>" aria-label="<?php echo esc_attr($date.'，'.$count.' 条记录'); ?>" title="<?php echo esc_attr($date.' · '.$count.' 条记录'); ?>"></button><?php endfor; ?></div></div>
 <div class="feng-profile-updates"> <div class="feng-profile-visitors"><div class="feng-visitors-heading"><small>最近来聊天的朋友</small><nav class="feng-discover-links" aria-label="发现更多博客"><span class="feng-discover-label">发现更多博客</span><a class="xf-icon-button" href="https://www.travellings.cn/go.html" target="_blank" rel="noopener noreferrer" aria-label="开往，发现更多博客" title="开往 · 发现更多博客"><i class="feng-icon feng-fa fa-solid fa-train" aria-hidden="true"></i></a><a class="xf-icon-button" href="https://www.foreverblog.cn/go.html" target="_blank" rel="noopener noreferrer" aria-label="十年之约，探索更多文章" title="十年之约 · 探索更多文章"><i class="feng-icon feng-fa fa-solid fa-blog" aria-hidden="true"></i></a></nav></div><div>
 <?php foreach($visitors as $visitor): $visitor_url=esc_url($visitor->comment_author_url,array('http','https')); ?>
 <?php if($visitor_url): ?><a class="feng-visitor-avatar" href="<?php echo $visitor_url; ?>" target="_blank" rel="ugc nofollow noopener noreferrer" title="<?php echo esc_attr($visitor->comment_author.'的网站'); ?>"><?php else: ?><span class="feng-visitor-avatar" title="<?php echo esc_attr($visitor->comment_author); ?>"><?php endif; ?>
 <?php echo get_avatar($visitor,44,'',$visitor->comment_author); ?>
 <?php if($visitor_url): ?></a><?php else: ?></span><?php endif; ?>
 <?php endforeach; ?>

 </div></div></div></div>

 
 <dialog class="feng-profile-dialog" data-profile-dialog aria-label="个人相册"><button type="button" data-profile-close aria-label="关闭">×</button><header><img src="<?php echo esc_url($portrait); ?>" alt=""><div><h2><?php echo esc_html($name); ?></h2><p><?php echo esc_html(feng_setting('profile_tagline','')); ?></p></div></header><div class="feng-profile-photos"><?php foreach($photos as $i=>$photo): ?><button type="button" data-profile-photo="<?php echo esc_url($photo); ?>" aria-label="放大生活照片 <?php echo $i+1; ?>"><img src="<?php echo esc_url($photo); ?>" alt="生活照片 <?php echo $i+1; ?>" loading="lazy"></button><?php endforeach; ?></div><img data-profile-large hidden alt="放大的生活照片"></dialog>
 <dialog class="feng-profile-dialog" data-profile-calendar-dialog aria-label="当天记录"><button type="button" data-profile-close aria-label="关闭">×</button><h2 data-profile-date></h2><?php foreach($days as $date=>$items): ?><ul data-profile-records="<?php echo esc_attr($date); ?>" hidden><?php foreach($items as $item): ?><li><a href="<?php echo esc_url($item->post_type==='feng_talk'?feng_page_url('talks').'#talk-'.$item->ID:get_permalink($item)); ?>"><small><?php echo $item->post_type==='feng_talk'?'说说 · ':'文章 · '; ?></small><?php echo esc_html($item->post_type==='feng_talk'?wp_trim_words(wp_strip_all_tags($item->post_content),25,'…'):get_the_title($item)); ?></a></li><?php endforeach; ?></ul><?php endforeach; ?><p data-profile-empty hidden>这一天没有发布记录。</p></dialog>
</section>
