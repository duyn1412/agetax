<?php
/**
 * Plugin Name: Custom Age Verifier and Tax Plugin
 * Description: A custom plugin to add an age verifier and apply taxes to specific product categories.
 * Version: 1.0
 * Author: Block Agency
 * Author URI: https://blockagency.co
 */

 if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

register_activation_hook(__FILE__, 'woo_price_calc_plugin_activate');



function woo_price_calc_plugin_activate() {
    if (!is_plugin_active('woocommerce/woocommerce.php')) {
        // Deactivate the plugin
        deactivate_plugins(plugin_basename(__FILE__));

        // Throw an error in the WordPress admin console
        $error_message = __('This plugin requires WooCommerce to be activated', 'woo_price_calc_plugin');
        die($error_message);
    }

    add_option('woo_price_calc_plugin_do_activation_redirect', true);
}










// #1: Age verifier
function enqueue_age_verifier_script() {
    wp_enqueue_style('age-verifier', plugin_dir_url(__FILE__) . '/styles.css', array(), '1.0');

    wp_enqueue_script('age-verifier', plugin_dir_url(__FILE__) . 'js/age-verifier.js', array('jquery'), '1.0.0', true);

    // Localize script with AJAX data
    wp_localize_script('age-verifier', 'ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('province_session_nonce')
    ));

      // Check if the 'woocommerce-google-address.php' plugin is active
      //if (is_plugin_active('woocommerce-google-address/woocommerce-google-address.php')) {
       
        // Enqueue the new JavaScript file only if the plugin is active
       wp_enqueue_script('woocommerce-address-handler', plugin_dir_url(__FILE__) . 'js/woocommerce-address-handler.js', array('jquery'), '1.0.0', true);
    //}
}
add_action('wp_enqueue_scripts', 'enqueue_age_verifier_script');


// add popup
add_action('wp_head', 'age_verification_dialog');

// Debug: Add action to check if function is being called
add_action('wp_footer', function() {
    echo '<!-- Footer debug: age_verification_dialog function status -->';
    echo '<script>console.log("Footer debug: Checking age verification status");</script>';
    
    if (!has_province_session()) {
        echo '<!-- Age verification dialog should be visible -->';
        error_log('Age verification dialog: Function called in footer - should be visible');
    } else {
        echo '<!-- Age verification dialog hidden - province exists -->';
        error_log('Age verification dialog: Hidden - province exists');
    }
});

// Debug: Add action to check if function is being called
add_action('wp_footer', function() {
    if (!has_province_session()) {
        echo '<!-- Age verification dialog should be visible -->';
        error_log('Age verification dialog: Function called in footer');
    } else {
        echo '<!-- Age verification dialog hidden - province exists -->';
        error_log('Age verification dialog: Hidden - province exists');
    }
});


function get_logo_url() {
    $custom_logo_id = get_theme_mod('custom_logo');
    $logo = wp_get_attachment_image_src($custom_logo_id, 'full');

    if (has_custom_logo()) {
        return $logo[0];
    } else {
        return false;
    }
}


function is_admin_simulating_customer_role() {
   // var_dump(VAA_API::is_view_active());
    // Check if the "View Admin As" plugin is active and a view is being simulated
    if ( VAA_API::is_view_active() ) {
        // Ensure the view_admin_as_role function exists before calling it
        return true;
    }
    return false;
}



// add_action('plugins_loaded', function() {
//     var_dump(is_admin_simulating_customer_role());
//     if ( is_admin_simulating_customer_role() ) {
//         // Perform actions if the admin is simulating the customer role
//         echo 'Admin is simulating the Customer role.';
//     } else {
//         // Perform other actions
//         echo 'Admin is not simulating the Customer role.';
//     }
// });




function d_add_province_class($classes) {
    if (!has_province_session()) {
        $classes[] = 'no-province';
    }
    return $classes;
}
add_filter('body_class', 'd_add_province_class');

