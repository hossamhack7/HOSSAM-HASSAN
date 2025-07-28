// API utility functions for making requests to the WordPress REST API

/**
 * Base API configuration
 */
const API_CONFIG = {
  baseURL: window.geminiCCAdmin?.restUrl || '/wp-json/gemini-cc/v1/',
  nonce: window.geminiCCAdmin?.restNonce || '',
  timeout: 30000,
};

/**
 * Make an authenticated API request
 * @param {string} endpoint - API endpoint (without base URL)
 * @param {object} options - Request options
 * @returns {Promise} Response data
 */
export const apiRequest = async (endpoint, options = {}) => {
  const {
    method = 'GET',
    data = null,
    headers = {},
    timeout = API_CONFIG.timeout,
  } = options;

  const url = `${API_CONFIG.baseURL}${endpoint.replace(/^\//, '')}`;
  
  const requestHeaders = {
    'Content-Type': 'application/json',
    'X-WP-Nonce': API_CONFIG.nonce,
    ...headers,
  };

  const requestOptions = {
    method,
    headers: requestHeaders,
    credentials: 'same-origin',
  };

  if (data && method !== 'GET') {
    requestOptions.body = JSON.stringify(data);
  }

  try {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), timeout);
    
    requestOptions.signal = controller.signal;

    const response = await fetch(url, requestOptions);
    
    clearTimeout(timeoutId);

    if (!response.ok) {
      const errorData = await response.json().catch(() => ({}));
      throw new Error(
        errorData.message || 
        `HTTP ${response.status}: ${response.statusText}`
      );
    }

    const responseData = await response.json();
    return responseData;

  } catch (error) {
    if (error.name === 'AbortError') {
      throw new Error('Request timeout. Please try again.');
    }
    
    console.error('API Request Error:', error);
    throw error;
  }
};

/**
 * GET request helper
 * @param {string} endpoint - API endpoint
 * @param {object} options - Request options
 * @returns {Promise} Response data
 */
export const apiGet = (endpoint, options = {}) => {
  return apiRequest(endpoint, { ...options, method: 'GET' });
};

/**
 * POST request helper
 * @param {string} endpoint - API endpoint
 * @param {object} data - Request data
 * @param {object} options - Request options
 * @returns {Promise} Response data
 */
export const apiPost = (endpoint, data, options = {}) => {
  return apiRequest(endpoint, { ...options, method: 'POST', data });
};

/**
 * PUT request helper
 * @param {string} endpoint - API endpoint
 * @param {object} data - Request data
 * @param {object} options - Request options
 * @returns {Promise} Response data
 */
export const apiPut = (endpoint, data, options = {}) => {
  return apiRequest(endpoint, { ...options, method: 'PUT', data });
};

/**
 * DELETE request helper
 * @param {string} endpoint - API endpoint
 * @param {object} options - Request options
 * @returns {Promise} Response data
 */
export const apiDelete = (endpoint, options = {}) => {
  return apiRequest(endpoint, { ...options, method: 'DELETE' });
};

/**
 * Upload file helper
 * @param {string} endpoint - API endpoint
 * @param {FormData} formData - Form data with file
 * @param {object} options - Request options
 * @returns {Promise} Response data
 */
export const apiUpload = async (endpoint, formData, options = {}) => {
  const url = `${API_CONFIG.baseURL}${endpoint.replace(/^\//, '')}`;
  
  const requestHeaders = {
    'X-WP-Nonce': API_CONFIG.nonce,
    ...options.headers,
  };
  
  // Don't set Content-Type for FormData - let browser set it with boundary
  delete requestHeaders['Content-Type'];

  const requestOptions = {
    method: 'POST',
    headers: requestHeaders,
    body: formData,
    credentials: 'same-origin',
  };

  try {
    const response = await fetch(url, requestOptions);

    if (!response.ok) {
      const errorData = await response.json().catch(() => ({}));
      throw new Error(
        errorData.message || 
        `HTTP ${response.status}: ${response.statusText}`
      );
    }

    const responseData = await response.json();
    return responseData;

  } catch (error) {
    console.error('API Upload Error:', error);
    throw error;
  }
};

/**
 * Test API connection
 * @returns {Promise} Connection test result
 */
export const testApiConnection = () => {
  return apiGet('status');
};

/**
 * Test Gemini API connection
 * @param {string} apiKey - Gemini API key
 * @returns {Promise} Connection test result
 */
export const testGeminiConnection = (apiKey) => {
  return apiPost('test-connection', { api_key: apiKey });
};

/**
 * Get plugin settings
 * @returns {Promise} Settings object
 */
export const getSettings = () => {
  return apiGet('settings');
};

/**
 * Save plugin settings
 * @param {object} settings - Settings object
 * @returns {Promise} Save result
 */
export const saveSettings = (settings) => {
  return apiPost('settings', settings);
};

/**
 * Get system health status
 * @returns {Promise} Health check results
 */
export const getSystemHealth = () => {
  return apiGet('system/health-check');
};

