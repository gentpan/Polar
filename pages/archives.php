<?php
/**
 * Template Name: Polar · 文章存档
 *
 * Chronological month cards with a year filter.
 * @package Polar
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
while ( have_posts() ) :
 the_post();
 if ( post_password_required() ) {
  echo '<section class="feng-panel">' . get_the_password_form() . '</section>';
  continue;
 }
 $years = feng_archive_years();
 $current_year = (int) wp_date( 'Y' );
 $known = array_map( 'intval', wp_list_pluck( $years, 'year' ) );
 // Keep this year's tab available even before its first article is published.
 if ( ! in_array( $current_year, $known, true ) ) {
  $years[] = (object) array( 'year' => $current_year, 'total' => 0 );
  usort( $years, static function( $a, $b ) { return (int) $b->year <=> (int) $a->year; } );
 }
 $requested_year = get_query_var( 'feng_archive_year', '' );
 if ( ! is_string( $requested_year ) ) { $requested_year = ''; }
 $year = $current_year;
 if ( 'all' === $requested_year ) {
  $year = 0;
 } elseif ( preg_match( '/^[1-9][0-9]{3}$/', $requested_year ) ) {
  $year = (int) $requested_year;
 }
 if ( $year && ! in_array( $year, array_map( 'intval', wp_list_pluck( $years, 'year' ) ), true ) ) {
  $years[] = (object) array( 'year' => $year, 'total' => 0 );
  usort( $years, static function( $a, $b ) { return (int) $b->year <=> (int) $a->year; } );
 }
 $query_args = array(
  'post_type' => 'post',
  'post_status' => 'publish',
  'posts_per_page' => -1,
  'no_found_rows' => true,
  'ignore_sticky_posts' => true,
 );
 if ( $year ) { $query_args['year'] = $year; }
 $archive = new WP_Query( $query_args );
 $groups = array();
 foreach ( $archive->posts as $entry ) {
  $groups[ get_the_date( 'Y年m月', $entry ) ][] = $entry;
 }
 ?>
 <div class="feng-panel feng-special-page feng-archive-page">
  <div class="feng-archive-layout">
   <aside class="feng-archive-sidebar" data-xf-reveal>
    <header class="feng-archive-heading">
     <p class="xf-section-kicker">ARCHIVES / 时间里的记录</p>
     <h1><?php echo feng_page_title_icon(); the_title(); ?><span aria-hidden="true">.</span></h1>
     <p>和时间交手，也和时间交朋友。</p>
     <p class="feng-archive-total"><?php echo esc_html( number_format_i18n( array_sum( wp_list_pluck( $years, 'total' ) ) ) ); ?> 篇文章 · <?php echo esc_html( count( $known ) ); ?> 个年份</p>
      <?php
 $footer_totals=feng_footer_content_stats();
 $footer_since=feng_setting('site_since','');
 $footer_date=DateTimeImmutable::createFromFormat('!Y-m-d',$footer_since,wp_timezone());
 if(!$footer_date||$footer_date->format('Y-m-d')!==$footer_since)$footer_date=null;
 ?>
 <div class="feng-archive-site-stats">
 <?php if($footer_date): ?><span><?php echo feng_icon('clock'); ?><?php echo $footer_date>current_datetime()?'距建站还有':'已运行'; ?> <?php echo number_format_i18n((int)$footer_date->diff(current_datetime())->days); ?> 天</span>
 <?php elseif($footer_totals['first']): ?><span><?php echo feng_icon('calendar'); ?>首篇文章 <?php echo esc_html($footer_totals['first']); ?></span><?php endif; ?>
 <span title="仅统计公开文章正文，中文按字、英文按词计数"><?php echo feng_icon('edit'); ?>累计 <?php echo number_format_i18n($footer_totals['words']); ?> 字</span>
 </div>
    </header>
    <nav class="feng-years" aria-label="按年份筛选">
     <a href="<?php echo esc_url( feng_archive_filter_url( 'all' ) ); ?>" <?php if ( ! $year ) { echo 'aria-current="true"'; } ?>>全部记录 <span><?php echo feng_icon('arrow-up-right'); ?></span></a>
     <?php foreach ( $years as $item ) : ?>
      <a href="<?php echo esc_url( feng_archive_filter_url( $item->year ) ); ?>" <?php if ( $year === (int) $item->year ) { echo 'aria-current="true"'; } ?>><?php echo esc_html( $item->year ); ?> 年 <span><?php echo esc_html( $item->total ); ?></span></a>
     <?php endforeach; ?>
    </nav>
   </aside>
   <div class="feng-archive-body">
    <?php feng_activity_calendar('post',$year); ?>
    <?php if ( get_the_content() ) : ?><div class="xf-prose feng-page-prose"><?php the_content(); ?></div><?php endif; ?>
    <div class="feng-archive-months">
     <?php foreach ( $groups as $month => $entries ) : ?>
      <section class="feng-month" data-xf-reveal aria-label="<?php echo esc_attr( $month ); ?>">
       <h2><?php echo esc_html( $month ); ?></h2>
       <ol>
        <?php foreach ( $entries as $entry ) : $entry_title = get_the_title( $entry ) ?: __( '无标题', 'feng' ); ?>
         <li><a href="<?php echo esc_url( get_permalink( $entry ) ); ?>" title="<?php echo esc_attr( $entry_title ); ?>"><time datetime="<?php echo esc_attr( get_the_date( DATE_W3C, $entry ) ); ?>"><?php echo esc_html( get_the_date( 'd日', $entry ) ); ?></time><span><?php echo esc_html( $entry_title ); ?></span></a></li>
        <?php endforeach; ?>
       </ol>
      </section>
     <?php endforeach; ?>
    </div>
    <?php if ( ! $groups ) { echo '<p class="feng-empty">' . ( $year ? '这一年还没有记录。' : '还没有发布文章。' ) . '</p>'; } ?>
   </div>
  </div>
 </div>
 <?php
endwhile;
get_footer();
