<?php


namespace SergeLiatko\WPBulkAction;

/**
 * Class Factory
 *
 * @package SergeLiatko\WPBulkAction
 */
class Factory {

	/**
	 * @param array $items
	 *
	 * @return array|\SergeLiatko\WPBulkAction\getIdInterface[]
	 */
	public static function mapIdToKey( array $items ): array {
		$new_items = array();
		/** @var mixed|\SergeLiatko\WPBulkAction\getIdInterface $item */
		foreach ( $items as $item ) {
			if ( in_array( 'SergeLiatko\WPBulkAction\getIdInterface', class_implements( get_class( $item ) ) ) ) {
				$new_items[ $item->getId() ] = $item;
			}
		}

		return $new_items;
	}

	/**
	 * @param array $item
	 *
	 * @return mixed
	 * @noinspection PhpUnused
	 */
	public static function createManager( array $item ) {
		return self::create( $item, 'SergeLiatko\WPBulkAction\Manager' );
	}

	/**
	 * @param array  $item
	 * @param string $default_class
	 *
	 * @return mixed
	 */
	protected static function create( array $item, string $default_class ) {
		$class = empty( $item['_class'] ) ? $default_class : $item['_class'];
		unset( $item['_class'] );

		return new $class( $item );
	}

	/**
	 * @param array $item
	 *
	 * @return mixed
	 */
	public static function createScreen( array $item ) {
		return self::create( $item, 'SergeLiatko\WPBulkAction\Screen' );
	}

	/**
	 * @param array $item
	 *
	 * @return mixed
	 */
	public static function createAction( array $item ) {
		return self::create( $item, 'SergeLiatko\WPBulkAction\Action' );
	}
}