function age_verification_dialog() {
    // PHP code to generate the HTML for the form
    // Check if the province session exists
  
    // Check if province cookie exists - if yes, don't show dialog
    if (has_province_cookie()) {
        return; // Don't show dialog if province is already set
    }
    
    // Debug: Check what's happening
    error_log('Age verification dialog: Function called - Province session status: ' . (has_province_session() ? 'EXISTS' : 'NOT EXISTS'));
    
    ob_start(); // Start output buffering

    $province_group2 = array(
        'AB' => 'Alberta',
        'BC' => 'British Columbia',
        'MB' => 'Manitoba',
        'NB' => 'New Brunswick',
        'NL' => 'Newfoundland and Labrador',
        'NS' => 'Nova Scotia',
        'ON' => 'Ontario',
        'PE' => 'Prince Edward Island',
        'QC' => 'Quebec',
        'SK' => 'Saskatchewan',
        'NT' => 'Northwest Territories',
        'NU' => 'Nunavut',
        'YT' => 'Yukon Territory'
    );
   ?>
    <!-- DEBUG: Age verification dialog is being rendered -->
    <div id="custom-age-popup-wrapper" style="display: block !important; z-index: 999999 !important;">
		<div id="custom-age-popup-box">
		
				<center>
                    <?php
                    $logo_url = get_logo_url();
                    ?>
                    <img class="d-logo" src="<?php echo $logo_url; ?>" alt="Logo"><br>
                    <hr class="custom-age-saparater">
		<!-- <h2 class="custom-age-title">Age Verification</h2> -->
		<p>This site is intended for adults <span class="bold-txt"><span id="age-limit">19</span> years and older</span>. If you are not legally able to purchase tobacco products in your province, please do not enter this site.</p>
		</center>
		<div class="custom-age-btn-box">
        <form id="d-age-verification-form" action="<?php echo admin_url('admin-post.php'); ?>" method="post">
        <input type="hidden" name="action" value="d_age_verification_form">
        <?php wp_nonce_field('d_age_verification_form_nonce', 'davf_nonce'); ?>

        <div class="custom-age-btn-box-row" id="province-box">
      
			<div class="province_wrapper">
			
            <select id="province" name="province" required>
            <option value="" disabled selected>Select your province</option>
                <?php foreach ($province_group2 as $code => $name): ?>
                 

                    <option value="<?php echo $code; ?>">
                        <?php echo $name; ?>
                    </option>
                <?php endforeach; ?>
            </select>
			</div>
		</div>


		<div id="dob-box">
		
		<p class="select-dob-label">Please select your birthdate to confirm you are at least 19 years of age.</p>
		<div class="custom-age-btn-box-row custom-birthdate">
                <div class="birth_month_wrapper">
                    <select id="month" name="month">
                        <?php 
                        $months = array(1 => 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
                        for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo date('n') == $i ? 'selected' : ''; ?>>
                                <?php echo $months[$i]; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="birth_date_wrapper">
                    <select id="day" name="day">
                        <?php for ($i = 1; $i <= 31; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo date('j') == $i ? 'selected' : ''; ?>>
                                <?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="birth_year_wrapper">
                    <select id="year" name="year">
                        <?php for ($i = date('Y'); $i >= date('Y') - 100; $i--): ?>
                            <option value="<?php echo $i; ?>" <?php echo ($i === 2000) ? 'selected' : ''; ?>>
                                <?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
		</div>
		


        <input type="submit" value="Enter" class="custom-btn-age yes">
        <a href="https://www.google.com/" id="" class="custom-btn-age no" class="custom-btn-age no">Exit</a>


		</div>
		</div>

        <p id="error-message" style="display: none; color: red;">Sorry! You are under age to visit website. Only 19+ age person can visit.</p>
		</form>
		</div>
	</div>
    <?php
     $output_string = ob_get_contents();
     ob_end_clean();
 
    echo $output_string;
    
    // Debug: Add a visible message
    echo '<!-- Age verification dialog rendered successfully -->';
    echo '<script>console.log("Age verification dialog rendered");</script>';

}

// Enhanced cache control for FlyingPress + CDN compatibility
add_action('send_headers', function() {
    if (in_array('no-province', get_body_class())) { // Check for the age verification class
        // Stronger cache control headers for CDN compatibility
        header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0, private');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('X-Accel-Expires: 0');
        header('X-Cache-Status: BYPASS');
        
        // Additional headers for CDN
        header('Surrogate-Control: no-store');
        header('CDN-Cache-Control: no-cache');
    }
});

// FlyingPress specific cache exclusion
add_filter('flying_press_exclude_urls', function($exclude_urls) {
    // Exclude age verification pages from FlyingPress cache
    if (isset($_COOKIE['province']) && !empty($_COOKIE['province'])) {
        $exclude_urls[] = '.*';
    }
    return $exclude_urls;
});

// Exclude from FlyingPress cache when province cookie is not set
add_filter('flying_press_exclude_cookies', function($exclude_cookies) {
    $exclude_cookies[] = 'province';
    return $exclude_cookies;
});

// Enhanced cookie handling for CDN compatibility
function set_province_cookie_enhanced($province) {
    // Clear any existing cookie first
    if (isset($_COOKIE['province'])) {
        setcookie('province', '', time() - 3600, '/');
        unset($_COOKIE['province']);
    }
    
    // Set cookie with enhanced parameters for CDN compatibility
    $cookie_params = array(
        'expires' => time() + (86400 * 60), // 60 days
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => is_ssl(),
        'httponly' => false, // Allow JavaScript access
        'samesite' => 'Lax' // CDN compatible
    );
    
    // Set cookie using setcookie with all parameters
    setcookie('province', $province, $cookie_params);
    
    // Also set in $_COOKIE superglobal for immediate access
    $_COOKIE['province'] = $province;
    
    // Force cookie to be sent immediately
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
    
    // Clear FlyingPress cache if available
    if (function_exists('flying_press_clear_cache')) {
        flying_press_clear_cache();
    }
    
    // Clear WP Rocket cache if available
    if (function_exists('rocket_clean_domain')) {
        rocket_clean_domain();
    }
}

// Enhanced province cookie validation
function get_province_cookie_enhanced() {
    // Check multiple sources for province
    $province = null;
    
    // 1. Check $_COOKIE superglobal
    if (isset($_COOKIE['province']) && !empty($_COOKIE['province'])) {
        $province = sanitize_text_field($_COOKIE['province']);
    }
    
    // 2. Check HTTP headers (for CDN compatibility)
    if (!$province && isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // CDN might pass province in custom headers
        $headers = getallheaders();
        if (isset($headers['X-Province'])) {
            $province = sanitize_text_field($headers['X-Province']);
        }
    }
    
    // 3. Check session if cookie fails
    if (!$province && !session_id()) {
        session_start();
    }
    if (!$province && isset($_SESSION['province'])) {
        $province = sanitize_text_field($_SESSION['province']);
    }
    
    return $province;
}


/**
 * Remove Woocommerce Select2 only for Checkout - Woocommerce 3.2.1+
 */
function woo_dequeue_select2_only_for_checkout() {
   // var_dump(is_admin_simulating_customer_role());
	if ( is_checkout() && !is_admin_simulating_customer_role() ) {
	    if ( class_exists( 'woocommerce' ) ) {
	        wp_dequeue_style( 'select2' );
	        wp_deregister_style( 'select2' );

	        wp_dequeue_script( 'selectWoo');
	        wp_deregister_script('selectWoo');
	    } 
	}
}
//add_action( 'wp_enqueue_scripts', 'woo_dequeue_select2_only_for_checkout', 900 );

function custom_checkout_css() {
    if (is_checkout() && !is_admin_simulating_customer_role()) {
        ?>
        <style type="text/css">
           

            /* Optional: Add a border or styling to make it look like a text field */
            select[name="billing_state"],
            select[name="shipping_state"]{
                appearance: none; /* Remove default styling */
                -webkit-appearance: none; /* Safari and Chrome */
                -moz-appearance: none; /* Firefox */
                background-color: var(--input-bg-color) !important;
                    cursor: not-allowed !important;
            
            }
            p#billing_state_field:after,
            p#shipping_state_field:after {
                    content: "Please note: Province selection is locked to ensure correct tax calculation based on your location.";
                    margin-top: 5px;
                      line-height: 1.3;
                }
                .woocommerce form .form-row .select2-container {
                    display: none;
                }
        </style>

          

        <?php
    }
}
//add_action('wp_head', 'custom_checkout_css');


//add_filter('woocommerce_form_field_args', 'disable_select2_for_state_field', 999, 3);

function disable_select2_for_state_field($args, $key, $value) {
  
    if (in_array($key, array('billing_state', 'shipping_state')) && get_province_cookie() && !is_admin_simulating_customer_role()) {
      

        // Set the field as read-only
        $args['custom_attributes']['disabled'] = 'disabled';
       // var_dump( $args['custom_attributes']['disabled'] );
    }

    return $args;
}


function custom_override_checkout_fields( $fields ) {
    if(!is_admin_simulating_customer_role()){

        $fields['billing']['billing_phone']['description'] = 'Please enter your phone number for order-related communication.';
    
    }
   
    return $fields;
}

//add_filter( 'woocommerce_checkout_fields', 'custom_override_checkout_fields',99 );




// Hook for logged-in users
add_action('admin_post_d_age_verification_form', 'handle_d_age_verification_form');

// Hook for non-logged-in users
add_action('admin_post_nopriv_d_age_verification_form', 'handle_d_age_verification_form');

function handle_d_age_verification_form() {
    // Verify the nonce
 
    if (!isset($_POST['davf_nonce']) || !wp_verify_nonce($_POST['davf_nonce'], 'd_age_verification_form_nonce')) {
        wp_die('Session expired. Please refresh the page and try again.', 'Error', array('back_link' => true));
        // error_log('Invalid nonce');

    }
    // Check if the province is set in the POST data
    if (isset($_POST['province'])) {
        // Sanitize the province value
        $province = sanitize_text_field($_POST['province']);
        
        // Use simple cookie storage
        set_province_cookie($province);
        
        // Also set cookie as fallback for non-JavaScript users
        setcookie('province', $province, time() + (86400 * 60), "/");
        $_COOKIE['province'] = $province;
    }
    
    // Redirect to the current page
    wp_redirect($_SERVER['HTTP_REFERER']);
    exit;
}

function no_cache_for_referer($uri) {
    if ($_SERVER['REQUEST_URI'] == $_SERVER['HTTP_REFERER']) {
        return false;
    }
    return $uri;
}
add_filter('rocket_no_cache', 'no_cache_for_referer');


add_filter('woocommerce_checkout_get_value', 'change_default_checkout_state', 10, 2);

function change_default_checkout_state($value, $input) {
   
    //if(!is_admin_simulating_customer_role()){
      
        if ($input === 'billing_state' || $input === 'shipping_state') {
            // Use WooCommerce session for better CDN compatibility
                    $province = get_province_cookie();
        if ($province) { 
            return $province;
        }
        }
   // }

    return $value;
}

add_action('woocommerce_checkout_process', 'validate_billing_shipping_state');

function validate_billing_shipping_state() {
    // Use simple cookie storage
    $province = get_province_cookie();
    if ($province) {
        
        if (empty($_POST['billing_state'])) {
            $_POST['billing_state'] = $province;
            WC()->customer->set_billing_state($province);
        }

        if (empty($_POST['shipping_state'])) {
            $_POST['shipping_state'] = $province;
            WC()->customer->set_shipping_state($province);
        }
    }
}






// Clear product transients (cache) on shop and category pages
add_action('woocommerce_before_shop_loop_item', function() {
    global $product;
    wc_delete_product_transients($product->get_id());
});


add_action('woocommerce_before_single_product', function() {
    global $product;
    wc_delete_product_transients($product->get_id());
});






//add_action('woocommerce_checkout_update_order_review', 'store_guest_billing_state',999);

function store_guest_billing_state($post_data) {
    parse_str($post_data, $checkout_data);
    if (isset($checkout_data['billing_state'])) {
        $province = sanitize_text_field($checkout_data['billing_state']);
        
        // Store in WooCommerce session for better CDN compatibility
        set_province_session_enhanced($province);
        
        // Also set cookie as fallback
        setcookie('province', $province, time() + (86400 * 60), "/");
        $_COOKIE['province'] = $province; // Manually update the $_COOKIE superglobal
    }
}


function wc_add_custom_settings($settings) {
    require_once __DIR__ . '/WC_Settings_Custom.php';

    $settings[] = new WC_Settings_Custom();
    return $settings;
}
add_filter('woocommerce_get_settings_pages', 'wc_add_custom_settings');






function check_taxable_categories($product) {
    $product_id = $product->get_id();
    if ($product->is_type('variation')) {
        $parent_product = wc_get_product($product->get_parent_id());
        $product_id = $parent_product->get_id();
    }

    $product_categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));

    $taxable_categories = array();
    $taxable_categories = get_option('woocommerce_taxable_categories', array());
    foreach ($taxable_categories as $category) {     
        // Get subcategories
        $subcategories = get_terms('product_cat', array('hide_empty' => false, 'parent' => $category));
        foreach ($subcategories as $subcategory) {
        
                $taxable_categories[] = $subcategory->term_id;
            
        }
    }
    // Check if there's an intersection between the product categories and the taxable categories
    $intersection = array_intersect($taxable_categories, $product_categories);
  //  var_dump(!empty($intersection));
    // Return true if there's an intersection, false otherwise
    return !empty($intersection);
}