/**
 * Get system logs
 * @param {object} params - Query parameters
 * @returns {Promise} Logs data
 */
export const getSystemLogs = (params = {}) => {
  const queryString = new URLSearchParams(params).toString();
  const endpoint = queryString ? `system/logs?${queryString}` : 'system/logs';
  return apiGet(endpoint);
};

/**
 * Clear system logs
 * @returns {Promise} Clear result
 */
export const clearSystemLogs = () => {
  return apiPost('system/clear-logs');
};

/**
 * Create backup
 * @param {string} type - Backup type ('database' or 'full')
 * @returns {Promise} Backup result
 */
export const createBackup = (type) => {
  return apiPost('backup/create', { type });
};

/**
 * List backups
 * @returns {Promise} Backup list
 */
export const listBackups = () => {
  return apiGet('backup/list');
};

/**
 * Download backup
 * @param {string} filename - Backup filename
 * @returns {Promise} Download URL
 */
export const downloadBackup = (filename) => {
  return apiGet(`backup/download/${filename}`);
};

/**
 * Restore backup
 * @param {string} filename - Backup filename
 * @returns {Promise} Restore result
 */
export const restoreBackup = (filename) => {
  return apiPost('backup/restore', { filename });
};

/**
 * Generate content with AI
 * @param {object} params - Generation parameters
 * @returns {Promise} Generated content
 */
export const generateContent = (params) => {
  return apiPost('content/generate-article', params);
};

/**
 * Start A/B test
 * @param {object} params - Test parameters
 * @returns {Promise} Test result
 */
export const startABTest = (params) => {
  return apiPost('content/start-ab-test', params);
};

/**
 * Amplify content for social media
 * @param {object} params - Amplification parameters
 * @returns {Promise} Amplified content
 */
export const amplifyContent = (params) => {
  return apiPost('content/amplify', params);
};

/**
 * Run SEO technical audit
 * @returns {Promise} Audit results
 */
export const runTechnicalAudit = () => {
  return apiGet('seo/technical-audit');
};

/**
 * Generate content cluster
 * @param {string} pillarTopic - Main topic
 * @returns {Promise} Content cluster
 */
export const generateContentCluster = (pillarTopic) => {
  return apiPost('seo/generate-cluster', { pillar_topic: pillarTopic });
};

/**
 * Analyze competitor
 * @param {string} url - Competitor URL
 * @returns {Promise} Analysis results
 */
export const analyzeCompetitor = (url) => {
  return apiPost('seo/analyze-competitor', { url });
};

/**
 * Get orphan pages
 * @returns {Promise} Orphan pages list
 */
export const getOrphanPages = () => {
  return apiGet('links/orphan-pages');
};

/**
 * Create internal link
 * @param {object} params - Link parameters
 * @returns {Promise} Link creation result
 */
export const createInternalLink = (params) => {
  return apiPost('links/create', params);
};

/**
 * Analyze design
 * @returns {Promise} Design analysis
 */
export const analyzeDesign = () => {
  return apiPost('uiux/analyze-design');
};

/**
 * Suggest color palette
 * @param {string} primaryColor - Primary color hex
 * @returns {Promise} Color palette suggestions
 */
export const suggestColorPalette = (primaryColor) => {
  return apiPost('uiux/suggest-palette', { primary_color: primaryColor });
};

/**
 * Save UI styles
 * @param {object} styles - Style configurations
 * @returns {Promise} Save result
 */
export const saveUIStyles = (styles) => {
  return apiPost('uiux/save-styles', styles);
};

/**
 * Get KPI summary
 * @returns {Promise} KPI data
 */
export const getKPISummary = () => {
  return apiGet('reports/kpi-summary');
};

/**
 * Get impact analysis
 * @returns {Promise} Impact analysis data
 */
export const getImpactAnalysis = () => {
  return apiGet('reports/impact-analysis');
};

/**
 * Converse with AI agent
 * @param {object} params - Conversation parameters
 * @returns {Promise} AI response
 */
export const converseWithAI = (params) => {
  return apiPost('agent/converse', params);
};

/**
 * Error handler utility
 * @param {Error} error - Error object
 * @returns {string} User-friendly error message
 */
export const handleApiError = (error) => {
  if (error.message) {
    return error.message;
  }
  
  if (typeof error === 'string') {
    return error;
  }
  
  return 'An unexpected error occurred. Please try again.';
};

/**
 * Retry wrapper for API requests
 * @param {Function} apiFunction - API function to retry
 * @param {number} maxRetries - Maximum number of retries
 * @param {number} delay - Delay between retries in ms
 * @returns {Promise} API result
 */
export const withRetry = async (apiFunction, maxRetries = 3, delay = 1000) => {
  let lastError;
  
  for (let i = 0; i <= maxRetries; i++) {
    try {
      return await apiFunction();
    } catch (error) {
      lastError = error;
      
      if (i < maxRetries) {
        await new Promise(resolve => setTimeout(resolve, delay * (i + 1)));
      }
    }
  }
  
  throw lastError;
};