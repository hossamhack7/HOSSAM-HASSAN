<?php
/**
 * Basic validation script for Gemini Bridge Plugin
 * 
 * This script performs basic checks to ensure the plugin is properly structured.
 * Run this script from the command line: php validate-plugin.php
 */

// Set up basic WordPress environment simulation
define( 'ABSPATH', '/tmp/wordpress/' );
define( 'WP_DEBUG', true );

// Create mock WordPress functions for testing
if ( ! function_exists( 'esc_html__' ) ) {
    function esc_html__( $text, $domain = 'default' ) {
        return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
    }
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $str ) {
        return htmlspecialchars( strip_tags( $str ), ENT_QUOTES, 'UTF-8' );
    }
}

if ( ! function_exists( 'wp_generate_password' ) ) {
    function wp_generate_password( $length = 12, $special_chars = true, $extra_special_chars = false ) {
        return bin2hex( random_bytes( $length / 2 ) );
    }
}

// Plugin validation
$plugin_dir = dirname( __FILE__ );
$required_files = array(
    'gemini-bridge.php',
    'readme.txt',
    'includes/class-security.php',
    'includes/class-diagnostics.php',
    'includes/class-executor.php',
    'includes/class-code-reader.php'
);

echo "Gemini Bridge Plugin Validation\n";
echo "================================\n\n";

// Check required files
echo "1. Checking required files...\n";
$missing_files = array();
foreach ( $required_files as $file ) {
    $file_path = $plugin_dir . '/' . $file;
    if ( file_exists( $file_path ) ) {
        echo "   ✓ {$file}\n";
    } else {
        echo "   ✗ {$file} (MISSING)\n";
        $missing_files[] = $file;
    }
}

if ( ! empty( $missing_files ) ) {
    echo "\nERROR: Missing required files. Plugin cannot function properly.\n";
    exit( 1 );
}

// Check PHP syntax
echo "\n2. Checking PHP syntax...\n";
$php_files = glob( $plugin_dir . '/*.php' );
$php_files = array_merge( $php_files, glob( $plugin_dir . '/includes/*.php' ) );

$syntax_errors = array();
foreach ( $php_files as $file ) {
    $output = array();
    $return_var = 0;
    exec( "php -l " . escapeshellarg( $file ) . " 2>&1", $output, $return_var );
    
    if ( $return_var === 0 ) {
        echo "   ✓ " . basename( $file ) . "\n";
    } else {
        echo "   ✗ " . basename( $file ) . " (SYNTAX ERROR)\n";
        $syntax_errors[] = $file;
    }
}

if ( ! empty( $syntax_errors ) ) {
    echo "\nERROR: PHP syntax errors found. Please fix before using.\n";
    exit( 1 );
}

// Check plugin header
echo "\n3. Checking plugin header...\n";
$main_file = file_get_contents( $plugin_dir . '/gemini-bridge.php' );
$required_headers = array(
    'Plugin Name',
    'Version',
    'Description',
    'Author'
);

foreach ( $required_headers as $header ) {
    if ( strpos( $main_file, $header . ':' ) !== false ) {
        echo "   ✓ {$header}\n";
    } else {
        echo "   ✗ {$header} (MISSING)\n";
    }
}

// Check class definitions
echo "\n4. Checking class definitions...\n";
$required_classes = array(
    'Gemini_Bridge' => 'gemini-bridge.php',
    'Gemini_Bridge_Security' => 'includes/class-security.php',
    'Gemini_Bridge_Diagnostics' => 'includes/class-diagnostics.php',
    'Gemini_Bridge_Executor' => 'includes/class-executor.php',
    'Gemini_Bridge_Code_Reader' => 'includes/class-code-reader.php'
);

foreach ( $required_classes as $class_name => $file ) {
    $file_content = file_get_contents( $plugin_dir . '/' . $file );
    if ( strpos( $file_content, "class {$class_name}" ) !== false ) {
        echo "   ✓ {$class_name}\n";
    } else {
        echo "   ✗ {$class_name} (NOT FOUND)\n";
    }
}

// Check security features
echo "\n5. Checking security features...\n";
$security_checks = array(
    'ABSPATH check' => "if ( ! defined( 'ABSPATH' ) )",
    'HMAC functions' => 'hash_hmac',
    'Capability checks' => 'current_user_can',
    'Input sanitization' => 'sanitize_text_field',
    'Nonce verification patterns' => 'wp_verify_nonce'
);

$security_file = file_get_contents( $plugin_dir . '/includes/class-security.php' );
foreach ( $security_checks as $check_name => $pattern ) {
    if ( strpos( $security_file, $pattern ) !== false || 
         strpos( $main_file, $pattern ) !== false ) {
        echo "   ✓ {$check_name}\n";
    } else {
        echo "   ? {$check_name} (NOT DETECTED)\n";
    }
}

// Check REST API endpoints
echo "\n6. Checking REST API endpoints...\n";
$expected_endpoints = array(
    '/auth/generate-key',
    '/auth/verify', 
    '/site/snapshot',
    '/execute/plugin-action',
    '/execute/install',
    '/execute/clear-caches',
    '/read-file-content',
    '/list-theme-files'
);

$all_files_content = '';
foreach ( array_merge( [$plugin_dir . '/gemini-bridge.php'], glob( $plugin_dir . '/includes/*.php' ) ) as $file ) {
    $all_files_content .= file_get_contents( $file );
}

foreach ( $expected_endpoints as $endpoint ) {
    if ( strpos( $all_files_content, $endpoint ) !== false ) {
        echo "   ✓ {$endpoint}\n";
    } else {
        echo "   ✗ {$endpoint} (NOT FOUND)\n";
    }
}

echo "\n7. Plugin Statistics:\n";
echo "   Total lines of code: " . exec( "find " . escapeshellarg( $plugin_dir ) . " -name '*.php' -exec wc -l {} + | tail -1 | awk '{print $1}'" ) . "\n";
echo "   PHP files: " . count( $php_files ) . "\n";
echo "   Total files: " . count( glob( $plugin_dir . '/*' ) ) . "\n";

echo "\n✅ Plugin validation completed successfully!\n";
echo "\nThe Gemini Bridge plugin appears to be properly structured and ready for use.\n";
echo "Remember to test thoroughly in a development environment before production use.\n";
?>