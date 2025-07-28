# Gemini Bridge Plugin - Developer Documentation

## Overview

Gemini Bridge is a security-first WordPress plugin that provides a comprehensive REST API for React-based frontend applications to safely manage, analyze, and execute advanced operations on WordPress sites.

## Architecture

The plugin follows a modular architecture with four main components:

### 1. Security & Authentication Module (`class-security.php`)
- HMAC-SHA256 request signing
- Secret key generation and management
- Request signature verification
- Security event logging

### 2. Site Health & Diagnostics Module (`class-diagnostics.php`)
- WordPress environment analysis
- Plugin and theme inventory
- Site health check integration
- Server and database information

### 3. Command Executor Module (`class-executor.php`)
- Plugin management (activate/deactivate/delete)
- Plugin and theme installation
- Cache clearing for popular plugins
- Action logging and audit trails

### 4. Code Intelligence Module (`class-code-reader.php`)
- Secure theme file reading
- Directory listing with permissions
- File type and size validation
- Directory traversal protection

## API Authentication

All API endpoints (except `/auth/generate-key`) require HMAC authentication:

### Signature Generation

```javascript
// JavaScript example
function generateSignature(method, uri, body, timestamp, secretKey) {
    const stringToSign = `${method}\n${uri}\n${body}\n${timestamp}`;
    return crypto
        .createHmac('sha256', secretKey)
        .update(stringToSign)
        .digest('hex');
}

// Usage
const timestamp = Math.floor(Date.now() / 1000);
const signature = generateSignature('GET', '/wp-json/gemini-bridge/v1/site/snapshot', '', timestamp, secretKey);

// Headers
headers = {
    'X-Gemini-Signature': signature,
    'X-Gemini-Timestamp': timestamp
};
```

### PHP Example

```php
function generate_signature($method, $uri, $body, $timestamp, $secret_key) {
    $string_to_sign = $method . "\n" . $uri . "\n" . $body . "\n" . $timestamp;
    return hash_hmac('sha256', $string_to_sign, $secret_key);
}
```

## API Endpoints

### Authentication

#### Generate Secret Key
```
POST /wp-json/gemini-bridge/v1/auth/generate-key
```
- **Authentication**: WordPress admin session
- **Returns**: Secret key for HMAC signing

#### Verify Signature
```
POST /wp-json/gemini-bridge/v1/auth/verify
```
- **Authentication**: HMAC signature
- **Returns**: Verification status

### Site Diagnostics

#### Get Site Snapshot
```
GET /wp-json/gemini-bridge/v1/site/snapshot
```
- **Authentication**: HMAC signature
- **Returns**: Comprehensive site information including:
  - WordPress version and configuration
  - PHP environment details
  - Active theme information
  - Plugin inventory
  - Site health status
  - Database information
  - Server details
  - Cache configuration

### Command Execution

#### Plugin Actions
```
POST /wp-json/gemini-bridge/v1/execute/plugin-action
```
- **Authentication**: HMAC signature + admin capabilities
- **Parameters**:
  - `action`: 'activate', 'deactivate', or 'delete'
  - `slug`: Plugin slug in format 'folder/file.php'

#### Install Plugin/Theme
```
POST /wp-json/gemini-bridge/v1/execute/install
```
- **Authentication**: HMAC signature + admin capabilities
- **Parameters**:
  - `type`: 'plugin' or 'theme'
  - `slug`: WordPress.org slug

#### Clear Caches
```
POST /wp-json/gemini-bridge/v1/execute/clear-caches
```
- **Authentication**: HMAC signature + admin capabilities
- **Returns**: List of cleared caches

### Code Intelligence

#### Read File Content
```
GET /wp-json/gemini-bridge/v1/read-file-content?file_path=style.css
```
- **Authentication**: HMAC signature + theme edit capabilities
- **Parameters**:
  - `file_path`: Relative path within active theme directory
- **Restrictions**: 
  - Only files within active theme directory
  - Maximum file size: 1MB
  - Allowed extensions: php, css, js, json, txt, md, html, xml, scss, sass, less

#### List Theme Files
```
GET /wp-json/gemini-bridge/v1/list-theme-files?path=css
```
- **Authentication**: HMAC signature + theme edit capabilities
- **Parameters**:
  - `path`: Optional subdirectory within theme (default: root)

## Security Features

### Input Validation
- All inputs are sanitized using WordPress functions
- File paths are validated to prevent directory traversal
- Plugin slugs are validated against expected formats

### Permission Checks
- HMAC signature verification on all protected endpoints
- WordPress capability checks (manage_options, edit_themes, etc.)
- User session validation for admin-only endpoints

### Audit Logging
- All security events are logged with user ID, IP, and timestamp
- File access operations are tracked
- Command execution is audited
- Logs are stored in WordPress transients for 24 hours

### Rate Limiting
- Timestamp validation prevents replay attacks (5-minute window)
- File size limits prevent resource exhaustion
- Directory traversal protection

## Development Guidelines

### Adding New Endpoints

1. Create method in appropriate class
2. Register route in `register_routes()` method
3. Implement proper permission callback
4. Add input validation and sanitization
5. Include audit logging
6. Update documentation

### Security Checklist

- [ ] HMAC signature verification
- [ ] WordPress capability checks
- [ ] Input sanitization
- [ ] Output escaping
- [ ] Audit logging
- [ ] Error handling
- [ ] Rate limiting considerations

### Testing

The plugin should be tested with:
- Valid and invalid HMAC signatures
- Various user capability levels
- Directory traversal attempts
- Large file uploads
- Malformed input data
- WordPress multisite environments

## Troubleshooting

### Common Issues

1. **Invalid Signature**: Check timestamp and signature generation
2. **Permission Denied**: Verify user capabilities and HMAC key
3. **File Not Found**: Ensure file exists in theme directory
4. **Directory Traversal**: Path contains '..' or invalid characters

### Debug Mode

Enable WordPress debug mode for detailed error logging:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Performance Considerations

- Site snapshot data is cached for 5 minutes
- Security logs are limited to 100 entries
- File operations have size and type restrictions
- Transients are used for temporary data storage

## Extending the Plugin

The plugin is designed to be extensible. New modules can be added by:

1. Creating a new class file in `/includes/`
2. Following the existing naming convention
3. Implementing proper security measures
4. Registering routes in the main plugin class
5. Adding appropriate documentation