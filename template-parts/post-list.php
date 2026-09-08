<?php if ( have_posts() ) : ?>
<div class="xf-post-list<?php if(is_home()) echo ' feng-recent-list'; ?>">
<?php while ( have_posts() ) : the_post(); get_template_part(is_home()?'template-parts/post-row':'template-parts/post-card',null,array('post'=>get_post())); endwhile; ?>
</div>
<?php feng_pagination(); ?>
<?php else : get_template_part( 'template-parts/content', 'none' ); endif; ?>
