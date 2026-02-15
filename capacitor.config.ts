import type { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'com.fruttago.app',
  appName: 'Frutta Go',
  webDir: 'public',
  server: {
    url: 'https://peru-dove-134611.hostingersite.com',
    cleartext: false
  }
};

export default config;
