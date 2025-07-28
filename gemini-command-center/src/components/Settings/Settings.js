import React, { useState } from 'react';
import { useSettings } from '../../contexts/SettingsContext';
import { useNotification } from '../../contexts/NotificationContext';
import { testGeminiConnection } from '../../utils/api';
import LoadingSpinner from '../UI/LoadingSpinner';

const Settings = () => {
  const {
    settings,
    loading,
    saving,
    getSetting,
    updateSetting,
    saveSettings,
    exportSettings,
    importSettings,
  } = useSettings();
  
  const { showSuccess, showError, showLoading, removeNotification } = useNotification();
  
  const [activeTab, setActiveTab] = useState('general');
  const [testingConnection, setTestingConnection] = useState(false);

  const tabs = [
    { id: 'general', label: 'General & API', icon: '🔑' },
    { id: 'seo', label: 'SEO Settings', icon: '📈' },
    { id: 'uiux', label: 'UI/UX Settings', icon: '🎨' },
    { id: 'content', label: 'Content Settings', icon: '✍️' },
    { id: 'system', label: 'System & Data', icon: '💾' },
  ];

  const handleSave = async () => {
    try {
      await saveSettings();
      showSuccess('Settings saved successfully!');
    } catch (error) {
      showError('Failed to save settings: ' + error.message);
    }
  };

  const handleTestConnection = async () => {
    const apiKey = getSetting('general.api_key');
    
    if (!apiKey) {
      showError('Please enter your Gemini API key first.');
      return;
    }

    setTestingConnection(true);
    const loadingId = showLoading('Testing connection to Gemini API...');
    
    try {
      const result = await testGeminiConnection(apiKey);
      removeNotification(loadingId);
      
      if (result.success) {
        showSuccess('Connection successful! Gemini API is working properly.');
      } else {
        showError('Connection failed: ' + result.message);
      }
    } catch (error) {
      removeNotification(loadingId);
      showError('Connection test failed: ' + error.message);
    } finally {
      setTestingConnection(false);
    }
  };

  const handleExport = () => {
    try {
      exportSettings();
      showSuccess('Settings exported successfully!');
    } catch (error) {
      showError('Failed to export settings: ' + error.message);
    }
  };

  const handleImport = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    importSettings(file)
      .then(() => {
        showSuccess('Settings imported successfully!');
        event.target.value = ''; // Clear file input
      })
      .catch((error) => {
        showError('Failed to import settings: ' + error.message);
        event.target.value = ''; // Clear file input
      });
  };

  if (loading) {
    return (
      <div className="gcc-settings-loading">
        <LoadingSpinner size="large" message="Loading settings..." />
      </div>
    );
  }

  return (
    <div className="gcc-settings">
      <header className="gcc-settings-header">
        <h1>Plugin Settings</h1>
        <div className="gcc-settings-actions">
          <button
            className="gcc-button gcc-button-secondary"
            onClick={handleExport}
          >
            Export Settings
          </button>
          <label className="gcc-button gcc-button-secondary">
            Import Settings
            <input
              type="file"
              accept=".json"
              onChange={handleImport}
              style={{ display: 'none' }}
            />
          </label>
          <button
            className={`gcc-button gcc-button-success ${saving ? 'gcc-loading' : ''}`}
            onClick={handleSave}
            disabled={saving}
          >
            {saving ? 'Saving...' : 'Save Settings'}
          </button>
        </div>
      </header>

      <div className="gcc-settings-container">
        {/* Tab Navigation */}
        <div className="gcc-tabs">
          <ul className="gcc-tab-list">
            {tabs.map((tab) => (
              <li key={tab.id} className="gcc-tab">
                <button
                  className={`gcc-tab-button ${activeTab === tab.id ? 'active' : ''}`}
                  onClick={() => setActiveTab(tab.id)}
                >
                  <span className="gcc-tab-icon">{tab.icon}</span>
                  {tab.label}
                </button>
              </li>
            ))}
          </ul>
        </div>

        {/* Tab Content */}
        <div className="gcc-tab-content">
          {activeTab === 'general' && (
            <GeneralSettings
              getSetting={getSetting}
              updateSetting={updateSetting}
              onTestConnection={handleTestConnection}
              testingConnection={testingConnection}
            />
          )}
          
          {activeTab === 'seo' && (
            <SEOSettings
              getSetting={getSetting}
              updateSetting={updateSetting}
            />
          )}
          
          {activeTab === 'uiux' && (
            <UIUXSettings
              getSetting={getSetting}
              updateSetting={updateSetting}
            />
          )}
          
          {activeTab === 'content' && (
            <ContentSettings
              getSetting={getSetting}
              updateSetting={updateSetting}
            />
          )}
          
          {activeTab === 'system' && (
            <SystemSettings
              getSetting={getSetting}
              updateSetting={updateSetting}
            />
          )}
        </div>
      </div>
    </div>
  );
};