function get_curent_tax_province($product){
  

    // Use WooCommerce session for better CDN compatibility
    $customer_zone = get_province_cookie();
    
    $tax = 0;
    if (check_taxable_categories($product) && $customer_zone) { 
        // The product is in a taxable category
        $tagzone = get_option('wc_tax_rate_' . $customer_zone);
        if(isset($tagzone) && !empty($tagzone)){
              $tax = $tagzone;
        }
      
    // var_dump($tax );
    }
 
   
    return $tax;

}

add_filter('woocommerce_get_price_html', 'd_alter_price_display', 10, 2);


function d_alter_price_display($price, $product) {
    // ONLY ON FRONTEND
    if (is_admin()) return $price;

    // ONLY IF PRICE NOT NULL
    if ('' === $product->get_price()) return $price;
   // var_dump($product->get_id());

   $old_price = $price;
    $tax = get_curent_tax_province($product);
    $tax_60_ml = get_option('woocommerce_taxable_categories_60ml', 0);
    $tax_120_ml = get_option('woocommerce_taxable_categories_120ml', 0);

    if (isset($tax)) {
        if ($product->is_type('simple') || $product->is_type('variation')) {
            if ($product->is_on_sale()) {
              //  var_dump($product->get_regular_price());
              
                $price_regular = calculate_price_based_on_type(wc_get_price_to_display($product, array('price' => $product->get_regular_price())), $tax);
                $price_sale = calculate_price_based_on_type(wc_get_price_to_display($product), $tax);
                if($price_regular == $price_sale){
                    $price = wc_price($price_regular) . $product->get_price_suffix();
                }else{
                    $price = wc_format_sale_price(
                        $price_regular ,
                        $price_sale
                    ) . $product->get_price_suffix();
                }
                 
                // $price = wc_format_sale_price(
                //     $price_regular ,
                //     $price_sale
                // ) . $product->get_price_suffix();
                
                //var_dump($price);
            } else {
                $price = wc_price(calculate_price_based_on_type(wc_get_price_to_display($product), $tax)) . $product->get_price_suffix();

               //var_dump($price);
            }
            
        } elseif ($product->is_type('variable')) {

            // Get the raw min and max regular prices (no adjustment for regular prices)
            $min_regular_price = $product->get_variation_regular_price('min', true);
            $max_regular_price = $product->get_variation_regular_price('max', true);
            $d_regular_price = $product->get_variation_regular_price('d-regular-price', true);
           // var_dump($max_regular_price );
            // Get the raw min and max sale prices
            $min_sale_price = $product->get_variation_sale_price('min', true);
            $max_sale_price = $product->get_variation_sale_price('max', true);
        
            // Check if the product has the pa_size attribute
            $has_pa_size = false;
            
            $attributes = $product->get_attributes();
            if (!empty($attributes)) {
                foreach ($attributes as $attribute_name => $attribute_value) {
                    if (strpos($attribute_name, 'pa_size') !== false) {
                        $has_pa_size = true;
                        break;
                    }
                }
            }
           
        
            // If the product has the pa_size attribute, use adjusted prices
            if ($has_pa_size) {
                $adjusted_prices = get_adjusted_min_max_price($product);
                $min_sale_price = $adjusted_prices['min'];
                $max_sale_price = $adjusted_prices['max'];

               
                

             // var_dump($adjusted_prices);
            }

            //var_dump($has_pa_size);
        
            // Determine if we should display sale prices
            if (!empty($min_sale_price) && $min_sale_price < $min_regular_price) {
                // The product is on sale
        
                // Check if it's a range or single price for sale price
                if ($min_sale_price !== $max_sale_price) {
                    // Display the price range for sale price (adjusted)
                    $price = wc_format_price_range(
                        wc_price(calculate_price_based_on_type($min_sale_price, $tax)),
                        wc_price(calculate_price_based_on_type($max_sale_price, $tax))
                    );

                    
                } else {
                    // Single sale price
                    $price = wc_price(calculate_price_based_on_type($min_sale_price, $tax));
                }
        
                // Display regular price with strike-through if it's a range
                if ($min_regular_price !== $max_regular_price) {
                    $regular_price = wc_format_price_range(
                        wc_price(calculate_price_based_on_type($min_regular_price, $tax)),
                        wc_price(calculate_price_based_on_type($max_regular_price, $tax))
                    );
                } else {
                    $regular_price = wc_price(calculate_price_based_on_type($min_regular_price, $tax));
                }
                //var_dump($attributes);
                if(has_no_price_range($product)){
                    $price = '<del>' . $regular_price . '</del> <ins>' . $price . '</ins>';
                }

                // else{

                // }
                // Combine regular price (strikethrough) with the sale price
                //$price = '<del>' . $regular_price . '</del> <ins>' . $price . '</ins>';
            } else {
                // No sale, just display the regular price (range or single)
                if ($min_regular_price !== $max_regular_price) {
                    if ($has_pa_size) {
                        // $adjusted_prices = get_adjusted_min_max_price($product);
                        // $min_sale_price = $adjusted_prices['min'];
                        // $max_sale_price = $adjusted_prices['max'];
                        $price = wc_format_price_range(
                            wc_price(calculate_price_based_on_type($min_sale_price, $tax)),
                            wc_price(calculate_price_based_on_type($max_sale_price, $tax))
                        );
                    }else{
                        $price = wc_format_price_range(
                            wc_price(calculate_price_based_on_type($min_regular_price, $tax)),
                            wc_price(calculate_price_based_on_type($max_regular_price, $tax))
                        );
                    }
                   
                } else {
                   // var_dump(!has_no_price_range($product));
                    if ($has_pa_size) {
                        $price = wc_price(calculate_price_based_on_type($min_sale_price, $tax));


                       
                       
                    }else{
                        //var_dump($price);
                        $price = wc_price(calculate_price_based_on_type($min_regular_price, $tax));
                    }
                  
                }
            }
        
          
        }
        
        
        
    }
    //var_dump($price);
    return $price;
}

function has_no_price_range($product) {
    // Check if the product is a variable product.
    if ($product && $product->is_type('variable')) {
        // Get all variations of the variable product.
        $variations = $product->get_available_variations();

        // If there are no variations, return false.
        if (empty($variations)) {
            return false;
        }

        // Get the price of the first variation to compare with others.
        $first_variation_price = $variations[0]['display_price'];

        // Check if all variations have the same price.
        foreach ($variations as $variation) {
            if ($variation['display_price'] !== $first_variation_price) {
                // If any variation has a different price, return false.
                return false;
            }
        }

        // All variations have the same price, return true.
        return true;
    }

    return false;
}



function get_adjusted_min_max_price($product) {
    // Get the raw min and max prices
    $min_price = $product->get_variation_price('min', true);
    $max_price = $product->get_variation_price('max', true);

    //var_dump($min_price );
    //var_dump($max_price);

    // Apply your price adjustment function
    $min_price = check_product_pa_size($min_price, $product);
    $max_price = check_product_pa_size($max_price, $product);
    //$d_regular_price = $product->get_variation_regular_price('max', true);

    return array('min' => $min_price, 'max' => $max_price,'d-regular-price' => $max_price);
}



