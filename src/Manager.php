<?php


namespace SergeLiatko\WPBulkAction;

/**
 * Class Manager
 *
 * @package SergeLiatko\WPBulkAction
 */
class Manager {

	/**
	 * @var array|Screen[]
	 */
	protected array $screens;

	public function __construct( array $args ) {
		/**
		 * @var array|Screen[] $screens
		 */
		extract( wp_parse_args( $args, $this->defaults() ) );
		$this->setScreens( $screens );
		add_action( 'admin_init', array( $this, 'initiateScreens' ), 10, 0 );
	}

	/**
	 * @return array[]
	 */
	protected function defaults(): array {
		return array(
			'screens' => array(),
		);
	}

	/**
	 * Instantiates screen objects.
	 */
	public function initiateScreens(): void {
		$screens = $this->getScreens();
		array_walk( $screens, function ( &$item, $key ) {
			$item = Factory::createScreen( wp_parse_args( $item, array(
				'id' => $key,
			) ) );
		} );
		$this->setScreens( Factory::mapIdToKey( $screens ) );
	}

	/**
	 * @return array|GetActionsInterface[]
	 */
	public function getScreens(): array {
		return $this->screens;
	}

	/**
	 * @param array|GetActionsInterface[] $screens
	 *
	 * @return Manager
	 */
	public function setScreens( array $screens ): Manager {
		$this->screens = $screens;

		return $this;
	}
}
