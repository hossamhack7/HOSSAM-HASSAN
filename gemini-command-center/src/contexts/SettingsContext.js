import React, { createContext, useContext, useReducer, useEffect } from 'react';
import { getSettings, saveSettings } from '../utils/api';

// Initial state
const initialState = {
  settings: {},
  loading: true,
  saving: false,
  error: null,
  lastSaved: null,
};

// Action types
const ACTIONS = {
  LOAD_START: 'LOAD_START',
  LOAD_SUCCESS: 'LOAD_SUCCESS',
  LOAD_ERROR: 'LOAD_ERROR',
  SAVE_START: 'SAVE_START',
  SAVE_SUCCESS: 'SAVE_SUCCESS',
  SAVE_ERROR: 'SAVE_ERROR',
  UPDATE_SETTING: 'UPDATE_SETTING',
  RESET_ERROR: 'RESET_ERROR',
};

// Reducer
const settingsReducer = (state, action) => {
  switch (action.type) {
    case ACTIONS.LOAD_START:
      return {
        ...state,
        loading: true,
        error: null,
      };

    case ACTIONS.LOAD_SUCCESS:
      return {
        ...state,
        loading: false,
        settings: action.payload,
        error: null,
      };

    case ACTIONS.LOAD_ERROR:
      return {
        ...state,
        loading: false,
        error: action.payload,
      };

    case ACTIONS.SAVE_START:
      return {
        ...state,
        saving: true,
        error: null,
      };

    case ACTIONS.SAVE_SUCCESS:
      return {
        ...state,
        saving: false,
        settings: action.payload,
        lastSaved: new Date().toISOString(),
        error: null,
      };

    case ACTIONS.SAVE_ERROR:
      return {
        ...state,
        saving: false,
        error: action.payload,
      };

    case ACTIONS.UPDATE_SETTING:
      return {
        ...state,
        settings: updateNestedSetting(state.settings, action.path, action.value),
      };

    case ACTIONS.RESET_ERROR:
      return {
        ...state,
        error: null,
      };

    default:
      return state;
  }
};

// Helper function to update nested settings
const updateNestedSetting = (settings, path, value) => {
  const keys = path.split('.');
  const result = { ...settings };
  let current = result;

  for (let i = 0; i < keys.length - 1; i++) {
    const key = keys[i];
    if (!current[key] || typeof current[key] !== 'object') {
      current[key] = {};
    } else {
      current[key] = { ...current[key] };
    }
    current = current[key];
  }

  current[keys[keys.length - 1]] = value;
  return result;
};

// Helper function to get nested setting value
const getNestedSetting = (settings, path, defaultValue = null) => {
  const keys = path.split('.');
  let current = settings;

  for (const key of keys) {
    if (current && typeof current === 'object' && key in current) {
      current = current[key];
    } else {
      return defaultValue;
    }
  }

  return current;
};

// Create context
const SettingsContext = createContext();

