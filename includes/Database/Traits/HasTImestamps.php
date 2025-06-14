<?php
/**
 * Has Timestamps Trait
 *
 * @package SpiderBoxes\Database\Traits
 */

namespace SpiderBoxes\Database\Traits;

/**
 * Has Timestamps Trait
 */
trait HasTimestamps {

	/**
	 * Timestamp format
	 *
	 * @var string
	 */
	protected $timestamp_format = 'Y-m-d H:i:s';

	/**
	 * Add timestamps to data
	 *
	 * @param array $data Data array.
	 * @param bool  $is_update Whether this is an update operation.
	 * @return array Data with timestamps.
	 */
	public function add_timestamps( array $data, $is_update = false ) {
		$now = current_time( 'mysql' );

		// Always update modified time
		$data['updated_at'] = $now;

		// Only set created time on new records
		if ( ! $is_update && ! isset( $data['created_at'] ) ) {
			$data['created_at'] = $now;
		}

		return apply_filters( 'spider_boxes_model_add_timestamps', $data, $is_update, $this );
	}

	/**
	 * Get created at timestamp
	 *
	 * @param string $format Optional format.
	 * @return string|null
	 */
	public function get_created_at( $format = null ) {
		$created_at = $this->get_attribute( 'created_at' );

		if ( ! $created_at ) {
			return null;
		}

		if ( $format ) {
			$datetime = \DateTime::createFromFormat( 'Y-m-d H:i:s', $created_at );
			return $datetime ? $datetime->format( $format ) : $created_at;
		}

		return $created_at;
	}

	/**
	 * Get updated at timestamp
	 *
	 * @param string $format Optional format.
	 * @return string|null
	 */
	public function get_updated_at( $format = null ) {
		$updated_at = $this->get_attribute( 'updated_at' );

		if ( ! $updated_at ) {
			return null;
		}

		if ( $format ) {
			$datetime = \DateTime::createFromFormat( 'Y-m-d H:i:s', $updated_at );
			return $datetime ? $datetime->format( $format ) : $updated_at;
		}

		return $updated_at;
	}

	/**
	 * Get human readable time difference for created_at
	 *
	 * @return string
	 */
	public function get_created_at_human() {
		$created_at = $this->get_attribute( 'created_at' );

		if ( ! $created_at ) {
			return __( 'Unknown', 'spider-boxes' );
		}

		return sprintf(
			/* translators: %s: human time difference */
			__( '%s ago', 'spider-boxes' ),
			human_time_diff( strtotime( $created_at ), current_time( 'timestamp' ) )
		);
	}

	/**
	 * Get human readable time difference for updated_at
	 *
	 * @return string
	 */
	public function get_updated_at_human() {
		$updated_at = $this->get_attribute( 'updated_at' );

		if ( ! $updated_at ) {
			return __( 'Unknown', 'spider-boxes' );
		}

		return sprintf(
			/* translators: %s: human time difference */
			__( '%s ago', 'spider-boxes' ),
			human_time_diff( strtotime( $updated_at ), current_time( 'timestamp' ) )
		);
	}

	/**
	 * Check if model was created today
	 *
	 * @return bool
	 */
	public function is_created_today() {
		$created_at = $this->get_attribute( 'created_at' );

		if ( ! $created_at ) {
			return false;
		}

		$created_date = date( 'Y-m-d', strtotime( $created_at ) );
		$today        = date( 'Y-m-d' );

		return $created_date === $today;
	}

	/**
	 * Check if model was updated today
	 *
	 * @return bool
	 */
	public function is_updated_today() {
		$updated_at = $this->get_attribute( 'updated_at' );

		if ( ! $updated_at ) {
			return false;
		}

		$updated_date = date( 'Y-m-d', strtotime( $updated_at ) );
		$today        = date( 'Y-m-d' );

		return $updated_date === $today;
	}

	/**
	 * Check if model was recently created (within last hour)
	 *
	 * @return bool
	 */
	public function is_recently_created() {
		$created_at = $this->get_attribute( 'created_at' );

		if ( ! $created_at ) {
			return false;
		}

		$created_timestamp = strtotime( $created_at );
		$hour_ago          = current_time( 'timestamp' ) - HOUR_IN_SECONDS;

		return $created_timestamp > $hour_ago;
	}

	/**
	 * Check if model was recently updated (within last hour)
	 *
	 * @return bool
	 */
	public function is_recently_updated() {
		$updated_at = $this->get_attribute( 'updated_at' );

		if ( ! $updated_at ) {
			return false;
		}

		$updated_timestamp = strtotime( $updated_at );
		$hour_ago          = current_time( 'timestamp' ) - HOUR_IN_SECONDS;

		return $updated_timestamp > $hour_ago;
	}

	/**
	 * Touch the model (update the updated_at timestamp)
	 *
	 * @return $this
	 */
	public function touch() {
		$this->set_attribute( 'updated_at', current_time( 'mysql' ) );
		return $this;
	}

	/**
	 * Get age in seconds
	 *
	 * @return int
	 */
	public function get_age() {
		$created_at = $this->get_attribute( 'created_at' );

		if ( ! $created_at ) {
			return 0;
		}

		return current_time( 'timestamp' ) - strtotime( $created_at );
	}

	/**
	 * Get age in days
	 *
	 * @return int
	 */
	public function get_age_in_days() {
		return floor( $this->get_age() / DAY_IN_SECONDS );
	}

	/**
	 * Scope to get records created within a date range
	 *
	 * @param string $start_date Start date (Y-m-d format).
	 * @param string $end_date End date (Y-m-d format).
	 * @return array Filter array for repository.
	 */
	public static function created_between( $start_date, $end_date ) {
		return array(
			'created_at_start' => $start_date . ' 00:00:00',
			'created_at_end'   => $end_date . ' 23:59:59',
		);
	}

	/**
	 * Scope to get records updated within a date range
	 *
	 * @param string $start_date Start date (Y-m-d format).
	 * @param string $end_date End date (Y-m-d format).
	 * @return array Filter array for repository.
	 */
	public static function updated_between( $start_date, $end_date ) {
		return array(
			'updated_at_start' => $start_date . ' 00:00:00',
			'updated_at_end'   => $end_date . ' 23:59:59',
		);
	}
}
