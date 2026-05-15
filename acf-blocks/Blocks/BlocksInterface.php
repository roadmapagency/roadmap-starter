<?php namespace RoadmapStarter\Blocks;

interface BlocksInterface {
	/**
	 * Responsible for initializing the block with ACF
	 *
	 * @return mixed
	 */
	public function acf_init();

	/**
	 * Responsible for registering the block with ACF
	 *
	 * @return mixed
	 */
	public function register_fields();
}
