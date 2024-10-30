const ENV = "development"; // Default to "development" for local testing

const CONFIG = {
  development: {
    BASE_URL: "backend"
  },
  production: {
    BASE_URL: "__PRODUCTION_BASE_URL__" // Placeholder to be replaced in GitHub Actions
  }
};

// Set API_URL based on the current environment
window.API_URL = CONFIG[ENV].BASE_URL;