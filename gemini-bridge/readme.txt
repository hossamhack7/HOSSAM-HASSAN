=== Gemini Bridge ===
Contributors: hossamhack7
Tags: api, bridge, react, ai, security, management, diagnostics, automation
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A comprehensive and highly secure companion plugin that serves as a backend bridge for sophisticated React-based frontend applications.

== Description ==

**Gemini Bridge** is a security-first WordPress plugin designed to serve as a backend bridge for sophisticated React-based frontend applications. It enables safe management, analysis, and execution of advanced operations on WordPress sites through a comprehensive REST API with HMAC-based authentication.

= Core Features =

**🔐 Security & Authentication**
* HMAC-based request signing for secure API authentication
* No password-based authentication required for API calls
* Comprehensive permission checks for all endpoints
* Security event logging and monitoring
* Timestamp validation to prevent replay attacks

**🏥 Site Health & Diagnostics**
* Complete WordPress environment analysis
* PHP configuration and extension detection
* Theme and plugin inventory with detailed information
* Built-in WordPress Site Health integration
* Database and server information gathering
* Caching system detection (object cache, page cache, CDN)
* Security configuration analysis

**⚡ Command Execution Engine**
* Safe plugin activation, deactivation, and deletion
* Automated plugin and theme installation from WordPress.org
* Intelligent cache clearing for popular caching plugins
* Comprehensive action logging and audit trails
* Protection against self-modification

**📁 Code Intelligence (Read-Only)**
* Secure theme file reading for AI analysis
* Directory traversal protection
* File type and size restrictions
* Theme directory sandboxing
* Support for multiple file formats (PHP, CSS, JS, etc.)

= Security Model =

This plugin implements a **security-first architecture**:

1. **HMAC Authentication**: All API requests must be signed with a secret key using HMAC-SHA256
2. **Capability Verification**: Every action verifies WordPress user capabilities
3. **Input Sanitization**: All inputs are properly sanitized and validated
4. **Directory Sandboxing**: File operations are restricted to the active theme directory
5. **No Write Operations**: The plugin intentionally does not allow code modification
6. **Audit Logging**: All actions are logged for security monitoring

= API Endpoints =

**Authentication**
* `POST /wp-json/gemini-bridge/v1/auth/generate-key` - Generate HMAC secret key
* `POST /wp-json/gemini-bridge/v1/auth/verify` - Verify request signature

**Site Analysis**
* `GET /wp-json/gemini-bridge/v1/site/snapshot` - Get comprehensive site information

**Command Execution**
* `POST /wp-json/gemini-bridge/v1/execute/plugin-action` - Manage plugins
* `POST /wp-json/gemini-bridge/v1/execute/install` - Install plugins/themes
* `POST /wp-json/gemini-bridge/v1/execute/clear-caches` - Clear various caches

**Code Intelligence**
* `GET /wp-json/gemini-bridge/v1/read-file-content` - Read theme files
* `GET /wp-json/gemini-bridge/v1/list-theme-files` - List theme directory contents

= Use Cases =

* **AI-Powered Site Management**: Enable AI systems to analyze and manage WordPress sites
* **React Frontend Integration**: Provide secure backend services for React applications
* **Site Monitoring**: Comprehensive site health and configuration monitoring
* **Automated Maintenance**: Safe automation of common WordPress management tasks
* **Code Analysis**: Allow AI systems to read and analyze theme code for optimization

= Requirements =

* WordPress 5.0 or higher
* PHP 7.4 or higher
* Administrator or appropriate user capabilities
* HTTPS recommended for production use

== Installation ==

1. Upload the `gemini-bridge` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to the WordPress admin and generate a secret key via the REST API
4. Configure your React application to use HMAC authentication with the generated key

= Manual Installation =

1. Download the plugin zip file
2. Go to WordPress admin > Plugins > Add New > Upload Plugin
3. Choose the zip file and click "Install Now"
4. Activate the plugin

= API Setup =

1. **Generate Secret Key**: Make a POST request to `/wp-json/gemini-bridge/v1/auth/generate-key` with administrator authentication
2. **Store the Key**: Securely store the returned secret key in your React application
3. **Sign Requests**: Use HMAC-SHA256 to sign all subsequent API requests
4. **Include Headers**: Add `X-Gemini-Signature` and `X-Gemini-Timestamp` headers to all requests

== Frequently Asked Questions ==

= How does the HMAC authentication work? =

The plugin uses HMAC-SHA256 for request authentication. Your React app must:
1. Generate a timestamp
2. Create a signature string: `METHOD\nURI\nBODY\nTIMESTAMP`
3. Sign this string with the secret key using HMAC-SHA256
4. Include the signature and timestamp in request headers

= Is it safe to use this plugin? =

Yes, when used properly. The plugin follows WordPress security best practices:
- All actions require proper user capabilities
- HMAC authentication prevents unauthorized access
- File operations are sandboxed to the theme directory
- No write operations are allowed for code files
- All actions are logged for auditing

= Can this plugin modify my site's code? =

No. By design, this plugin only provides **read access** to theme files. All code modifications must be done manually by users, maintaining a human-in-the-loop security model.

= What caching plugins are supported for cache clearing? =

The plugin supports automatic cache clearing for:
- W3 Total Cache
- WP Super Cache
- LiteSpeed Cache
- WP Rocket
- WP Fastest Cache
- Cache Enabler
- Hummingbird
- WordPress object cache and transients

= How do I revoke access? =

You can revoke access by:
1. Deactivating the plugin
2. Generating a new secret key (invalidates the old one)
3. Removing the stored secret key from the database

== Screenshots ==

1. API endpoint structure and authentication flow
2. Site health and diagnostics information
3. Plugin management capabilities
4. Theme file reading with security restrictions
5. Cache clearing functionality

== Changelog ==

= 1.0.0 =
* Initial release
* HMAC-based authentication system
* Comprehensive site diagnostics
* Plugin and theme management
* Secure theme file reading
* Cache clearing for popular plugins
* Security event logging
* WordPress coding standards compliance

== Upgrade Notice ==

= 1.0.0 =
Initial release of Gemini Bridge plugin.

== Security Considerations ==

**Important Security Notes:**

1. **HTTPS Required**: Always use HTTPS in production to protect API communications
2. **Secret Key Protection**: Store the HMAC secret key securely and never expose it in client-side code
3. **User Capabilities**: Only users with appropriate capabilities can access the endpoints
4. **File Access Limitations**: File reading is restricted to the active theme directory only
5. **No Code Modification**: The plugin intentionally does not allow writing or modifying code files
6. **Audit Logging**: Monitor the security logs for any suspicious activity
7. **Regular Updates**: Keep the plugin updated to ensure security patches are applied

**Recommended Security Practices:**

- Generate new secret keys periodically
- Monitor access logs for unusual activity
- Restrict API access to trusted IP addresses when possible
- Use strong WordPress user passwords and two-factor authentication
- Keep WordPress core, themes, and plugins updated
- Use a security plugin for additional protection

== Support ==

For support, bug reports, or feature requests, please visit:
https://github.com/hossamhack7/HOSSAM-HASSAN

== License ==

This plugin is licensed under the GPLv2 or later license.
License URI: https://www.gnu.org/licenses/gpl-2.0.html