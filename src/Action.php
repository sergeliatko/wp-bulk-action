<?php


namespace SergeLiatko\WPBulkAction;

/**
 * Class Action
 *
 * @package SergeLiatko\WPBulkAction
 */
class Action implements getIdInterface {

	public const REGISTRATION_FILTER_PREFIX = 'bulk_actions-';
	public const HANDLER_FILTER_PREFIX      = 'handle_bulk_actions-';
	public const DEFAULT_SCREEN             = 'edit-post';
	public const QUERY_PREFIX               = 'bulk-';

	/**
	 * @var string $screen
	 */
	protected $screen;

	/**
	 * @var string $action
	 */
	protected $action;

	/**
	 * @var string
	 */
	protected $query_param;

	/**
	 * @var callable $callback
	 */
	protected $callback;

	/**
	 * @var string $label
	 */
	protected $label;

	/**
	 * @var string $msg_single
	 */
	protected $msg_single;

	/**
	 * @var string $msg_plural
	 */
	protected $msg_plural;

	/**
	 * @var int[] $single_numbers
	 */
	protected $single_numbers;

	/**
	 * BulkAction constructor.
	 *
	 * @param array $args
	 */
	public function __construct( array $args ) {
		/**
		 * @var string        $screen
		 * @var string        $action
		 * @var callable|null $callback
		 * @var string        $label
		 * @var string        $msg_single
		 * @var string        $msg_plural
		 * @var int[]         $single_numbers
		 */
		extract( wp_parse_args( $args, $this->defaults() ), EXTR_OVERWRITE );
		$this->setScreen( $screen );
		$this->setAction( $action );
		$this->setCallback( $callback );
		$this->setLabel( $label );
		$this->setMsgSingle( $msg_single );
		$this->setMsgPlural( $msg_plural );
		$this->setSingleNumbers( $single_numbers );
		if (
			!$this->isEmpty( $screen = $this->getScreen() )
			&& !$this->isEmpty( $this->getAction() )
			&& is_callable( $this->getCallback() )
		) {
			add_filter( self::REGISTRATION_FILTER_PREFIX . $screen, array( $this, 'register' ), 10, 1 );
			add_filter( self::HANDLER_FILTER_PREFIX . $screen, array( $this, 'handle' ), 10, 3 );
			add_action( 'admin_notices', array( $this, 'notice' ), 10, 0 );
		}
	}

	/**
	 * @return array
	 */
	protected function defaults() {
		return array(
			'screen'         => self::DEFAULT_SCREEN,
			'action'         => '',
			'callback'       => null,
			'label'          => '',
			'msg_single'     => '',
			'msg_plural'     => '',
			'single_numbers' => array( 1 ),
		);
	}

	/**
	 * @param mixed $data
	 *
	 * @return bool
	 */
	protected function isEmpty( $data = null ): bool {
		return empty( $data );
	}

	/**
	 * @return string
	 */
	public function getScreen(): string {
		return $this->screen;
	}

	/**
	 * @param string $screen
	 *
	 * @return Action
	 */
	public function setScreen( string $screen ): Action {
		$this->screen = sanitize_key( $screen );

		return $this;
	}

	/**
	 * @return string
	 */
	public function getAction(): string {
		return $this->action;
	}

	/**
	 * @param string $action
	 *
	 * @return Action
	 */
	public function setAction( string $action ): Action {
		$this->action = sanitize_key( $action );

		return $this;
	}

	/**
	 * @return callable
	 */
	public function getCallback(): callable {
		return $this->callback;
	}

	/**
	 * @param callable|null $callback
	 *
	 * @return Action
	 */
	public function setCallback( ?callable $callback ): Action {
		$this->callback = $callback;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getId(): string {
		return $this->getAction();
	}

	/**
	 * @param array $bulk_actions
	 *
	 * @return array
	 */
	public function register( array $bulk_actions ): array {
		$bulk_actions[ $this->getAction() ] = $this->getLabel();

		return $bulk_actions;
	}

	/**
	 * @return string
	 */
	public function getLabel(): string {
		return $this->label;
	}

	/**
	 * @param string $label
	 *
	 * @return Action
	 */
	public function setLabel( string $label ): Action {
		$this->label = sanitize_text_field( $label );

		return $this;
	}

	/**
	 * @param string $redirect
	 * @param string $action
	 * @param array  $item_ids
	 *
	 * @return string
	 */
	public function handle( string $redirect, string $action, array $item_ids = array() ): string {
		if ( $this->getAction() !== $action ) {
			return $redirect;
		}
		foreach ( $item_ids as $id ) {
			call_user_func( $this->getCallback(), $id );
		}

		return add_query_arg( $this->getQueryParam(), count( $item_ids ), $redirect );
	}

	/**
	 * @return string
	 */
	public function getQueryParam(): string {
		if ( empty( $this->query_param ) ) {
			$this->setQueryParam( $this->getAction() );
		}

		return $this->query_param;
	}

	/**
	 * @param string $query_param
	 *
	 * @return Action
	 */
	public function setQueryParam( string $query_param ): Action {
		$this->query_param = self::QUERY_PREFIX . $query_param;

		return $this;
	}

	/**
	 * Displays admin notice if query parameter is present.
	 */
	public function notice() {
		if (
			!is_null( $screen = get_current_screen() )
			&& ( $screen->id === $this->getScreen() )
			&& isset( $_REQUEST[ $field = $this->getQueryParam() ] )
		) {
			$count = absint( $_REQUEST[ $field ] );
			printf(
				'<div class="%1$s">%2$s</div>',
				'notice notice-success is-dismissible',
				wpautop( sprintf( $this->getMessage( $count ), $count ), false )
			);
		}
	}

	/**
	 * @param int $items_count
	 *
	 * @return string
	 */
	protected function getMessage( int $items_count ) {
		return in_array( $items_count, $this->getSingleNumbers() ) ? $this->getMsgSingle() : $this->getMsgPlural();
	}

	/**
	 * @return int[]
	 */
	public function getSingleNumbers(): array {
		return $this->single_numbers;
	}

	/**
	 * @param int[] $single_numbers
	 *
	 * @return Action
	 */
	public function setSingleNumbers( array $single_numbers ): Action {
		$single_numbers       = array_unique( array_filter( $single_numbers, 'is_int' ) );
		$this->single_numbers = empty( $single_numbers ) ? array( 1 ) : $single_numbers;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getMsgSingle(): string {
		return $this->msg_single;
	}

	/**
	 * @param string $msg_single
	 *
	 * @return Action
	 */
	public function setMsgSingle( string $msg_single ): Action {
		$this->msg_single = sanitize_text_field( $msg_single );

		return $this;
	}

	/**
	 * @return string
	 */
	public function getMsgPlural(): string {
		return $this->msg_plural;
	}

	/**
	 * @param string $msg_plural
	 *
	 * @return Action
	 */
	public function setMsgPlural( string $msg_plural ): Action {
		$this->msg_plural = sanitize_text_field( $msg_plural );

		return $this;
	}

}