// add_filter('woocommerce_get_price_html', 'd_alter_price_display', 20, 2);

// function d_alter_price_display($price, $product) {
//     // ONLY ON FRONTEND
//     if (is_admin()) return $price;.


//     // ONLY IF PRICE NOT NULL
//     if ('' === $product->get_price()) return $price;

//     $tax = get_curent_tax_province($product);
//     $tax_60_ml = get_option('woocommerce_taxable_categories_60ml', 0);
//     $tax_120_ml = get_option('woocommerce_taxable_categories_120ml', 0);

//     if (isset($tax)) {
//         if ($product->is_type('simple') || $product->is_type('variation')) {
//             if ($product->is_on_sale()) {
//                 $price = wc_format_sale_price(
//                     calculate_price_based_on_type(wc_get_price_to_display($product, array('price' => $product->get_regular_price())), $tax),
//                     calculate_price_based_on_type(wc_get_price_to_display($product), $tax)
//                 ) . $product->get_price_suffix();
//             } else {
//                 $price = wc_price(calculate_price_based_on_type(wc_get_price_to_display($product), $tax)) . $product->get_price_suffix();
//             }
//         } elseif ($product->is_type('variable')) {
//             $prices = array();
//             foreach ($product->get_available_variations() as $variation) {
//                 $variation_product = wc_get_product($variation['variation_id']);
//                 $size = $variation_product->get_attribute('pa_size');
//                 $variation_price = $variation_product->get_price();

//                 if ($size == '120ml') {
//                     $prices[] = calculate_price_based_on_type($variation_price, $tax_120_ml, 'size');
//                 } elseif ($size == '60ml') {
//                     $prices[] = calculate_price_based_on_type($variation_price, $tax_60_ml, 'size');
//                 } else {
//                     $prices[] = calculate_price_based_on_type($variation_price, $tax, 'size');
//                 }
//             }

//             $min_price = min($prices);
//             $max_price = max($prices);

//             if ($min_price !== $max_price) {
//                 $price = wc_format_price_range(
//                     wc_price($min_price),
//                     wc_price($max_price)
//                 );
//             } else {
//                 $price = wc_price($min_price);
//             }
//         }
//     }

//     return $price;
// }


function calculate_price_based_on_type($price, $tax, $type = null) {
  //var_dump($price);
    
    if($tax <= 0){
        return $price;
    }
    if($type === 'size'){
        $calculation_type = get_option('wc_tax_calculation_size_type', 'percentage');
       
    }else{
        $calculation_type = get_option('wc_tax_calculation_type', 'percentage');
    }
       
   

    if ($calculation_type === 'percentage') {
        $new_price = (float)$price + (float)($price * ($tax / 100));     
    } else {
       // $new_price = $price + $tax;
       
        $new_price = (float)$price + (float)$tax;
       
    }

    
    //var_dump($new_price); 

    return $new_price;
}


//add_action( 'woocommerce_before_calculate_totals', 'd_alter_price_cart' );
 
function d_alter_price_cart( $cart ) {
 
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
 
    if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) return;
 
    foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
        $product = $cart_item['data'];

        $price = $product->get_regular_price();
        $new_price = check_product_pa_size($price, $product);
        if($new_price > 0){
            $price = $new_price;
        }
        //var_dump($price);
      
        //$tax = 0;
        $tax = get_curent_tax_province($product);
        // var_dump($tax );
        if (isset($tax)) {
       
                $cart_item['data']->set_price( calculate_price_based_on_type($price,$tax) );
          
            
        }
      
       
    }
 
}

function check_product_pa_size($price, $product) {
    $customer_zone = isset($_COOKIE['province']) ? $_COOKIE['province'] : null;
    $provinces = get_option('woocommerce_taxable_provinces', array());
    $tax_60_ml = get_option('woocommerce_taxable_categories_60ml', 0);
    $tax_120_ml = get_option('woocommerce_taxable_categories_120ml', 0);
   
   // var_dump(in_array($customer_zone, $provinces));
    if (check_taxable_categories($product) && in_array($customer_zone, $provinces)) {
       // var_dump($provinces);
      // var_dump($tax_120_ml);
      
           
  //  $customer_zone = isset($_COOKIE['province']) ? $_COOKIE['province'] : null;
    //var_dump($customer_zone);


        if ($product->is_type('variation')) {
            $size = $product->get_attribute('pa_size'); 
           // var_dump($size);
            if ($size) {
                //var_dump($price);
               // var_dump("Product ID: " . $product->get_id());
         //    var_dump("Original Price: " . $price);
         // var_dump("Size: " . $size);

                switch ($size) {
                    case '60ml':
                        $price = calculate_price_based_on_type($price, $tax_60_ml, 'size');
                        break;
                    case '120ml':
                        $price = calculate_price_based_on_type($price, $tax_120_ml, 'size');
                        break;
                }

            // var_dump("Adjusted Price: " . $price);
            } else {
                //var_dump("No size attribute found for this variation");
            }
        } else {
            //var_dump("Product is not a variation");
            //var_dump("Product ID: " . $product->get_id());
           // var_dump("Product Type: " . $product->get_type());
            //var_dump($product); // Show the full product object for detailed inspection
        }
    } else {
       // var_dump("Product not in taxable categories or customer not in taxable province");
    }
   // var_dump($price);
    return $price;
}

// add_filter('woocommerce_product_variation_get_price', 'd_adjust_variation_price', 10, 2);
// add_filter('woocommerce_product_variation_get_regular_price', 'd_adjust_variation_regular_price', 999, 2);
// add_filter('woocommerce_product_variation_get_sale_price', 'd_adjust_variation_sale_price', 10, 2);

//  add_filter('woocommerce_variation_prices_price', 'd_adjust_variation_sale_price', 10, 2);

add_filter('woocommerce_product_variation_get_price', 'd_adjust_variation_price', 10, 2);
add_filter('woocommerce_product_variation_get_regular_price', 'd_adjust_variation_regular_price', 10, 2);
add_filter('woocommerce_product_variation_get_sale_price', 'd_adjust_variation_sale_price', 10, 2);
add_filter('woocommerce_variation_prices_price', 'd_adjust_variation_price', 10, 2);
add_filter('woocommerce_variation_prices_regular_price', 'd_adjust_variation_regular_price', 10, 2);
add_filter('woocommerce_variation_prices_sale_price', 'd_adjust_variation_sale_price', 10, 2);





function d_adjust_variation_price($price, $variation) {
    return check_product_pa_size($price, $variation);
}

function d_adjust_variation_regular_price($price, $variation) {
    return check_product_pa_size($price, $variation);
}

function d_adjust_variation_sale_price($price, $variation) {
    return check_product_pa_size($price, $variation);
}

// function d_calculate_adjusted_price($price, $variation) {
//     $tax = get_curent_tax_province($variation);
//     $tax_60_ml = get_option('woocommerce_taxable_categories_60ml', 0);
//     $tax_120_ml = get_option('woocommerce_taxable_categories_120ml', 0);

//     $size = $variation->get_attribute('pa_size');

//     if ($size == '120ml') {
//         return calculate_price_based_on_type($price, $tax_120_ml, 'size');
//     } elseif ($size == '60ml') {
//         return calculate_price_based_on_type($price, $tax_60_ml, 'size');
//     }

//     return $price;
// }


function modify_fibosearch_query_args_based_on_province( $args ) {
    if (isset($_COOKIE['province'])) {
        $province_code = $_COOKIE['province'];

        // Get excluded product IDs from options
        $exclude_ids = get_option('wc_province_hide_product_' . $province_code, array());
        if (!empty($exclude_ids)) {
            $args['post__not_in'] = $exclude_ids;
        }

        // Get excluded category IDs from options
        $selected_categories = get_option('wc_province_product_hide_category_' . $province_code, array());
        if (!empty($selected_categories)) {
            $args['tax_query'][] = array(
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => $selected_categories,
                'operator' => 'NOT IN',
            );
        }
    }

    return $args;
}
add_filter( 'dgwt/wcas/search_query/args', 'modify_fibosearch_query_args_based_on_province' );




