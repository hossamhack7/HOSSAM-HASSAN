import React, { useState, useEffect } from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import './App.css';

// Import components
import Dashboard from './components/Dashboard/Dashboard';
import Settings from './components/Settings/Settings';
import SEOCenter from './components/SEO/SEOCenter';
import ContentCenter from './components/Content/ContentCenter';
import UIUXCenter from './components/UIUX/UIUXCenter';
import BackupCenter from './components/Backup/BackupCenter';
import ReportsCenter from './components/Reports/ReportsCenter';
import AIAgent from './components/AIAgent/AIAgent';
import Navigation from './components/Navigation/Navigation';
import LoadingSpinner from './components/UI/LoadingSpinner';
import OnboardingTour from './components/Onboarding/OnboardingTour';

// Import utilities
import { apiRequest } from './utils/api';
import { SettingsProvider } from './contexts/SettingsContext';
import { NotificationProvider } from './contexts/NotificationContext';

function App() {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [isFirstTime, setIsFirstTime] = useState(false);
  const [showOnboarding, setShowOnboarding] = useState(false);

  useEffect(() => {
    initializeApp();
  }, []);

  const initializeApp = async () => {
    try {
      // Check plugin status
      const statusResponse = await apiRequest('/status');
      
      if (statusResponse.status === 'ok') {
        // Check if this is the first time user is using the plugin
        const settings = await apiRequest('/settings');
        const hasApiKey = settings?.general?.api_key;
        
        if (!hasApiKey) {
          setIsFirstTime(true);
          setShowOnboarding(true);
        }
      }
    } catch (err) {
      setError('Failed to initialize the plugin. Please refresh the page.');
      console.error('App initialization error:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleOnboardingComplete = () => {
    setShowOnboarding(false);
    setIsFirstTime(false);
  };

  if (loading) {
    return (
      <div className="gcc-app-loading">
        <LoadingSpinner />
        <p>Loading Gemini Command Center...</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="gcc-app-error">
        <h2>Oops! Something went wrong</h2>
        <p>{error}</p>
        <button onClick={() => window.location.reload()}>
          Retry
        </button>
      </div>
    );
  }

  return (
    <NotificationProvider>
      <SettingsProvider>
        <div className="gcc-app">
          <Router>
            <Navigation />
            <main className="gcc-main-content">
              <Routes>
                <Route path="/" element={<Dashboard />} />
                <Route path="/dashboard" element={<Dashboard />} />
                <Route path="/settings" element={<Settings />} />
                <Route path="/seo/*" element={<SEOCenter />} />
                <Route path="/content/*" element={<ContentCenter />} />
                <Route path="/uiux/*" element={<UIUXCenter />} />
                <Route path="/backup" element={<BackupCenter />} />
                <Route path="/reports/*" element={<ReportsCenter />} />
                <Route path="/ai-agent" element={<AIAgent />} />
                <Route path="*" element={<Navigate to="/dashboard" replace />} />
              </Routes>
            </main>
          </Router>
          
          {showOnboarding && (
            <OnboardingTour
              isFirstTime={isFirstTime}
              onComplete={handleOnboardingComplete}
            />
          )}
        </div>
      </SettingsProvider>
    </NotificationProvider>
  );
}

export default App;