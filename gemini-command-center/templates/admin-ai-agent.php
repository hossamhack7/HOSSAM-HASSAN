<?php
/**
 * Admin AI Agent page template.
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
        <?php esc_html_e('AI Agent', 'gemini-command-center'); ?>
    </h1>
    
    <hr class="wp-header-end">
    
    <div id="gcc-ai-agent-root"></div>
    
    <noscript>
        <div class="notice notice-error">
            <p><?php esc_html_e('The AI Agent requires JavaScript to be enabled. Please enable JavaScript in your browser.', 'gemini-command-center'); ?></p>
        </div>
    </noscript>
</div>