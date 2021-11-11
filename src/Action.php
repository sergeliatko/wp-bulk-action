<?php


namespace SergeLiatko\WPBulkAction;

/**
 * Class Action
 *
 * @package SergeLiatko\WPBulkAction
 */
class Action implements GetIdInterface {

	public const REGISTRATION_FILTER_PREFIX = 'bulk_actions-';
	public const HANDLER_FILTER_PREFIX      = 'handle_bulk_actions-';
	public const QUERY_PREFIX               = 'bulk-';
	public const QUERY_PREFIX_REQUESTED     = 'bulk-requested-';

	/**
	 * @var GetActionsInterface $screen
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
	 * @var string $query_param_requested
	 */
	protected $query_param_requested;

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
	 * @var bool $did_action
	 */
	private $did_action;

	/**
	 * @var int $processed_count
	 */
	private $processed_count;

	/**
	 * BulkAction constructor.
	 *
	 * @param array $args
	 */
	public function __construct( array $args ) {
		/**
		 * @var GetActionsInterface $screen
		 * @var string              $action
		 * @var callable|null       $callback
		 * @var string              $label
		 * @var string              $msg_single
		 * @var string              $msg_plural
		 * @var int[]               $single_numbers
		 */
		extract( wp_parse_args( $args, $this->defaults() ), EXTR_OVERWRITE );
		$this->setScreen( $screen );
		$this->setAction( $action );
		$this->setCallback( $callback );
		$this->setLabel( $label );
		$this->setMsgSingle( $msg_single );
		$this->setMsgPlural( $msg_plural );
		$this->setSingleNumbers( $single_numbers );
		$this->setDidAction( false );
		$this->setProcessedCount( 0 );
		if (
			!$this->isEmpty( $screen = $this->getScreen() )
			&& !$this->isEmpty( $this->getAction() )
			&& is_callable( $this->getCallback() )
		) {
			add_filter( self::REGISTRATION_FILTER_PREFIX . $screen->getId(), array( $this, 'register' ), 10, 1 );
			add_filter( self::HANDLER_FILTER_PREFIX . $screen->getId(), array( $this, 'handle' ), 10, 3 );
			add_filter( 'removable_query_args', array( $this, 'removable_query_args' ), 10, 1 );
			add_action( 'admin_notices', array( $this, 'notice' ), 10, 0 );
		}
	}

	/**
	 * @return array
	 */
	protected function defaults(): array {
		return array(
			'screen'         => null,
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
	 * @return \SergeLiatko\WPBulkAction\GetActionsInterface
	 */
	public function getScreen(): GetActionsInterface {
		return $this->screen;
	}

	/**
	 * @param \SergeLiatko\WPBulkAction\GetActionsInterface $screen
	 *
	 * @return Action
	 */
	public function setScreen( GetActionsInterface $screen ): Action {
		$this->screen = $screen;

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
		//do not process items if already processed
		if ( false === $this->getDidAction() ) {
			$processed_count = 0;
			foreach ( $item_ids as $id ) {
				//if result is not empty, then it is processed successfully
				if ( !self::isEmpty( call_user_func( $this->getCallback(), $id ) ) ) {
					$processed_count ++;
				}
			}
			$this->setProcessedCount( $processed_count );
			$this->setDidAction( true );
		}

		return add_query_arg(
			array(
				$this->getQueryParam()          => $this->getProcessedCount(),
				$this->getQueryParamRequested() => count( $item_ids ),
			),
			$this->getScreen()->remove_actions_query_params( $redirect )
		);
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
		$this->query_param = self::QUERY_PREFIX . sanitize_key( $query_param );

		return $this;
	}

	/**
	 * @return string
	 */
	public function getQueryParamRequested(): string {
		if ( empty( $this->query_param_requested ) ) {
			$this->setQueryParamRequested( $this->getAction() );
		}

		return $this->query_param_requested;
	}

	/**
	 * @param string $query_param_requested
	 *
	 * @return Action
	 */
	public function setQueryParamRequested( string $query_param_requested ): Action {
		$this->query_param_requested = self::QUERY_PREFIX_REQUESTED . sanitize_key( $query_param_requested );

		return $this;
	}

	/**
	 * @param string[] $query_args
	 *
	 * @return string[]
	 */
	public function removable_query_args( array $query_args ): array {
		array_push( $query_args, $this->getQueryParam(), $this->getQueryParamRequested() );

		return $query_args;
	}

	/**
	 * Displays admin notice if query parameter is present.
	 */
	public function notice() {
		if (
			!is_null( $screen = get_current_screen() )
			&& ( $screen->id === $this->getScreen()->getId() )
			&& isset( $_REQUEST[ $field = $this->getQueryParam() ] )
		) {
			$updated_count   = absint( $_REQUEST[ $field ] );
			$requested_count = absint( $_REQUEST[ $this->getQueryParamRequested() ] );
			printf(
				'<div class="%1$s">%2$s</div>',
				sprintf(
					'notice %s is-dismissible',
					( $updated_count === $requested_count ) ? 'notice-success' : 'notice-warning'
				),
				wpautop( sprintf( $this->getMessage( $updated_count ), $updated_count ), false )
			);
		}
	}

	/**
	 * @param int $items_count
	 *
	 * @return string
	 */
	protected function getMessage( int $items_count ): string {
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

	/**
	 * @return bool
	 */
	private function getDidAction(): bool {
		return !empty( $this->did_action );
	}

	/**
	 * @param bool $did_action
	 *
	 * @return void
	 */
	private function setDidAction( bool $did_action ): void {
		$this->did_action = $did_action;
	}

	/**
	 * @return int
	 */
	private function getProcessedCount(): int {
		return $this->processed_count;
	}

	/**
	 * @param int $processed_count
	 */
	private function setProcessedCount( int $processed_count ): void {
		$this->processed_count = $processed_count;
	}

}
