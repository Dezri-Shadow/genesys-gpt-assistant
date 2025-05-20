<?php
/**
 * Plugin Name: Genesys GPT Assistant
 * Description: Integrates a GPT-based tool into your site for Genesys RPG content.
 * Version: 1.1
 * Author: Ben Sellars
 */

defined('ABSPATH') || exit;

// Include components
require_once plugin_dir_path(__FILE__) . 'includes/admin-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/api-handler.php';

// Enqueue JS
function gga_enqueue_assets() {
    wp_enqueue_script('jquery'); // This ensures jQuery is loaded

    wp_enqueue_style('bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css');
    wp_enqueue_script('bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js', [], null, true);

    wp_enqueue_style('gga-style', plugin_dir_url(__FILE__) . 'assets/css/gga-style.css');
    wp_enqueue_script('gga-frontend', plugin_dir_url(__FILE__) . 'assets/js/frontend.js', ['jquery'], '1.1', true);

    wp_localize_script('gga-frontend', 'gga_data', [
      'ajax_url' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('wp_rest'), // Enables auth for REST API calls
      'api_url'  => esc_url_raw(rest_url('gga/v1/')),
    ]);

}
add_action('wp_enqueue_scripts', 'gga_enqueue_assets');

add_action('wp_footer', 'gga_render_modal');
function gga_render_modal() {
    ?>
    <div class="modal fade" id="gga-modal" tabindex="-1" aria-labelledby="ggaModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="ggaModalLabel">Generated NPC</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <pre id="gga-modal-content" class="mb-3"></pre> 
            <div id="gga-json-display"></div>
          </div>
          <div class="modal-footer justify-content-start">
            <a id="gga-download-json" class="btn btn-outline-secondary me-2" style="display:none;">Download JSON</a>
            <a id="gga-download-md" class="btn btn-outline-secondary" style="display:none;">Download Markdown</a>
            <button id="gga-save-npc" class="btn btn-success me-2" style="display:none;">Save NPC</button>
          </div>
        </div>
      </div>
    </div>
    <?php
}

add_shortcode('gga_saved_npcs', 'gga_saved_npcs_shortcode');
function gga_saved_npcs_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>You must be logged in to view your saved NPCs.</p>';
    }

    wp_enqueue_script('gga-frontend');
    wp_localize_script('gga-frontend', 'gga_data', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wp_rest')
        'api_url' => esc_url_raw(rest_url('gga/v1/'))
    ]);

    ob_start(); ?>
    <div id="gga-saved-npcs" class="mt-4">
        <h4>My Saved NPCs</h4>
        <ul id="gga-npc-link-list" class="list-unstyled"></ul>
    </div>

    <!-- Modal for rendering NPCs -->
    <div class="modal fade" id="gga-saved-npc-modal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="gga-modal-title">NPC Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" id="gga-saved-npc-display">
            <em>Loading...</em>
          </div>
        </div>
      </div>
    </div>
    <?php
    return ob_get_clean();
}

// Shortcode for frontend form
function gga_render_form() {
    ob_start();
    include plugin_dir_path(__FILE__) . 'templates/form-display.php';
    return ob_get_clean();
}
add_shortcode('genesys_gpt_form', 'gga_render_form');