function hide_selected_products($query) {
    if ( is_admin() || ! $query->is_main_query() || is_checkout() ) {
        return;
    }
 
    $province_code = isset($_COOKIE['province']) ? sanitize_text_field($_COOKIE['province']) : '';
    

            // Get the selected products from the settings
            $selected_products = get_option('wc_province_hide_product_' . $province_code, array());
            $selected_categories = get_option('wc_province_product_hide_category_' . $province_code, array());

           // var_dump($selected_products);

           
    // Check if we are not in the admin area, it's the main query, and it's not a single product page
 
        if ( is_post_type_archive( 'product' ) || is_tax( 'product_cat' ) || is_tax( 'product_tag' ) ) {
            // Get the province code from the cookie
           // var_dump($selected_categories);
           
            if (is_array($selected_products)) {
                $selected_products = array_values($selected_products);
            } else {
                $selected_products = array();
            }
            //var_dump($selected_products );



            // Exclude the selected products from the query
            if (!empty($selected_products)) {
               
               $query->set('post__not_in', $selected_products);
                // Force rebuilding of the query
               //  $query->query_vars['post__not_in'] = $selected_products;
              //$query->query = http_build_query($query->query_vars);
               // var_dump('Query after setting post__not_in: ' . print_r($query, true));

            }
            //var_dump($selected_categories);
            if (!empty($selected_categories)) {
                $tax_query = array(
                    array(
                        'taxonomy' => 'product_cat',
                        'field'    => 'slug',
                        'terms'    => $selected_categories,
                        'operator' => 'NOT IN',
                    ),
                );
                $query->set('tax_query', $tax_query);
            }
        }
       
}
 
add_action('pre_get_posts', 'hide_selected_products');

// This part should be in a separate hook because `pre_get_posts` runs too early for redirections.

function product_hide_redirect() {
    $province_code = isset($_COOKIE['province']) ? sanitize_text_field($_COOKIE['province']) : '';

    // Get the selected products and categories from the settings
    $selected_products = get_option('wc_province_hide_product_' . $province_code, array());
    $selected_categories = get_option('wc_province_product_hide_category_' . $province_code, array());

    // Ensure the options return arrays
    $selected_products = is_array($selected_products) ? $selected_products : array();
    $selected_categories = is_array($selected_categories) ? $selected_categories : array();

    if (is_singular('product')) {
        $product_id = get_the_ID();
        $redirect = false;

        // Check if the current product should be hidden based on ID
        if (in_array($product_id, $selected_products)) {
            $redirect = true;
        }

        // Check if the current product should be hidden based on category, only if categories are specified
        if (!$redirect && !empty($selected_categories) && has_term($selected_categories, 'product_cat', $product_id)) {
            $redirect = true;
        }

        // If any of the conditions are met, redirect to the shop page
        if ($redirect) {
            wp_redirect(home_url());

            exit;
        }
    }
}
//add_action('template_redirect', 'product_hide_redirect');

function d_better_is_checkout() {
    $checkout_path    = wp_parse_url(wc_get_checkout_url(), PHP_URL_PATH);
    $current_url_path = wp_parse_url("http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]", PHP_URL_PATH);
    
    return (
      $checkout_path !== null
      && $current_url_path !== null
      && trailingslashit($checkout_path) === trailingslashit($current_url_path)
    );
  }



function modify_product_purchasability( $purchasable, $product ) {
   // var_dump(d_better_is_checkout());
    // if (is_woocommerce_ajax_checkout() || d_better_is_checkout() ) {
    //    // error_log('WooCommerce AJAX checkout update detected.');
    //     return $purchasable;
    // } else{
        
    // }
    if(is_product()){
        $province_code = isset($_COOKIE['province']) ? sanitize_text_field($_COOKIE['province']) : '';

        // Retrieve settings
        $selected_products = get_option('wc_province_hide_product_' . $province_code, array());
        $selected_categories = get_option('wc_province_product_hide_category_' . $province_code, array());
        //var_dump($selected_products);
        // Convert to arrays if necessary and map them to integers for type-safe comparison
        $selected_products = array_map('intval', (array) $selected_products);
        $selected_categories = is_array($selected_categories) ? $selected_categories : array();
    
        $product_id = $product->get_id();
    
        // Check if the product should be non-purchasable based on ID or category
        if (in_array($product_id, $selected_products, true)) {
            return false;
        }
    
        // Check if the product's categories should make it non-purchasable, only if categories are specified
        if (!empty($selected_categories) && has_term($selected_categories, 'product_cat', $product_id)) {
            return false;
        }
    }

   

    return $purchasable;
}



add_filter('woocommerce_is_purchasable', 'modify_product_purchasability', 10, 2);



// Hook into WooCommerce AJAX request handler for 'update_order_review' and 'checkout'
add_action('woocommerce_before_template_part', 'custom_handle_ajax_update_order_review');

function custom_handle_ajax_update_order_review($template_name) {
    // Check if it's the checkout update template
    if ($template_name === 'checkout/review-order.php') {
        if (defined('DOING_AJAX') && DOING_AJAX && isset($_REQUEST['wc-ajax'])) {
            // Check for both 'update_order_review' and 'checkout' AJAX requests
            if ($_REQUEST['wc-ajax'] === 'update_order_review') {
               // error_log('Handling WooCommerce update_order_review AJAX request.');
            } elseif ($_REQUEST['wc-ajax'] === 'checkout') {
               // error_log('Handling WooCommerce checkout AJAX request.');
            }
            
            // Log the full request for debugging
           // error_log('Full AJAX request: ' . print_r($_REQUEST, true));
            
            // Insert your custom logic here for province-based product availability or other logic
        }
    }
}

// Function to check if it's an AJAX request for WooCommerce checkout (update_order_review or checkout)
function is_woocommerce_ajax_checkout() {
    if (defined('DOING_AJAX') && DOING_AJAX && isset($_REQUEST['wc-ajax'])) {
        if ($_REQUEST['wc-ajax'] === 'update_order_review' || $_REQUEST['wc-ajax'] === 'checkout') {
          //  error_log('Detected an AJAX checkout update for ' . $_REQUEST['wc-ajax']);
            return true;
        }
    }
    return false;
}

function modify_variation_purchasability( $purchasable, $variation ) {
  
    
    // if (is_woocommerce_ajax_checkout() || d_better_is_checkout()) {
    //     //error_log('WooCommerce AJAX checkout update detected.');
    //     return $purchasable;
    // }
    if(is_product()){
        $province_code = isset($_COOKIE['province']) ? sanitize_text_field($_COOKIE['province']) : '';
        
        // Fetch settings for excluded products and categories
        $selected_products = get_option('wc_province_hide_product_' . $province_code, array());
        $selected_categories = get_option('wc_province_product_hide_category_' . $province_code, array());

        // Ensure the options return arrays and map them to integers
        $selected_products = array_map('intval', (array) $selected_products);
        $selected_categories = array_map('intval', (array) $selected_categories);

        // Log for debugging
    //// error_log('Excluded Products: ' . implode(', ', $selected_products));
    // error_log('Excluded Categories: ' . implode(', ', $selected_categories));

        $product_id = $variation->get_id();
        $parent_id = $variation->get_parent_id();

        // Check if the variation or its parent product should be non-purchasable
        if (in_array($product_id, $selected_products, true) || in_array($parent_id, $selected_products, true)) {
        //  error_log('Variation or parent is not purchasable: Variation ID ' . $product_id . ' Parent ID ' . $parent_id);
            return false;
        }
    }

    return $purchasable;
}
add_filter('woocommerce_variation_is_purchasable', 'modify_variation_purchasability', 10, 2);