// General Settings Tab
const GeneralSettings = ({ getSetting, updateSetting, onTestConnection, testingConnection }) => (
  <div className="gcc-settings-section">
    <h2>General & API Settings</h2>
    
    <div className="gcc-form-group">
      <label className="gcc-form-label">
        Gemini API Key *
      </label>
      <input
        type="password"
        className="gcc-form-input"
        value={getSetting('general.api_key', '')}
        onChange={(e) => updateSetting('general.api_key', e.target.value)}
        placeholder="Enter your Google Gemini API key"
      />
      <div className="gcc-form-help">
        Get your API key from <a href="https://makersuite.google.com/app/apikey" target="_blank" rel="noopener noreferrer">Google AI Studio</a>
      </div>
    </div>

    <div className="gcc-form-group">
      <button
        className={`gcc-button gcc-button-primary ${testingConnection ? 'gcc-loading' : ''}`}
        onClick={onTestConnection}
        disabled={testingConnection || !getSetting('general.api_key')}
      >
        {testingConnection ? 'Testing...' : 'Test Connection'}
      </button>
    </div>

    <div className="gcc-form-group">
      <label className="gcc-form-label">
        Operation Mode
      </label>
      <select
        className="gcc-form-select"
        value={getSetting('general.operation_mode', 'approval')}
        onChange={(e) => updateSetting('general.operation_mode', e.target.value)}
      >
        <option value="approval">Approval Mode (Recommended)</option>
        <option value="autonomous">Autonomous Mode</option>
      </select>
      <div className="gcc-form-help">
        Approval mode requires confirmation for AI actions. Autonomous mode executes actions automatically.
      </div>
    </div>
  </div>
);

// SEO Settings Tab
const SEOSettings = ({ getSetting, updateSetting }) => (
  <div className="gcc-settings-section">
    <h2>SEO Settings</h2>
    
    <div className="gcc-form-group">
      <label className="gcc-form-label">
        <input
          type="checkbox"
          className="gcc-form-checkbox"
          checked={getSetting('seo.internal_links.enabled', true)}
          onChange={(e) => updateSetting('seo.internal_links.enabled', e.target.checked)}
        />
        Enable Internal Link Architect
      </label>
      <div className="gcc-form-help">
        Automatically suggest and create internal links between your posts.
      </div>
    </div>

    {getSetting('seo.internal_links.enabled') && (
      <div className="gcc-form-group">
        <label className="gcc-form-label">
          Maximum Links per Post
        </label>
        <input
          type="number"
          className="gcc-form-input"
          min="1"
          max="20"
          value={getSetting('seo.internal_links.max_links', 5)}
          onChange={(e) => updateSetting('seo.internal_links.max_links', parseInt(e.target.value))}
        />
      </div>
    )}

    <div className="gcc-form-group">
      <label className="gcc-form-label">
        <input
          type="checkbox"
          className="gcc-form-checkbox"
          checked={getSetting('seo.ab_testing.enabled', false)}
          onChange={(e) => updateSetting('seo.ab_testing.enabled', e.target.checked)}
        />
        Enable A/B Testing
      </label>
      <div className="gcc-form-help">
        Test different headlines to improve click-through rates.
      </div>
    </div>

    {getSetting('seo.ab_testing.enabled') && (
      <div className="gcc-form-group">
        <label className="gcc-form-label">
          Default Test Duration (days)
        </label>
        <input
          type="number"
          className="gcc-form-input"
          min="1"
          max="30"
          value={getSetting('seo.ab_testing.default_duration', 7)}
          onChange={(e) => updateSetting('seo.ab_testing.default_duration', parseInt(e.target.value))}
        />
      </div>
    )}

    <div className="gcc-form-group">
      <label className="gcc-form-label">
        Search Region for Competitive Analysis
      </label>
      <select
        className="gcc-form-select"
        value={getSetting('seo.competitive_analysis.search_region', 'google.com')}
        onChange={(e) => updateSetting('seo.competitive_analysis.search_region', e.target.value)}
      >
        <option value="google.com">Google.com (Global)</option>
        <option value="google.co.uk">Google.co.uk (UK)</option>
        <option value="google.ca">Google.ca (Canada)</option>
        <option value="google.com.au">Google.com.au (Australia)</option>
        <option value="google.de">Google.de (Germany)</option>
        <option value="google.fr">Google.fr (France)</option>
      </select>
    </div>
  </div>
);

