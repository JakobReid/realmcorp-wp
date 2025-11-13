<?php
// Template for building hub hero section
// Variables available: $building_name, $postcode, $classification, $billing_system
?>
<div class="building-hub-hero">
    <div class="building-hub-hero-inner">
        <h1><?php echo esc_html($building_name); ?></h1>
        <div class="building-details">
            <?php if ($postcode): ?>
            <div class="detail-item">
                <span><i class="fas fa-map-marker-alt"></i> Postcode: <?php echo esc_html($postcode); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($classification): ?>
            <div class="detail-item">
                <span><i class="fas fa-building"></i> Type: <?php echo esc_html($classification); ?></span>
            </div>
            <?php endif; ?>
        </div>
        <?php if ($billing_system === 'bluebilling'): ?>
        <div style="margin-top: 2rem;">
            <a href="/customer-portal" class="wp-block-button__link bluebilling-portal-button">
                <i class="fas fa-lock"></i> Access BlueBilling Portal
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>