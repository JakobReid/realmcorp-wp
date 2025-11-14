<?php
/*
Plugin Name: Realm Community Lookup
Description: Community search and building hub functionality with multi-utility rate management, service cards, and billing system integration. Modern search bar design with gradient button. Streamlined to show hub navigation only.
Version: 5.0
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
        '5.0'
    );

    // Enqueue our JS, depends on jQuery
    wp_enqueue_script(
        'realm-community-lookup-script',
        plugin_dir_url(__FILE__) . 'realm-community-lookup.js',
        array('jquery'),
        '5.0',
        true
    );

    // Make the admin-ajax URL available to JS
    wp_localize_script('realm-community-lookup-script', 'realmClAjax', [
        'ajax_url' => admin_url('admin-ajax.php'),
    ]);
}
add_action('wp_enqueue_scripts', 'realm_cl_enqueue_scripts', 999);



// 2. Shortcode: [realm_community_lookup]
function realm_cl_display_search_form() {
    ob_start();

    // Include the search form template (includes result templates too)
    include plugin_dir_path(__FILE__) . 'templates/search-form.php';

    return ob_get_clean();
}
add_shortcode('realm_community_lookup', 'realm_cl_display_search_form');

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
        // Read header and create column name to index mapping
        $header = array_flip(fgetcsv($handle));

        // Loop through each row of the CSV
        while (($row = fgetcsv($handle)) !== false) {
            // Match by postcode column
            if (strcasecmp(trim($row[$header['postcode']]), trim($query)) === 0) {
                $building_name = $row[$header['building_name']];
                $electricity_authority = isset($header['electricity_authority']) ? trim($row[$header['electricity_authority']]) : '';
                $water_authority = isset($header['water_authority']) ? trim($row[$header['water_authority']]) : '';

                // Determine services from authority columns
                $services = array();
                if (!empty($electricity_authority)) {
                    $services[] = 'Electricity';
                }
                if (!empty($water_authority)) {
                    $services[] = 'Water';
                }

                // Get charges for this building
                $charges = isset($charges_by_building[$building_name]) ? $charges_by_building[$building_name] : array();

                // Determine display name based on service count
                $display_name = $building_name;
                if (count($services) === 1) {
                    $display_name = $building_name . ' - ' . $services[0];
                }

                $results[] = array(
                    'building_name'         => $building_name,
                    'billing_system'        => $row[$header['billing_system']],
                    'building_code'         => $row[$header['building_code']],
                    'postcode'              => $row[$header['postcode']],
                    'classification'        => $row[$header['classification']],
                    'electricity_authority' => $electricity_authority,
                    'water_authority'       => $water_authority,
                    'services'              => $services,
                    'display_name'          => $display_name,
                    'charges'               => $charges
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
    // Get building name from URL parameter
    $building_name = isset($_GET['building']) ? sanitize_text_field($_GET['building']) : '';

    // Try to get building data from CSV
    $postcode = '';
    $billing_system = '';
    $classification = '';
    $electricity_authority = '';
    $water_authority = '';

    if ($building_name) {
        $communities_csv = plugin_dir_path(__FILE__) . 'communities.csv';
        if (file_exists($communities_csv) && ($handle = fopen($communities_csv, 'r')) !== false) {
            // Read header row and create mapping
            $header = array_flip(fgetcsv($handle));

            while (($row = fgetcsv($handle)) !== false) {
                if ($row[$header['building_name']] === $building_name) {
                    $billing_system = strtolower(str_replace(' ', '', trim($row[$header['billing_system']])));
                    $postcode = $row[$header['postcode']];
                    $classification = $row[$header['classification']];
                    $electricity_authority = isset($header['electricity_authority']) ? $row[$header['electricity_authority']] : '';
                    $water_authority = isset($header['water_authority']) ? $row[$header['water_authority']] : '';
                    break;
                }
            }
            fclose($handle);
        }
    }

    // Fallback if not found
    if (empty($building_name)) {
        $building_name = 'Building Hub';
    }

    // Set URLs based on billing system
    $base_url = '';
    $urls = array();

    if ($billing_system === 'stratamax') {
        $urls = array(
            'move_in' => '', // Custom form needed
            'pay_bill' => $base_url . '/making-a-payment',
            'move_out' => '', // Custom form needed
            'direct_debit' => 'https://www.stratapay.com.au/directdebit/',
            'contact' => $base_url . '/contact'
        );
    } elseif ($billing_system === 'bluebilling') {
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

    // load variables into the template
    ob_start();
    include plugin_dir_path(__FILE__) . 'templates/building-hub-hero.php';
    return ob_get_clean();
}
add_shortcode('building_hub_hero', 'realm_building_hub_hero_display');

// 7. Building Hub Services Shortcode: [building_hub_services]
function realm_building_hub_services_display() {
    // Get building name from URL parameter
    $building_name = isset($_GET['building']) ? sanitize_text_field($_GET['building']) : '';

    if (!$building_name) {
        return '<p>No building selected.</p>';
    }

    // Get billing system from CSV
    $billing_system = '';
    $communities_csv = plugin_dir_path(__FILE__) . 'communities.csv';

    if (file_exists($communities_csv) && ($handle = fopen($communities_csv, 'r')) !== false) {
        $header = array_flip(fgetcsv($handle));
        while (($row = fgetcsv($handle)) !== false) {
            if ($row[$header['building_name']] === $building_name) {
                $billing_system = strtolower(str_replace(' ', '', trim($row[$header['billing_system']])));
                break;
            }
        }
        fclose($handle);
    }

    // Set URLs based on billing system
    $base_url = '';
    $urls = array();

    if ($billing_system === 'stratamax') {
        $urls = array(
            'move_in' => '', // Custom form needed
            'pay_bill' => $base_url . '/making-a-payment',
            'move_out' => '', // Custom form needed
            'direct_debit' => 'https://www.stratapay.com.au/directdebit/',
            'contact' => $base_url . '/contact'
        );
    } elseif ($billing_system === 'bluebilling') {
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
    // Get building name from URL parameter
    $building_name = isset($_GET['building']) ? sanitize_text_field($_GET['building']) : '';

    if (!$building_name) {
        return '<p>No building selected.</p>';
    }

    // Get building data from CSV
    $charges = array();
    $charges_csv = plugin_dir_path(__FILE__) . 'community_charges.csv';

    // Get charges from charges CSV
    if (file_exists($charges_csv) && ($handle = fopen($charges_csv, 'r')) !== false) {
        $header = array_flip(fgetcsv($handle));

        while (($row = fgetcsv($handle)) !== false) {
            if ($row[$header['building_name']] === $building_name) {
                $charges[] = array(
                    'utility' => isset($header['utility']) ? $row[$header['utility']] : '',
                    'charge_name' => isset($header['charge_name']) ? $row[$header['charge_name']] : '',
                    'charge_rate' => isset($header['charge_rate']) ? $row[$header['charge_rate']] : '',
                    'charge_rate_unit' => isset($header['charge_rate_unit']) ? $row[$header['charge_rate_unit']] : ''
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
