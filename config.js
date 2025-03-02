// Auto-detect environment based on hostname
const isLocalhost = window.location.hostname === "localhost" || window.location.hostname === "127.0.0.1";
const ENV = isLocalhost ? "development" : "production";

const CONFIG = {
  development: {
    BASE_URL: "backend" // For local development
  },
  production: {
    BASE_URL: "__PRODUCTION_BASE_URL__" // Relative URL that should work if frontend and backend are on same domain
  }
};

// Set API_URL based on the current environment
window.API_URL = CONFIG[ENV].BASE_URL;