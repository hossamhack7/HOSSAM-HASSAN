import React from 'react';

const HelloWorld = () => {
  return (
    <div style={{
      padding: '20px',
      textAlign: 'center',
      backgroundColor: '#f0f8ff',
      border: '2px solid #4a90e2',
      borderRadius: '8px',
      margin: '20px',
      fontFamily: 'Arial, sans-serif'
    }}>
      <h1 style={{ color: '#4a90e2', marginBottom: '10px' }}>
        🚀 Hello World from Gemini Command Center!
      </h1>
      <p style={{ color: '#666', fontSize: '16px' }}>
        The React framework is successfully loaded and running.
      </p>
      <p style={{ color: '#888', fontSize: '14px', marginTop: '10px' }}>
        This proves that the WordPress plugin ↔ React integration is working perfectly.
      </p>
    </div>
  );
};

export default HelloWorld;