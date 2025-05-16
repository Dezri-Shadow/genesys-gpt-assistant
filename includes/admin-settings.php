<?php
// Admin settings page for API key
add_action('admin_menu', function() {
    add_options_page('Genesys GPT Settings', 'Genesys GPT', 'manage_options', 'genesys-gpt', 'gga_render_settings_page');
});

add_action('admin_init', function() {
    register_setting('gga_settings_group', 'gga_openai_api_key');
});

function gga_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>Genesys GPT Settings</h1>
        <form method="post" action="options.php">
            <?php
                settings_fields('gga_settings_group');
                do_settings_sections('gga_settings_group');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">OpenAI API Key</th>
                    <td><input type="text" name="gga_openai_api_key" value="<?php echo esc_attr(get_option('gga_openai_api_key')); ?>" style="width: 400px;" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>

        <?php
        if (current_user_can('manage_options')) {
            echo '<hr>';
            echo '<h2>Debug Info</h2>';
            echo '<p>This section displays internal diagnostics for the Genesys GPT Assistant plugin.</p>';

            $data = gga_debug_endpoint();
            echo '<pre>' . esc_html(print_r($data, true)) . '</pre>';
            
        }
        ?>
    </div>
    <?php
}
