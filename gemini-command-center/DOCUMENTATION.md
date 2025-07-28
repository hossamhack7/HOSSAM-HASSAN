# Gemini Command Center - Complete Documentation

## Overview

Gemini Command Center is a comprehensive, AI-powered WordPress management suite that leverages Google's Gemini AI to provide intelligent automation, advanced SEO tools, content generation, and strategic insights for WordPress websites.

## 🚀 Key Features

### Core Functionality
- **Security-First Architecture**: All actions secured with WordPress nonces and capability checks
- **Performance Optimized**: Lightweight code with transient caching and conditional loading
- **WordPress Best Practices**: Strict adherence to WordPress Coding Standards
- **React-Powered UI**: Modern SPA interface with responsive design
- **Modular Architecture**: Each feature in its own class/module

### AI-Powered Features
- **Content Generation**: AI article writer with SEO optimization
- **SEO Analysis**: Technical audits and competitive analysis  
- **Design Optimization**: UI/UX analysis and improvement suggestions
- **Conversational Agent**: Natural language interface with memory system
- **A/B Testing**: Intelligent headline testing with automatic winner selection

## 📁 File Structure

```
gemini-command-center/
├── gemini-command-center.php          # Main plugin file
├── readme.txt                         # WordPress plugin readme
├── package.json                       # React dependencies
├── includes/                           # PHP backend classes
│   ├── class-gemini-command-center.php    # Main plugin class
│   ├── class-assets-loader.php            # Asset management
│   ├── class-api-registrar.php            # REST API endpoints
│   ├── class-settings-handler.php         # Settings management
│   ├── class-security-manager.php         # Security features
│   ├── class-gemini-api.php              # Gemini AI integration
│   ├── class-rate-limiter.php            # API rate limiting
│   ├── class-logger.php                  # System logging
│   ├── class-backup-manager.php          # Backup/restore functionality
│   ├── class-seo-manager.php             # SEO tools and analysis
│   ├── class-content-manager.php         # Content creation/optimization
│   ├── class-uiux-manager.php            # UI/UX improvements
│   ├── class-reporting-manager.php       # Analytics and reporting
│   ├── class-ai-agent.php               # Conversational AI
│   ├── class-system-health.php          # System diagnostics
│   ├── class-gemini-cc-activator.php    # Plugin activation
│   ├── class-gemini-cc-deactivator.php  # Plugin deactivation
│   └── class-gemini-cc-uninstaller.php  # Plugin uninstall
├── templates/                          # Admin page templates
│   ├── admin-main.php                  # Main dashboard template
│   ├── admin-ai-agent.php             # AI agent page template
│   └── admin-settings.php             # Settings page template
├── assets/                             # Static assets
│   ├── css/
│   │   └── admin.css                   # Admin styles
│   ├── js/
│   └── images/
├── src/                               # React source code
│   ├── App.js                         # Main React component
│   ├── App.css                        # App styles
│   ├── index.js                       # React entry point
│   ├── index.css                      # Base styles
│   ├── utils/
│   │   └── api.js                     # API utility functions
│   ├── contexts/                      # React contexts
│   │   ├── SettingsContext.js         # Settings state management
│   │   └── NotificationContext.js     # Notification system
│   └── components/                    # React components
│       ├── Dashboard/
│       ├── Settings/
│       ├── SEO/
│       ├── Content/
│       ├── UIUX/
│       ├── Backup/
│       ├── Reports/
│       ├── AIAgent/
│       ├── Navigation/
│       ├── UI/
│       └── Onboarding/
└── build/                             # Compiled React app (generated)
```

## 🔧 Installation & Setup

### Prerequisites
- WordPress 5.0+
- PHP 7.4+
- Google Gemini API key

### Installation Steps

1. **Upload Plugin**
   ```bash
   # Upload to WordPress plugins directory
   wp-content/plugins/gemini-command-center/
   ```

2. **Activate Plugin**
   - Go to WordPress Admin → Plugins
   - Find "Gemini Command Center"
   - Click "Activate"

3. **Configure API Key**
   - Navigate to Gemini Command Center → Settings
   - Enter your Google Gemini API key
   - Click "Test Connection" to verify
   - Save settings

4. **Complete Setup**
   - Follow the onboarding tour
   - Configure your preferred settings
   - Start using AI-powered features

### Getting Gemini API Key

