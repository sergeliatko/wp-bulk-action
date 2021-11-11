<?php


namespace SergeLiatko\WPBulkAction;

/**
 * Interface GetActionsInterface
 *
 * @package SergeLiatko\WPBulkAction
 */
interface GetActionsInterface extends GetIdInterface {

	/**
	 * @return array|\SergeLiatko\WPBulkAction\Action[]
	 */
	public function getActions(): array;

	/**
	 * @param string $url
	 *
	 * @return string
	 */
	public function remove_actions_query_params( string $url ): string;

}
