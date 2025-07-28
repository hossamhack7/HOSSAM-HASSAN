import React from 'react';

const OnboardingTour = ({ isFirstTime, onComplete }) => {
  if (!isFirstTime) return null;

  return (
    <div className="gcc-onboarding-overlay">
      <div className="gcc-onboarding-modal">
        <div className="gcc-onboarding-content">
          <h2>🚀 Welcome to Gemini Command Center!</h2>
          <p>
            This powerful WordPress plugin uses Google's Gemini AI to help you manage, 
            optimize, and grow your website. Let's get you started!
          </p>
          
          <div className="gcc-onboarding-steps">
            <div className="gcc-onboarding-step">
              <div className="gcc-step-number">1</div>
              <div className="gcc-step-content">
                <h3>Configure API Key</h3>
                <p>Add your Google Gemini API key in Settings to unlock AI features</p>
              </div>
            </div>
            
            <div className="gcc-onboarding-step">
              <div className="gcc-step-number">2</div>
              <div className="gcc-step-content">
                <h3>Explore Features</h3>
                <p>Check out SEO tools, content generation, and backup options</p>
              </div>
            </div>
            
            <div className="gcc-onboarding-step">
              <div className="gcc-step-number">3</div>
              <div className="gcc-step-content">
                <h3>Chat with AI</h3>
                <p>Use the AI Agent for intelligent assistance and recommendations</p>
              </div>
            </div>
          </div>
          
          <div className="gcc-onboarding-actions">
            <button
              className="gcc-button gcc-button-secondary"
              onClick={onComplete}
            >
              Skip Tour
            </button>
            <button
              className="gcc-button gcc-button-primary"
              onClick={() => {
                onComplete();
                window.location.hash = '#/settings';
              }}
            >
              Get Started
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default OnboardingTour;