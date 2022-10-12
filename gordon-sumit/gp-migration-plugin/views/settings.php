<div class="wrap">
    <h1>Migration Settings</h1>
    <?php settings_errors(); ?>

    <div class="option-page-wrap">
        <form action="<?php echo admin_url('options.php') ?>" method="post" id="gpm-form" novalidate>
            <?php
            //wp inbuilt nonce field , etc
            settings_fields($option_group);
            ?>

            <table class="form-table">
                <tr>
                    <th scope="row">Driver Name*</th>
                    <td>
                        <input type="text"
                               class="regular-text"
                               name="gpm_options[driver]"
                               value="<?php echo esc_attr($db['driver']); ?>">
                    </td>
                </tr>
                <tr>
                    <th scope="row">Host Name*</th>
                    <td>
                        <input type="text"
                               class="regular-text"
                               name="gpm_options[host]"
                               value="<?php echo esc_attr($db['host']); ?>">
                    </td>
                </tr>
                <tr>
                    <th scope="row">Port*</th>
                    <td>
                        <input type="text"
                               class="regular-text"
                               name="gpm_options[port]"
                               value="<?php echo esc_attr($db['port']); ?>">
                    </td>
                </tr>
                <tr>
                    <th scope="row">Database Name*</th>
                    <td>
                        <input type="text"
                               class="regular-text"
                               name="gpm_options[database]"
                               value="<?php echo esc_attr($db['database']); ?>">
                    </td>
                </tr>
                <tr>
                    <th scope="row">Database Username*</th>
                    <td>
                        <input type="text"
                               class="regular-text"
                               name="gpm_options[username]"
                               value="<?php echo esc_attr($db['username']); ?>">
                    </td>
                </tr>
                <tr>
                    <th scope="row">Database Password</th>
                    <td>
                        <input type="password"
                               class="regular-text"
                               name="gpm_options[password]"
                               value="<?php echo esc_attr($db['password']); ?>">
                    </td>
                </tr>
            </table>
            <?php submit_button() ?>
        </form>
    </div>
</div>
