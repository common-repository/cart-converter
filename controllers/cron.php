<?php
/**
 * Handle all cron action to wp-cron/server cron
 */
class CronController extends Controller {

	/* Define hook name constance */
	const HOOK_COLLECT_CART = "cart_app_collect_abandon_cart";
	const HOOK_PROCESS_MAIL = "cart_app_process_email_queue";

	/**
     * Constructor
     * @uses  add_hook(): single job to collecting abandoned cart
     * @uses  add_hook(): schedule job to send queue email each 20 minutes
     */
    public function __construct()
    {
        $this->hooks();
        $this->setup();
    }

    /**
     * Setup Hook for WP-CRON
     * @uses  add_hook(): single job to collecting abandoned cart
     * @uses  add_hook(): schedule job to send queue email each 20 minutes
     */
    private function hooks(){
    	CartController::getInstance();
    	EmailController::getInstance();

    	add_action( self::HOOK_COLLECT_CART,  array( "CartController", "collect_cart" ) );
    	add_action( self::HOOK_PROCESS_MAIL,  array( "EmailController", "process_queue" ) );
    }
	/**
	 * Set cron for process email each 20 minitues
	 * @uses wp_schdule_next
	 */
	public function setup(){
		//self::add_collecting_job();
		//self::add_sendmail_job();
	}

	/**
	 * Create schedule_single to collect abandoned cart
	 * @param WooOrder $order OrderData from CartController
	 * @uses  wp_schedule_single_event( $timestamp, $hook, $args )
	 * @uses  HOOK_COLLECT_CART = CartController::collect_cart
	*/ 
	public static function add_collecting_job( $order = null ){
		$current_time = current_time("timestamp");
		
		$cartOptions  = new CartOptionsController();
		$cut_off_time = $cartOptions->get_cut_off_time();
		
		// DONT SEND IT BECAUSE WILL NO DELETE ALL CRON 
		// WHEN SAVE SETTINGS
		// $hook_args = array(
		// 	'order_id'=> $order->id,
		// 	'user_id' => $order->user_id
		// 	);
		MainController::getInstance()->logger->debug("cron add-collecting run at: " . current_time( "mysql",1 ) );
		// create single event for each order
		wp_schedule_single_event( $current_time + $cut_off_time, self::HOOK_COLLECT_CART );
	}

	/**
	 * Create schedule_single to collect abandon cart
	 * @param WooOrder $order OrderData from CartController
	 * @uses  wp_schedule_single_event( $timestamp, $hook, $args )
	 * @uses  HOOK_PROCESS_MAIL = EmailController::process_queue
	 */
	public static function add_sendmail_job(){
		$current_time = current_time("timestamp");
		$cut_off_time = 20 * 60; // 20 minutes
		$timestamp    = $current_time + $cut_off_time;
		$recurrence   = true;

		MainController::getInstance()->logger->debug("cron add_sendmail_job run at: " . current_time( "mysql",1 ) );
		wp_schedule_single_event( $timestamp, self::HOOK_PROCESS_MAIL );
	}

	/**
	 * Remove all schedule hooked by
	 * @uses cart_app_collect_abandon_cart
	 * @uses cart_app_process_email_queue
	 */
	public static function deactivate(){
		wp_clear_scheduled_hook( self::HOOK_COLLECT_CART );
		wp_clear_scheduled_hook( self::HOOK_PROCESS_MAIL );
	}

}