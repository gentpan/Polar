<?php $xf_cover = feng_post_image_url( null, 'full' ); ?>
<article data-feng-pet-post="<?php echo (get_post_type()==='post' && get_post_status()==='publish' && !post_password_required())?get_the_ID():0; ?>" id="post-<?php the_ID(); ?>" <?php post_class( 'xf-article' ); ?>>
 <?php if ('post' === get_post_type()): get_template_part( 'template-parts/article-hero', null, array( 'cover' => $xf_cover ) ); else: ?>
 <header class="xf-reading-cover <?php echo $xf_cover ? 'xf-reading-cover--image' : ''; ?>">
  <?php if ( $xf_cover ) : ?><div class="xf-reading-cover__media" aria-hidden="true"><?php the_post_thumbnail( 'full', array( 'alt' => '', 'fetchpriority' => 'high' ) ); ?></div><?php endif; ?>
  <div class="xf-reading-cover__copy xf-reading"><p class="xf-section-kicker"><?php echo esc_html( 'post' === get_post_type() ? 'JOURNAL / ' . get_the_date( 'Y' ) : 'PERSONAL SPACE' ); ?></p><h1><?php if(is_page())echo feng_page_title_icon(); echo esc_html( get_the_title() ?: __( '无标题', 'feng' ) ); ?></h1>
  <?php if ( 'post' === get_post_type() ) : ?><div class="xf-reading-cover__meta"><span><?php the_author_posts_link(); ?></span><time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time><a href="#xf-article-text">开始阅读 <?php echo feng_icon('arrow-down'); ?></a></div><?php endif; ?></div>
 </header>
 <?php endif; ?>
 <?php if('post'===get_post_type()){get_template_part('template-parts/article-freshness');get_template_part('template-parts/reading-summary');} ?>
 <div id="xf-article-text" class="xf-prose xf-container"<?php if('post'===get_post_type() && !post_password_required())echo ' data-feng-reading'; ?>><?php the_content(); ?><?php wp_link_pages( array( 'before' => '<nav class="xf-page-links" aria-label="' . esc_attr__( '文章分页', 'feng' ) . '">', 'after' => '</nav>' ) ); ?></div>
 <?php if ( 'post' === get_post_type() && ! post_password_required() ) : ?><footer class="xf-article-footer xf-reading"><div class="feng-article-ending"><span class="feng-article-end-stamp" role="img" aria-label="正文结束">完</span></div><?php get_template_part('template-parts/article-copyright'); ?></footer><?php endif; ?>
</article>
