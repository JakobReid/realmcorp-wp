<?php
/**
 * Template for the community search form and result templates
 *
 * @package Realm_Community_Lookup
 * @version 4.2
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<!-- Community Search Form -->
<div class="realm-search-wrapper">
    <div class="realm-search-input-group">
        <input
            type="text"
            id="realm_cl_search_query"
            class="realm-search-input"
            maxlength="4"
            placeholder="Enter your postcode..."
            autocomplete="postal-code"
        />
        <button id="realm_cl_search_btn" class="realm-search-button">
            Search
        </button>
    </div>
</div>

<!-- Building Select (Hidden by default) -->
<div id="realm_cl_select_container" class="realm-select-container">
    <label for="realm_cl_site_select" class="realm-search-label">
        Select your building:
    </label>
    <select id="realm_cl_site_select" class="realm-building-select">
        <option value="">Choose your building...</option>
    </select>
</div>

<!-- Loading Container -->
<div id="realm_cl_loading" class="realm-loading">
    <div class="realm-spinner"></div>
    <div class="realm-loading-text">Searching buildings...</div>
</div>

<!-- Results Container -->
<div id="realm_cl_pricing_details"></div>

<!-- JavaScript Templates for Results -->
<!-- Template for single building result -->
<script type="text/template" id="realm-result-template">
    <div class="realm-result-container">
        <p class="realm-result-text">
            Building found: <strong>{{buildingName}}</strong>
        </p>
        <button
            id="realm_cl_hub_btn"
            data-building-code="{{buildingCode}}"
            class="realm-hub-button"
        >
            Continue
            <span class="realm-hub-button-arrow">→</span>
        </button>
    </div>
</script>

<!-- Template for no results -->
<script type="text/template" id="realm-no-results-template">
    <div class="realm-result-container">
        <p class="realm-result-text realm-no-results">
            No results found for postcode: <strong>{{postcode}}</strong>
        </p>
    </div>
</script>