function hide_selected_products_from_shortcode($query_args, $attributes, $type) {
   // var_dump($type);
    if ($type == 'products') {
      //  var_dump($type);
     //   if (($query_args->get('post_type') == 'product_variation' || $query_args->get('post_type') == 'product' )) {
            // Get the province code from the cookie
            $province_code = isset($_COOKIE['province']) ? sanitize_text_field($_COOKIE['province']) : '';

            // Get the selected products from the settings
            $selected_products = get_option('wc_province_hide_product_' . $province_code);
            $selected_categories = get_option('wc_province_product_hide_category_' . $province_code);

            if (is_array($selected_products)) {
                $selected_products = array_values($selected_products);
            } else {
                $selected_products = array();
            }

            // Check if post__in is set
            if (isset($query_args['post__in'])) {
                // Intersect post__in with selected_products
                $query_args['post__in'] = array_diff($query_args['post__in'], $selected_products);
            } else {
                // Exclude the selected products from the query
                if (!empty($selected_products)) {
                    $query_args['post__not_in'] = $selected_products;
                }
            }
            if (!empty($selected_categories)) {
                $query_args['tax_query'] = array(
                    array(
                        'taxonomy' => 'product_cat',
                        'field'    => 'slug',
                        'terms'    => $selected_categories,
                        'operator' => 'NOT IN',
                    ),
                );
            }
       // }
    }

    return $query_args;
}
add_filter('woocommerce_shortcode_products_query', 'hide_selected_products_from_shortcode', 10, 3);

function validate_cart_items_for_province() {
    // Get the selected province from the checkout fields
    $selected_province = WC()->customer->get_shipping_state();

    // Fetch settings for excluded products and categories
    $selected_products = get_option('wc_province_hide_product_' . $selected_province, array());
    $selected_categories = get_option('wc_province_product_hide_category_' . $selected_province, array());

    // Ensure the options return arrays and map them to integers
    $selected_products = array_map('intval', (array) $selected_products);
    $selected_categories = array_map('intval', (array) $selected_categories);

    // Get the cart items
    $cart = WC()->cart->get_cart();
    $unavailable_items = array();

    // Check each cart item for exclusion based on products and categories
    foreach ( $cart as $cart_item_key => $cart_item ) {
        $product_id = $cart_item['product_id'];
        $product_categories = wc_get_product_term_ids( $product_id, 'product_cat' );

        // Check if the product is in the excluded products list
        if ( in_array( $product_id, $selected_products ) ) {
            $unavailable_items[] = $cart_item['data']->get_name();
        }

        // Check if any of the product's categories are in the excluded categories list
        if ( array_intersect( $selected_categories, $product_categories ) ) {
            $unavailable_items[] = $cart_item['data']->get_name();
        }
    }

    // If there are unavailable items, prevent checkout and show an error
    if ( !empty( $unavailable_items ) ) {
        // Prevent WooCommerce from automatically removing the products
        remove_action( 'woocommerce_before_cart_item_quantity_zero', 'woocommerce_remove_cart_item' );

        // Add the error notice with the unavailable items
        wc_add_notice(
            sprintf(
                'The items "%s" are not available for the selected province, please remove them or reselect the previous province.',
                implode( ', ', $unavailable_items )
            ),
            'error'
        );
    }
}
add_action( 'woocommerce_checkout_process', 'validate_cart_items_for_province' );

// Additional FlyingPress compatibility hooks
add_action('init', function() {
    // Start session early for better cookie handling
    if (!session_id() && !is_admin()) {
        session_start();
    }
});

// Exclude province-dependent pages from FlyingPress cache
add_filter('flying_press_exclude_urls', function($exclude_urls) {
    // Always exclude checkout and cart pages
    $exclude_urls[] = '/cart/';
    $exclude_urls[] = '/checkout/';
    $exclude_urls[] = '/my-account/';
    
    // Exclude pages when province session is not set
    if (!has_province_session()) {
        $exclude_urls[] = '.*';
    }
    
    return $exclude_urls;
});

// Exclude specific cookies from FlyingPress cache
add_filter('flying_press_exclude_cookies', function($exclude_cookies) {
    $exclude_cookies[] = 'province';
    $exclude_cookies[] = 'woocommerce_cart_hash';
    $exclude_cookies[] = 'woocommerce_items_in_cart';
    return $exclude_cookies;
});

// Exclude user-specific content from cache
add_filter('flying_press_exclude_user_agents', function($exclude_user_agents) {
    // Exclude when province session is not set
    if (!has_province_session()) {
        $exclude_user_agents[] = '.*';
    }
    return $exclude_user_agents;
});

// Force cache refresh when province changes
add_action('wp_ajax_update_province', 'force_cache_refresh_on_province_change');
add_action('wp_ajax_nopriv_update_province', 'force_cache_refresh_on_province_change');

function force_cache_refresh_on_province_change() {
    // Clear all caches when province changes
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
    
    if (function_exists('flying_press_clear_cache')) {
        flying_press_clear_cache();
    }
    
    if (function_exists('rocket_clean_domain')) {
        rocket_clean_domain();
    }
    
    wp_die('Cache cleared');
}

// Add JavaScript to handle province changes and force cache refresh
add_action('wp_footer', 'add_province_cache_refresh_script');

function add_province_cache_refresh_script() {
    if (has_province_session()) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Force cache refresh when province session changes
            var currentProvince = '<?php echo get_province_cookie(); ?>';
            
            // Check if province changed in session vs cookie
            if (currentProvince && currentProvince !== getCookie('province')) {
                // Province changed, clear cache
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'update_province'
                    },
                    success: function() {
                        location.reload();
                    }
                });
            }
            
            // Helper function to get cookie
            function getCookie(name) {
                var value = "; " + document.cookie;
                var parts = value.split("; " + name + "=");
                if (parts.length == 2) return parts.pop().split(";").shift();
            }
        });
        </script>
        <?php
    }
}

// Enhanced province cookie function with Bunny CDN support
function set_province_cookie($province) {
    // Set cookie with enhanced parameters
    $cookie_params = array(
        'expires' => time() + (86400 * 60), // 60 days
        'path' => '/',
        'domain' => '',
        'secure' => is_ssl(),
        'httponly' => false,
        'samesite' => 'Lax'
    );
    
    // Set the cookie
    setcookie('province', $province, $cookie_params);
    $_COOKIE['province'] = $province;
    
    // Force cache clearing for FlyingPress
    if (function_exists('flying_press_clear_cache')) {
        flying_press_clear_cache();
        error_log('FlyingPress cache cleared for province: ' . $province);
    }
    
    // Force WordPress cache clearing
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
        error_log('WordPress cache flushed for province: ' . $province);
    }
    
    // Force WP Rocket cache clearing if available
    if (function_exists('rocket_clean_domain')) {
        rocket_clean_domain();
        error_log('WP Rocket cache cleared for province: ' . $province);
    }
    
    // Set cache control headers immediately
    if (!headers_sent()) {
        header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0, private');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('X-Accel-Expires: 0');
        header('X-Cache-Status: BYPASS');
        header('Surrogate-Control: no-store');
        header('CDN-Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
        header('X-Bunny-Cache: BYPASS');
        header('X-Bunny-Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
    }
    
    return true;
}

// Simple cookie-based province retrieval
function get_province_cookie() {
    if (isset($_COOKIE['province'])) {
        return sanitize_text_field($_COOKIE['province']);
    }
    return null;
}

// Check if province is set using cookie
function has_province_cookie() {
    $province = get_province_cookie();
    return $province !== null && $province !== '';
}

// Ensure WooCommerce session is initialized early
add_action('init', 'ensure_woocommerce_session', 1);

function ensure_woocommerce_session() {
    if (!is_admin() && function_exists('WC')) {
        // Check if WooCommerce is loaded
        if (!WC()->session) {
            WC()->session = new WC_Session_Handler();
            WC()->session->init();
            error_log('WooCommerce session initialized manually');
        }
        
        // Debug: Log session status
        error_log('WooCommerce session status: ' . (WC()->session ? 'INITIALIZED' : 'NOT INITIALIZED'));
        if (WC()->session) {
            error_log('Session customer ID: ' . WC()->session->get_customer_id());
            error_log('Session province: ' . WC()->session->get('selected_province'));
            
            // Test setting and getting a value
            WC()->session->set('test_value', 'test');
            $test_value = WC()->session->get('test_value');
            error_log('Session test - Set: test, Get: ' . $test_value);
        } else {
            error_log('ERROR: WooCommerce session could not be initialized');
        }
    } else {
        error_log('WooCommerce not available or admin page');
    }
}

// Clean up province session on logout
add_action('wp_logout', 'cleanup_province_session');

function cleanup_province_session() {
    if (function_exists('WC') && WC()->session) {
        WC()->session->__unset('selected_province');
    }
    
    // Also clear transient
    if (function_exists('WC') && WC()->session) {
        $customer_id = WC()->session->get_customer_id();
        if ($customer_id) {
            delete_transient('province_' . $customer_id);
        }
    }
    
    // Clear cookie
    if (isset($_COOKIE['province'])) {
        setcookie('province', '', time() - 3600, '/');
        unset($_COOKIE['province']);
    }
}

// Add AJAX endpoint for province updates
add_action('wp_ajax_update_province_session', 'ajax_update_province_session');
add_action('wp_ajax_nopriv_update_province_session', 'ajax_update_province_session');

function ajax_update_province_session() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'province_session_nonce')) {
        wp_send_json_error('Invalid nonce');
        return;
    }
    
    if (isset($_POST['province'])) {
        $province = sanitize_text_field($_POST['province']);
        if (set_province_session_enhanced($province)) {
            wp_send_json_success('Province updated successfully');
        } else {
            wp_send_json_error('Failed to update province');
        }
    } else {
        wp_send_json_error('No province provided');
    }
}

