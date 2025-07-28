<?php
/**
 * Uninstall script for Hossam Intelligent Agent plugin
 */

// Prevent direct access
if (!defined('ABSPATH') || !defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Clean up plugin data on uninstall
 */
function hia_uninstall_cleanup() {
    // Remove plugin options
    delete_option('hia_backend_url');
    delete_option('hia_api_key');
    delete_option('hia_enable_chat');
    
    // Remove any custom database tables (if we had any)
    // global $wpdb;
    // $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}hia_conversations");
    
    // Clear any cached data
    wp_cache_flush();
    
    // Remove any custom user meta (if we had any)
    // delete_metadata('user', 0, 'hia_user_preference', '', true);
    
    // Log uninstall for debugging (optional)
    error_log('Hossam Intelligent Agent plugin uninstalled and cleaned up.');
}

// Run cleanup
hia_uninstall_cleanup();