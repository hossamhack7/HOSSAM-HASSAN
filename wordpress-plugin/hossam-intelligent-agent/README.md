# Hossam Intelligent Agent WordPress Plugin

A WordPress plugin that integrates with Hossam's Intelligent Agent backend system to provide AI-powered features for WordPress sites.

## Description

This plugin creates a bridge between your WordPress website and the Hossam Intelligent Agent backend (Python Flask application). It allows you to:

- Add AI chat functionality to your WordPress pages and posts
- Configure connection to the backend AI system
- Provide a user-friendly interface for AI interactions
- Support both Arabic and English languages

## Features

- **Chat Widget**: Easy-to-use shortcode `[hia_chat]` for adding AI chat to any page
- **Admin Interface**: Complete settings panel in WordPress admin
- **AJAX Integration**: Seamless communication with the backend API
- **Responsive Design**: Mobile-friendly chat interface
- **Multilingual Support**: Ready for Arabic and English translations
- **Security**: Proper nonce validation and data sanitization

## Installation

1. Upload the `hossam-intelligent-agent` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > Intelligent Agent to configure the plugin
4. Set your backend URL (default: http://localhost:5000)
5. Add an API key if required by your backend

## Configuration

### Backend URL
Set the URL where your Hossam Intelligent Agent backend is running. Default is `http://localhost:5000`.

### API Key
If your backend requires authentication, enter the API key here.

### Enable Chat Widget
Toggle to enable or disable the chat functionality site-wide.

## Usage

### Adding Chat Widget

Use the shortcode `[hia_chat]` in any post, page, or widget area:

```
[hia_chat]
```

You can customize the chat widget with parameters:

```
[hia_chat title="مساعد ذكي" height="500px" width="100%"]
```

Parameters:
- `title`: Custom title for the chat widget
- `height`: Height of the chat container (default: 400px)
- `width`: Width of the chat container (default: 100%)

## Backend Integration

The plugin communicates with the Flask backend via HTTP POST requests to the `/v1/chat` endpoint. The expected format is:

```json
{
    "message": "User message text",
    "api_key": "optional_api_key"
}
```

Expected response:
```json
{
    "response": "AI response text"
}
```

## File Structure

```
hossam-intelligent-agent/
├── hossam-intelligent-agent.php    # Main plugin file
├── assets/
│   ├── css/
│   │   ├── frontend.css           # Frontend styles
│   │   └── admin.css              # Admin styles
│   └── js/
│       └── frontend.js            # Frontend JavaScript
├── includes/
│   └── uninstall.php              # Cleanup script
├── languages/                     # Translation files
└── README.md                      # This file
```

## Development

### Requirements
- WordPress 5.0 or higher
- PHP 7.4 or higher
- Running Hossam Intelligent Agent backend

### Customization

The plugin is designed to be easily customizable:

1. **Styling**: Modify CSS files in `assets/css/`
2. **Functionality**: Extend the main class in the main plugin file
3. **Translations**: Add language files in the `languages/` directory

### Adding New Features

To add new features:

1. Extend the `HossamIntelligentAgent` class
2. Add new admin settings if needed
3. Update the JavaScript for frontend interactions
4. Add appropriate CSS styling

## API Documentation

### WordPress Hooks

The plugin provides several hooks for customization:

- `hia_before_send_message`: Filter message before sending to backend
- `hia_after_receive_response`: Filter response after receiving from backend
- `hia_chat_widget_html`: Filter chat widget HTML output

### Shortcode Attributes

The `[hia_chat]` shortcode accepts:
- `height`: Chat container height
- `width`: Chat container width  
- `title`: Chat widget title

## Troubleshooting

### Common Issues

1. **Chat not working**: Check backend URL in settings
2. **Connection errors**: Ensure backend server is running
3. **Styling issues**: Check for CSS conflicts with theme

### Debug Mode

Enable WordPress debug mode to see detailed error messages:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Security

The plugin implements several security measures:

- Nonce verification for AJAX requests
- Data sanitization and validation
- Proper escaping of output
- Capability checks for admin functions

## License

This plugin is licensed under the GPL v2 or later.

## Support

For support and issues:
- GitHub: https://github.com/hossamhack7/HOSSAM-HASSAN
- Check the backend server status and logs
- Verify WordPress and PHP versions

## Changelog

### Version 1.0.0
- Initial release
- Basic chat functionality
- Admin interface
- Backend integration
- Arabic/English support