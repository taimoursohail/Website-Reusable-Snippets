// Create the Shipping class 'pickup-only' from woocommerce

/Past this code into function.php child theme

// Disable the other Shipping methods if the Class: Pickup Only Selected 
add_filter( 'woocommerce_package_rates', 'restrict_shipping_for_pickup_only', 10, 2 );
function restrict_shipping_for_pickup_only( $rates, $package ) {
    $pickup_only = false;

    foreach ( WC()->cart->get_cart() as $cart_item ) {
        $product_id = $cart_item['product_id'];
        $product = wc_get_product( $product_id );

        // Check if product has shipping class "pickup-only"
        if ( $product->get_shipping_class() === 'pickup-only' ) {
            $pickup_only = true;
            break;
        }
    }

    // If pickup-only item is in cart, remove all methods except Local Pickup
    if ( $pickup_only ) {
        foreach ( $rates as $rate_id => $rate ) {
            if ( 'local_pickup' !== $rate->method_id ) {
                unset( $rates[ $rate_id ] );
            }
        }
    }

    return $rates;
}
