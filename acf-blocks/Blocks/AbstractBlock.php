<?php
/**
 * Abstract block to be extended by custom block
 *
 * @package RoadmapStarter\Blocks
 */

namespace RoadmapStarter\Blocks;

use ReflectionClass;
use RoadmapStarter\Vendor\StoutLogic\AcfBuilder\FieldsBuilder;

/**
 * Abstract block to be extended by custom block
 *
 * Class AbstractBlock
 */
abstract class AbstractBlock implements BlocksInterface {

	/**
	 * ACF fields initialized by StoutLogic\AcfBuilder\FieldsBuilder
	 *
	 * @var \StoutLogic\AcfBuilder\FieldsBuilder
	 */
	protected $field_set;

	/**
	 * AbstractBlock constructor.
	 */
	public function __construct() {
		$this->field_set = new FieldsBuilder( $this->get_slug() );

		// Add AI fields but make them hidden in the UI
		$this->field_set->addTextArea( 'ai_content' );

		add_action(
			'acf/init',
			function () {
				$this->register_fields();
			}
		);

		/** @var \StoutLogic\AcfBuilder\FieldsBuilder $field_set */
		$field_set = $this->field_set;

		// make sure this loads after we have registered our fields
		add_action(
			'acf/init',
			function () use ( $field_set ) {
				acf_add_local_field_group( $field_set->build() );
			},
			20
		);

		add_action( 'acf/init', array( $this, 'acf_init' ) );
	}

	/**
	 * Gets the template file for this block.
	 *
	 * @return string
	 * @throws \Exception Cannot find a template.
	 */
	public function get_template() {
		if ( file_exists( self::get_block_dir() . '/template.php' ) ) {
			return self::get_block_dir() . '/template.php';
		} else {
			throw new \Exception( 'No template could be found for ' . self::get_slug() );
		}
	}

	public function get_field_set() {
		$this->register_fields();
		return $this->field_set;
	}

	/**
	 * Get the theme's slug name.
	 *
	 * @return string
	 * @throws \ReflectionException
	 */
	public static function get_slug() {
		$rc = new ReflectionClass( get_called_class() );

		return strtolower( $rc->getShortName() );
	}

	/**
	 * Get the block's directory path.
	 *
	 * @return string
	 * @throws \ReflectionException
	 */
	protected static function get_block_dir() {
		$rc = new ReflectionClass( get_called_class() );

		return dirname( $rc->getFileName() );
	}

	public function get_css_classes( $block ) {
		return '';
	}

	/**
	 * Get the block's class name along with any additional passed in.
	 *
	 * @param string $suffix Additional classes.
	 *
	 * @return string
	 * @throws \ReflectionException
	 */
	public static function get_block_class_name( $suffix = '' ) {
		return 'wp-block-roadmap-starter-' . strtolower( self::get_slug() ) . $suffix;
	}

	/**
	 * Returns the slug needed to identify this with ACF
	 *
	 * @return string
	 * @throws \ReflectionException
	 */
	public static function get_acf_slug() {
		return 'acf/' . strtolower( self::get_slug() );
	}

	/**
	 * Method to allow ACF to render the template
	 *
	 * @param array $block passed in by ACF. Probably use xdebug to see whats in there.
	 */
	public function render( $block ) {
		$block_id = self::get_slug() . '-' . substr( $block['id'], - 5 );
		$id       = ! empty( $block['anchor'] ) ? $block['anchor'] : $block_id;
		$classes  = $block['align'] ? 'align' . $block['align'] : '';
		$classes .= ' container-fluid wp-block-roadmap-starter' . $this->get_css_classes( $block );

		$args = array();
		/** @var \StoutLogic\AcfBuilder\FieldsBuilder $field_set */
		$field_set = $this->field_set;
		foreach ( $field_set->getFields() as $field ) {
			$build                  = $field->build();
			$args[ $build['name'] ] = get_field( $build['name'] );
		}

		$args = apply_filters( 'roadmap_starter/before_block_render', $args );

		extract( $args );

		include $this->get_template();
	}

	public static function display( array $data ) {
		$name          = strtolower( ( new \ReflectionClass( static::class ) )->getShortName() );
		$block         = acf_get_block_type( 'acf/' . $name );
		$block['data'] = $data;
		echo acf_rendered_block( $block, '', false );
	}
}
