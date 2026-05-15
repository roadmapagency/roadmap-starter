<?php if ( get_post_field( 'post_name' ) !== 'contact-us' ) : ?>
<div class="container">
	<div class="row text-center px-3 bg-primary text-white py-4 position-relative mb-6">
		<div class=" col-sm col-lg-6 offset-lg-3 offset-0 text-center">
			<span class="fs-6 text-sans"><strong>Have questions?</strong></span><br>
			<p>We'd be happy to help you out</p>
			<div class="row">
				<div class="col-sm-4 offset-sm-2">
					<!-- TODO: replace with client phone number -->
					<a class="text-white fs-3 btn btn-outline-secondary btn-block" href="#">Call Us</a>
				</div>
				<div class="mt-3 mt-sm-0 col-sm-4">
					<a class="text-white fs-3 btn btn-outline-secondary btn-block" href="<?php echo get_permalink( get_page_by_path( 'contact-us' ) ); ?>">Send us a message</a>
				</div>
			</div>
		</div>
	</div>
</div>
<?php endif; ?>
