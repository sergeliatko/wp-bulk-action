<?php


namespace SergeLiatko\WPBulkAction;

/**
 * Class Screen
 *
 * @package SergeLiatko\WPBulkAction
 */
class Screen implements GetActionsInterface {

	/**
	 * @var string $id
	 */
	protected $id;

	/**
	 * @var array|\SergeLiatko\WPBulkAction\Action[]
	 */
	protected $actions;

	/**
	 * Screen constructor.
	 *
	 * @param array $args
	 */
	public function __construct( array $args ) {
		/**
		 * @var string                                   $id
		 * @var array|\SergeLiatko\WPBulkAction\Action[] $actions
		 */
		extract( wp_parse_args( $args, $this->defaults() ), EXTR_OVERWRITE );
		$this->setId( $id );
		$this->setActions( $actions );
	}

	/**
	 * @return string
	 */
	public function getId(): string {
		return $this->id;
	}

	/**
	 * @param string $id
	 *
	 * @return Screen
	 */
	public function setId( string $id ): Screen {
		$this->id = sanitize_key( $id );

		return $this;
	}

	/**
	 * @return array|\SergeLiatko\WPBulkAction\Action[]
	 * @noinspection PhpUnused
	 */
	public function getActions(): array {
		return $this->actions;
	}

	/**
	 * @param array|\SergeLiatko\WPBulkAction\Action[] $actions
	 *
	 * @return Screen
	 */
	public function setActions( array $actions ): Screen {
		$defaults = array( 'screen' => $this );
		array_walk( $actions, function ( &$item ) use ( $defaults ) {
			$item = Factory::createAction( wp_parse_args( $item, $defaults ) );
		} );
		$this->actions = Factory::mapIdToKey( $actions );

		return $this;
	}

	/**
	 * @param string $url
	 *
	 * @return string
	 */
	public function remove_actions_query_params( string $url ): string {
		$params = array();
		foreach ( $this->getActions() as $action ) {
			$params[ $param = $action->getQueryParam() ]                    = $param;
			$params[ $param_requested = $action->getQueryParamRequested() ] = $param_requested;
		}

		return remove_query_arg( $params, $url );
	}

	/**
	 * @return array[]
	 */
	protected function defaults(): array {
		return array(
			'id'      => 'edit-post',
			'actions' => array(),
		);
	}

}
