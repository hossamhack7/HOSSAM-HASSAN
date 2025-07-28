<?php
/**
 * Backup Manager class.
 * Handles backup creation, restoration, and management.
 *
 * @package GeminiCommandCenter
 */

class Gemini_CC_Backup_Manager {

    /**
     * Backup directory path.
     */
    private $backup_dir;

    /**
     * Constructor.
     */
    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->backup_dir = $upload_dir['basedir'] . '/gemini-cc-backups';
        
        // Ensure backup directory exists
        $this->ensure_backup_directory();
    }

    /**
     * Create a backup.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response
     */
    public function create_backup($request) {
        $type = $request->get_param('type');
        
        if (!in_array($type, array('database', 'full'), true)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => __('Invalid backup type.', 'gemini-command-center'),
            ), 400);
        }

        try {
            $backup_info = $this->perform_backup($type);
            
            // Log backup creation
            $this->log_backup_action('created', $backup_info);
            
            return new WP_REST_Response(array(
                'success' => true,
                'message' => __('Backup created successfully.', 'gemini-command-center'),
                'backup' => $backup_info,
            ), 200);
            
        } catch (Exception $e) {
            $this->log_backup_error('create', $e->getMessage());
            
            return new WP_REST_Response(array(
                'success' => false,
                'message' => sprintf(
                    __('Backup creation failed: %s', 'gemini-command-center'),
                    $e->getMessage()
                ),
            ), 500);
        }
    }

    /**
     * List available backups.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response
     */
    public function list_backups($request) {
        try {
            $backups = $this->get_backup_list();
            
            return new WP_REST_Response(array(
                'success' => true,
                'backups' => $backups,
            ), 200);
            
        } catch (Exception $e) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => sprintf(
                    __('Failed to list backups: %s', 'gemini-command-center'),
                    $e->getMessage()
                ),
            ), 500);
        }
    }

    /**
     * Download a backup.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response
     */
    public function download_backup($request) {
        $filename = $request->get_param('filename');
        $filepath = $this->backup_dir . '/' . $filename;
        
        if (!$this->is_valid_backup_file($filename) || !file_exists($filepath)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => __('Backup file not found.', 'gemini-command-center'),
            ), 404);
        }

        try {
            $this->log_backup_action('downloaded', array('filename' => $filename));
            
            return new WP_REST_Response(array(
                'success' => true,
                'download_url' => $this->get_secure_download_url($filename),
            ), 200);
            
        } catch (Exception $e) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => sprintf(
                    __('Download preparation failed: %s', 'gemini-command-center'),
                    $e->getMessage()
                ),
            ), 500);
        }
    }

    /**
     * Restore a backup.
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response
     */
    public function restore_backup($request) {
        $filename = $request->get_param('filename');
        $filepath = $this->backup_dir . '/' . $filename;
        
        if (!$this->is_valid_backup_file($filename) || !file_exists($filepath)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => __('Backup file not found.', 'gemini-command-center'),
            ), 404);
        }

        try {
            $restore_info = $this->perform_restore($filepath);
            
            // Log restore action
            $this->log_backup_action('restored', array(
                'filename' => $filename,
                'restore_info' => $restore_info,
            ));
            
            return new WP_REST_Response(array(
                'success' => true,
                'message' => __('Backup restored successfully.', 'gemini-command-center'),
                'restore_info' => $restore_info,
            ), 200);
            
        } catch (Exception $e) {
            $this->log_backup_error('restore', $e->getMessage());
            
            return new WP_REST_Response(array(
                'success' => false,
                'message' => sprintf(
                    __('Restore failed: %s', 'gemini-command-center'),
                    $e->getMessage()
                ),
            ), 500);
        }
    }

    /**
     * Perform the actual backup.
     *
     * @param string $type Backup type ('database' or 'full').
     * @return array Backup information.
     */
    private function perform_backup($type) {
        $timestamp = current_time('timestamp');
        $date_string = date('Y-m-d_H-i-s', $timestamp);
        $filename = "gemini-cc-backup_{$type}_{$date_string}.zip";
        $filepath = $this->backup_dir . '/' . $filename;
        
        $zip = new ZipArchive();
        if ($zip->open($filepath, ZipArchive::CREATE) !== TRUE) {
            throw new Exception(__('Unable to create backup file.', 'gemini-command-center'));
        }

        // Always backup database
        $this->backup_database($zip);
        
        // Backup files if full backup
        if ($type === 'full') {
            $this->backup_files($zip);
        }

        $zip->close();
        
        return array(
            'filename' => $filename,
            'filepath' => $filepath,
            'type' => $type,
            'size' => filesize($filepath),
            'created_at' => current_time('c'),
            'created_by' => get_current_user_id(),
        );
    }

    /**
     * Backup database to ZIP.
     *
     * @param ZipArchive $zip ZIP archive object.
     */
    private function backup_database($zip) {
        global $wpdb;
        
        $sql_file = tempnam(sys_get_temp_dir(), 'gcc_db_');
        $handle = fopen($sql_file, 'w');
        
        if (!$handle) {
            throw new Exception(__('Unable to create database dump file.', 'gemini-command-center'));
        }

        // Get all tables
        $tables = $wpdb->get_col('SHOW TABLES');
        
        foreach ($tables as $table) {
            // Table structure
            $create_table = $wpdb->get_row("SHOW CREATE TABLE `$table`", ARRAY_N);
            fwrite($handle, "DROP TABLE IF EXISTS `$table`;\n");
            fwrite($handle, $create_table[1] . ";\n\n");
            
            // Table data
            $rows = $wpdb->get_results("SELECT * FROM `$table`", ARRAY_A);
            
            if (!empty($rows)) {
                $insert_sql = "INSERT INTO `$table` VALUES ";
                $value_strings = array();
                
                foreach ($rows as $row) {
                    $values = array();
                    foreach ($row as $value) {
                        $values[] = is_null($value) ? 'NULL' : "'" . esc_sql($value) . "'";
                    }
                    $value_strings[] = '(' . implode(',', $values) . ')';
                }
                
                fwrite($handle, $insert_sql . implode(',', $value_strings) . ";\n\n");
            }
        }
        
        fclose($handle);
        
        $zip->addFile($sql_file, 'database.sql');
        
        // Clean up temp file
        register_shutdown_function(function() use ($sql_file) {
            if (file_exists($sql_file)) {
                unlink($sql_file);
            }
        });
    }

    /**
     * Backup WordPress files to ZIP.
     *
     * @param ZipArchive $zip ZIP archive object.
     */
    private function backup_files($zip) {
        $wp_root = ABSPATH;
        $upload_dir = wp_upload_dir();
        
        // Backup wp-content directory
        $this->add_directory_to_zip($zip, $wp_root . 'wp-content', 'wp-content');
        
        // Backup wp-config.php if it exists
        $wp_config = $wp_root . 'wp-config.php';
        if (file_exists($wp_config)) {
            $zip->addFile($wp_config, 'wp-config.php');
        }
        
        // Backup .htaccess if it exists
        $htaccess = $wp_root . '.htaccess';
        if (file_exists($htaccess)) {
            $zip->addFile($htaccess, '.htaccess');
        }
    }

    /**
     * Add directory to ZIP recursively.
     *
     * @param ZipArchive $zip ZIP archive object.
     * @param string     $dir Directory path.
     * @param string     $zip_dir ZIP directory path.
     */
    private function add_directory_to_zip($zip, $dir, $zip_dir) {
        if (!is_dir($dir)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $file_path = $file->getRealPath();
            $relative_path = $zip_dir . '/' . substr($file_path, strlen($dir) + 1);
            
            // Skip backup directory and cache files
            if (strpos($file_path, $this->backup_dir) !== false ||
                strpos($file_path, '/cache/') !== false ||
                strpos($file_path, '/tmp/') !== false) {
                continue;
            }
            
            if ($file->isDir()) {
                $zip->addEmptyDir($relative_path);
            } else {
                $zip->addFile($file_path, $relative_path);
            }
        }
    }

    /**
     * Perform backup restoration.
     *
     * @param string $filepath Backup file path.
     * @return array Restore information.
     */
    private function perform_restore($filepath) {
        $zip = new ZipArchive();
        
        if ($zip->open($filepath) !== TRUE) {
            throw new Exception(__('Unable to open backup file.', 'gemini-command-center'));
        }

        $temp_dir = sys_get_temp_dir() . '/gcc_restore_' . uniqid();
        if (!wp_mkdir_p($temp_dir)) {
            throw new Exception(__('Unable to create temporary restore directory.', 'gemini-command-center'));
        }

        // Extract backup
        $zip->extractTo($temp_dir);
        $zip->close();

        $restore_info = array(
            'database_restored' => false,
            'files_restored' => false,
            'restored_at' => current_time('c'),
        );

        // Restore database if present
        $db_file = $temp_dir . '/database.sql';
        if (file_exists($db_file)) {
            $this->restore_database($db_file);
            $restore_info['database_restored'] = true;
        }

        // Restore files if present
        $wp_content_dir = $temp_dir . '/wp-content';
        if (is_dir($wp_content_dir)) {
            $this->restore_files($temp_dir);
            $restore_info['files_restored'] = true;
        }

        // Clean up
        $this->delete_directory($temp_dir);

        return $restore_info;
    }

    /**
     * Restore database from SQL file.
     *
     * @param string $sql_file SQL file path.
     */
    private function restore_database($sql_file) {
        global $wpdb;
        
        $sql_content = file_get_contents($sql_file);
        
        if (empty($sql_content)) {
            throw new Exception(__('Database backup file is empty.', 'gemini-command-center'));
        }

        // Execute SQL statements
        $statements = explode(';', $sql_content);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                $result = $wpdb->query($statement);
                if ($result === false) {
                    throw new Exception(__('Database restore failed.', 'gemini-command-center'));
                }
            }
        }
    }

    /**
     * Restore files from backup.
     *
     * @param string $temp_dir Temporary directory with extracted files.
     */
    private function restore_files($temp_dir) {
        $wp_root = ABSPATH;
        
        // Restore wp-content
        $wp_content_backup = $temp_dir . '/wp-content';
        if (is_dir($wp_content_backup)) {
            $this->copy_directory($wp_content_backup, $wp_root . 'wp-content');
        }
        
        // Restore wp-config.php
        $wp_config_backup = $temp_dir . '/wp-config.php';
        if (file_exists($wp_config_backup)) {
            copy($wp_config_backup, $wp_root . 'wp-config.php');
        }
        
        // Restore .htaccess
        $htaccess_backup = $temp_dir . '/.htaccess';
        if (file_exists($htaccess_backup)) {
            copy($htaccess_backup, $wp_root . '.htaccess');
        }
    }

    /**
     * Get list of available backups.
     *
     * @return array List of backups.
     */
    private function get_backup_list() {
        $backups = array();
        
        if (!is_dir($this->backup_dir)) {
            return $backups;
        }

        $files = scandir($this->backup_dir);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || !$this->is_valid_backup_file($file)) {
                continue;
            }
            
            $filepath = $this->backup_dir . '/' . $file;
            $backups[] = array(
                'filename' => $file,
                'size' => filesize($filepath),
                'created_at' => date('c', filemtime($filepath)),
                'type' => $this->get_backup_type_from_filename($file),
            );
        }
        
        // Sort by creation date (newest first)
        usort($backups, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        return $backups;
    }

    /**
     * Check if filename is a valid backup file.
     *
     * @param string $filename Filename to check.
     * @return bool True if valid backup file.
     */
    private function is_valid_backup_file($filename) {
        return preg_match('/^gemini-cc-backup_(database|full)_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.zip$/', $filename);
    }

    /**
     * Get backup type from filename.
     *
     * @param string $filename Backup filename.
     * @return string Backup type.
     */
    private function get_backup_type_from_filename($filename) {
        if (strpos($filename, '_database_') !== false) {
            return 'database';
        } elseif (strpos($filename, '_full_') !== false) {
            return 'full';
        }
        return 'unknown';
    }

    /**
     * Generate secure download URL for backup file.
     *
     * @param string $filename Backup filename.
     * @return string Download URL.
     */
    private function get_secure_download_url($filename) {
        $nonce = wp_create_nonce('gemini_cc_download_backup_' . $filename);
        return add_query_arg(array(
            'action' => 'gemini_cc_download_backup',
            'file' => $filename,
            'nonce' => $nonce,
        ), admin_url('admin-ajax.php'));
    }

    /**
     * Ensure backup directory exists and is protected.
     */
    private function ensure_backup_directory() {
        if (!file_exists($this->backup_dir)) {
            wp_mkdir_p($this->backup_dir);
            
            // Create .htaccess for protection
            $htaccess_content = "# Protect Gemini Command Center backup files\n";
            $htaccess_content .= "Order deny,allow\n";
            $htaccess_content .= "Deny from all\n";
            
            file_put_contents($this->backup_dir . '/.htaccess', $htaccess_content);
            
            // Create index.php to prevent directory listing
            file_put_contents($this->backup_dir . '/index.php', '<?php // Silence is golden');
        }
    }

    /**
     * Copy directory recursively.
     *
     * @param string $src Source directory.
     * @param string $dst Destination directory.
     */
    private function copy_directory($src, $dst) {
        if (!is_dir($src)) {
            return;
        }
        
        if (!is_dir($dst)) {
            wp_mkdir_p($dst);
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $dst_path = $dst . '/' . $iterator->getSubPathName();
            
            if ($item->isDir()) {
                wp_mkdir_p($dst_path);
            } else {
                copy($item, $dst_path);
            }
        }
    }

    /**
     * Delete directory recursively.
     *
     * @param string $dir Directory to delete.
     */
    private function delete_directory($dir) {
        if (!is_dir($dir)) {
            return;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            if ($fileinfo->isDir()) {
                rmdir($fileinfo->getRealPath());
            } else {
                unlink($fileinfo->getRealPath());
            }
        }
        
        rmdir($dir);
    }

    /**
     * Log backup action.
     *
     * @param string $action Action performed.
     * @param array  $data   Additional data.
     */
    private function log_backup_action($action, $data = array()) {
        if (class_exists('Gemini_CC_Logger')) {
            $logger = new Gemini_CC_Logger();
            $logger->info("Backup {$action}", array(
                'action' => $action,
                'data' => $data,
                'user_id' => get_current_user_id(),
            ));
        }
    }

    /**
     * Log backup error.
     *
     * @param string $action Action that failed.
     * @param string $error  Error message.
     */
    private function log_backup_error($action, $error) {
        if (class_exists('Gemini_CC_Logger')) {
            $logger = new Gemini_CC_Logger();
            $logger->error("Backup {$action} failed", array(
                'action' => $action,
                'error' => $error,
                'user_id' => get_current_user_id(),
            ));
        }
    }
}