// Settings provider component
export const SettingsProvider = ({ children }) => {
  const [state, dispatch] = useReducer(settingsReducer, initialState);

  // Load settings on mount
  useEffect(() => {
    loadSettings();
  }, []);

  // Load settings from API
  const loadSettings = async () => {
    dispatch({ type: ACTIONS.LOAD_START });
    
    try {
      const settings = await getSettings();
      dispatch({ type: ACTIONS.LOAD_SUCCESS, payload: settings });
    } catch (error) {
      dispatch({ type: ACTIONS.LOAD_ERROR, payload: error.message });
    }
  };

  // Save settings to API
  const saveSettingsData = async (settingsToSave = null) => {
    const settings = settingsToSave || state.settings;
    dispatch({ type: ACTIONS.SAVE_START });
    
    try {
      const result = await saveSettings(settings);
      dispatch({ type: ACTIONS.SAVE_SUCCESS, payload: settings });
      return result;
    } catch (error) {
      dispatch({ type: ACTIONS.SAVE_ERROR, payload: error.message });
      throw error;
    }
  };

  // Update a specific setting
  const updateSetting = (path, value) => {
    dispatch({ type: ACTIONS.UPDATE_SETTING, path, value });
  };

  // Get a specific setting value
  const getSetting = (path, defaultValue = null) => {
    return getNestedSetting(state.settings, path, defaultValue);
  };

  // Reset error state
  const resetError = () => {
    dispatch({ type: ACTIONS.RESET_ERROR });
  };

  // Check if settings have changes
  const hasUnsavedChanges = () => {
    // This would need to compare with original settings
    // For now, we'll assume changes exist if lastSaved is null or old
    return !state.lastSaved || state.saving;
  };

  // Bulk update settings
  const updateSettings = (newSettings) => {
    Object.keys(newSettings).forEach(section => {
      if (typeof newSettings[section] === 'object') {
        Object.keys(newSettings[section]).forEach(key => {
          const path = `${section}.${key}`;
          const value = newSettings[section][key];
          
          if (typeof value === 'object') {
            Object.keys(value).forEach(subKey => {
              updateSetting(`${path}.${subKey}`, value[subKey]);
            });
          } else {
            updateSetting(path, value);
          }
        });
      } else {
        updateSetting(section, newSettings[section]);
      }
    });
  };

  // Validate settings
  const validateSettings = () => {
    const errors = [];
    
    // Check required API key
    if (!getSetting('general.api_key')) {
      errors.push('Gemini API key is required');
    }
    
    // Validate email settings if enabled
    if (getSetting('system.notifications.email_reports')) {
      const adminEmail = getSetting('system.notifications.admin_email');
      if (!adminEmail || !adminEmail.includes('@')) {
        errors.push('Valid admin email is required for email reports');
      }
    }
    
    // Validate backup settings
    const backupSchedule = getSetting('system.backup.schedule');
    if (backupSchedule !== 'disabled') {
      const retention = getSetting('system.backup.retention');
      if (!retention || retention < 1) {
        errors.push('Backup retention must be at least 1');
      }
    }
    
    // Validate rate limiting
    const rateLimit = getSetting('system.rate_limiting.requests_per_minute');
    if (rateLimit < 1 || rateLimit > 60) {
      errors.push('Rate limit must be between 1 and 60 requests per minute');
    }
    
    return errors;
  };

  // Export settings
  const exportSettings = () => {
    const dataStr = JSON.stringify(state.settings, null, 2);
    const dataBlob = new Blob([dataStr], { type: 'application/json' });
    const url = URL.createObjectURL(dataBlob);
    
    const link = document.createElement('a');
    link.href = url;
    link.download = `gemini-cc-settings-${new Date().toISOString().split('T')[0]}.json`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    URL.revokeObjectURL(url);
  };

  // Import settings
  const importSettings = (file) => {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      
      reader.onload = (event) => {
        try {
          const importedSettings = JSON.parse(event.target.result);
          
          // Validate imported settings structure
          if (typeof importedSettings !== 'object') {
            throw new Error('Invalid settings file format');
          }
          
          // Update settings with imported data
          updateSettings(importedSettings);
          
          resolve(importedSettings);
        } catch (error) {
          reject(new Error('Failed to parse settings file: ' + error.message));
        }
      };
      
      reader.onerror = () => {
        reject(new Error('Failed to read settings file'));
      };
      
      reader.readAsText(file);
    });
  };

  // Reset settings to defaults
  const resetToDefaults = async () => {
    // This would need to call an API endpoint to get default settings
    // For now, we'll reload settings from the server
    await loadSettings();
  };

  const value = {
    // State
    settings: state.settings,
    loading: state.loading,
    saving: state.saving,
    error: state.error,
    lastSaved: state.lastSaved,
    
    // Actions
    loadSettings,
    saveSettings: saveSettingsData,
    updateSetting,
    updateSettings,
    getSetting,
    resetError,
    
    // Utilities
    hasUnsavedChanges,
    validateSettings,
    exportSettings,
    importSettings,
    resetToDefaults,
  };

  return (
    <SettingsContext.Provider value={value}>
      {children}
    </SettingsContext.Provider>
  );
};

// Hook to use settings context
export const useSettings = () => {
  const context = useContext(SettingsContext);
  
  if (!context) {
    throw new Error('useSettings must be used within a SettingsProvider');
  }
  
  return context;
};

// HOC for components that need settings
export const withSettings = (Component) => {
  return function SettingsWrappedComponent(props) {
    return (
      <SettingsProvider>
        <Component {...props} />
      </SettingsProvider>
    );
  };
};

export default SettingsContext;