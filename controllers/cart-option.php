<?php
/**
* Create Setting Page under WooCommerce Menu
*/
class CartOptionsController extends Controller {
	public $default = array();
	public $url = "";
	/**
	 * Constructor
	 */
	function __construct(){
		$this->view_folder = "options";
		$this->default = array(
			CART_CONVERTER_PREFIX . "_cart_time"      => 1, 
			CART_CONVERTER_PREFIX . "_cart_time_measure" => 1, // 1 : Days | 2 : Hours | 3 : Minutes,
			CART_CONVERTER_PREFIX . "_subject"        => "Hey, did you forget something?",
			CART_CONVERTER_PREFIX . "_email_template" => "<p>Hey {#customer_name},</p> <p>We noticed your were shopping on our site but didn't complete your purchase.</p> <p>If you change your mind, it's not too late!</p> <p>You can access your abandoned cart here {#cart_link}</p> Thanks!",
			CART_CONVERTER_PREFIX . "_coupon_percent" => "10",
			CART_CONVERTER_PREFIX . "_coupon_code"    => ""
		);
		$this->message[1] = __("Save Sucessful", CART_CONVERTER_PREFIX);
		$this->url = admin_url( "admin.php" ) . '?page=cart-converter';
		parent::__construct();
	}

	/**
	 * Initialization hooks and filters
	 */
	public function init(){
		add_action( 'admin_menu', array( &$this, "_admin_menu" ) );
		//load script
		add_action( 'admin_enqueue_scripts', array( &$this, "_load_scripts" ) );
		add_action( "init", array( &$this, "_save_options" ) );
	}

	/**
	 * Sanitize by WP_KSES
	 * @param  string $str
	 * @uses   wp_kses
	 */
	private function _sanitize( $str ) {
	    if ( !function_exists( 'wp_kses' ) ) {
	        require_once( ABSPATH . 'wp-includes/kses.php' );
	    }
	    global $allowedposttags;
	    global $allowedprotocols;
	    
	    if ( is_string( $str ) ) {
	        $str = wp_kses( $str, $allowedposttags, $allowedprotocols );
	    } elseif( is_array( $str ) ) {
	        $arr = array();
	        foreach( (array) $str as $key => $val ) {
	            $arr[$key] = $this->_sanitize( $val );
	        }
	        $str = $arr;
	    }
	    
	    return $str;
	}
	/**
	 * Save settings
	 * @uses wp_verify_nonce
	 * @uses wp_safe_redirect
	 * @uses update_option
	 */
	public function _save_options(){
		if ( $_SERVER['REQUEST_METHOD'] == "POST" 
			&& isset( $_GET["page"] ) 
			&& $_GET["page"] == "cart-converter" 
			&& isset( $_REQUEST[CART_CONVERTER_PREFIX."_nonce"] ) 
		){
			$data = array();
			if ( wp_verify_nonce( $_REQUEST[CART_CONVERTER_PREFIX."_nonce"], CART_CONVERTER_PREFIX . "-update-options" ) ){
				if ( ! empty( $_POST["data"] ) ) {
					foreach ( $_POST["data"] as $key => $value ) {
							$data[ $key ] = $this->_sanitize( $value );
					}
				}
				$old_data   = $this->_get_option();
				$active_tab = isset( $_GET["tab"] ) ? $_GET["tab"] : "";

				// create new coupon when percent change and if coupons are enabled
				$coupons_enabled = get_option( 'woocommerce_enable_coupons' );

				if ( 'email' == $active_tab && 
					 'yes'   == $coupons_enabled 
				) {
					
					$old_coupon_percent = $old_data[ CART_CONVERTER_PREFIX . "_coupon_percent" ];
					$coupon_percent     = $data[ CART_CONVERTER_PREFIX . "_coupon_percent" ];
					if ( $old_coupon_percent != $coupon_percent ){
						$coupon_code    = "CC" . $coupon_percent . date("YmdHs");

						$result = CouponsController::add_new_coupon( $coupon_code, $coupon_percent );
						
						// update current coupon code on applying
						if ( $result ) $data[ CART_CONVERTER_PREFIX . "_coupon_code" ] = $coupon_code;
					}

				}

				// prepare data before update
				$data       = array_merge( $old_data, $data );
				update_option( CART_CONVERTER_PREFIX, $data );
				
				// trigger event on associated tab
				if ( $active_tab == 'general' ) {
					// run collect cart after change settings
					$cart = new CartController();
					$cart->collect_cart();

					// clear all schedule before add new
					wp_clear_scheduled_hook( CronController::HOOK_COLLECT_CART );
					CronController::add_collecting_job();
				}

				// redirect after update new carts
				wp_safe_redirect( $_REQUEST['_wp_http_referer'] . '&message=1' );
			}
		}
	}

