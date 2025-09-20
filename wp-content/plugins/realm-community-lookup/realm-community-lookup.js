jQuery(document).ready(function($) {
    // When user clicks the Search button
    $('#realm_cl_search_btn').on('click', function() {
        let query = $('#realm_cl_search_query').val().trim();
        if (!query) {
            alert('Please enter a postcode.');
            return;
        }

        // Show loading state
        let loadingContainer = $('#realm_cl_loading');
        let searchButton = $('#realm_cl_search_btn');
        let selectContainer = $('#realm_cl_select_container');
        let pricingDetails = $('#realm_cl_pricing_details');

        // Clear previous results and show loading
        selectContainer.hide();
        pricingDetails.empty();
        loadingContainer.show();
        searchButton.addClass('loading');

        $.ajax({
            url: realmClAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'realm_cl_handle_search',
                query: query
            },
            success: function(response) {
                let selectElement = $('#realm_cl_site_select');

                // Hide loading and remove button loading state
                loadingContainer.hide();
                searchButton.removeClass('loading');

                // Clear out old data
                selectElement.empty().append('<option value="">-- Select Building --</option>');

                // If no matches
                if (!response || response.length === 0) {
                    // Use template if available, fallback to simple HTML
                    let noResultsTemplate = $('#realm-no-results-template').html();
                    if (noResultsTemplate) {
                        let html = noResultsTemplate.replace('{{postcode}}', query);
                        pricingDetails.html(html);
                    } else {
                        pricingDetails.html('<div class="realm-result-container"><p class="realm-result-text">No results found for postcode: <strong>' + query + '</strong></p></div>');
                    }
                    return;
                }

                // If exactly ONE match, display it directly
                if (response.length === 1) {
                    displayTable(response[0]);
                }
                // If MULTIPLE matches, show the dropdown
                else {
                    selectContainer.show();
                    // Save data for use after user picks from dropdown
                    window.realmClSiteData = response;

                    // Fill the <select> with building names
                    response.forEach(function(item, index) {
                        let bldName = item.building_name || ('Building ' + (index + 1));
                        selectElement.append('<option value="' + index + '">' + bldName + '</option>');
                    });
                }
            },
            error: function() {
                // Hide loading and remove button loading state
                loadingContainer.hide();
                searchButton.removeClass('loading');
                alert('An error occurred while searching.');
            }
        });
    });

    // When user picks a building from the dropdown
    $('#realm_cl_site_select').on('change', function() {
        let index = $(this).val();
        if (index !== '') {
            let item = window.realmClSiteData[index];
            displayTable(item);
        }
    });

    // Helper function: returns true if value is empty or 'NA' (case-insensitive).
    function isSkip(val) {
        if (!val || !val.trim()) return true; // skip if blank
        return val.trim().toUpperCase() === 'NA';
    }

    // Build the button for a single matched building
    function displayTable(item) {
        let pricingDetails = $('#realm_cl_pricing_details');

        let buildingName = isSkip(item.building_name) ? 'N/A' : item.building_name;

        // Try to use template first, fallback to direct HTML
        let resultTemplate = $('#realm-result-template').html();
        let hubButton;

        if (resultTemplate) {
            // Use template and replace placeholders
            hubButton = resultTemplate
                .replace(/{{buildingName}}/g, buildingName)
                .replace('{{buildingCode}}', item.building_code);
        } else {
            // Fallback to direct HTML if template not loaded
            hubButton = `
                <div class="realm-result-container">
                    <p class="realm-result-text">
                        Building found: <strong>${buildingName}</strong>
                    </p>
                    <button id="realm_cl_hub_btn" data-building-code="${item.building_code}" class="realm-hub-button">
                        Continue
                        <span class="realm-hub-button-arrow">→</span>
                    </button>
                </div>
            `;
        }

        pricingDetails.html(hubButton);

        // Handle hub button click
        $('#realm_cl_hub_btn').on('click', function() {
            let buildingCode = $(this).data('building-code');
            // For now, just placeholder - will be replaced with actual hub page
            window.location.href = '/staging/5684/building-hub/?building=' + encodeURIComponent(buildingCode);
        });
    }
});
