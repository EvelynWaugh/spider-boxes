<?php
/**
 * Repository Interface
 *
 * @package SpiderBoxes\Database\Contracts
 */

namespace SpiderBoxes\Database\Contracts;

/**
 * Repository Interface
 */
interface RepositoryInterface {

	/**
	 * Find a record by ID
	 *
	 * @param mixed $id Record ID.
	 * @return array|null
	 */
	public function find( $id );

	/**
	 * Get all records
	 *
	 * @param array $filters Optional filters.
	 * @return array
	 */
	public function all( $filters = array() );

	/**
	 * Create a new record
	 *
	 * @param array $data Record data.
	 * @return bool|int
	 */
	public function create( $data );

	/**
	 * Update a record
	 *
	 * @param mixed $id Record ID.
	 * @param array $data Updated data.
	 * @return bool
	 */
	public function update( $id, $data );

	/**
	 * Delete a record
	 *
	 * @param mixed $id Record ID.
	 * @return bool
	 */
	public function delete( $id );

	/**
	 * Check if a record exists
	 *
	 * @param mixed $id Record ID.
	 * @return bool
	 */
	public function exists( $id );
}
