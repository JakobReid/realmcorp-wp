<?php
// Template for building hub rates section
// Variables available: $building_name, $charges array

// Group charges by utility
$utilities = [];
foreach ($charges as $charge) {
    if (!empty(trim($charge['charge_rate'])) && strtoupper(trim($charge['charge_rate'])) !== 'NA') {
        $utility = !empty($charge['utility']) ? $charge['utility'] : 'Water';
        $utilities[$utility][] = $charge;
    }
}

// Sort utilities with Electricity first, then alphabetical
uksort($utilities, function($a, $b) {
    if ($a === 'Electricity') return -1;
    if ($b === 'Electricity') return 1;
    return strcmp($a, $b);
});
?>
<div class="building-rates-section">
    <h2 style="color:#015691; text-align: center; margin-bottom: 2rem;">Utility Rates for <?php echo esc_html($building_name); ?></h2>

    <?php foreach ($utilities as $utility_name => $utility_charges): ?>
    <div class="utility-rates-group">
        <h3 style="color:#015691; margin: 2rem 0 1rem 0; font-weight: 600;"><?php echo esc_html($utility_name); ?> Rates</h3>
        <div class="rates-table-container">
            <table class="rates-table">
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($utility_charges as $charge): ?>
                    <tr>
                        <td><?php echo esc_html($charge['charge_name']); ?></td>
                        <td>
                            <?php
                            echo esc_html($charge['charge_rate']);
                            if (!empty($charge['charge_rate_unit'])) {
                                echo ' ' . esc_html($charge['charge_rate_unit']);
                            }
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endforeach; ?>
</div>