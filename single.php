<?php
get_header();
while ( have_posts() ) :
	the_post();
	get_template_part( 'template-parts/content', 'single' );
	if(!post_password_required()) {
		get_template_part('template-parts/reading-tools');
		get_template_part('template-parts/reading-related');
	}
	?>
	<div class="xf-reading feng-comment-panel">
		<?php
		if ( comments_open() || get_comments_number() ) {
			comments_template();
		}
		?>
	</div>
	<?php
endwhile;
get_footer();