// Test function for debugging province session
function test_province_session_debug() {
    if (current_user_can('manage_options')) { // Only show to admins
        echo '<div style="position: fixed; bottom: 10px; right: 10px; background: #fff; border: 1px solid #ccc; padding: 10px; z-index: 999999; font-size: 12px; max-width: 300px; box-shadow: 0 0 10px rgba(0,0,0,0.3);">';
        echo '<h4 style="margin: 0 0 10px 0; color: #333;">Province Session Debug</h4>';
        
        // Test session functions
        echo '<p style="margin: 5px 0;"><strong>has_province_session():</strong> ' . (has_province_session() ? 'TRUE' : 'FALSE') . '</p>';
        
        $province = get_province_session_enhanced();
        echo '<p style="margin: 5px 0;"><strong>get_province_session_enhanced():</strong> ' . ($province ? $province : 'NULL') . '</p>';
        
        // Test WooCommerce session
        if (function_exists('WC') && WC()->session) {
            echo '<p style="margin: 5px 0;"><strong>WC Session:</strong> INITIALIZED</p>';
            echo '<p style="margin: 5px 0;"><strong>Customer ID:</strong> ' . WC()->session->get_customer_id() . '</p>';
            echo '<p style="margin: 5px 0;"><strong>Session Province:</strong> ' . WC()->session->get('selected_province') . '</p>';
        } else {
            echo '<p style="margin: 5px 0;"><strong>WC Session:</strong> NOT INITIALIZED</p>';
        }
        
        // Test cookie
        echo '<p style="margin: 5px 0;"><strong>Cookie Province:</strong> ' . (isset($_COOKIE['province']) ? $_COOKIE['province'] : 'NOT SET') . '</p>';
        
        // Test button to set province
        echo '<form method="post" style="margin-top: 10px;">';
        echo '<input type="hidden" name="test_province" value="ON">';
        echo '<button type="submit" style="background: #0073aa; color: #fff; border: none; padding: 5px 10px; cursor: pointer; border-radius: 3px;">Test Set Province ON</button>';
        echo '</form>';
        
        echo '</div>';
    }
}

// Add test function to footer for admins
add_action('wp_footer', 'test_province_session_debug');

// Handle test province setting
add_action('init', function() {
    if (isset($_POST['test_province']) && current_user_can('manage_options')) {
        set_province_session_enhanced('ON');
        wp_redirect(remove_query_arg('test_province'));
        exit;
    }
});

// Test function to force show age verification dialog
function force_show_age_verification() {
    echo '<div style="position: fixed; top: 50px; left: 10px; background: #fff; border: 2px solid #ff0000; padding: 10px; z-index: 999999; font-size: 12px; max-width: 300px;">';
    echo '<h4 style="color: #ff0000;">FORCE SHOW AGE VERIFICATION</h4>';
    echo '<p><strong>has_province_session():</strong> ' . (has_province_session() ? 'TRUE' : 'FALSE') . '</p>';
    echo '<p><strong>get_province_session_enhanced():</strong> ' . (get_province_session_enhanced() ?: 'NULL') . '</p>';
    
    // Force show the dialog
    echo '<div id="custom-age-popup-wrapper" style="display: block !important; z-index: 999999 !important; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8);">';
    echo '<div id="custom-age-popup-box" style="max-width: 700px; text-align: center; position: relative; border-radius: 8px; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; padding: 20px;">';
    echo '<h2>AGE VERIFICATION TEST</h2>';
    echo '<p>This is a test dialog to verify the function is working.</p>';
    echo '<button onclick="this.parentElement.parentElement.remove()" style="background: #0073aa; color: #fff; border: none; padding: 10px 20px; cursor: pointer;">Close Test</button>';
    echo '</div>';
    echo '</div>';
    
    echo '</div>';
}

// Add force show function to footer for testing
add_action('wp_footer', 'force_show_age_verification');

// Test function to manually test WooCommerce session
function test_woocommerce_session() {
    if (current_user_can('manage_options')) {
        echo '<div style="position: fixed; bottom: 10px; left: 10px; background: #fff; border: 2px solid #00ff00; padding: 10px; z-index: 999999; font-size: 12px; max-width: 300px;">';
        echo '<h4 style="color: #00ff00;">WC Session Test</h4>';
        
        if (function_exists('WC') && WC()->session) {
            echo '<p><strong>WC Session:</strong> AVAILABLE</p>';
            echo '<p><strong>Customer ID:</strong> ' . WC()->session->get_customer_id() . '</p>';
            
            // Test set and get
            WC()->session->set('test_province', 'TEST_ON');
            $test_value = WC()->session->get('test_province');
            echo '<p><strong>Test Set/Get:</strong> ' . $test_value . '</p>';
            
            // Test province
            $current_province = WC()->session->get('selected_province');
            echo '<p><strong>Current Province:</strong> ' . ($current_province ?: 'NULL') . '</p>';
            
            // Test button to set province
            echo '<form method="post" style="margin-top: 10px;">';
            echo '<input type="hidden" name="test_wc_province" value="ON">';
            echo '<button type="submit" style="background: #00ff00; color: #000; border: none; padding: 5px 10px; cursor: pointer; border-radius: 3px;">Test Set WC Province ON</button>';
            echo '</form>';
            
        } else {
            echo '<p><strong>WC Session:</strong> NOT AVAILABLE</p>';
        }
        
        echo '</div>';
    }
}

// Add test function to footer
add_action('wp_footer', 'test_woocommerce_session');

// Handle test WC province setting
add_action('init', function() {
    if (isset($_POST['test_wc_province']) && current_user_can('manage_options')) {
        if (function_exists('WC') && WC()->session) {
            WC()->session->set('selected_province', 'ON');
            error_log('Test: Manually set WC session province to ON');
        }
        wp_redirect(remove_query_arg('test_wc_province'));
        exit;
    }
});

