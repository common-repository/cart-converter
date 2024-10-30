<?php
/**
 * EmailModel to handle all email interaction to Wordpress option
 */
class EmailModel {
	/**
     * Get table name of Email Queue
     * 
     */
    private static function get_email_table(){
        global $wpdb;
        $table_name = $wpdb->prefix . CART_CONVERTER_PREFIX . '_email_queue';
        return $table_name;
    }
	/**
     * Create table to keep all queue of email
     * @uses wpdb
     * @uses dbDelta
     */
	public static function setup_db(){
		//require for dbDelta()
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        global $wpdb;
        $table_name = EmailModel::get_email_table();

        $sql = "CREATE TABLE " . $table_name . "(
             id int(9) NOT NULL AUTO_INCREMENT,
             cart_id INT NOT NULL,
             subject VARCHAR(200) NOT NULL,
             email_content LONGTEXT NOT NULL,
             send_to VARCHAR(255),
             created BIGINT NOT NULL,
             status VARCHAR(20),
        UNIQUE KEY id (id)
        ) DEFAULT CHARACTER SET utf8;";

        //database write/update
        dbDelta($sql);
	}
	/**
	 * Create queue for sending email
	 * @param  array $items Array contains data to create a queue
	 * @uses $wpdb
	 */
	public function insert_queue( $items ){
		global $wpdb;
		
		$cartOptions   = new CartOptionsController();
		$mail_settings = $cartOptions->get_email_settings();

		foreach ( $items as $key => $item ) {
			$email_content  = $this->get_email_content( $item, $mail_settings );
			$customer_email = $item->cart_email;

			$data = array(
				'cart_id' => $item->id,
				'subject' => $mail_settings['subject'],
				'email_content' => $email_content,
				'send_to' => $customer_email,
				'created' => current_time("timestamp"),
				'status'  => 'NEW'
			);
			$wpdb->insert( $this->get_email_table(), $data );
		}
	}

	/**
	 * get_email_content from Cart Table and Setting Options
	 * @param  Object $cart_item 
	 * @param  String $email_template 
	 * @return String Email Content
	 */
	public function get_email_content( $cart_item, $settings ){
		// Member only
		if ( $cart_item->user_id > 0 ) {
			$user_details = get_userdata( $cart_item->user_id );
	        
	        // {#customer_name}
	        $email_template = str_replace( "{#customer_name}", $user_details->display_name, $settings['email_template'] );

	        // {#cart_link}
	        $current_time = current_time('timestamp');
            $cart_page_id = wc_get_page_id( 'cart' );
			$cart_url 	  = apply_filters( 'woocommerce_get_cart_url', $cart_page_id ? get_permalink( $cart_page_id ) : '' );

			$query_args = array(
				'abandon_cart' => $cart_item->id
			);

			// check coupon enable and get current applying coupon
			$coupons_enabled = get_option( 'woocommerce_enable_coupons' );
			if ( 'yes' == $coupons_enabled ){
				$cartOptions    = new CartOptionsController();
				$coupon_percent = $cartOptions->get_option( 'coupon_percent' );
				$coupon_code    = $cartOptions->get_option( 'coupon_code' );
				if ( $coupon_percent > 0 && !empty( $coupon_code ) ) {
					$query_args['coupon_code'] = $coupon_code;
				}
			}

			// cart link to recover
            $url_to_click = esc_url_raw(add_query_arg(
            	$query_args, 
            	$cart_url
        	));

	        $email_template = str_replace( "{#cart_link}", $url_to_click, $email_template );

		    // get woo email template
			ob_start();
			if ( function_exists('wc_get_template') ) {
	            wc_get_template('emails/email-header.php', array('email_heading' => $settings['subject']));
	            echo $email_template;
	            wc_get_template('emails/email-footer.php');
	        } else {
	            woocommerce_get_template('emails/email-header.php', array('email_heading' => $settings['subject']));
	            echo $email_template;
	            woocommerce_get_template('emails/email-footer.php');
	        }

	        $woo_temp_msg = ob_get_clean();
	    	return $woo_temp_msg;
	    }
	    return false;
	}

	/**
	 * Get email list from email_queue database
	 * 
	 */
	public function get_email_list( $limit ){
		global $wpdb;
		$email_table = $this->get_email_table();

		$sql = "SELECT * FROM $email_table WHERE status = 'NEW' LIMIT $limit";

		return $wpdb->get_results( $sql, OBJECT );
	}
	/**
	 * Update status completed for email
	 * @param  Object $email Email
	 * 
	 */
	public function update_status( $email ){
		global $wpdb;
		$email_table = $this->get_email_table();

		$wpdb->update( $email_table, array( 'status'=>'SENT' ), array( 'id' => $email->id ) );
	}
}