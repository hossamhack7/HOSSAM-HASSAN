<?php
/**
 * Admin main page template.
 *
 * @package GeminiCommandCenter
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

?>
<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php esc_html_e('Gemini Command Center', 'gemini-command-center'); ?>
        <span class="title-count theme-count"><?php echo esc_html(GEMINI_CC_VERSION); ?></span>
    </h1>
    
    <hr class="wp-header-end">
    
    <div id="gcc-react-root"></div>
    
    <noscript>
        <div class="notice notice-error">
            <p><?php esc_html_e('Gemini Command Center requires JavaScript to be enabled. Please enable JavaScript in your browser to use this plugin.', 'gemini-command-center'); ?></p>
        </div>
    </noscript>
</div>