// Comprehensive debug panel for session issues
function comprehensive_session_debug() {
    if (current_user_can('manage_options')) {
        echo '<div style="position: fixed; top: 10px; left: 10px; background: #000; color: #00ff00; border: 2px solid #00ff00; padding: 15px; z-index: 999999; font-size: 12px; max-width: 400px; font-family: monospace;">';
        echo '<h4 style="margin: 0 0 10px 0; color: #00ff00;">🔍 COMPREHENSIVE SESSION DEBUG</h4>';
        
        // Check WooCommerce availability
        echo '<p><strong>WC Available:</strong> ' . (function_exists('WC') ? 'YES' : 'NO') . '</p>';
        
        if (function_exists('WC')) {
            echo '<p><strong>WC Session:</strong> ' . (WC()->session ? 'YES' : 'NO') . '</p>';
            
            if (WC()->session) {
                echo '<p><strong>Customer ID:</strong> ' . WC()->session->get_customer_id() . '</p>';
                echo '<p><strong>Session Province:</strong> ' . (WC()->session->get('selected_province') ?: 'NULL') . '</p>';
                
                // Test session set/get
                WC()->session->set('debug_test', 'DEBUG_VALUE');
                $test_value = WC()->session->get('debug_test');
                echo '<p><strong>Session Test:</strong> ' . $test_value . '</p>';
                
                // Check all session data
                $session_data = WC()->session->get_session_data();
                echo '<p><strong>Session Data Keys:</strong> ' . implode(', ', array_keys($session_data)) . '</p>';
            }
        }
        
        // Check user status
        echo '<p><strong>User Logged In:</strong> ' . (is_user_logged_in() ? 'YES' : 'NO') . '</p>';
        if (is_user_logged_in()) {
            echo '<p><strong>User ID:</strong> ' . get_current_user_id() . '</p>';
            echo '<p><strong>User Meta Province:</strong> ' . get_user_meta(get_current_user_id(), 'selected_province', true) . '</p>';
        }
        
        // Check cookies
        echo '<p><strong>Province Cookie:</strong> ' . (isset($_COOKIE['province']) ? $_COOKIE['province'] : 'NOT SET') . '</p>';
        
        // Check transients
        if (function_exists('WC') && WC()->session) {
            $customer_id = WC()->session->get_customer_id();
            if ($customer_id) {
                $transient_province = get_transient('province_' . $customer_id);
                echo '<p><strong>Transient Province:</strong> ' . ($transient_province ?: 'NULL') . '</p>';
            }
        }
        
        // Test our functions
        echo '<hr style="border-color: #00ff00;">';
        echo '<p><strong>get_province_session_enhanced():</strong> ' . get_province_session_enhanced() . '</p>';
        echo '<p><strong>has_province_session():</strong> ' . (has_province_session() ? 'TRUE' : 'FALSE') . '</p>';
        
        // Manual test buttons
        echo '<hr style="border-color: #00ff00;">';
        echo '<form method="post" style="margin-top: 10px;">';
        echo '<input type="hidden" name="debug_set_province" value="TEST_QC">';
        echo '<button type="submit" style="background: #00ff00; color: #000; border: none; padding: 5px 10px; cursor: pointer; border-radius: 3px; margin-right: 5px;">Set Province TEST_QC</button>';
        echo '</form>';
        
        echo '<form method="post" style="margin-top: 5px;">';
        echo '<input type="hidden" name="debug_clear_province" value="1">';
        echo '<button type="submit" style="background: #ff0000; color: #fff; border: none; padding: 5px 10px; cursor: pointer; border-radius: 3px;">Clear Province</button>';
        echo '</form>';
        
        echo '</div>';
    }
}

// Add comprehensive debug to footer
add_action('wp_footer', 'comprehensive_session_debug');

// Handle debug actions
add_action('init', function() {
    if (current_user_can('manage_options')) {
        if (isset($_POST['debug_set_province'])) {
            $province = sanitize_text_field($_POST['debug_set_province']);
            if (set_province_session_enhanced($province)) {
                error_log('Debug: Manually set province to ' . $province);
            }
            wp_redirect(remove_query_arg('debug_set_province'));
            exit;
        }
        
        if (isset($_POST['debug_clear_province'])) {
            if (function_exists('WC') && WC()->session) {
                WC()->session->__unset('selected_province');
                error_log('Debug: Manually cleared province from session');
            }
            wp_redirect(remove_query_arg('debug_clear_province'));
            exit;
        }
    }
});

// Test basic PHP logic
function test_php_logic() {
    error_log('=== TESTING BASIC PHP LOGIC ===');
    
    $test_null = null;
    $test_empty = '';
    $test_string = 'QC';
    
    error_log('Test NULL:');
    error_log('  $test_null = ' . ($test_null ?: 'NULL'));
    error_log('  $test_null !== null: ' . ($test_null !== null ? 'TRUE' : 'FALSE'));
    error_log('  $test_null !== "": ' . ($test_null !== '' ? 'TRUE' : 'FALSE'));
    error_log('  ($test_null !== null && $test_null !== ""): ' . (($test_null !== null && $test_null !== '') ? 'TRUE' : 'FALSE'));
    
    error_log('Test EMPTY:');
    error_log('  $test_empty = "' . $test_empty . '"');
    error_log('  $test_empty !== null: ' . ($test_empty !== null ? 'TRUE' : 'FALSE'));
    error_log('  $test_empty !== "": ' . ($test_empty !== '' ? 'TRUE' : 'FALSE'));
    error_log('  ($test_empty !== null && $test_empty !== ""): ' . (($test_empty !== null && $test_empty !== '') ? 'TRUE' : 'FALSE'));
    
    error_log('Test STRING:');
    error_log('  $test_string = "' . $test_string . '"');
    error_log('  $test_string !== null: ' . ($test_string !== null ? 'TRUE' : 'FALSE'));
    error_log('  $test_string !== "": ' . ($test_string !== '' ? 'TRUE' : 'FALSE'));
    error_log('  ($test_string !== null && $test_string !== ""): ' . (($test_string !== null && $test_string !== '') ? 'TRUE' : 'FALSE'));
    
    error_log('=== END PHP LOGIC TEST ===');
}

// Add PHP logic test to comprehensive debug
add_action('wp_footer', function() {
    if (current_user_can('manage_options')) {
        echo '<div style="position: fixed; top: 10px; right: 10px; background: #000; color: #ffff00; border: 2px solid #ffff00; padding: 15px; z-index: 999999; font-size: 12px; max-width: 300px; font-family: monospace;">';
        echo '<h4 style="margin: 0 0 10px 0; color: #ffff00;">🧪 PHP LOGIC TEST</h4>';
        echo '<button onclick="testPhpLogic()" style="background: #ffff00; color: #000; border: none; padding: 5px 10px; cursor: pointer; border-radius: 3px; width: 100%;">Test PHP Logic</button>';
        echo '<div id="php-logic-result" style="margin-top: 10px; font-size: 10px;"></div>';
        echo '</div>';
        
        echo '<script>
        function testPhpLogic() {
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "' . admin_url('admin-ajax.php') . '", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    document.getElementById("php-logic-result").innerHTML = "Check error logs for results";
                }
            };
            xhr.send("action=test_php_logic&nonce=' . wp_create_nonce('test_php_logic') . '");
        }
        </script>';
    }
});

// AJAX handler for PHP logic test
add_action('wp_ajax_test_php_logic', function() {
    if (wp_verify_nonce($_POST['nonce'], 'test_php_logic')) {
        test_php_logic();
        wp_send_json_success('PHP logic test completed - check error logs');
    } else {
        wp_send_json_error('Invalid nonce');
    }
});

// Bunny CDN + FlyingPress cache control for province cookies
add_action('send_headers', function() {
    // Check if user has province cookie
    if (isset($_COOKIE['province'])) {
        // Set aggressive cache control headers for Bunny CDN
        header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0, private');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Bunny CDN specific headers
        header('X-Accel-Expires: 0');
        header('X-Cache-Status: BYPASS');
        header('Surrogate-Control: no-store');
        header('CDN-Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
        
        // Additional headers for Bunny CDN
        header('X-Bunny-Cache: BYPASS');
        header('X-Bunny-Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
    }
});

// FlyingPress cache exclusion for pages with province cookies
add_filter('flying_press_exclude_urls', function($exclude_urls) {
    // Exclude pages that might have province cookies
    $exclude_urls[] = '/';
    $exclude_urls[] = '/shop/';
    $exclude_urls[] = '/cart/';
    $exclude_urls[] = '/checkout/';
    $exclude_urls[] = '/my-account/';
    
    return $exclude_urls;
});

// FlyingPress cookie exclusion
add_filter('flying_press_exclude_cookies', function($exclude_cookies) {
    $exclude_cookies[] = 'province';
    return $exclude_cookies;
});

// FlyingPress user agent exclusion for dynamic content
add_filter('flying_press_exclude_user_agents', function($exclude_user_agents) {
    // Exclude bots and crawlers that might cache content
    $exclude_user_agents[] = 'bot';
    $exclude_user_agents[] = 'crawler';
    $exclude_user_agents[] = 'spider';
    
    return $exclude_user_agents;
});

