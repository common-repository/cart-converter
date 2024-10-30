<?php
/**
 * Add report to WC Admin Report
 */
class ReportController extends Controller{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		add_action('woocommerce_reports_charts', array($this, 'report_tabs'));
	}

	public function report_tabs($tabs) {
		
		$tabs['cart-converter'] = array(
				'title' 	=>  __( 'Cart Converter', 'woocommerce' ),
				'reports' => array(
					"carts_by_date" => array(
						'title'       => __( 'Carts By Date', 'woocommerce' ),
						'description' => '',
						'hide_title'  => true,
						'callback'    => array( __CLASS__, 'get_report' )
					),
					"carts_by_product" => array(
						'title' => __('Carts By Product', 'woocommerce'),
						'description' => '',
						'hide_title'  => true,
						'callback'    => array( __CLASS__, 'get_report' )
					),
			)
		);
		
		return $tabs;
	}

	/**
	 * Get a report from our reports subfolder
	 */
	public static function get_report( $name ) {
		$name  = sanitize_title( str_replace( '_', '-', $name ) );
		$class = 'WC_Report_' . str_replace( '-', '_', $name );

		include_once( CART_CONVERTER_DIR . '/reports/class-wc-report-' . $name . '.php' );

		if ( ! class_exists( $class ) )
			return;

		$report = new $class();
		$report->output_report();
	}
}