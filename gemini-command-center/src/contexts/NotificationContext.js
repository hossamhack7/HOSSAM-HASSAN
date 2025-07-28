import React, { createContext, useContext, useReducer } from 'react';

// Notification types
export const NOTIFICATION_TYPES = {
  SUCCESS: 'success',
  ERROR: 'error',
  WARNING: 'warning',
  INFO: 'info',
};

// Initial state
const initialState = {
  notifications: [],
};

// Action types
const ACTIONS = {
  ADD_NOTIFICATION: 'ADD_NOTIFICATION',
  REMOVE_NOTIFICATION: 'REMOVE_NOTIFICATION',
  CLEAR_ALL: 'CLEAR_ALL',
};

// Reducer
const notificationReducer = (state, action) => {
  switch (action.type) {
    case ACTIONS.ADD_NOTIFICATION:
      return {
        ...state,
        notifications: [action.payload, ...state.notifications],
      };

    case ACTIONS.REMOVE_NOTIFICATION:
      return {
        ...state,
        notifications: state.notifications.filter(
          notification => notification.id !== action.payload
        ),
      };

    case ACTIONS.CLEAR_ALL:
      return {
        ...state,
        notifications: [],
      };

    default:
      return state;
  }
};

// Create context
const NotificationContext = createContext();

// Notification provider component
export const NotificationProvider = ({ children }) => {
  const [state, dispatch] = useReducer(notificationReducer, initialState);

  // Generate unique ID for notifications
  const generateId = () => {
    return Date.now().toString(36) + Math.random().toString(36).substr(2);
  };

  // Add notification
  const addNotification = (message, type = NOTIFICATION_TYPES.INFO, options = {}) => {
    const notification = {
      id: generateId(),
      message,
      type,
      timestamp: new Date().toISOString(),
      duration: options.duration || (type === NOTIFICATION_TYPES.ERROR ? 0 : 5000), // Errors don't auto-dismiss
      persistent: options.persistent || false,
      action: options.action || null,
      ...options,
    };

    dispatch({ type: ACTIONS.ADD_NOTIFICATION, payload: notification });

    // Auto-remove notification after duration (if not persistent and has duration)
    if (notification.duration > 0 && !notification.persistent) {
      setTimeout(() => {
        removeNotification(notification.id);
      }, notification.duration);
    }

    return notification.id;
  };

  // Remove notification
  const removeNotification = (id) => {
    dispatch({ type: ACTIONS.REMOVE_NOTIFICATION, payload: id });
  };

  // Clear all notifications
  const clearAll = () => {
    dispatch({ type: ACTIONS.CLEAR_ALL });
  };

  // Convenience methods for different notification types
  const showSuccess = (message, options = {}) => {
    return addNotification(message, NOTIFICATION_TYPES.SUCCESS, options);
  };

  const showError = (message, options = {}) => {
    return addNotification(message, NOTIFICATION_TYPES.ERROR, {
      persistent: true,
      ...options,
    });
  };

  const showWarning = (message, options = {}) => {
    return addNotification(message, NOTIFICATION_TYPES.WARNING, options);
  };

  const showInfo = (message, options = {}) => {
    return addNotification(message, NOTIFICATION_TYPES.INFO, options);
  };

  // Show API error with proper formatting
  const showApiError = (error, options = {}) => {
    let message = 'An unexpected error occurred';
    
    if (typeof error === 'string') {
      message = error;
    } else if (error?.message) {
      message = error.message;
    } else if (error?.data?.message) {
      message = error.data.message;
    }

    return showError(message, options);
  };

  // Show loading notification
  const showLoading = (message = 'Loading...', options = {}) => {
    return addNotification(message, NOTIFICATION_TYPES.INFO, {
      persistent: true,
      showSpinner: true,
      ...options,
    });
  };

  // Update existing notification
  const updateNotification = (id, updates) => {
    const notification = state.notifications.find(n => n.id === id);
    if (notification) {
      removeNotification(id);
      addNotification(updates.message || notification.message, updates.type || notification.type, {
        ...notification,
        ...updates,
        id: undefined, // Let it generate a new ID
      });
    }
  };

  // Show confirmation notification with action buttons
  const showConfirmation = (message, onConfirm, onCancel = null, options = {}) => {
    return addNotification(message, NOTIFICATION_TYPES.WARNING, {
      persistent: true,
      action: {
        type: 'confirmation',
        onConfirm,
        onCancel,
        confirmText: options.confirmText || 'Confirm',
        cancelText: options.cancelText || 'Cancel',
      },
      ...options,
    });
  };

  // Show notification with custom action
  const showWithAction = (message, actionText, onAction, type = NOTIFICATION_TYPES.INFO, options = {}) => {
    return addNotification(message, type, {
      action: {
        type: 'custom',
        text: actionText,
        onClick: onAction,
      },
      ...options,
    });
  };

  // Get notifications by type
  const getNotificationsByType = (type) => {
    return state.notifications.filter(notification => notification.type === type);
  };

  // Check if there are any error notifications
  const hasErrors = () => {
    return getNotificationsByType(NOTIFICATION_TYPES.ERROR).length > 0;
  };

  // Get the most recent notification
  const getLatestNotification = () => {
    return state.notifications[0] || null;
  };

  const value = {
    // State
    notifications: state.notifications,
    
    // Basic actions
    addNotification,
    removeNotification,
    clearAll,
    
    // Convenience methods
    showSuccess,
    showError,
    showWarning,
    showInfo,
    showApiError,
    showLoading,
    showConfirmation,
    showWithAction,
    
    // Utilities
    updateNotification,
    getNotificationsByType,
    hasErrors,
    getLatestNotification,
  };

  return (
    <NotificationContext.Provider value={value}>
      {children}
      <NotificationContainer />
    </NotificationContext.Provider>
  );
};

