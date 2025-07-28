import React, { useState, useEffect } from 'react';
import { useSettings } from '../../contexts/SettingsContext';
import { useNotification } from '../../contexts/NotificationContext';
import LoadingSpinner from '../UI/LoadingSpinner';
import HelloWorld from '../HelloWorld';

const Dashboard = () => {
  const { settings, loading, getSetting } = useSettings();
  const { showSuccess, showInfo } = useNotification();
  const [recommendations, setRecommendations] = useState([]);

  useEffect(() => {
    if (!loading && settings) {
      // Generate recommendations based on current settings
      generateRecommendations();
      
      // Show welcome message for first-time users
      if (!getSetting('general.first_setup_completed')) {
        showInfo('Welcome to Gemini Command Center! Start by configuring your settings.');
      }
    }
  }, [loading, settings, getSetting, showInfo]);

  const generateRecommendations = () => {
    const recs = [];
    
    // Check if API key is configured
    if (!getSetting('general.api_key')) {
      recs.push({
        id: 'setup-api-key',
        title: 'Configure Gemini API Key',
        description: 'Set up your Google Gemini API key to unlock AI-powered features.',
        priority: 'high',
        action: 'Go to Settings',
        icon: '🔑',
      });
    }
    
    // Check if backup is enabled
    if (getSetting('system.backup.schedule') === 'disabled') {
      recs.push({
        id: 'enable-backup',
        title: 'Enable Automatic Backups',
        description: 'Protect your website with scheduled backups.',
        priority: 'medium',
        action: 'Configure Backup',
        icon: '💾',
      });
    }
    
    // Check if SEO features are enabled
    if (!getSetting('seo.internal_links.enabled')) {
      recs.push({
        id: 'enable-seo',
        title: 'Enable SEO Features',
        description: 'Improve your website\'s SEO with automated internal linking.',
        priority: 'medium',
        action: 'Enable SEO Tools',
        icon: '📈',
      });
    }
    
    setRecommendations(recs);
  };

  const handleRecommendationAction = (recommendation) => {
    switch (recommendation.id) {
      case 'setup-api-key':
        // Navigate to settings
        window.location.hash = '#/settings';
        break;
      case 'enable-backup':
        showSuccess('Redirecting to backup settings...');
        break;
      case 'enable-seo':
        showSuccess('Redirecting to SEO settings...');
        break;
      default:
        break;
    }
  };

  if (loading) {
    return (
      <div className="gcc-dashboard-loading">
        <LoadingSpinner size="large" message="Loading dashboard..." />
      </div>
    );
  }

  return (
    <div className="gcc-dashboard">
      <header className="gcc-dashboard-header">
        <h1>Welcome to Gemini Command Center</h1>
        <p>Your AI-powered WordPress management suite</p>
      </header>

      {/* Hello World Demo Component */}
      <div className="gcc-section">
        <HelloWorld />
      </div>

      {/* Quick Stats */}
      <div className="gcc-section">
        <h2>Quick Overview</h2>
        <div className="gcc-stats-grid">
          <div className="gcc-stat-card">
            <div className="gcc-stat-icon">⚙️</div>
            <div className="gcc-stat-content">
              <h3>Settings</h3>
              <p>Configuration Status</p>
              <div className="gcc-stat-value">
                {getSetting('general.api_key') ? 
                  <span className="gcc-status-success">✓ Configured</span> : 
                  <span className="gcc-status-warning">⚠ Needs Setup</span>
                }
              </div>
            </div>
          </div>
          
          <div className="gcc-stat-card">
            <div className="gcc-stat-icon">🤖</div>
            <div className="gcc-stat-content">
              <h3>AI Features</h3>
              <p>Gemini API Status</p>
              <div className="gcc-stat-value">
                {getSetting('general.api_key') ? 
                  <span className="gcc-status-success">✓ Ready</span> : 
                  <span className="gcc-status-error">✕ Disabled</span>
                }
              </div>
            </div>
          </div>
          
          <div className="gcc-stat-card">
            <div className="gcc-stat-icon">💾</div>
            <div className="gcc-stat-content">
              <h3>Backups</h3>
              <p>Protection Status</p>
              <div className="gcc-stat-value">
                {getSetting('system.backup.schedule') !== 'disabled' ? 
                  <span className="gcc-status-success">✓ Active</span> : 
                  <span className="gcc-status-warning">⚠ Disabled</span>
                }
              </div>
            </div>
          </div>
          
          <div className="gcc-stat-card">
            <div className="gcc-stat-icon">📈</div>
            <div className="gcc-stat-content">
              <h3>SEO Tools</h3>
              <p>Optimization Status</p>
              <div className="gcc-stat-value">
                {getSetting('seo.internal_links.enabled') ? 
                  <span className="gcc-status-success">✓ Enabled</span> : 
                  <span className="gcc-status-info">ℹ Available</span>
                }
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Recommendations */}
      {recommendations.length > 0 && (
        <div className="gcc-section">
          <h2>Recommended Actions</h2>
          <div className="gcc-recommendations">
            {recommendations.map((rec) => (
              <div key={rec.id} className={`gcc-recommendation gcc-priority-${rec.priority}`}>
                <div className="gcc-recommendation-icon">{rec.icon}</div>
                <div className="gcc-recommendation-content">
                  <h3>{rec.title}</h3>
                  <p>{rec.description}</p>
                </div>
                <div className="gcc-recommendation-action">
                  <button
                    className="gcc-button gcc-button-primary"
                    onClick={() => handleRecommendationAction(rec)}
                  >
                    {rec.action}
                  </button>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Quick Actions */}
      <div className="gcc-section">
        <h2>Quick Actions</h2>
        <div className="gcc-quick-actions">
          <button className="gcc-action-card" onClick={() => window.location.hash = '#/settings'}>
            <div className="gcc-action-icon">⚙️</div>
            <h3>Settings</h3>
            <p>Configure plugin options</p>
          </button>
          
          <button className="gcc-action-card" onClick={() => window.location.hash = '#/backup'}>
            <div className="gcc-action-icon">💾</div>
            <h3>Create Backup</h3>
            <p>Backup your website now</p>
          </button>
          
          <button className="gcc-action-card" onClick={() => window.location.hash = '#/ai-agent'}>
            <div className="gcc-action-icon">🤖</div>
            <h3>AI Agent</h3>
            <p>Chat with your AI assistant</p>
          </button>
          
          <button className="gcc-action-card" onClick={() => window.location.hash = '#/seo'}>
            <div className="gcc-action-icon">📈</div>
            <h3>SEO Audit</h3>
            <p>Run technical SEO analysis</p>
          </button>
        </div>
      </div>
    </div>
  );
};

export default Dashboard;