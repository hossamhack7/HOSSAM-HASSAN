import React from 'react';

const LoadingSpinner = ({ size = 'medium', message = '' }) => {
  const sizeClasses = {
    small: 'gcc-spinner-small',
    medium: 'gcc-spinner-medium',
    large: 'gcc-spinner-large',
  };

  return (
    <div className="gcc-loading-container">
      <div className={`gcc-spinner ${sizeClasses[size] || sizeClasses.medium}`}></div>
      {message && <p className="gcc-loading-message">{message}</p>}
    </div>
  );
};

export default LoadingSpinner;