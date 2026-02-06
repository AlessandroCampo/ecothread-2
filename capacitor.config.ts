import type { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'com.ecothread.app',
  appName: 'EcoThread',
  webDir: 'dist',
  server: {
    url: 'https://ecothread-2-production.up.railway.app',
    cleartext: true,
  },
  plugins: {
    App: {
      url: 'ecothread',
    },
  },
};

export default config;
