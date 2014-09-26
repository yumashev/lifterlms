<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
* Base Course Class
*
* Class used for instantiating course object
*
* @version 1.0
* @author codeBOX
* @project lifterLMS
*/
class LLMS_Course {

	/**
	* ID
	* @access public
	* @var int
	*/
	public $id;

	/**
	* Post Object
	* @access public
	* @var array
	*/
	public $post;

	/**
	* Constructor
	*
	* initializes the course object based on post data
	*/
	public function __construct( $course ) {

		if ( is_numeric( $course ) ) {

			$this->id   = absint( $course );
			$this->post = get_post( $this->id );

		} 

		elseif ( $course instanceof LLMS_Course ) {

			$this->id   = absint( $course->id );
			$this->post = $course;

		} 

		elseif ( isset( $course->ID ) ) {

			$this->id   = absint( $course->ID );
			$this->post = $course;

		}

	}

	/**
	* __isset function
	*
	* checks if metadata exists
	*
	* @param string $item
	*/
	public function __isset( $item ) {

		return metadata_exists( 'post', $this->id, '_' . $item );

	}

	/**
	* __get function
	*
	* initializes the course object based on post data
	*
	* @param string $item
	* @return string $value
	*/
	public function __get( $item ) {

		$value = get_post_meta( $this->id, '_' . $item, true );

		return $value;
	}

	/**
	 * Get SKU
	 *
	 * @return string
	 */
	public function get_sku() {

		return $this->sku;

	}

	/**
	 * Get Lesson Length
	 *
	 * @return string
	 */
	public function get_lesson_length() {

		return $this->lesson_length;

	}


	/**
	 * Get Video (oembed)
	 *
	 * @return mixed (default: '')
	 */
	public function get_video() {
		
		if ( ! isset( $this->video_embed ) ) {

			return '';

		}

		else {

			return wp_oembed_get($this->video_embed);

		}

	}

	/**
	 * Get Difficulty
	 *
	 * @return string
	 */
	public function get_difficulty() {

		$terms = get_the_terms($this->id, 'course_difficulty'); 

		if ( $terms === false ) {

			return '';

		}

		else {

			foreach ( $terms as $term ) {

        		return $term->name;
        	}

		}

	}

	/**
	 * Get the Course Section and Lesson information
	 *
	 * @return string
	 */
	public function get_syllabus() {

		$syllabus = $this->sections; 
		
		return $syllabus;

	}

	/**
	 * Get price in html format
	 *
	 * @return string
	 */
	public function get_price_html( $price = '' ) {

		$suffix 				= $this->get_price_suffix_html();
		$currency_symbol 		= get_lifterlms_currency_symbol() != '' ? get_lifterlms_currency_symbol() : '';
		$display_price 			= $this->get_price();
		$display_base_price 	= $this->get_base_price();
		$display_sale_price    	= $this->get_sale_price();
		
		if ( $this->get_price() > 0 ) {

			$price = $this->set_price_html_as_value($suffix, $currency_symbol, $display_price, $display_base_price, $display_sale_price);	

		} 

		elseif ( $this->get_price() === '' ) {

			$price = apply_filters( 'lifterlms_empty_price_html', '', $this );

		} 

		elseif ( $this->get_price() == 0 ) {

			$price = $this->list_price_html_as_free();
			
		}

		return apply_filters( 'lifterlms_get_price_html', $price, $this );
	}


	/**
	 * Set price html to a decimal value with currency and suffix.
	 *
	 * @return string
	 */
	public function set_price_html_as_value($suffix, $currency_symbol, $display_price, $display_base_price, $display_sale_price) {
		

		// Check if price is on sale and base price exists 
		if ( $this->is_on_sale() && $this->get_base_price() ) {

			//generate price with formatting and suffix
			$price = $currency_symbol;

			$price .= $this->get_price_variations_html( $display_base_price, $display_price ) . $suffix;

			$price = apply_filters( 'lifterlms_sale_price_html', $price, $this );

		} 

		else {

			//generate price with formatting and suffix
			$price = $currency_symbol;

			$price .= llms_price( $display_price ) . $suffix;

			$price = apply_filters( 'lifterlms_price_html', $price, $this );

		}

		return $price;

	}

	/**
	 * Set price html to Free is ocurse is 0
	 *
	 * @return string
	 */
	public function set_price_html_as_free() {

		if ( $this->is_on_sale() && $this->get_base_price() ) {

			$price .= $this->get_price_variations_html( $display_base_price, __( 'Free!', 'lifterlms' ) );

			$price .= apply_filters( 'lifterlms_free_sale_price_html', $price, $this );

		} 

		else {

			$price = __( 'Free!', 'lifterlms' );

			$price = apply_filters( 'lifterlms_free_price_html', $price, $this );

		}

		return $price;

	}

	/**
	 * Check: Is the sale price different than the base price and is the sale price equal to the price returned from get_price(). 
	 *
	 * @return bool
	 */
	public function is_on_sale() {

		return ( $this->get_sale_price() != $this->get_base_price() && $this->get_sale_price() == $this->get_price() );

	}

	/**
	 * Get function for price value.
	 *
	 * @return void
	 */
	public function get_price() {

		return apply_filters( 'lifterlms_get_price', $this->price, $this );

	}

	/**
	 * Set function for price value.
	 *
	 * @return void
	 */
	public function set_price( $price ) {

		$this->price = $price;

	}

	/**
	 * get the base price value.
	 *
	 * @return void
	 */
	public function get_base_price( $price = '' ) {

		$price = $price;

	}

	/**
	 * get the base price value.
	 *
	 * @return void
	 */
	public function get_sale_price( $price = '' ) {

		$price = $price;

	}

	/**
	 * creates the price suffix html
	 *
	 * @return void
	 */
	public function get_price_suffix_html() {


		$price_display_suffix  = get_option( 'lifterlms_price_display_suffix' );

		if ( $price_display_suffix ) {

			$price_display_suffix = ' <small class="lifterlms-price-suffix">' . $price_display_suffix . '</small>';

			$price_display_suffix = str_replace( $find, $replace, $price_display_suffix );

		}

		return apply_filters( 'lifterlms_get_price_suffix_html', $price_display_suffix, $this );
	}

	/**
	 * Returns base price and sale price in html format.
	 *
	 * @return string
	 */
	public function get_price_variations_html( $base, $sale ) {

		return '<del>' . ( ( is_numeric( $base ) ) ? llms_price( $base ) : $base ) . '</del> <ins>' . ( ( is_numeric( $sale ) ) ? llms_price( $sale ) : $sale ) . '</ins>';
	
	}


	/**
	 * checks if course is visible
	 *
	 * @return bool
	 */
	public function is_visible() {

		$visible = true;


		// visibility setting
		if ( $this->visibility === 'hidden' ) {

			$visible = false;

		} 

		elseif ( $this->visibility === 'visible' ) {

			$visible = true;

		// Visibility in loop
		} 

		elseif ( $this->visibility === 'search' && is_search() ) {

			$visible = true;

		} 

		elseif ( $this->visibility === 'search' && ! is_search() ) {

			$visible = false;

		} 

		elseif ( $this->visibility === 'catalog' && is_search() ) {

			$visible = false;

		} 

		elseif ( $this->visibility === 'catalog' && ! is_search() ) {

			$visible = true;
		}

		return apply_filters( 'lifterlms_course_is_visible', $visible, $this->id );

	}

}