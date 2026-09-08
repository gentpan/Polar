<?php
/** Own media contract: figure > optional link > responsive WordPress image. */
$xf_context = isset( $args['context'] ) && 'card' === $args['context'] ? 'card' : 'cover';
if ( ! feng_post_image_url() ) { return; }
?>
<figure class="xf-media xf-media--<?php echo esc_attr( $xf_context ); ?>" data-xf-media>
 <?php if ( 'card' === $xf_context ) : ?><a href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true"><?php endif; ?>
 <?php echo feng_post_image( null, 'card' === $xf_context ? 'large' : 'full', array( 'class' => 'xf-media__image', 'data-xf-full' => feng_post_image_url( null, 'full' ), 'decoding' => 'async', 'sizes' => 'card' === $xf_context ? '(max-width: 700px) 100vw, (max-width: 1050px) 50vw, 600px' : '(max-width: 1200px) 100vw, 1200px' ) ); ?>
 <?php if ( 'card' === $xf_context ) : ?></a><?php endif; ?>
</figure>
