import './bootstrap';

import { createApp } from "vue";
import { createPinia } from "pinia";
import App from "./App.vue";

import axios from "axios";

axios.defaults.baseURL = import.meta.env.VITE_API_URL;
axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;
axios.defaults.headers.common['Accept'] = 'application/json';
axios.defaults.headers.common['Content-Type'] = 'application/json';

const app = createApp(App);
app.use(createPinia());
app.mount("#app");
