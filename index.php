<?php
get_header();
$xf_home = is_home() && ! is_paged();
if ( $xf_home ) :
?>
<?php get_template_part( 'template-parts/home-hero' ); ?>

<?php endif; ?>
<?php if(!$xf_home) get_template_part('template-parts/post-list'); ?>
<?php if ( $xf_home ) { get_template_part( 'template-parts/collections' ); } ?>
<?php get_footer(); ?>
