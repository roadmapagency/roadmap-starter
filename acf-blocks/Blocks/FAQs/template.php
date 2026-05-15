<?php
/**
 * @var array{
 *     category: string,
 *     questions: array{
 *     question: string,
 *     answer: string
 *      }
 * }           $categories
 * @var string $title
 * @var string $id
 * @var string $block_id
 * @var string $classes
 * @var array  $block
 */
?>
<div id="<?php echo $id; ?>" data-block-id="<?php echo $block_id; ?>" class="<?php echo self::get_block_class_name(); ?> <?php echo $classes; ?>">
	<div class="container">
		<?php if ( $title ) : ?>
			<div class="row">
				<div class="col-12 mb-4 mb-md-5 text-center">
					<?php echo $title ? '<h2>' . $title . '</h2>' : ''; ?>
				</div>
			</div>
		<?php endif; ?>
		<div class="row">
			<div class="col-md-10 offset-md-1 d-flex flex-column row-gap-5 px-0">
				<?php foreach ( $categories as $category ) : ?>
					<div class="row">
						<div class="col-12">
							<h3 class="mb-4"><?php echo $category['category']; ?></h3>
						</div>

						<div class="col-12">
							<div class="accordion accordion-flush"
								id="accordion-<?php echo sanitize_title( $category['category'] ); ?>"
							>
								<?php foreach ( $category['questions'] as $index => $question ) : ?>
									<div class="accordion-item">
										<h2 class="accordion-header">
											<button class="accordion-button collapsed"
													type="button"
													data-bs-toggle="collapse"
													data-bs-target="#collapse-<?php echo sanitize_title( $category['category'] ); ?>-<?php echo $index; ?>"
													aria-expanded="false"
													aria-controls="collapse-<?php echo sanitize_title( $category['category'] ); ?>-<?php echo $index; ?>"
											>
												<?php echo $question['question']; ?>
											</button>
										</h2>
										<div id="collapse-<?php echo sanitize_title( $category['category'] ); ?>-<?php echo $index; ?>"
											class="accordion-collapse collapse"
											data-bs-parent="#accordion-<?php echo sanitize_title( $category['category'] ); ?>"
										>
											<div class="accordion-body">
												<?php echo $question['answer']; ?>
											</div>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</div>
