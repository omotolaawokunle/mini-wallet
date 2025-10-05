import type { AxiosInstance } from 'axios';
import type Pusher from 'pusher-js';

declare global {
  interface Window {
    axios: AxiosInstance;
    Pusher: typeof Pusher;
  }
}

export {};

