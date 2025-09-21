<?php
/*
Plugin Name: Realm Community Lookup
Description: Community search and building hub functionality with multi-utility rate management, service cards, and billing system integration. Modern search bar design with gradient button. Streamlined to show hub navigation only.
Version: 4.2
Author: Ryan Reid / Jakob Reid
License: GPL2
*/

// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// 1. Enqueue Scripts and Styles
function realm_cl_enqueue_scripts() {

    // Enqueue our CSS
    wp_enqueue_style(
        'realm-community-lookup-style',
        plugin_dir_url(__FILE__) . 'realm-community-lookup.css',
        array(),
        '4.2'
    );

    // Enqueue our JS, depends on jQuery
    wp_enqueue_script(
        'realm-community-lookup-script',
        plugin_dir_url(__FILE__) . 'realm-community-lookup.js',
        array('jquery'),
        '2.0',
        true
    );

    // Make the admin-ajax URL available to JS
    wp_localize_script('realm-community-lookup-script', 'realmClAjax', [
        'ajax_url' => admin_url('admin-ajax.php'),
    ]);
}
add_action('wp_enqueue_scripts', 'realm_cl_enqueue_scripts', 5);



// 2. Shortcode: [realm_community_lookup]
function realm_cl_display_search_form() {
    ob_start();

    // Include the search form template (includes result templates too)
    include plugin_dir_path(__FILE__) . 'templates/search-form.php';

    return ob_get_clean();
}
add_shortcode('realm_community_lookup', 'realm_cl_display_search_form');
add_shortcode('realm_test', function() { return '<p>Realm plugin is working!</p>'; });

// Debug: Check if shortcode is registered
add_action('init', function() {
    if (shortcode_exists('realm_community_lookup')) {
        error_log('Realm Community Lookup shortcode is registered');
    } else {
        error_log('Realm Community Lookup shortcode is NOT registered');
    }
});

// 3. AJAX Handler
function realm_cl_handle_search() {
    // The search term (postcode) from the request
    $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
    $results = array();

    // CSV paths in the same folder as this plugin file
    $communities_csv = plugin_dir_path(__FILE__) . 'communities.csv';
    $charges_csv = plugin_dir_path(__FILE__) . 'community_charges.csv';

    // First, read all charges data into memory for quick lookup
    $charges_by_building = array();
    if ( file_exists($charges_csv) && ($charges_handle = fopen($charges_csv, 'r')) !== false ) {
        // Read header row and create mapping
        $charges_header = fgetcsv($charges_handle);
        $header_map = array_flip($charges_header);

        // Read all charges data using column names
        while (($charge_row = fgetcsv($charges_handle)) !== false) {
            $building_name = $charge_row[$header_map['building_name']];
            if (!isset($charges_by_building[$building_name])) {
                $charges_by_building[$building_name] = array();
            }
            $charges_by_building[$building_name][] = array(
                'utility' => isset($header_map['utility']) ? $charge_row[$header_map['utility']] : '',
                'charge_name' => isset($header_map['charge_name']) ? $charge_row[$header_map['charge_name']] : '',
                'charge_rate' => isset($header_map['charge_rate']) ? $charge_row[$header_map['charge_rate']] : '',
                'charge_rate_unit' => isset($header_map['charge_rate_unit']) ? $charge_row[$header_map['charge_rate_unit']] : ''
            );
        }
        fclose($charges_handle);
    }

    // Now read communities data and match by postcode
    if ( file_exists($communities_csv) && ($handle = fopen($communities_csv, 'r')) !== false ) {
        // Skip header row
        $header = fgetcsv($handle);

        // Loop through each row of the CSV
        while (($row = fgetcsv($handle)) !== false) {
            // row[3] is the Postcode column in communities.csv
            if (strcasecmp(trim($row[3]), trim($query)) === 0) {
                $building_name = $row[0];

                // Get charges for this building
                $charges = isset($charges_by_building[$building_name]) ? $charges_by_building[$building_name] : array();

                $results[] = array(
                    'building_name'     => $row[0],
                    'billing_system'    => $row[1],
                    'building_code'     => $row[2],
                    'postcode'          => $row[3],
                    'water_authority'   => $row[4],
                    'classification'    => $row[5],
                    'charges'           => $charges
                );
            }
        }
        fclose($handle);
    }

    // Return the matching rows as JSON
    wp_send_json($results);
}

