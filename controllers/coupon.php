<?php
/**
 * Create new coupon when Cart Option change
 */
class CouponsController extends Controller{

	/**
	 * Add new coupon by coupon code
	 * @param string $coupon_code Random coupon code base on datetime and percent value
	 * @uses  wp_insert_post
	 */
	public static function add_new_coupon( $coupon_code, $percent ){
		$coupon = array(
            'post_title'   => $coupon_code,
            'post_content' => 'Cart Converter generated coupon % cart discount',
            'post_status'  => 'publish',
            'post_author'  => 1, // TODO: need get admin user
            'post_type'    => 'shop_coupon'
		);

        $new_coupon_id = wp_insert_post( $coupon );

		// 'fixed_cart'      => __( 'Cart Discount', 'woocommerce' ),
		// 'percent'         => __( 'Cart % Discount', 'woocommerce' ),
		// 'fixed_product'   => __( 'Product Discount', 'woocommerce' ),
		// 'percent_product' => __( 'Product % Discount', 'woocommerce' )
		update_post_meta( $new_coupon_id, 'discount_type', 'percent' );
        update_post_meta( $new_coupon_id, 'coupon_amount', $percent );
        update_post_meta( $new_coupon_id, 'individual_use', 'no' );
        //update_post_meta( $new_coupon_id, 'product_ids', array_filter(array_map('intval', $allowproducts)) );
        //update_post_meta( $new_coupon_id, 'exclude_product_ids', array_filter(array_map('intval', $excluded_products)) );
        //update_post_meta( $new_coupon_id, 'product_categories', array_filter(array_map('intval', $allowcategory)) );
        //update_post_meta( $new_coupon_id, 'exclude_product_categories', array_filter(array_map('intval', $excludecategory)) );
        //update_post_meta( $new_coupon_id, 'usage_limit', ''); //unlimited
        //update_post_meta( $new_coupon_id, 'expiry_date', $expire_date );
        update_post_meta( $new_coupon_id, 'apply_before_tax', 'yes' );
        //update_post_meta( $new_coupon_id, 'free_shipping', 'no' );
        //update_post_meta( $new_coupon_id, 'minimum_amount', $minimum_amount );
        //update_post_meta( $new_coupon_id, 'maximum_amount', $maximum_amount );
        
        return $new_coupon_id;
	}
}