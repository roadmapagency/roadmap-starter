<div class="col-md-8 offset-md-2 py-3 px-4 mb-4 light-green">
	<div class="mb-1 post-meta"><?php echo get_the_date( 'M jS, Y' ); ?> | <strong><?php echo implode( ', ', array_map( fn( WP_Term $cat ) => $cat->name, get_the_category() ) ); ?></strong></div>
	<?php the_title( '<h4><a href="' . get_the_permalink() . '">', '</a></h4>' ); ?>
	<a href="<?php the_permalink(); ?>" class="fw-bold h5 text-decoration-underline mt-2 icon-link">Read More <?php echo roadmap_starter_fontawesome_icon( 'arrow-right' ); ?></a>
</div>
