<?php
/**
 * Main Class for Cart Converter
 */
class MainController extends Controller {

	//** @var MainController The single instance of the class */
    protected static $_instance = null;

    public $logger;

    private $cartController;

    /**
     * Main MainController Instance
     *
     * Ensures only one instance of MainController is loaded or can be loaded.
     *
     * @since 1.0
     * @static
     * @return MainController Main instance
     */
    public static function getInstance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     * include all files related to normal action
     */
    public function __construct()
    {
        $this->includes();
        $this->setup();
        $this->hooks();
    }

    /**
     * Run this setup database once time when register plugin
     * @uses $wpdb
     * @uses $dbDelta
     */
	public static function setup_db()
    {
        $installed_ver = get_option( CART_CONVERTER . "_db_version" );

        // prevent create table when re-active plugin
        if ( $installed_ver != CART_CONVERTER_DB_VERSION ) {
            CartModel::setup_db();
            EmailModel::setup_db();
            //MailTemplateModel::setup_db();
            
            add_option( CART_CONVERTER . "_db_version", CART_CONVERTER_DB_VERSION );
        }
    }

    /**
     * All includes for Normal Action
     * @uses All Controller
     * @uses All Model
     */
	function includes()
    {
        
        do_action('cart_converter_includes_action');
    }

    /**
     * Setup logger and controller to process cart and option screen
     * @uses CartController
     * @uses CartOptionsController
     */
    function setup()
    {
        $this->logger = Logger::getLogger( CART_CONVERTER );
        //$this->logger->debug("main controller setup " + current_time( "timestamp" ) );
        $this->cartController = CartController::getInstance();
        
        // create options panel
        $cartOptions = new CartOptionsController();
        $cartOptions->init();

        // init cron
        $cron = new CronController();

        // init report
        $report = new ReportController();

        do_action( 'cart_converter_setup_action' );
    }

    /**
     * All hook for this plugin
     * @uses woocommerce_order_status_failed hooked
     * @uses woocommerce_order_status_changed hooked
     * @uses plugins_loaded hooked
     */
    function hooks()
    {
        // Add a settings link next to the "Deactivate" link on the plugin listing page
        add_filter( 'plugin_action_links_cart-converter/cart-converter.php', array( &$this, 'plugin_action_links' ) );
   	
        // woo order status changed
     	add_action( 'woocommerce_order_status_failed', array( &$this->cartController, 'add_abandon_cart' ) );
        add_action( 'woocommerce_order_status_changed', array( &$this->cartController, 'order_status_changed' ), 10, 3);
		
        // place order when create new from cart_id
        add_action( 'woocommerce_checkout_order_processed', array( &$this->cartController, 'convert_place_order' ), 10, 3);

        // restore cart from email
        add_action( 'plugins_loaded', array( &$this, 'plugins_loaded' ) );

        do_action( 'cart_converter_main_hooks_action' );
    }

    /**
     * Call everytime plugin loaded
     * @uses log4php::log
     */
    function plugins_loaded(){
        // $current_time = current_time('timestamp');
        // $this->logger->debug( 'plugins_loaded ' . $current_time );
        
        // perform recover from member mail
        add_action( 'wp_loaded', array( &$this->cartController, 'restore_abandon_cart' ) );
    }

    /**
     * Hook into plugin_action_links filter
     * 
     * Adds a "Settings" link next to the "Deactivate" link in the plugin listing page
     * when the plugin is active.
     * 
     * @param object $links An array of the links to show, this will be the modified variable
     */
    function plugin_action_links( $links ) {
        $links[] = '<a href="'. esc_url( get_admin_url( null, 'admin.php?page=cart-converter' ) ) .'">Settings</a>';
        $rate_url = 'http://wordpress.org/support/view/plugin-reviews/cart-converter?rate=5#postform';
        $links[] = '<a target="_blank" href="' . $rate_url . '" title="Click here to rate and review this plugin on WordPress.org">Rate this plugin</a>';
        return $links;
    }
    public static function deactivate(){
        CronController::deactivate();
    }
}
// init once
MainController::getInstance();