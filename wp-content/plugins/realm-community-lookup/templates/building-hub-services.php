<?php
// Template for building hub services section
// Variables available: $urls array with service URLs
?>
<div class="building-services-section">
    <h2 class="building-services-title">Building Services</h2>
    <p class="building-services-subtitle">Select a service to manage your account</p>

    <!-- First row of services -->
    <div class="wp-block-columns">
        <div class="wp-block-column">
            <div class="wp-block-group service-card">
                <?php if (!empty($urls['move_in'])): ?>
                    <a href="<?php echo esc_url($urls['move_in']); ?>" style="text-decoration: none; color: inherit;">
                <?php else: ?>
                    <div style="opacity: 0.5; cursor: not-allowed;">
                <?php endif; ?>
                    <div class="service-icon"><i class="fas fa-home"></i></div>
                    <h3>Move In</h3>
                    <p>Start your utility services and set up your account for your new home</p>
                <?php if (!empty($urls['move_in'])): ?>
                    </a>
                <?php else: ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="wp-block-column">
            <div class="wp-block-group service-card">
                <?php if (!empty($urls['pay_bill'])): ?>
                    <a href="<?php echo esc_url($urls['pay_bill']); ?>" style="text-decoration: none; color: inherit;">
                <?php else: ?>
                    <div style="opacity: 0.5; cursor: not-allowed;">
                <?php endif; ?>
                    <div class="service-icon"><i class="fas fa-credit-card"></i></div>
                    <h3>Pay a Bill</h3>
                    <p>Make a payment through your secure payment system</p>
                <?php if (!empty($urls['pay_bill'])): ?>
                    </a>
                <?php else: ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="wp-block-column">
            <div class="wp-block-group service-card">
                <?php if (!empty($urls['move_out'])): ?>
                    <a href="<?php echo esc_url($urls['move_out']); ?>" style="text-decoration: none; color: inherit;">
                <?php else: ?>
                    <div style="opacity: 0.5; cursor: not-allowed;">
                <?php endif; ?>
                    <div class="service-icon"><i class="fas fa-box"></i></div>
                    <h3>Move Out</h3>
                    <p>Close your account and finalize your utility services</p>
                <?php if (!empty($urls['move_out'])): ?>
                    </a>
                <?php else: ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Second row of services -->
    <div class="wp-block-columns">
        <div class="wp-block-column">
            <div class="wp-block-group service-card">
                <?php if (!empty($urls['direct_debit'])): ?>
                    <a href="<?php echo esc_url($urls['direct_debit']); ?>" style="text-decoration: none; color: inherit;">
                <?php else: ?>
                    <div style="opacity: 0.5; cursor: not-allowed;">
                <?php endif; ?>
                    <div class="service-icon"><i class="fas fa-university"></i></div>
                    <h3>Direct Debit</h3>
                    <p>Set up automatic payments for hassle-free bill management</p>
                <?php if (!empty($urls['direct_debit'])): ?>
                    </a>
                <?php else: ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="wp-block-column">
            <div class="wp-block-group service-card">
                <a href="<?php echo esc_url($urls['contact']); ?>" style="text-decoration: none; color: inherit;">
                    <div class="service-icon"><i class="fas fa-phone"></i></div>
                    <h3>Contact Support</h3>
                    <p>Get help with your account or report an issue</p>
                </a>
            </div>
        </div>

        <div class="wp-block-column">
            <!-- Empty column for spacing -->
        </div>
    </div>
</div>