import * as Keychain from 'react-native-keychain';

const SERVICE = 'cadeostimes.sanctum';

export const secureToken = {
  async save(token: string): Promise<void> {
    await Keychain.setGenericPassword('token', token, {service: SERVICE});
  },

  async load(): Promise<string | null> {
    const creds = await Keychain.getGenericPassword({service: SERVICE});
    return creds ? creds.password : null;
  },

  async clear(): Promise<void> {
    await Keychain.resetGenericPassword({service: SERVICE});
  },
};
