<?php
namespace SpiderBoxes\Database\Traits;

trait Serializable {

	/**
	 * Serialize attributes that need serialization
	 */
	protected function serialize_attributes() {
		foreach ( $this->serializable as $field ) {
			if ( isset( $this->attributes[ $field ] ) && is_array( $this->attributes[ $field ] ) ) {
				// Keep as array in memory, only serialize for database
			}
		}

		return $this;
	}

	/**
	 * Unserialize attributes from database
	 */
	protected function unserialize_attributes() {
		foreach ( $this->serializable as $field ) {
			if ( isset( $this->attributes[ $field ] ) ) {
				$this->attributes[ $field ] = maybe_unserialize( $this->attributes[ $field ] );

			}
		}

		return $this;
	}

	/**
	 * Prepare serializable data for database
	 */
	protected function prepare_serializable_for_database( array $data ) {
		foreach ( $this->serializable as $field ) {
			if ( isset( $data[ $field ] ) && is_array( $data[ $field ] ) ) {
				$data[ $field ] = maybe_serialize( $data[ $field ] );
			}
		}

		return $data;
	}
}