1. Visit [Google AI Studio](https://makersuite.google.com/app/apikey)
2. Sign in with your Google account
3. Create a new API key
4. Copy the key to plugin settings

## ⚙️ Configuration

### General Settings
- **API Key**: Your Google Gemini API key
- **Operation Mode**: 
  - Approval Mode (recommended): Requires confirmation for AI actions
  - Autonomous Mode: Executes AI actions automatically

### SEO Settings
- **Internal Link Architect**: Automatically suggest and create internal links
- **A/B Testing**: Test different headlines for better engagement
- **Competitive Analysis**: Analyze competitor websites
- **Search Region**: Target region for SEO analysis

### UI/UX Settings
- **Enable UI/UX Module**: Toggle custom CSS injection
- **Color Palette**: AI-generated color schemes
- **Font Pairing**: Typography optimization

### Content Settings
- **AI Writer**: Default post status and tone settings
- **Social Amplifier**: Platform-specific tone configuration
- **Meta Generation**: Automatic SEO meta tag creation

### System Settings
- **Backup Schedule**: Automated backup frequency
- **Logging**: System activity tracking
- **Rate Limiting**: API usage protection
- **Performance**: Caching and optimization options

## 🔌 API Reference

The plugin exposes a comprehensive REST API under the namespace `gemini-cc/v1/`.

### Authentication
All endpoints require:
- `manage_options` capability
- WordPress nonce verification
- Current user authentication

### Core Endpoints

#### Plugin Status
```
GET /gemini-cc/v1/status
```
Returns plugin version and status information.

#### Settings Management
```
GET /gemini-cc/v1/settings
POST /gemini-cc/v1/settings
```
Retrieve and update plugin settings.

#### API Connection Testing
```
POST /gemini-cc/v1/test-connection
```
Test Gemini API connectivity.

### Feature-Specific Endpoints

#### Backup Management
```
POST /gemini-cc/v1/backup/create          # Create backup
GET  /gemini-cc/v1/backup/list            # List backups
GET  /gemini-cc/v1/backup/download/{file} # Download backup
POST /gemini-cc/v1/backup/restore         # Restore backup
```

#### SEO Tools
```
GET  /gemini-cc/v1/seo/technical-audit    # Run SEO audit
POST /gemini-cc/v1/seo/generate-cluster   # Generate content cluster
POST /gemini-cc/v1/seo/analyze-competitor # Analyze competitor
GET  /gemini-cc/v1/links/orphan-pages     # Find orphan pages
POST /gemini-cc/v1/links/create           # Create internal link
```

#### Content Management
```
POST /gemini-cc/v1/content/generate-article # Generate AI article
POST /gemini-cc/v1/content/save-draft       # Save content draft
POST /gemini-cc/v1/content/start-ab-test    # Start A/B test
POST /gemini-cc/v1/content/amplify          # Social media amplification
```

#### UI/UX Tools
```
POST /gemini-cc/v1/uiux/analyze-design     # Analyze website design
POST /gemini-cc/v1/uiux/suggest-palette    # Generate color palette
POST /gemini-cc/v1/uiux/save-styles        # Save custom styles
```

#### System Monitoring
```
GET  /gemini-cc/v1/system/health-check     # System health check
GET  /gemini-cc/v1/system/logs             # Retrieve system logs
POST /gemini-cc/v1/system/clear-logs       # Clear system logs
```

#### AI Agent
```
POST /gemini-cc/v1/agent/converse          # Chat with AI agent
```

#### Reporting
```
GET /gemini-cc/v1/reports/kpi-summary      # KPI dashboard data
GET /gemini-cc/v1/reports/impact-analysis  # Performance impact analysis
```

## 🛡️ Security Features

### Authentication & Authorization
- WordPress nonce verification on all requests
- `manage_options` capability requirement
- User session validation
- CSRF protection

### Data Protection
- Input sanitization for all user data
- Output escaping to prevent XSS
- SQL injection prevention with prepared statements
- File upload validation and restrictions

### API Security
- Rate limiting to prevent abuse
- Request timeout protection
- Secure API key storage
- Error message sanitization

### Backup Security
- Protected backup directory with .htaccess
- Secure download URLs with nonces
- Backup file validation
- Access logging

## 📊 Database Schema

The plugin creates several custom tables:

### AI Agent Memory (`wp_gemini_cc_memory`)
```sql
CREATE TABLE wp_gemini_cc_memory (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) NOT NULL,
    fact_type varchar(50) NOT NULL,
    fact_content longtext NOT NULL,
    importance_score int(3) DEFAULT 50,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY user_id (user_id),
    KEY fact_type (fact_type)
);
```

### System Logs (`wp_gemini_cc_logs`)
```sql
CREATE TABLE wp_gemini_cc_logs (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    level varchar(20) NOT NULL DEFAULT 'info',
    message longtext NOT NULL,
    context longtext,
    user_id bigint(20),
    ip_address varchar(45),
    user_agent text,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY level (level),
    KEY user_id (user_id),
    KEY created_at (created_at)
);
```

### A/B Tests (`wp_gemini_cc_ab_tests`)
```sql
CREATE TABLE wp_gemini_cc_ab_tests (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    post_id bigint(20) NOT NULL,
    title_a varchar(255) NOT NULL,
    title_b varchar(255) NOT NULL,
    impressions_a int(10) DEFAULT 0,
    impressions_b int(10) DEFAULT 0,
    clicks_a int(10) DEFAULT 0,
    clicks_b int(10) DEFAULT 0,
    status varchar(20) DEFAULT 'active',
    winner varchar(1),
    started_at datetime DEFAULT CURRENT_TIMESTAMP,
    ended_at datetime,
    created_by bigint(20),
    PRIMARY KEY (id),
    KEY post_id (post_id),
    KEY status (status)
);
```

## 🔨 Development

### Building React Components

1. **Install Dependencies**
   ```bash
   cd gemini-command-center/
   npm install
   ```

2. **Development Mode**
   ```bash
   npm start
   ```

3. **Production Build**
   ```bash
   npm run build
   ```

### Adding New Features

1. **Backend (PHP)**
   - Create new class in `includes/`
   - Add to main plugin class initialization
   - Register API endpoints in `class-api-registrar.php`
   - Add appropriate security checks

2. **Frontend (React)**
   - Create component in `src/components/`
   - Add routing in `App.js`
   - Implement API calls in `src/utils/api.js`
   - Update navigation if needed

### Code Standards

- **PHP**: WordPress Coding Standards
- **JavaScript**: ESLint with React configuration
- **CSS**: BEM methodology with responsive design
- **Security**: WordPress nonces, capability checks, sanitization

## 🐛 Troubleshooting

### Common Issues

#### API Connection Failed
- Verify Gemini API key is correct
- Check network connectivity
- Ensure rate limits not exceeded
- Review error logs in plugin

#### React Components Not Loading
- Check if build files exist in `/build/` directory
- Run `npm run build` to compile React app
- Verify asset URLs in browser developer tools
- Check for JavaScript errors in console

#### Backup Creation Failed
- Verify uploads directory is writable
- Check available disk space
- Ensure PHP memory limit is sufficient
- Review backup directory permissions

#### Database Errors
- Check database connection
- Verify table creation permissions
- Review MySQL version compatibility
- Check for conflicting plugins

### Debug Mode

Enable debug mode for detailed logging:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// In plugin settings
$settings['advanced']['debug_mode'] = true;
```

### Log Analysis

Access logs through:
- Plugin dashboard → System Logs
- WordPress debug.log file
- Database logs table
- Browser developer console

## 📈 Performance Optimization

### Caching Strategy
- Transient caching for API responses
- Database query optimization
- Asset minification and compression
- Conditional script loading

### Resource Management
- Memory usage monitoring
- Database query limiting
- File size optimization
- Background processing for heavy tasks

### Rate Limiting
- API request throttling
- User-based limitations
- Automatic cooldown periods
- Queue management for bulk operations

## 🔄 Maintenance

### Regular Tasks
- Clear old logs and cache
- Update AI model responses
- Monitor API usage
- Review security logs
- Test backup integrity

### Updates
- Plugin updates through WordPress
- React dependency updates
- Security patches
- Feature enhancements

### Monitoring
- System health dashboard
- Performance metrics
- Error rate tracking
- User engagement analytics

## 🆘 Support

### Getting Help
- Plugin documentation
- WordPress.org support forums
- GitHub repository issues
- Developer documentation

### Reporting Issues
- Include WordPress and PHP versions
- Provide error logs
- Describe reproduction steps
- Include screenshot if relevant

## 📜 License

This plugin is licensed under the GPL v2 or later.

```
Copyright (C) 2024 Hossam Hassan

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## 🙏 Acknowledgments

- Google Gemini AI for powerful language processing
- WordPress community for best practices
- React team for excellent framework
- Open source contributors

---

**Gemini Command Center** - Transforming WordPress management with AI-powered intelligence.

For more information, visit the [GitHub repository](https://github.com/hossamhack7/HOSSAM-HASSAN) or contact the development team.