// 4. Register the AJAX hooks
add_action('wp_ajax_realm_cl_handle_search', 'realm_cl_handle_search');
add_action('wp_ajax_nopriv_realm_cl_handle_search', 'realm_cl_handle_search');

// 5. Building Hub Shortcode: [building_hub] - Hidden functionality for backend routing
function realm_building_hub_display() {
    // This shortcode now just handles backend logic without displaying anything
    // The billing system routing happens here but customers don't see it
    $billing_system = isset($_GET['system']) ? sanitize_text_field($_GET['system']) : '';

    // You can add any backend logic here for different billing systems
    // but return empty string so nothing displays to customer

    return '';
}
add_shortcode('building_hub', 'realm_building_hub_display');

// 6. Building Hub Hero Shortcode: [building_hub_hero]
function realm_building_hub_hero_display() {
    // Get URL parameters
    $building_code = isset($_GET['building']) ? sanitize_text_field($_GET['building']) : '';

    // Try to get building name from CSV if we have building code
    $building_name = 'Building Hub';
    $postcode = '';
    $billing_system = '';
    $classification = '';

    if ($building_code) {
        $communities_csv = plugin_dir_path(__FILE__) . 'communities.csv';
        if (file_exists($communities_csv) && ($handle = fopen($communities_csv, 'r')) !== false) {
            // Read header row and create mapping
            $header = fgetcsv($handle);
            $header_map = array_flip($header);

            while (($row = fgetcsv($handle)) !== false) {
                if ($row[$header_map['building_code']] === $building_code) {
                    $building_name = $row[$header_map['building_name']];
                    $billing_system = $row[$header_map['billing_system']];
                    $postcode = $row[$header_map['postcode']];
                    $classification = $row[$header_map['classification']];
                    break;
                }
            }
            fclose($handle);
        }
    }

    // Set URLs based on billing system
    $base_url = '/staging/5684';
    $urls = array();

    if ($billing_system === 'StrataMax') {
        $urls = array(
            'move_in' => '', // Custom form needed
            'pay_bill' => $base_url . '/making-a-payment',
            'move_out' => '', // Custom form needed
            'direct_debit' => 'https://www.stratapay.com.au/directdebit/',
            'contact' => $base_url . '/contact'
        );
    } elseif ($billing_system === 'BlueBilling') {
        $urls = array(
            'move_in' => $base_url . '/move-in',
            'pay_bill' => $base_url . '/customer-portal',
            'move_out' => $base_url . '/move-out',
            'direct_debit' => $base_url . '/customer-portal',
            'contact' => $base_url . '/contact',
            'portal' => $base_url . '/customer-portal'
        );
    } else {
        // Default fallback
        $urls = array(
            'move_in' => '',
            'pay_bill' => '',
            'move_out' => '',
            'direct_debit' => '',
            'contact' => $base_url . '/contact'
        );
    }

    ob_start();
    include plugin_dir_path(__FILE__) . 'templates/building-hub-hero.php';
    return ob_get_clean();
}
add_shortcode('building_hub_hero', 'realm_building_hub_hero_display');

