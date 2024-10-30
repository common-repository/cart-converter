<?php
/**
 * WC_Report_Carts_By_Product class
 */
class WC_Report_Carts_By_Product extends WC_Admin_Report {

	public $product_ids = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		if ( isset( $_GET['product_ids'] ) && is_array( $_GET['product_ids'] ) )
			$this->product_ids = array_map( 'absint', $_GET['product_ids'] );
		elseif ( isset( $_GET['product_ids'] ) )
			$this->product_ids = array( absint( $_GET['product_ids'] ) );
	}

	/**
	 * Get the legend for the main chart sidebar
	 * @return array
	 */
	public function get_chart_legend() {
		if ( ! $this->product_ids )
			return array();

		$order_items = CartModel::get_carts_one_year();
		$found_products = array();		
		
		$product_objs = array();

		if ( $order_items ) {

			foreach ( $order_items as $order_item ) {

				$date 	= date( 'Y-m-d h:i:s', $order_item->post_date );

				$items 	= maybe_unserialize( $order_item->items );

				foreach ( $items as $item ) {
					$cart = $item['cart'];
					$key  = key($cart);
					
					if ( $this->product_ids[0] != $cart[$key]['product_id'] )
				 		continue;
					if ( isset( $cart[$key]['line_total'] ) ) $row_cost = $cart[$key]['line_total'];
					
					$obj = new stdClass();

					$obj->post_date = $date;
					$obj->order_item_count = $cart[$key]['quantity'];
					$obj->product_id = $cart[$key]['product_id'];

					$product_objs[] = $obj;

				}
			}

		}	
			// Prepare data for report
			$order_item_counts  = $this->prepare_chart_data( $product_objs, 'post_date', 'order_item_count', $this->chart_interval, $this->start_date, $this->chart_groupby );

			$count = 0;
			foreach($order_item_counts as $order_counter => $val):
				$count += $val[1];
			endforeach;

		$legend   = array();

		$total_items    = $count;
		$legend[] = array(
			'title' => sprintf( __( '%s Product Abandonments', 'cart_converter' ), '<strong>' . $total_items  . '</strong>' ),
			'color' => $this->chart_colours['item_counts'],
			'highlight_series' => 0
		);

		return $legend;
	}

	/**
	 * Output the report
	 */
	public function output_report() {
		global $woocommerce, $wpdb, $wp_locale;

		$ranges = array(
			'year'         => __( 'Year', 'cart_converter' ),
			'last_month'   => __( 'Last Month', 'cart_converter' ),
			'month'        => __( 'This Month', 'cart_converter' ),
			'7day'         => __( 'Last 7 Days', 'cart_converter' )
		);

		$this->chart_colours = array(
			'item_counts'   => '#d54e21'
		);

		$current_range = ! empty( $_GET['range'] ) ? $_GET['range'] : '7day';

		if ( ! in_array( $current_range, array( 'custom', 'year', 'last_month', 'month', '7day' ) ) )
			$current_range = '7day';

		$this->calculate_current_range( $current_range );

		include( WC()->plugin_path() . '/includes/admin/views/html-report-by-date.php');
	}

	/**
	 * [get_chart_widgets description]
	 * @return array
	 */
	public function get_chart_widgets() {

		$widgets = array();

		if ( ! empty( $this->product_ids ) ) {
			$widgets[] = array(
				'title'    => __( 'Showing reports for:', 'cart_converter' ),
				'callback' => array( $this, 'current_filters' )
			);
		}

		$widgets[] = array(
			'title'    => '',
			'callback' => array( $this, 'products_widget' )
		);

		return $widgets;
	}

	/**
	 * Show current filters
	 * @return void
	 */
	public function current_filters() {
		$this->product_ids_titles = array();

		foreach ( $this->product_ids as $product_id ) {
			$product = get_product( $product_id );
			$this->product_ids_titles[] = $product->get_formatted_name();
		}

		echo '<p>' . ' <strong>' . implode( ', ', $this->product_ids_titles ) . '</strong></p>';
		echo '<p><a class="button" href="' . esc_url( remove_query_arg( 'product_ids' ) ) . '">' . __( 'Reset', 'cart_converter' ) . '</a></p>';
	}

	/**
	 * Product selection
	 * @return void
	 */
	public function products_widget() {
		?>
		<h4 class="section_title"><span><?php _e( 'Product Search', 'cart_converter' ); ?></span></h4>
		<div class="section">
			<form method="GET">
				<div>
					<input type="hidden" class="wc-product-search" style="width:203px;" name="product_ids[]" data-placeholder="<?php _e( 'Search for a product&hellip;', 'woocommerce' ); ?>" data-action="woocommerce_json_search_products_and_variations" />
					<input type="submit" class="submit button" value="<?php _e( 'Show', 'woocommerce' ); ?>" />
					<input type="hidden" name="range" value="<?php if ( ! empty( $_GET['range'] ) ) echo esc_attr( $_GET['range'] ) ?>" />
					<input type="hidden" name="start_date" value="<?php if ( ! empty( $_GET['start_date'] ) ) echo esc_attr( $_GET['start_date'] ) ?>" />
					<input type="hidden" name="end_date" value="<?php if ( ! empty( $_GET['end_date'] ) ) echo esc_attr( $_GET['end_date'] ) ?>" />
					<input type="hidden" name="page" value="<?php if ( ! empty( $_GET['page'] ) ) echo esc_attr( $_GET['page'] ) ?>" />
					<input type="hidden" name="tab" value="<?php if ( ! empty( $_GET['tab'] ) ) echo esc_attr( $_GET['tab'] ) ?>" />
					<input type="hidden" name="report" value="<?php if ( ! empty( $_GET['report'] ) ) echo esc_attr( $_GET['report'] ) ?>" />
				</div>
			</form>
		</div>

		
		<script type="text/javascript">
			jQuery('.section_title').click(function(){
				var next_section = jQuery(this).next('.section');

				if ( jQuery(next_section).is(':visible') )
					return false;

				jQuery('.section:visible').slideUp();
				jQuery('.section_title').removeClass('open');
				jQuery(this).addClass('open').next('.section').slideDown();

				return false;
			});
			jQuery('.section').slideUp( 100, function() {
				<?php if ( empty( $this->product_ids ) ) : ?>
					jQuery('.section_title:eq(1)').click();
				<?php endif; ?>
			});
		</script>
		<?php
	}

	/**
	 * Output an export link
	 */
	public function get_export_button() {
		$current_range = ! empty( $_GET['range'] ) ? $_GET['range'] : '7day';
		?>
		<a
			href="#"
			download="report-<?php echo $current_range; ?>-<?php echo date_i18n( 'Y-m-d', current_time('timestamp') ); ?>.csv"
			class="export_csv"
			data-export="chart"
			data-xaxes="<?php _e( 'Date', 'cart_converter' ); ?>"
			data-groupby="<?php echo $this->chart_groupby; ?>"
		>
			<?php _e( 'Export CSV', 'cart_converter' ); ?>
		</a>
		<?php
	}

	/**
	 * Get the main chart
	 * @return string
	 */
	public function get_main_chart() {
		global $wp_locale;

		if ( ! $this->product_ids ) {
			?>
			<div class="chart-container">
				<p class="chart-prompt"><?php _e( '&larr; Choose a product to view stats', 'cart_converter' ); ?></p>
			</div>
			<?php
		} else {
			// Get orders and dates in range - we want the SUM of order totals, COUNT of order items, COUNT of orders, and the date

		$order_items = CartModel::get_carts_one_year();
		$found_products = array();		
		
		$product_objs = array();

		if ( $order_items ) {

			foreach ( $order_items as $order_item ) {

				$date = date( 'Y-m-d h:i:s', $order_item->post_date );

				$items 	= maybe_unserialize( $order_item->items );
				
				foreach ( $items as $item ) {
					$cart = $item['cart'];
					$key  = key($cart);
					
					if ( $this->product_ids[0] != $cart[$key]['product_id'] )
				 		continue;
					if ( isset( $cart[$key]['line_total'] ) ) $row_cost = $cart[$key]['line_total'];
					
					$obj = new stdClass();

					$obj->post_date = $date;
					$obj->order_item_count = $cart[$key]['quantity'];
					$obj->product_id = $cart[$key]['product_id'];

					$product_objs[] = $obj;

				}

			}

		}	
			// Prepare data for report
			$order_item_counts  = $this->prepare_chart_data( $product_objs, 'post_date', 'order_item_count', $this->chart_interval, $this->start_date, $this->chart_groupby );

			//$order_item_counts  = $this->prepare_chart_data( $product_sales, 'post_date', 'order_item_count', $this->chart_interval, $this->start_date, $this->chart_groupby );

			// Encode in json format
			$chart_data = json_encode( array(
				'order_item_counts'  => array_values( $order_item_counts ),
			) );
			?>
			<div class="chart-container">
				<div class="chart-placeholder main"></div>
			</div>
			<script type="text/javascript">
				var main_chart;

				jQuery(function(){
					var order_data = jQuery.parseJSON( '<?php echo $chart_data; ?>' );

					var drawGraph = function( highlight ) {

						var series = [
							{
								label: "<?php echo esc_js( __( 'Number of items Abandoned', 'cart_converter' ) ) ?>",
								data: order_data.order_item_counts,
								color: '<?php echo $this->chart_colours['item_counts']; ?>',
								points: { show: true, radius: 5, lineWidth: 3, fillColor: '#fff', fill: true },
								lines: { show: true, lineWidth: 4, fill: false },
								shadowSize: 0,
								hoverable: true
							}
						];

						if ( highlight !== 'undefined' && series[ highlight ] ) {
							highlight_series = series[ highlight ];

							highlight_series.color = '#9c5d90';

							if ( highlight_series.bars )
								highlight_series.bars.fillColor = '#9c5d90';

							if ( highlight_series.lines ) {
								highlight_series.lines.lineWidth = 5;
							}
						}

						main_chart = jQuery.plot(
							jQuery('.chart-placeholder.main'),
							series,
							{
								legend: {
									show: false
								},
							    grid: {
							        color: '#aaa',
							        borderColor: 'transparent',
							        borderWidth: 0,
							        hoverable: true
							    },
							    xaxes: [ {
							    	color: '#aaa',
							    	position: "bottom",
							    	tickColor: 'transparent',
									mode: "time",
									timeformat: "<?php if ( $this->chart_groupby == 'day' ) echo '%d %b'; else echo '%b'; ?>",
									monthNames: <?php echo json_encode( array_values( $wp_locale->month_abbrev ) ) ?>,
									tickLength: 1,
									minTickSize: [1, "<?php echo $this->chart_groupby; ?>"],
									font: {
							    		color: "#aaa"
							    	}
								} ],
							    yaxes: [
							    	{
							    		min: 0,
							    		minTickSize: 1,
							    		tickDecimals: 0,
							    		color: '#ecf0f1',
							    		font: { color: "#aaa" }
							    	},
							    	{
							    		position: "right",
							    		min: 0,
							    		tickDecimals: 2,
							    		alignTicksWithAxis: 1,
							    		color: 'transparent',
							    		font: { color: "#aaa" }
							    	}
							    ],
					 		}
					 	);

					 	jQuery('.chart-placeholder').resize();
					 }

					drawGraph();

					jQuery('.highlight_series').hover(
						function() {
							drawGraph( jQuery(this).data('series') );
						},
						function() {
							drawGraph();
						}
					);
				});
			</script>
			<?php
		}
	}
}