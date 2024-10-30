<?php
/**
 * Handle all action to Abandon Cart Items
 */
class CartController extends Controller{
	/** @var CartController keep instance */
	protected static $instance;

    public  $logger;
    private $cartModel;
    private $emailModel;

	/**
	 * Singleton pattern for CartController
	 * @return CartController one instance
	 */
    public static function getInstance()
    {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     * include all files related to normal action
     */
    public function __construct()
    {
        // $this->includes();
        $this->setup();
        // $this->hooks();
    }

    /**
     * Setup logger and Model object
     * @return [type] [description]
     */
    function setup(){
    	$this->logger = Logger::getLogger( CART_CONVERTER );
    	$this->cartModel  = new CartModel();
    	$this->emailModel = new EmailModel();
    }

    /**
     * Catch checkout failed on collect abandoned cart
     * @uses  woocommerce_order_status_failed hooked
     */
    public function add_abandon_cart( $order_id ){
    	$this->logger->debug( 'woocommerce_order_status_failed = ' . $order_id );
    	$order = new WC_Order( $order_id );
    	
    	// Member Only now
    	if ( is_user_logged_in() ) {
    		
    		// collect new cart
    		if ( true === $this->cartModel->add_abandon_cart_member( $order ) ){
    			// set wp-cron schedule_single to check order based on get_option
    			CronController::add_collecting_job( $order );
    		}
    	} else {
    		// TODO: need more action to collect GUEST id = 0 data
            $guest_cart = maybe_serialize( get_user_meta( 0, '_woocommerce_persistent_cart' ) );
            $this->logger->debug( 'failed guest_cart data = ' . $guest_cart );
    	}
    }

    /**
     * Catch order status changed
     * @uses  woocommerce_order_status_changed hooked
     */
    public function order_status_changed( $order_id, $old_status, $new_status ){
    	$this->logger->debug( 'woocommerce_order_status_changed |' . $order_id . ' |' . $old_status . ' |' . $new_status );

    	if ( $new_status == "cancelled" ) {
    		// redirect to collecting
    		$this->add_abandon_cart( $order_id );
    	}
    	if ( $new_status == "completed" ) {
            // nothing to do           
    	}
    }
    /**
     * [convert_place_order description]
     * @param  [type] $order_id [description]
     * @return [type]           [description]
     */
    public function convert_place_order( $order_id ){
        $this->logger->debug( 'woocommerce_order_status_completed |' . $order_id );

        // update status for order if abandon to complete
        if ( isset( $_COOKIE['convert_cart_id'] ) ) {
            $cart_id = $_COOKIE['convert_cart_id'];

            // update cart place_order = new order_id
            $this->cartModel->set_cart_place_order( $cart_id, $order_id );
            
            // and add data to report table
            $this->cartModel->add_convert_paid_order( $cart_id, $order_id );

            unset( $_COOKIE['convert_cart_id'] );
            setcookie( "convert_cart_id", null, -1, "/" );
        }
    }
    /**
     * Collect abandoned carts which cart status is still canceled or failed after time-setting
     *
     * 
     */
    public static function collect_cart(){
        // 1. get all cart-status is NEW which has time + cut_off_time < current
        $cartOptions  = new CartOptionsController();
        $cut_off_time = $cartOptions->get_cut_off_time();

    	$abandoned_carts = CartController::getInstance()->cartModel->get_carts_by_status( 'NEW', $cut_off_time );
    	
    	// 2. update cart status to ABANDONED
    	CartController::getInstance()->cartModel->insert_abandoned_carts( $abandoned_carts );
    	
    	// 3. insert to mail_queue
    	CartController::getInstance()->emailModel->insert_queue( $abandoned_carts );
    }

    /**
     * Restore abandone cart when user click to cart_link
     * 
     */
    public function restore_abandon_cart(){
        global $woocommerce;

        if ( isset( $_GET['abandon_cart'] ) ){
            $cart_id = $_GET['abandon_cart'];
            
            // get cart data from database
            $cart = CartController::getInstance()->cartModel->get_abandoned_cart( $cart_id );

            // restore Cart Session
            try {
                if ( function_exists('WC') ) {
                    WC()->session->cart = $cart;
                    WC()->cart->set_session();
                } else {
                    $woocommerce->session->cart = $cart;
                }
            } catch (Exception $e) {
                MainController::getInstance()->logger->debug( $e->getTraceAsString() );
            }
            
            if ( isset( $_GET['coupon_code'] ) ){
                $coupon_code = $_GET['coupon_code'];
                $coupon      = new WC_Coupon( $coupon_code );

                if ( $coupon->is_valid() ){
                    $this->logger->debug( 'coupon code is valid |' . $coupon_code );

                    WC()->cart->add_discount( $coupon_code );
                }
            }

            // update status to CONVERTED
            CartController::getInstance()->cartModel->set_cart_converted( $cart_id );
            setcookie( "convert_cart_id", $cart_id, time() + 3600, "/" );
            
            wp_safe_redirect( WC()->cart->get_cart_url() );
            exit;
        }
    }
}
// init once
CartController::getInstance();