	/**
	 * Get All options
	 * @uses  wp::load-script::get_option()
	 * @return Array Array of Option
	 */
	private function _get_option(){
		$data = get_option( CART_CONVERTER_PREFIX );
		if( empty( $data ) ){
			$data = $this->default;
		}
		return $data;
	}

	/**
	 * Get single key option
	 * @param  string $key option name
	 * @return option-value
	 */
	public function get_option ( $key ){
	    $options = $this->_get_option();
	    return $options[ CART_CONVERTER_PREFIX . "_" . $key ];
	}

	/**
	 * Load script for Admin Screen
	 * @param  [type] $hook [description]
	 * @return [type]       [description]
	 */
	public function _load_scripts( $hook ){
		if( isset($_GET["page"]) && $_GET["page"] == "cart-converter" ){
			wp_enqueue_script( "jquery-ui-tabs" );
			wp_enqueue_style( "jquery-ui-css", CART_CONVERTER_URL . '/assets/css/jquery-ui.min.css' );
			wp_enqueue_style( "admin-css", CART_CONVERTER_URL . '/assets/css/admin.css' );
		}
	}

	/**
	 * Register admin menu
	 * @uses add_submenu_page
	 */
	public function _admin_menu(){
		$hook = add_submenu_page( 'woocommerce', CART_CONVERTER_NAME, CART_CONVERTER_NAME, 'manage_woocommerce', 'cart-converter', array( &$this, '_admin_panel' ));
		add_action( "load-$hook", array( &$this, "_add_options" ) );
	}
	/**
	 * Register creen options
	 * @uses add_screen_options
	*/
	function _add_options(){
		$option = 'per_page';
		$args = array(
		    'label' => 'items',
		    'default' => 10,
		    'option' => CART_CONVERTER_PREFIX . '_per_page'
		);
		add_screen_option( $option, $args );
	}
	/**
	 * Admin panel action to review index
	 * @uses Controller::render_view
	 */
	public function _admin_panel(){
		$data["data"] 	  = $this->_get_option();
		$data["_wpnonce"] = wp_nonce_field( CART_CONVERTER_PREFIX . "-update-options", CART_CONVERTER_PREFIX."_nonce", true, false );
		$data["message"]  = isset( $_GET["message"] ) ? $this->message[ $_GET["message"] ] : "";
		$table = new CartConvertTable();
		$data["table"] = $table;
		$tab =  array(
			"general" => array(
				"label" => "General Setting",
				"url"  => $this->url . "&tab=general",
				"active" => 0
			),
			"email" => array(
				"label" => "Email Setting",
				"url" => $this->url . "&tab=email",
				"active" => 0
			),
			"cart-list" => array( 
				"label" => "Cart List",
				"url" => $this->url . "&tab=cart-list",
				"active" => 0
			)
			
		);
		$active_tab = isset( $_GET["tab"] ) ? $_GET["tab"] : "";
		foreach ( $tab as $key => $value ) {
			if ( $key == $active_tab ){
				$tab[ $key ]["active"] = 1;
			}
		}
		if ( empty( $active_tab ) ) {
			$tab["general"]["active"] = 1;
		}
		$data["tab"] = $tab;
		
		$this->render_view( "index.php", $data );
	}

	/**
	 * Get Cut off time from Options
	 * @return timestamp
	 */
	public function get_cut_off_time(){
		$cut_off_time_option = array(
			"value" => $this->get_option("cart_time"),
			"type"  => $this->get_option("cart_time_measure")
		);

		// 1 : Days | 2 : Hours | 3 : Minutes,
		if ( $cut_off_time_option["type"] == 1 ){
			// Days
			$cut_off_time = $cut_off_time_option["value"] * 86400;
		} else if  ( $cut_off_time_option["type"] == 2 ){
			// Hours
			$cut_off_time = $cut_off_time_option["value"] * 3600;
		} else if  ( $cut_off_time_option["type"] == 3 ){
			// Minutes
			$cut_off_time = $cut_off_time_option["value"] * 60;
		} else 
			// Default = 1 hour
			$cut_off_time = 3600;

		return $cut_off_time;
	}

	/**
	 * Get email setting from options
	 * 
	 */
	public function get_email_settings(){
		return array(
				'subject'        => $this->get_option("subject"),
				'email_template' => $this->get_option("email_template")
			);
	}

}