// Notification container component
const NotificationContainer = () => {
  const { notifications, removeNotification } = useNotification();

  if (notifications.length === 0) {
    return null;
  }

  return (
    <div className="gcc-notification-container">
      {notifications.map(notification => (
        <NotificationItem
          key={notification.id}
          notification={notification}
          onRemove={() => removeNotification(notification.id)}
        />
      ))}
    </div>
  );
};

// Individual notification component
const NotificationItem = ({ notification, onRemove }) => {
  const handleActionClick = (action) => {
    if (action.type === 'confirmation') {
      // Handle confirmation actions
      if (action.onConfirm) {
        action.onConfirm();
      }
      onRemove();
    } else if (action.type === 'custom') {
      // Handle custom actions
      if (action.onClick) {
        action.onClick();
      }
      if (!notification.persistent) {
        onRemove();
      }
    }
  };

  const getIconForType = (type) => {
    switch (type) {
      case NOTIFICATION_TYPES.SUCCESS:
        return '✓';
      case NOTIFICATION_TYPES.ERROR:
        return '✕';
      case NOTIFICATION_TYPES.WARNING:
        return '⚠';
      case NOTIFICATION_TYPES.INFO:
      default:
        return 'ℹ';
    }
  };

  return (
    <div className={`gcc-notification gcc-notification-${notification.type}`}>
      <div className="gcc-notification-content">
        <div className="gcc-notification-icon">
          {notification.showSpinner ? (
            <div className="gcc-spinner"></div>
          ) : (
            getIconForType(notification.type)
          )}
        </div>
        
        <div className="gcc-notification-message">
          {notification.message}
        </div>
        
        <div className="gcc-notification-actions">
          {notification.action && (
            <>
              {notification.action.type === 'confirmation' ? (
                <>
                  <button
                    className="gcc-button gcc-button-small gcc-button-success"
                    onClick={() => handleActionClick(notification.action)}
                  >
                    {notification.action.confirmText}
                  </button>
                  <button
                    className="gcc-button gcc-button-small gcc-button-secondary"
                    onClick={onRemove}
                  >
                    {notification.action.cancelText}
                  </button>
                </>
              ) : (
                <button
                  className="gcc-button gcc-button-small"
                  onClick={() => handleActionClick(notification.action)}
                >
                  {notification.action.text}
                </button>
              )}
            </>
          )}
          
          {!notification.persistent && (
            <button
              className="gcc-notification-close"
              onClick={onRemove}
              aria-label="Close notification"
            >
              ✕
            </button>
          )}
        </div>
      </div>
      
      {notification.duration > 0 && !notification.persistent && (
        <div
          className="gcc-notification-progress"
          style={{
            animationDuration: `${notification.duration}ms`,
          }}
        />
      )}
    </div>
  );
};

// Hook to use notification context
export const useNotification = () => {
  const context = useContext(NotificationContext);
  
  if (!context) {
    throw new Error('useNotification must be used within a NotificationProvider');
  }
  
  return context;
};

// HOC for components that need notifications
export const withNotification = (Component) => {
  return function NotificationWrappedComponent(props) {
    return (
      <NotificationProvider>
        <Component {...props} />
      </NotificationProvider>
    );
  };
};

export default NotificationContext;