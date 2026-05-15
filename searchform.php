<?php
$terms         = null;
$post_type     = 'post';
$selected_term = '';
$category_slug = '';

if ( ! is_category() ) {
	$post_type = is_archive() ? get_queried_object()->name : 'post';
	$post_type = ! empty( $_GET['post_type'] ) ? $_GET['post_type'] : $post_type;

	$taxonomies = get_object_taxonomies( $post_type, 'objects' );
	// find the first taxonomy with the name category in it
	$taxonomies = array_filter(
		$taxonomies,
		function ( WP_Taxonomy $taxonomy ) {
			return strpos( $taxonomy->name, 'category' ) !== false;
		}
	);

	$taxonomy      = count( $taxonomies ) > 0 ? array_shift( $taxonomies ) : null;
	$terms         = $taxonomy ? get_terms( $taxonomy->name ) : array();
	$selected_term = ! empty( $_GET[ $taxonomy->query_var ] ) ? $_GET[ $taxonomy->query_var ] : '';
	$category_slug = $taxonomy->query_var;
} elseif ( is_category() ) {
	$category      = get_queried_object();
	$taxonomy      = get_taxonomy( $category->taxonomy );
	$selected_term = $category->slug;
	$terms         = get_terms( $category->taxonomy );
	$category_slug = $taxonomy->query_var;
}

// get plural name from post type slug
$post_type_obj    = get_post_type_object( $post_type );
$post_type_plural = $post_type_obj->labels->name;

?>
<div class="container-fluid light-green py-2 mb-4">
	<div class="container">
		<form role="search" method="get" class="search-form" action="<?php echo home_url( '/' ); ?>">
			<input type="hidden" name="post_type" value="<?php echo $post_type; ?>"/>
			<div class="row align-items-center">
				<?php if ( $terms ) : ?>
					<div class="col-12 col-md-auto">
						<div class="form-floating">
							<select id="category-selector"
									name="<?php echo $category_slug; ?>"
									class="form-select d-inline w-auto"
							>
								<option value="">All Categories</option>
								<?php
								foreach ( $terms as $term ) {
									$selected = $selected_term === $term->slug ? 'selected' : '';
									echo "<option value='{$term->slug}' " . $selected . ">{$term->name}</option>";
								}
								?>
							</select>
							<label for="floatingSelect">Category</label>
						</div>
					</div>
				<?php endif; ?>
				<div class="col-12 col-md">
					<div class="input-group">
						<input type="search"
								class="search-field form-control bg-white"
								placeholder="Search"
								value="<?php echo get_search_query(); ?>"
								name="s"
						/>
						<button type="submit"
								class="search-submit btn btn-primary btn-sm rounded-end-1"
						><?php echo esc_attr_x( 'Search', 'submit button', 'roadmap-starter' ); ?></button>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>

<script>
	const is_category = <?php echo is_category() ? 'true' : 'false'; ?>;
	const post_type = '<?php echo $post_type_plural; ?>';
	jQuery(document).ready(function ($) {
		const $catSelect = $('#category-selector');
		const $searchInput = $('input[name="s"]');
		const $form = $('.search-form');

		if (is_category) {
			$searchInput.attr(
				'placeholder',
				'Search ' + '<?php echo esc_attr_x( single_cat_title( '', false ), 'placeholder', 'roadmap-starter' ); ?> News'
			)
		}

		$catSelect.on('change', submit_form);

		function submit_form(){
			$form.submit();
		}

		function update_placeholder_name() {
			let cat = $catSelect.find('option:selected').text() + " " + post_type;
			if ($catSelect.val() === '') {
				cat = post_type;
			}
			$searchInput.attr('placeholder', 'Search ' + cat)
		}

		update_placeholder_name();
	});
</script>
