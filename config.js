const ENV = "development"; // Change to "development" for local testing

const CONFIG = {
  development: {
    BASE_URL: "./backend"
  },
  production: {
    BASE_URL: "https://sg-eventpark-gde0frhxe5ffeubv.northeurope-01.azurewebsites.net/backend"
  }
};

window.API_URL = CONFIG[ENV].BASE_URL;