// UI/UX Settings Tab
const UIUXSettings = ({ getSetting, updateSetting }) => (
  <div className="gcc-settings-section">
    <h2>UI/UX Settings</h2>
    
    <div className="gcc-form-group">
      <label className="gcc-form-label">
        <input
          type="checkbox"
          className="gcc-form-checkbox"
          checked={getSetting('uiux.enabled', false)}
          onChange={(e) => updateSetting('uiux.enabled', e.target.checked)}
        />
        Enable UI/UX Module
      </label>
      <div className="gcc-form-help">
        Globally enable or disable custom CSS injection for UI/UX improvements.
      </div>
    </div>

    {getSetting('uiux.enabled') && (
      <>
        <div className="gcc-form-group">
          <label className="gcc-form-label">
            Primary Color
          </label>
          <input
            type="color"
            className="gcc-form-input"
            value={getSetting('uiux.colors.primary', '#0073aa')}
            onChange={(e) => updateSetting('uiux.colors.primary', e.target.value)}
          />
        </div>

        <div className="gcc-form-group">
          <label className="gcc-form-label">
            Secondary Color
          </label>
          <input
            type="color"
            className="gcc-form-input"
            value={getSetting('uiux.colors.secondary', '#005a87')}
            onChange={(e) => updateSetting('uiux.colors.secondary', e.target.value)}
          />
        </div>

        <div className="gcc-form-group">
          <button
            className="gcc-button gcc-button-secondary"
            onClick={() => {
              // This would trigger AI color palette suggestion
              alert('AI color palette suggestion would be implemented here');
            }}
          >
            Generate AI Color Palette
          </button>
        </div>
      </>
    )}
  </div>
);

// Content Settings Tab
const ContentSettings = ({ getSetting, updateSetting }) => (
  <div className="gcc-settings-section">
    <h2>Content Settings</h2>
    
    <div className="gcc-form-group">
      <label className="gcc-form-label">
        AI Writer Default Post Status
      </label>
      <select
        className="gcc-form-select"
        value={getSetting('content.ai_writer.default_status', 'draft')}
        onChange={(e) => updateSetting('content.ai_writer.default_status', e.target.value)}
      >
        <option value="draft">Draft</option>
        <option value="publish">Publish</option>
        <option value="private">Private</option>
      </select>
    </div>

    <div className="gcc-form-group">
      <label className="gcc-form-label">
        Default Tone for Twitter
      </label>
      <input
        type="text"
        className="gcc-form-input"
        value={getSetting('content.social_amplifier.twitter_tone', 'engaging')}
        onChange={(e) => updateSetting('content.social_amplifier.twitter_tone', e.target.value)}
        placeholder="engaging, casual, professional..."
      />
    </div>

    <div className="gcc-form-group">
      <label className="gcc-form-label">
        Default Tone for LinkedIn
      </label>
      <input
        type="text"
        className="gcc-form-input"
        value={getSetting('content.social_amplifier.linkedin_tone', 'professional')}
        onChange={(e) => updateSetting('content.social_amplifier.linkedin_tone', e.target.value)}
        placeholder="professional, authoritative, thought-leadership..."
      />
    </div>
  </div>
);

// System Settings Tab
const SystemSettings = ({ getSetting, updateSetting }) => (
  <div className="gcc-settings-section">
    <h2>System & Data Settings</h2>
    
    <div className="gcc-form-group">
      <label className="gcc-form-label">
        Backup Schedule
      </label>
      <select
        className="gcc-form-select"
        value={getSetting('system.backup.schedule', 'disabled')}
        onChange={(e) => updateSetting('system.backup.schedule', e.target.value)}
      >
        <option value="disabled">Disabled</option>
        <option value="daily">Daily</option>
        <option value="weekly">Weekly</option>
        <option value="monthly">Monthly</option>
      </select>
    </div>

    {getSetting('system.backup.schedule') !== 'disabled' && (
      <div className="gcc-form-group">
        <label className="gcc-form-label">
          Number of Backups to Retain
        </label>
        <input
          type="number"
          className="gcc-form-input"
          min="1"
          max="30"
          value={getSetting('system.backup.retention', 5)}
          onChange={(e) => updateSetting('system.backup.retention', parseInt(e.target.value))}
        />
      </div>
    )}

    <div className="gcc-form-group">
      <label className="gcc-form-label">
        <input
          type="checkbox"
          className="gcc-form-checkbox"
          checked={getSetting('system.logging.enabled', true)}
          onChange={(e) => updateSetting('system.logging.enabled', e.target.checked)}
        />
        Enable System Logging
      </label>
    </div>

    <div className="gcc-form-group">
      <label className="gcc-form-label">
        <input
          type="checkbox"
          className="gcc-form-checkbox"
          checked={getSetting('system.rate_limiting.auto_cooldown', true)}
          onChange={(e) => updateSetting('system.rate_limiting.auto_cooldown', e.target.checked)}
        />
        Enable Auto-Cooldown Mode
      </label>
      <div className="gcc-form-help">
        Automatically slow down API requests when rate limits are approached.
      </div>
    </div>
  </div>
);

export default Settings;