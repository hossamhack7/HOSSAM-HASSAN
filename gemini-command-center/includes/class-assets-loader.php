<?php
/**
 * Assets Loader class.
 * Responsible for enqueuing scripts and styles.
 *
 * @package GeminiCommandCenter
 */

class Gemini_CC_Assets_Loader {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
    }

    /**
     * Enqueue admin assets.
     *
     * @param string $hook_suffix The current admin page.
     */
    public function enqueue_admin_assets($hook_suffix) {
        // Only load on our plugin pages
        if (!$this->is_gemini_cc_page($hook_suffix)) {
            return;
        }

        // Enqueue React app
        $this->enqueue_react_app();

        // Enqueue admin styles
        wp_enqueue_style(
            'gemini-cc-admin-styles',
            GEMINI_CC_ASSETS_URL . 'css/admin.css',
            array(),
            GEMINI_CC_VERSION
        );

        // Enqueue admin scripts
        wp_enqueue_script(
            'gemini-cc-admin-scripts',
            GEMINI_CC_ASSETS_URL . 'js/admin.js',
            array('jquery'),
            GEMINI_CC_VERSION,
            true
        );

        // Localize script with admin data
        wp_localize_script('gemini-cc-admin-scripts', 'geminiCCAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('gemini-cc/v1/'),
            'nonce' => wp_create_nonce('gemini_cc_admin_nonce'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'currentUser' => wp_get_current_user(),
            'strings' => array(
                'loading' => __('Loading...', 'gemini-command-center'),
                'error' => __('An error occurred.', 'gemini-command-center'),
                'success' => __('Operation completed successfully.', 'gemini-command-center'),
                'confirm' => __('Are you sure?', 'gemini-command-center'),
                'confirmRestore' => __('Are you sure you want to restore this backup? This action cannot be undone.', 'gemini-command-center'),
            ),
        ));
    }

    /**
     * Enqueue React application.
     */
    private function enqueue_react_app() {
        // Check if build files exist
        $js_file = GEMINI_CC_PLUGIN_DIR . 'build/static/js/main.js';
        $css_file = GEMINI_CC_PLUGIN_DIR . 'build/static/css/main.css';

        if (file_exists($js_file)) {
            // Get the actual filename from the build directory
            $build_files = $this->get_build_files();
            
            if (!empty($build_files['js'])) {
                wp_enqueue_script(
                    'gemini-cc-react-app',
                    GEMINI_CC_BUILD_URL . 'static/js/' . $build_files['js'],
                    array(),
                    GEMINI_CC_VERSION,
                    true
                );
            }

            if (!empty($build_files['css'])) {
                wp_enqueue_style(
                    'gemini-cc-react-app-styles',
                    GEMINI_CC_BUILD_URL . 'static/css/' . $build_files['css'],
                    array(),
                    GEMINI_CC_VERSION
                );
            }
        } else {
            // Development mode - load from src
            $this->enqueue_development_assets();
        }
    }

    /**
     * Get build files from the manifest.
     */
    private function get_build_files() {
        $manifest_file = GEMINI_CC_PLUGIN_DIR . 'build/asset-manifest.json';
        
        if (!file_exists($manifest_file)) {
            return array();
        }

        $manifest = json_decode(file_get_contents($manifest_file), true);
        
        if (!$manifest) {
            return array();
        }

        $files = array();
        
        // Extract main JS and CSS files
        if (isset($manifest['files']['main.js'])) {
            $files['js'] = basename($manifest['files']['main.js']);
        }
        
        if (isset($manifest['files']['main.css'])) {
            $files['css'] = basename($manifest['files']['main.css']);
        }

        return $files;
    }

    /**
     * Enqueue development assets.
     */
    private function enqueue_development_assets() {
        // In development, we might want to load from a dev server
        // For now, just enqueue a placeholder
        wp_enqueue_script(
            'gemini-cc-react-dev',
            GEMINI_CC_ASSETS_URL . 'js/react-app.js',
            array('wp-element'),
            GEMINI_CC_VERSION,
            true
        );
    }

    /**
     * Enqueue public assets.
     */
    public function enqueue_public_assets() {
        // Only enqueue if UI/UX module is enabled
        $settings = get_option('gemini_cc_settings', array());
        
        if (!empty($settings['uiux']['enabled'])) {
            wp_enqueue_style(
                'gemini-cc-public-styles',
                GEMINI_CC_ASSETS_URL . 'css/public.css',
                array(),
                GEMINI_CC_VERSION
            );
        }

        // A/B testing script
        if (!empty($settings['content']['ab_testing']['enabled'])) {
            wp_enqueue_script(
                'gemini-cc-ab-testing',
                GEMINI_CC_ASSETS_URL . 'js/ab-testing.js',
                array('jquery'),
                GEMINI_CC_VERSION,
                true
            );

            wp_localize_script('gemini-cc-ab-testing', 'geminiCCAB', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('gemini_cc_ab_nonce'),
            ));
        }
    }

    /**
     * Check if current page is a Gemini CC page.
     *
     * @param string $hook_suffix The current admin page.
     * @return bool
     */
    private function is_gemini_cc_page($hook_suffix) {
        $gemini_pages = array(
            'toplevel_page_gemini-command-center',
            'gemini-command-center_page_gemini-ai-agent',
            'gemini-command-center_page_gemini-settings',
        );

        return in_array($hook_suffix, $gemini_pages, true);
    }

    /**
     * Get inline styles for UI/UX customizations.
     *
     * @return string
     */
    public function get_inline_styles() {
        $settings = get_option('gemini_cc_settings', array());
        $styles = '';

        if (!empty($settings['uiux']['colors'])) {
            $colors = $settings['uiux']['colors'];
            
            if (!empty($colors['primary'])) {
                $styles .= ':root { --gcc-primary-color: ' . esc_attr($colors['primary']) . '; }';
            }
            
            if (!empty($colors['secondary'])) {
                $styles .= ':root { --gcc-secondary-color: ' . esc_attr($colors['secondary']) . '; }';
            }
        }

        if (!empty($settings['uiux']['fonts'])) {
            $fonts = $settings['uiux']['fonts'];
            
            if (!empty($fonts['heading'])) {
                $styles .= 'h1, h2, h3, h4, h5, h6 { font-family: "' . esc_attr($fonts['heading']) . '", sans-serif; }';
            }
            
            if (!empty($fonts['body'])) {
                $styles .= 'body { font-family: "' . esc_attr($fonts['body']) . '", sans-serif; }';
            }
        }

        return $styles;
    }
}