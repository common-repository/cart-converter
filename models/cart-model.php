<?php
/**
 * Abandon Cart Model to handle all interaction to database of Cart table
 */
class CartModel {

    /**
     * Get table name of Cart
     * 
     */
    private static function get_cart_table(){
        global $wpdb;
        $table_name = $wpdb->prefix . CART_CONVERTER_PREFIX . '_cart';
        return $table_name;
    }

    /**
     * Get table name of Cart
     * 
     */
    private static function get_order_table(){
        global $wpdb;
        $table_name = $wpdb->prefix . CART_CONVERTER_PREFIX . '_order';
        return $table_name;
    }
	/**
     * Create table to keep Abandoned Cart
     * @uses wpdb
     * @uses dbDelta
     */
	public static function setup_db(){
		//require for dbDelta()
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        global $wpdb;
        $table_name = CartModel::get_cart_table();

        $sql = "CREATE TABLE " . $table_name . "(
             id int(9) NOT NULL AUTO_INCREMENT,
             user_id LONGTEXT NOT NULL,
             cart_content LONGTEXT NOT NULL,
             cart_email VARCHAR(255),
             cart_abandon_time BIGINT NOT NULL,
             cart_status VARCHAR(50) NOT NULL,
             ip_address VARCHAR(20),
             sending_status VARCHAR(15) NOT NULL DEFAULT 'NOT SEND',
             placed_order VARCHAR(20),
             completed VARCHAR(20),
        UNIQUE KEY id (id)
        ) DEFAULT CHARACTER SET utf8;";

        //database write/update
        dbDelta($sql);

        // create order table to kept converted payment
        $order_table_name = CartModel::get_order_table();