// 7. Building Hub Services Shortcode: [building_hub_services]
function realm_building_hub_services_display() {
    // Get URL parameters
    $building_code = isset($_GET['building']) ? sanitize_text_field($_GET['building']) : '';

    if (!$building_code) {
        return '<p>No building selected.</p>';
    }

    // Get billing system from CSV
    $billing_system = '';
    $communities_csv = plugin_dir_path(__FILE__) . 'communities.csv';

    if (file_exists($communities_csv) && ($handle = fopen($communities_csv, 'r')) !== false) {
        fgetcsv($handle); // Skip header
        while (($row = fgetcsv($handle)) !== false) {
            if ($row[2] === $building_code) { // building_code is column 2
                $billing_system = $row[1]; // billing_system is column 1
                break;
            }
        }
        fclose($handle);
    }

    // Set URLs based on billing system
    $base_url = '/staging/5684';
    $urls = array();

    if ($billing_system === 'StrataMax') {
        $urls = array(
            'move_in' => '', // Custom form needed
            'pay_bill' => $base_url . '/making-a-payment',
            'move_out' => '', // Custom form needed
            'direct_debit' => 'https://www.stratapay.com.au/directdebit/',
            'contact' => $base_url . '/contact'
        );
    } elseif ($billing_system === 'BlueBilling') {
        $urls = array(
            'move_in' => $base_url . '/move-in',
            'pay_bill' => $base_url . '/customer-portal',
            'move_out' => $base_url . '/move-out',
            'direct_debit' => $base_url . '/customer-portal',
            'contact' => $base_url . '/contact',
            'portal' => $base_url . '/customer-portal'
        );
    } else {
        // Default fallback
        $urls = array(
            'move_in' => '',
            'pay_bill' => '',
            'move_out' => '',
            'direct_debit' => '',
            'contact' => $base_url . '/contact'
        );
    }

    ob_start();
    include plugin_dir_path(__FILE__) . 'templates/building-hub-services.php';
    return ob_get_clean();
}
add_shortcode('building_hub_services', 'realm_building_hub_services_display');

// 7. Building Hub Rates Shortcode: [building_hub_rates]
function realm_building_hub_rates_display() {
    // Get URL parameters
    $building_code = isset($_GET['building']) ? sanitize_text_field($_GET['building']) : '';

    if (!$building_code) {
        return '<p>No building selected.</p>';
    }

    // Get building data from CSV
    $building_name = '';
    $charges = array();

    $communities_csv = plugin_dir_path(__FILE__) . 'communities.csv';
    $charges_csv = plugin_dir_path(__FILE__) . 'community_charges.csv';

    // Get building name from communities CSV
    if (file_exists($communities_csv) && ($handle = fopen($communities_csv, 'r')) !== false) {
        fgetcsv($handle); // Skip header
        while (($row = fgetcsv($handle)) !== false) {
            if ($row[2] === $building_code) { // building_code is column 2
                $building_name = $row[0]; // building_name is column 0
                break;
            }
        }
        fclose($handle);
    }

    // Get charges from charges CSV using column headers
    if (file_exists($charges_csv) && ($handle = fopen($charges_csv, 'r')) !== false) {
        $charges_header = fgetcsv($handle); // Read headers
        $header_map = array_flip($charges_header); // Create header to index mapping

        while (($row = fgetcsv($handle)) !== false) {
            if ($row[$header_map['building_name']] === $building_name) {
                $charges[] = array(
                    'utility' => isset($header_map['utility']) ? $row[$header_map['utility']] : '',
                    'charge_name' => isset($header_map['charge_name']) ? $row[$header_map['charge_name']] : '',
                    'charge_rate' => isset($header_map['charge_rate']) ? $row[$header_map['charge_rate']] : '',
                    'charge_rate_unit' => isset($header_map['charge_rate_unit']) ? $row[$header_map['charge_rate_unit']] : ''
                );
            }
        }
        fclose($handle);
    }

    if (empty($charges)) {
        return '<p>No rate information available for this building.</p>';
    }

    ob_start();
    include plugin_dir_path(__FILE__) . 'templates/building-hub-rates.php';
    return ob_get_clean();
}
add_shortcode('building_hub_rates', 'realm_building_hub_rates_display');
