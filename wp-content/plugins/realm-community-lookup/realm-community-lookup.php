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

    // CSV paths in the same folder as this plugin file
    $communities_csv = plugin_dir_path(__FILE__) . 'communities.csv';
    $charges_csv = plugin_dir_path(__FILE__) . 'community_charges.csv';

    // First, read all charges data into memory for quick lookup
    $charges_by_building = array();
    if ( file_exists($charges_csv) && ($charges_handle = fopen($charges_csv, 'r')) !== false ) {
        // Skip header row
        $charges_header = fgetcsv($charges_handle);

        // Read all charges data
        while (($charge_row = fgetcsv($charges_handle)) !== false) {
            $building_name = $charge_row[0];
            if (!isset($charges_by_building[$building_name])) {
                $charges_by_building[$building_name] = array();
            }
            $charges_by_building[$building_name][] = array(
                'utility' => $charge_row[1],
                'charge_name' => $charge_row[2],
                'charge_rate' => $charge_row[3]
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
            fgetcsv($handle); // Skip header
            while (($row = fgetcsv($handle)) !== false) {
                if ($row[2] === $building_code) { // building_code is column 2
                    $building_name = $row[0] . ' Hub'; // building_name is column 0
                    $billing_system = $row[1]; // billing_system is column 1
                    $postcode = $row[3]; // postcode is column 3
                    $classification = $row[5]; // classification is column 5
                    break;
                }
            }
            fclose($handle);
        }
    }

    ob_start();
    ?>
    <div class="building-hub-hero">
        <h1><?php echo esc_html($building_name); ?></h1>
        <div class="building-details">
            <?php if ($postcode): ?>
            <div class="detail-item">
                <span>📍 Postcode: <?php echo esc_html($postcode); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($classification): ?>
            <div class="detail-item">
                <span>🏢 Type: <?php echo esc_html($classification); ?></span>
            </div>
            <?php endif; ?>
        </div>
        <?php if ($billing_system === 'BlueBilling'): ?>
        <div style="margin-top: 2rem;">
            <a href="#portal" class="wp-block-button__link" style="
                background-color: #015691 !important;
                color: white !important;
                padding: 0.75rem 2rem !important;
                border-radius: 4px;
                text-decoration: none;
                display: inline-block;
                font-weight: bold;
                transition: all 0.3s;
            ">
                🔐 Access Customer Portal
            </a>
        </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('building_hub_hero', 'realm_building_hub_hero_display');
