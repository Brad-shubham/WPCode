<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       #
 * @since      1.0.0
 *
 * @package    Woo_Google_Syc
 * @subpackage Woo_Google_Syc/admin/partials
 */

$authUrl = !empty($this->auth_link) ? $this->auth_link : '';
if (get_option('wc_settings_tab_client_oauth')) {
    if ($authUrl) {
        echo '<div style="text-align: center;"><a href="' . $authUrl . '" class="button-secondary">Login With Google</a></div>';
    } else {
        echo '<div class="woo-gc-syc-main">';
        ?>
        <div class="sync-opt" style="padding: 8px;">
            <label for="sync-all-opt">All
                <input type="radio" data-opt=".sync-all" name="sync-opt" value="all" id="sync-all"
                       checked>
            </label>
            <label for="sync-range-opt">Range
                <input type="radio" data-opt=".sync-range" name="sync-opt" value="range"
                       id="sync-range-opt">
            </label>
            <label for="sync-specific-opt">Specific
                <input type="radio" data-opt=".sync-specific" name="sync-opt"
                       value="specific" id="sybc-specific-opt">
            </label>

            <div class="sync-elem sync-range" style="padding: 15px;">
                <label for="fromClm">From: <input type="text" id="fromClm"></label>
                <label for="toClm">To: <input type="text" id="toClm"></label>
            </div>

            <div class="sync-elem sync-specific" style="padding: 15px;">
                <label for="syncSpec">Random: <input type="text" id="syncSpec"></label>
            </div>
        </div>
        <button class="button sync-btn btn-tooltip" tabindex="1" data-toggle="tooltip"
                data-tip="On clicking this button all products will be added/updated in the google spreadsheet."
                data-nonce="<?php echo wp_create_nonce('syncGoogleSheet_nonce') ?>"
                data-action="syncGoogleSheet" id="sync-sheet">Sync Woocommerce to Google Sheet
        </button>
        <button class="button sync-btn btn-tooltip" tabindex="1" data-toggle="tooltip"
                data-tip="On clicking this button all products will be added/updated in the woocommerce."
                data-nonce="<?php echo wp_create_nonce('syncWooCommerce_nonce') ?>"
                data-action="syncWoocommerce" id="sync-wc">Sync Google Sheet to Woocommerce
        </button>
        <?php

        echo '<div id="wcgs_progressbar">';
        printf(__('<div class="pb-run">%s</div>', 'woogsyc'), 'Please wait...');
        echo '</div>';
        echo '<div id="response-error" class="response-error">';
        echo '</div>';
    }
}
?>


