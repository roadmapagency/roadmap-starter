<?php

/**
 * Discovers and registers the ACF blocks of the active theme.
 *
 * Blocks live in `acf-blocks/Blocks/{Block}/{Block}.php` (+ template.php, style.scss). They are
 * collected from every source returned by the `roadmap_starter_block_sources` filter — by default
 * the child theme (namespace {@see Identity::block_namespace()}) and then this parent theme. A
 * child block whose slug matches a parent block replaces it. Block names (`acf/{slug}`) and ACF
 * field keys come from the class short name, never the namespace, so moving a block between
 * themes or namespaces keeps existing content bound.
 *
 * @package roadmap-starter
 */

namespace RoadmapStarter;

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
	 * @var null|BlocksServiceProvider
	 */
	protected static $instance = null;

	/**
	 * Block sources, each `[ 'dir' => absolute Blocks dir, 'ns' => PHP namespace ]`.
	 *
	 * @var array<int, array{dir: string, ns: string}>
	 */
	protected $sources = array();

	/**
	 * BlocksServiceProvider constructor.
	 */
	public function __construct() {
		$this->sources = self::sources();
		$this->register_autoload();
		$this->register();
		add_filter( 'acf/blocks/default_block_version', [$this, 'bump_the_block_version'], 10, 2 );
		add_filter( 'acf/register_block_type_args', [$this, 'add_default_block_supports'] );
	}

	/**
	 * Where blocks are discovered, child theme first.
	 *
	 * @return array<int, array{dir: string, ns: string}>
	 */
	public static function sources() {
		$sources = array();
		if ( is_child_theme() ) {
			$sources[] = array(
				'dir' => get_stylesheet_directory() . '/acf-blocks/Blocks',
				'ns'  => Identity::block_namespace(),
			);
		}
		$sources[] = array(
			'dir' => __DIR__ . '/Blocks',
			'ns'  => __NAMESPACE__ . '\\Blocks',
		);

		return (array) apply_filters( 'roadmap_starter_block_sources', $sources );
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
	 * PSR-4 style autoloaders: the framework namespace (this directory), then one per block
	 * source mapping `{ns}\{Block}\{Class}` to `{dir}/{Block}/{Class}.php`.
	 */
	public function register_autoload() {
		$roots = array( array( __NAMESPACE__ . '\\', __DIR__ . '/' ) );
		foreach ( $this->sources as $source ) {
			$roots[] = array( trim( $source['ns'], '\\' ) . '\\', rtrim( $source['dir'], '/' ) . '/' );
		}

		spl_autoload_register(
			function ( $cls ) use ( $roots ) {
				$cls = ltrim( $cls, '\\' );
				foreach ( $roots as list( $prefix, $dir ) ) {
					if ( strpos( $cls, $prefix ) !== 0 ) {
						continue;
					}
					$path = $dir . str_replace( '\\', DIRECTORY_SEPARATOR, substr( $cls, strlen( $prefix ) ) ) . '.php';
					if ( file_exists( $path ) ) {
						require_once $path;
						return;
					}
				}
			}
		);
	}

	/**
	 * Instantiate every block once, earlier sources winning on slug.
	 */
	public function register() {
		$seen = array();
		foreach ( $this->sources as $source ) {
			foreach ( (array) glob( rtrim( $source['dir'], '/' ) . '/*', GLOB_ONLYDIR ) as $dir ) {
				$dirname = basename( $dir );
				$slug    = strtolower( $dirname );
				if ( isset( $seen[ $slug ] ) ) {
					continue;
				}
				$class = trim( $source['ns'], '\\' ) . '\\' . $dirname . '\\' . $dirname;
				if ( class_exists( $class ) ) {
					$seen[ $slug ] = true;
					new $class();
				}
			}
		}
	}

	/**
	 * Get instance of class
	 *
	 * @return BlocksServiceProvider
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function get_block_names() {
		return array_keys( acf_get_block_types() );
	}

	public function bump_the_block_version() : float {
		return 3;
	}
}

$roadmap_starter_blocks = BlocksServiceProvider::get_instance();
