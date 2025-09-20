jQuery(document).ready(function($) {
    // When user clicks the Search button
    $('#realm_cl_search_btn').on('click', function() {
        let query = $('#realm_cl_search_query').val().trim();
        if (!query) {
            alert('Please enter a postcode.');
            return;
        }

        $.ajax({
            url: realmClAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'realm_cl_handle_search',
                query: query
            },
            success: function(response) {
                let selectContainer = $('#realm_cl_select_container');
                let pricingDetails = $('#realm_cl_pricing_details');
                let selectElement = $('#realm_cl_site_select');

                // Clear out old data
                selectContainer.hide();
                selectElement.empty().append('<option value="">-- Select Building --</option>');
                pricingDetails.empty();

                // If no matches
                if (!response || response.length === 0) {
                    pricingDetails.html('<p>No results found for postcode: <strong>' + query + '</strong></p>');
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

    // Build the table of data for a single matched row
    function displayTable(item) {
        let pricingDetails = $('#realm_cl_pricing_details');

        // We'll handle the Building Name/Postcode in the table header,
        // but if they're blank or 'NA', we skip or set them to 'N/A'.
        let buildingName = isSkip(item.building_name) ? 'N/A' : item.building_name;
        let buildingPost = isSkip(item.postcode)      ? ''    : item.postcode;

        // Start building the HTML
        let tableHtml = `
            <table style="border-collapse: collapse; width:100%; max-width:800px;">
                <thead>
                    <tr>
                        <th colspan="2" style="padding: 8px; border: 1px solid #ccc; background: #eee; font-size:1.2rem;">
                            Rates for ${buildingName} ${buildingPost ? '(Postcode: ' + buildingPost + ')' : ''}
                        </th>
                    </tr>
                </thead>
                <tbody>
        `;

        // Helper to add a row if not NA/blank
        function addRow(label, value) {
            if (!isSkip(value)) {
                tableHtml += `
                    <tr>
                        <td style="border:1px solid #ccc; padding:8px; font-weight:bold;">${label}</td>
                        <td style="border:1px solid #ccc; padding:8px;">${value}</td>
                    </tr>
                `;
            }
        }

        // Display building info
        addRow('Billing System', item.billing_system);
        addRow('Water Authority', item.water_authority);
        addRow('Classification', item.classification);

        // Display charges from the charges array
        if (item.charges && item.charges.length > 0) {
            // Add a separator row
            tableHtml += `
                <tr>
                    <td colspan="2" style="border:1px solid #ccc; padding:8px; background:#f5f5f5; font-weight:bold;">
                        Water Charges
                    </td>
                </tr>
            `;

            // Display each charge
            item.charges.forEach(function(charge) {
                if (!isSkip(charge.charge_rate)) {
                    let chargeLabel = charge.charge_name;
                    // Format the charge rate appropriately
                    let chargeValue = charge.charge_rate;

                    // Add the charge row
                    addRow(chargeLabel, chargeValue);
                }
            });
        }

        tableHtml += `
                </tbody>
            </table>
        `;
        pricingDetails.html(tableHtml);
    }
});
