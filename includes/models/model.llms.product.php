<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class LLMS_Product extends LLMS_Post_Model {

	protected $db_post_type = 'product'; // maybe fix this
	protected $model_post_type = 'product';

	/**
	 * Retrieve the max number of access plans that can be created
	 * for this product
	 * @return int
	 * @since 3.0.0
	 */
	public function get_access_plan_limit() {
		return apply_filters( 'llms_get_product_access_plan_limit', 6, $this );
	}

	/**
	 * Get all access plans for the product
	 * @return array of LLMS_Access_Plans instances
	 * @since  3.0.0
	 */
	public function get_access_plans() {

		$q = new WP_Query( apply_filters( 'llms_get_product_access_plans_args ', array(
			'meta_key' => '_llms_product_id',
			'meta_value' => $this->get( 'id' ),
			'order' => 'ASC',
			'orderby' => 'menu_order',
			'post_per_page' => $this->get_access_plan_limit(),
			'post_type' => 'llms_access_plan',
			'status' => 'publish',
		), $this ) );

		// if we have plans, setup access plan instances
		if ( $q->have_posts() ) {
			$plans = array();
			foreach( $q->posts as $post ) {
				$plans[] = new LLMS_Access_Plan( $post );
			}
			return $plans;
		}
		// else return an empty array
		else {
			return array();
		}

	}

	public function get_pricing_table_columns_count() {
		$count = count( $this->get_access_plans() );

		switch( $count ) {

			case 0:
				$cols = 1;
			break;

			case 6:
				$cols = 3;
			break;

			default:
				$cols = $count;
		}
		return apply_filters( 'llms_get_product_pricing_table_columns_count', $cols, $this, $count );
	}

	/**
	 * Deterime if the product is purchasable
	 * At least one gateway must be enabled and at least one access plan must exist
	 * If the product is a course, additionally checks to ensure course enrollment is open and has capacity
	 * @return  boolean
	 * @since   3.0.0
	 * @version 3.0.0
	 */
	public function is_purchasable() {
		$gateways = LLMS()->payment_gateways();

		if ( 'course' === $this->get( 'type' ) ) {

			$course = new LLMS_Course( $this->get( 'id' ) );
			if ( ! $course->is_enrollment_open() ) {
				return false;
			}
			if ( ! $course->has_capacity() ) {
				return false;
			}

		}

		if ( $gateways->has_gateways( true ) && $this->get_access_plans() ) {
			return true;
		}
		return false;
	}



	/**
	 * Get a property's data type for scrubbing
	 * used by $this->scrub() to determine how to scrub the property
	 * @param  string $key  property key
	 * @return string
	 * @since  3.0.0
	 */
	protected function get_property_type( $key ) {

		switch( $key ) {

			case 'access_length':
			case 'frequency':
			case 'length':
			case 'product_id':
			case 'trial_length':
				$type = 'absint';
			break;

			case 'price':
			case 'trial_price':
			case 'sale_price':
				$type = 'float';
			break;

			case 'access_period':
			case 'access_expires':
			case 'access_expiration':
			case 'enroll_text':
			case 'featured':
			case 'on_sale':
			case 'period':
			case 'sale_end':
			case 'sale_start':
			case 'sku':
			case 'trial_offer':
			case 'trial_period':
			default:
				$type = 'text';

		}

		return $type;

	}

}