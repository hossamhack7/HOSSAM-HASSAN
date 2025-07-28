import React from 'react';
import { createRoot } from 'react-dom/client';
import App from './App';
import './index.css';

// Ensure the root element exists
const rootElement = document.getElementById('gcc-react-root');

if (rootElement) {
  const root = createRoot(rootElement);
  root.render(<App />);
} else {
  console.error('Gemini Command Center: Root element not found. Make sure the WordPress admin page includes <div id="gcc-react-root"></div>');
}