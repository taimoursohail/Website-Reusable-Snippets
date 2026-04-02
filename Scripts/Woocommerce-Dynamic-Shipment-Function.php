add_filter( 'woocommerce_package_rates', 'apply_custom_cart_shipping_logic', 10, 2 );

function apply_custom_cart_shipping_logic( $rates, $package ) {
    $total_weight  = 0;
    $total_qty     = 0;
    $cart_subtotal = 0;

    // 1. Loop through cart items to calculate weight, quantity, and total cart value
    foreach ( $package['contents'] as $item ) {
        $product = $item['data'];
        $qty     = $item['quantity'];

        // Calculate total cart weight
        $weight = (float) $product->get_weight();
        if ( $weight > 0 ) {
            $total_weight += ( $weight * $qty );
        }

        // Calculate total quantity and cart subtotal (using line_total accounts for any discounts)
        $total_qty     += $qty;
        $cart_subtotal += (float) $item['line_total'];
    }

    $final_cost    = 0;
    $is_overweight = false;

    // 2. Logic Calculation
    if ( $total_weight > 2 ) {
        // Condition A: Weight > 2kg (Overrides price logic entirely)
        if ( $total_weight <= 5 ) {
            $final_cost = 3.00;
        } elseif ( $total_weight <= 10 ) {
            $final_cost = 5.00;
        } elseif ( $total_weight <= 20 ) {
            $final_cost = 10.00;
        } else {
            // Over 20kg
            $is_overweight = true;
        }
    } else {
        // Condition B: Weight is 2kg or under
        if ( $cart_subtotal >= 4.00 ) {
            // Cart total is £4.00 or greater -> FREE SHIPPING
            $final_cost = 0.00;
        } else {
            // Cart total is strictly less than £4.00 -> Charge £1.59 + £0.49 per extra item
            if ( $total_qty > 0 ) {
                $final_cost = 1.59 + ( ( $total_qty - 1 ) * 0.49 );
            }
        }
    }

    // 3. Apply the calculated cost to the shipping method
    foreach ( $rates as $rate_key => $rate ) {
        if ( strpos( $rate_key, 'flat_rate' ) !== false || strpos( $rate_key, 'free_shipping' ) !== false ) {
            
            if ( $is_overweight ) {
                // Handle unsettled 20kg+ shipment
                $rates[$rate_key]->cost  = 0.00;
                $rates[$rate_key]->label = 'Shipping unsettled - We will contact you to confirm shipment payment';
            } else {
                // Apply the new cost
                $rates[$rate_key]->cost = $final_cost;

                // If cost is 0, explicitly change the label so the customer sees it's free
                if ( $final_cost == 0 ) {
                    $rates[$rate_key]->label = 'Free Shipping';
                }
            }
        }
    }

    return $rates;
}