        $sql = "CREATE TABLE " . $order_table_name . "(
             id int(9) NOT NULL AUTO_INCREMENT,
             cart_id int NOT NULL,
             order_id int NOT NULL,
             amount DECIMAL NOT NULL,
             convert_date datetime NOT NULL,
        UNIQUE KEY id (id)
        ) DEFAULT CHARACTER SET utf8;";

        //database write/update
        dbDelta($sql);
	}

    /**
     * add_abandon_cart for member only
     * @param Woocommerce::Order $order
     */
    public function add_abandon_cart_member( $order ){
        //OK: CartController::getInstance()->logger->debug('CartModel::add_abandon_cart');
        global $wpdb;
        $cart_table   = $this->get_cart_table();

        // prepare data before insert
        $user_id      = $order->user_id;
        $current_time = current_time('timestamp');
        $user_details = get_userdata( $user_id );
        $user_email   = $user_details->user_email;

        // get cart persitent from usermeta by key
        $cart_persistent = get_user_meta( $user_id, '_woocommerce_persistent_cart' );

        // if existence old cart
        if ( ! empty( $cart_persistent[0]['cart'] ) ) {
            $cart_content = maybe_serialize( $cart_persistent );

            $last_cart = $this->get_last_cart( $user_id );

            if ( false === $last_cart ){

                // collect new abandon cart
                $data = array(
                    'user_id'      => $user_id, 
                    'cart_email'   => $user_email, 
                    'cart_content' => $cart_content, 
                    'cart_abandon_time' => $current_time, 
                    'cart_status'  => 'NEW');

                $wpdb->insert( $cart_table, $data );

            } else {

                // update last cart
                $data = array( 'cart_content' => $cart_content, 'cart_abandon_time' => $current_time );
                $wpdb->update( $cart_table, $data, array( 'id' => $last_cart->id ) );
            }

            return true;
        } else {
            // no update
            return false;
        }
    }

    /**
     * Get Last Cart of user
     * @param  number $user_id
     * @return Cart
     */
    private function get_last_cart( $user_id ){
        global $wpdb;
        $cart_table   = $this->get_cart_table();

        $sql = "SELECT * 
                FROM   $cart_table 
                WHERE  user_id = $user_id 
                AND    cart_status NOT IN('CONVERTED') 
                ORDER BY id DESC 
                LIMIT 1";

        $last_cart = $wpdb->get_results( $sql, OBJECT );
        if ( ! empty( $last_cart ) ) {
            $last_cart = $last_cart[0];
            return $last_cart;
        }

        //  no last cart
        return false;
    }

    /**
     * Get all cart by status
     * @param  string   $status       NEW, ABANDONED, CONVERTED
     * @param  unixtime $cut_off_time Unix Time format by seconds
     * @return Array | Object
     */
    public function get_carts_by_status( $status, $cut_off_time = 0 ){
        global $wpdb;
        $cart_table    = $this->get_cart_table();
        $current_time  = current_time( "timestamp" );

        $sql = "SELECT * 
                    FROM   $cart_table 
                    WHERE  cart_status = '$status'";
        if ( $cut_off_time > 0 )
            $sql .= " AND    cart_abandon_time + $cut_off_time <= $current_time";

        return $wpdb->get_results( $sql, OBJECT );
    }

    /**
     * Update cart status to Abandoned
     * @param  ArrayObject $abandoned_carts
     * 
     */
    public function insert_abandoned_carts( $abandoned_carts ){
        global $wpdb;
        $cart_table = $this->get_cart_table();

        foreach ( $abandoned_carts as $key => $cart ) {
            $data  = array( 
                'cart_status'    => 'ABANDONED',
                'sending_status' => 'SENDING'
            );
            $where = array( 'id' => $cart->id );
            $wpdb->update( $cart_table, $data, $where );
        }
    }

    /**
     * Update status completed for cart
     * @param  Object $email Email
     * 
     */
    public function update_status( $cart_id ){
        global $wpdb;
        $cart_table = $this->get_cart_table();

        $wpdb->update( $cart_table, array( 'sending_status'=>'SENT' ), array( 'id' => $cart_id ) );
    }

    /**
     * Update status completed for cart
     * @param  Object $email Email
     * 
     */
    public function add_convert_paid_order( $cart_id, $order_id ){
        global $wpdb;
        $order_table_name = $this->get_order_table();

        $order  = new WC_Order( $order_id );
        $amount = $order->get_total();
        // collect new convert cart order
        $data = array(
            'cart_id'      => $cart_id, 
            'order_id'     => $order_id, 
            'amount'       => $amount, 
            'convert_date' => current_time( 'mysql', 1 )
        );

        $wpdb->insert( $order_table_name, $data );
    }

    /**
     * Set status CONVERTED for cart
     * @param  Object $email Email
     * 
     */
    public function set_cart_converted( $cart_id ){
        global $wpdb;
        $cart_table = $this->get_cart_table();

        $wpdb->update( $cart_table, array( 'cart_status'=>'CONVERTED' ), array( 'id' => $cart_id ) );
    }
    /**
     * Set status CONVERTED for cart
     * @param  Object $email Email
     * 
     */
    public function set_cart_place_order( $cart_id, $new_order_id ){
        global $wpdb;
        $cart_table = $this->get_cart_table();
        $data = array( 
            'cart_status'  => 'CONVERTED',
            'placed_order' => $new_order_id
        );
        $wpdb->update( $cart_table, $data, array( 'id' => $cart_id ) );
    }
    /**
     * Get Cart to restore Woo session
     * @param  int $cart_id Cart Id from Email
     * @return WooCartSession
     */
    public function get_abandoned_cart( $cart_id ){
        global $wpdb;
        $cart_table = $this->get_cart_table();

        $sql = "SELECT * FROM $cart_table WHERE id = $cart_id AND cart_status = 'ABANDONED'";
        $last_cart = $wpdb->get_results( $sql, OBJECT );
        
        end( $last_cart );
        $last_cart_key = key($last_cart);

        if ( isset( $last_cart_key ) ) {
            $cart_content = maybe_unserialize( $last_cart[ $last_cart_key ]->cart_content );

            // get the 1st value of object
            foreach ( $cart_content as $cart ) {
                $cart_details = $cart['cart'];
            }
            
            return $cart_details;
        }
        return null;
    }

    /**
     * Get data for one year
     * @return [type] [description]
     */
    public static function get_carts_one_year(){
        global $wpdb;
        $cart_table = $wpdb->prefix . CART_CONVERTER_PREFIX . '_cart';
        $sql = "
            SELECT cart_content AS items, cart_abandon_time as post_date 
            FROM {$cart_table} AS carts

            WHERE
                    cart_abandon_time > UNIX_TIMESTAMP( date_sub( NOW(), INTERVAL 1 YEAR ) )
            ORDER BY cart_abandon_time ASC
        ";
        return $wpdb->get_results( $sql );
    }
}