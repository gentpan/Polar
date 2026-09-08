<?php
get_header();
while ( have_posts() ) :
	the_post();
	get_template_part( 'template-parts/content', 'single' );
	if ( comments_open() || get_comments_number() ) :
		?>
		<div class="xf-reading"><?php comments_template(); ?></div>
		<?php
	endif;
endwhile;
get_footer();
