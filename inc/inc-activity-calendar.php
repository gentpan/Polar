<?php
if(!defined('ABSPATH'))exit;
/** Calendar uses site-local dates; only public, unprotected content is counted. */
function feng_activity_calendar($type='post',$year=0,$options=array()){
 global $wpdb;
 $talk=$type==='feng_talk';$column=$talk?'post_date':'post_modified';
 $today=new DateTimeImmutable('today',wp_timezone());
 $start=$year?new DateTimeImmutable($year.'-01-01',wp_timezone()):$today->modify('-'.(max(1,min(366,(int)($options['days']??365)))-1).' days');
 $end=$year?$start->modify('+1 year -1 day'):$today;
 $rows=$wpdb->get_results($wpdb->prepare("SELECT DATE($column) day,COUNT(*) total FROM {$wpdb->posts} WHERE post_type=%s AND post_status='publish' AND post_password='' AND $column >= %s AND $column < %s GROUP BY DATE($column)",$talk?'feng_talk':'post',$start->format('Y-m-d'),$end->modify('+1 day')->format('Y-m-d')));
 $counts=array();$total=0;foreach($rows as $row){$counts[$row->day]=(int)$row->total;$total+=(int)$row->total;}
 $grid_rows=max(1,min(7,(int)($options['rows']??7)));
 $grid_start=$grid_rows===7?$start->modify('-'.((int)$start->format('N')-1).' days'):$start;
 $weeks=(int)ceil(((int)$grid_start->diff($end)->days+1)/$grid_rows);
 ?>
 <section class="feng-calendar-activity" aria-label="<?php echo $talk?'说说发布热力图':'文章更新热力图'; ?>">
 <?php if(empty($options['hide_heading'])): ?><header><strong><?php echo $talk?'说说的足迹':'文章更新记录'; ?></strong><span><?php echo $year?(int)$year.' 年':'近一年'; ?> · <?php echo (int)$total; ?> <?php echo $talk?'条说说':'篇文章'; ?></span></header><?php endif; ?>
 <div class="feng-activity-scroll"><div class="feng-activity-grid" style="--activity-weeks:<?php echo (int)$weeks; ?>">
 <?php for($i=0;$i<$weeks*$grid_rows;$i++):$date=$grid_start->modify('+'.$i.' days');$day=$date->format('Y-m-d');$outside=$date<$start||$date>$end;$n=$counts[$day]??0;$level=$n?min(4,(int)ceil(log($n+1,2))):0;$label=$day.' · '.$n.($talk?' 条说说':' 篇文章最后更新'); ?>
 <span class="feng-activity-day" data-level="<?php echo $level; ?>" <?php if($outside): ?>aria-hidden="true" style="visibility:hidden"<?php else: ?>tabindex="0" aria-label="<?php echo esc_attr($label); ?>" data-tip="<?php echo esc_attr($label); ?>"<?php endif; ?>></span>
 <?php endfor; ?>
 </div></div>
 <?php if(empty($options['hide_footer'])): ?><footer><small><?php echo esc_html($start->format('Y.m.d').' — '.$end->format('Y.m.d')); ?><?php if(!$talk)echo ' · 每篇文章按最后更新时间计一次'; ?></small><span class="feng-activity-legend">少 <?php for($i=0;$i<5;$i++)echo '<i data-level="'.$i.'"></i>'; ?> 多</span></footer><?php endif; ?>
 </section>
 <?php
}
