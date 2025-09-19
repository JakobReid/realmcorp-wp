<?php
/*
Plugin Name: Realm Community Lookup
Description: Search community data by postcode, display building rates and community information with multi-site dropdown.
Version: 3.1
Author: Ryan Reid / Jakob Reid
License: GPL2
*/

// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// 1. Enqueue Scripts
function realm_cl_enqueue_scripts() {
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
add_action('wp_enqueue_scripts', 'realm_cl_enqueue_scripts');

// 2. Shortcode: [realm_community_lookup]
function realm_cl_display_search_form() {
    ob_start();
    ?>
    <!-- You can adjust styling here to match your desired look -->
    <div style="margin-bottom:1rem;">
        <label for="realm_cl_search_query" style="font-size:1.25rem; display:block; margin-bottom:0.5rem;">
            Enter Postcode:
        </label>
        <input
            type="text"
            id="realm_cl_search_query"
            maxlength="4"
            placeholder="e.g. 4207"
            style="padding: 0.5rem; margin-right: 0.5rem; font-size:1.25rem; width:120px;"
        />
        <button
            id="realm_cl_search_btn"
            style="
                padding: 0.5rem 1rem;
                font-size: 1.25rem;
                background-color:#0073AA;
                color:#fff;
                border:none;
                border-radius:4px;
                cursor:pointer;
            "
        >
            Search
        </button>
    </div>

    <div id="realm_cl_select_container" style="display:none; margin-bottom:1rem;">
        <label for="realm_cl_site_select" style="font-size:1.25rem; display:block; margin-bottom:0.5rem;">
            Select a Building:
        </label>
        <select
            id="realm_cl_site_select"
            style="padding: 0.5rem; font-size:1.25rem; width:250px;"
        >
            <option value="">-- Select Building --</option>
        </select>
    </div>

    <div id="realm_cl_pricing_details"></div>
    <?php
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

    // CSV path in the same folder as this plugin file
    $csv_file = plugin_dir_path(__FILE__) . 'data.csv';

    if ( file_exists($csv_file) && ($handle = fopen($csv_file, 'r')) !== false ) {
        // If your CSV has a header row, uncomment to skip it:
        $header = fgetcsv($handle);

        // Loop through each row of the CSV
        while (($row = fgetcsv($handle)) !== false) {
            // Adjust indices based on your CSV structure
            // row[2] is assumed to be the Postcode
            if (strcasecmp(trim($row[2]), trim($query)) === 0) {
                $results[] = array(
                    'building_name'  => $row[0],
                    'building_code'  => $row[1],
                    'postcode'       => $row[2],
                    'water_authority'=> $row[3],
                    'classification' => $row[4],
                    'water_usage'    => $row[5],
                    'tier2_usage'    => $row[6],
                    'waste_water_usage' => $row[7],
                    'waste_water_percent' => $row[8],
                    'water_access_day'   => $row[9],
                    'waste_water_access_day' => $row[10],
                    'state_bulk_water_charge' => $row[11],
                    'service_fee_day'         => $row[12],
                    'service_fee_owners'      => $row[13],
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
