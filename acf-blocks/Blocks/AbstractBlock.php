<?php
/**
 * Abstract block to be extended by custom block
 *
 * @package RoadmapStarter\Blocks
 */

namespace RoadmapStarter\Blocks;

use ReflectionClass;
use RoadmapStarter\Identity;
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

		add_action(
			'acf/init',
			function () {
				$this->register_fields();
				$this->add_source_content_field();
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
		$this->add_source_content_field();
		return $this->field_set;
	}

	/**
	 * Appends the shared Source Content field after the block's own fields.
	 *
	 * Stored as `ai_content` (the name ai-by-roadmap and the block switcher read): the verbatim
	 * source copy the block was filled from, kept stable so the block can be swapped for another.
	 *
	 * ai-by-roadmap 0.4+ adds this field itself (same key and position) for themes that declare
	 * `ai-by-roadmap` support, so it is only added here for an older plugin.
	 */
	protected function add_source_content_field() {
		if ( class_exists( '\Roadmap\AiByRoadmap\Theme\SourceField' ) || $this->field_set->fieldExists( 'ai_content' ) ) {
			return;
		}
		$this->field_set->addTextArea(
			'ai_content',
			array(
				'label'        => __( 'Source Content', 'roadmap-starter' ),
				'instructions' => __( 'The original copy this block was built from. Used when switching this block to a different block type — it does not appear on the page.', 'roadmap-starter' ),
				'rows'         => 4,
			)
		);
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
		return 'wp-block-' . Identity::block_prefix() . '-' . strtolower( self::get_slug() ) . $suffix;
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

		// Resolve the alignment. The saved block markup may omit `align` (e.g. programmatic
		// inserts that didn't carry it), which would drop the block to the narrow content
		// width. When the block locks alignment (`supports.align === false`) fall back to the
		// alignment it was registered with, so a band always gets its `alignfull`/`alignwide`
		// wrapper regardless of how it was inserted.
		$align = ! empty( $block['align'] ) ? $block['align'] : '';
		if ( '' === $align && function_exists( 'acf_get_block_type' ) ) {
			$block_type = acf_get_block_type( self::get_acf_slug() );
			if ( $block_type && isset( $block_type['supports']['align'] ) && false === $block_type['supports']['align'] && ! empty( $block_type['align'] ) ) {
				$align = $block_type['align'];
			}
		}

		$classes  = $align ? 'align' . $align : '';
		$classes .= ' container-fluid wp-block-' . Identity::block_prefix() . $this->get_css_classes( $block );

		$args = array();
		/** @var \StoutLogic\AcfBuilder\FieldsBuilder $field_set */
		$field_set = $this->field_set;
		foreach ( $field_set->getFields() as $field ) {
			$build                  = $field->build();
			$value                  = get_field( $build['name'] );
			// A block saved without this attribute returns null (not the schema default) — fall back to
			// the field's declared default so e.g. true/false toggles that default ON stay on.
			if ( null === $value && isset( $build['default_value'] ) && '' !== $build['default_value'] ) {
				$value = $build['default_value'];
			}
			$args[ $build['name'] ] = $value;
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
