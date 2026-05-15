<?php

/**
 * Service provider for blocks
 *
 * @package roadmap-starter
 */

namespace RoadmapStarter;

use Weareroadmap\AiForGutenberg\BlockRegistry;

require_once __DIR__ . '/../vendor_prefixed/autoload.php';

/**
 * Class BlocksServiceProvider
 *
 * @package RoadmapStarter
 */
class BlocksServiceProvider {

	/**
	 * The class instance
	 *
	 * @var null
	 */
	protected static $instance = null;

	/**
	 * BlocksServiceProvider constructor.
	 */
	public function __construct() {
		$this->register_autoload();
		$this->register();
		add_filter( 'acf/blocks/default_block_version', [$this, 'bump_the_block_version'], 10, 2 );
		add_filter( 'acf/register_block_type_args', [$this, 'add_default_block_supports'] );
	}

	/**
	 * Enable anchor support on every ACF block registered via acf_register_block_type().
	 *
	 * @param array $block The block settings being registered.
	 * @return array
	 */
	public function add_default_block_supports( $block ) {
		if ( ! isset( $block['supports'] ) || ! is_array( $block['supports'] ) ) {
			$block['supports'] = array();
		}

		if ( ! isset( $block['supports']['anchor'] ) ) {
			$block['supports']['anchor'] = true;
		}

		return $block;
	}

	/**
	 * Autoloader for psr namespaced block classes
	 */
	public function register_autoload() {
		spl_autoload_register(
			function ( $cls ) {
				$cls = ltrim( $cls, '\\' );
				if ( strpos( $cls, __NAMESPACE__ ) !== 0 ) {
					return;
				}

				$class_without_base_namespace = str_replace( __NAMESPACE__, '', $cls );

				$path = __DIR__
					. str_replace( '\\', DIRECTORY_SEPARATOR, $class_without_base_namespace )
					. '.php';

				if ( file_exists( $path ) ) {
					require_once $path;
				}
			}
		);
	}

	/**
	 * Auto register new blocks
	 */
	public function register() {
		foreach ( glob( __DIR__ . '/Blocks/*', GLOB_ONLYDIR ) as $dir ) {
			$dirname = basename( $dir );
			$class   = 'RoadmapStarter\\Blocks\\' . $dirname . '\\' . $dirname;
			if ( class_exists( $class ) ) {
				new $class();
			}
		}
	}

	/**
	 * Get instance of class
	 *
	 * @return null|BlocksServiceProvider
	 */
	public static function get_instance() {
		// create an object.
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;  // return the object.
	}

	public function get_block_names() {
		return array_keys( acf_get_block_types() );
	}

	public function bump_the_block_version() : float {
		return 3;
	}
}

$roadmap_starter_blocks = BlocksServiceProvider::get_instance();
