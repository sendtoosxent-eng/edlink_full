import React, { createContext, useContext, useEffect, useState } from 'react';
import * as SecureStore from 'expo-secure-store';
import { Platform } from 'react-native';
import { api } from '../api';
import type { User } from '../types';

const TOKEN_KEY = 'edlink.mobile.token';

interface AuthContextType {
  token?: string;
  user?: User;
  booting: boolean;
  signIn: (school_number: string, email: string, password: string) => Promise<void>;
  signOut: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [token, setToken] = useState<string>();
  const [user, setUser] = useState<User>();
  const [booting, setBooting] = useState(true);

  useEffect(() => {
    let isMounted = true;
    (async () => {
      try {
        const storedToken = await SecureStore.getItemAsync(TOKEN_KEY);
        if (storedToken) {
          const response = await api.me(storedToken);
          if (isMounted) {
            setToken(storedToken);
            setUser(response.data);
          }
        }
      } catch {
        await SecureStore.deleteItemAsync(TOKEN_KEY);
      } finally {
        if (isMounted) setBooting(false);
      }
    })();
    return () => { isMounted = false; };
  }, []);

  const signIn = async (school_number: string, email: string, password: string) => {
    const { data } = await api.login({
      school_number,
      email,
      password,
      device_name: `${Platform.OS} Edlink App`,
    });
    await SecureStore.setItemAsync(TOKEN_KEY, data.token);
    setToken(data.token);
    setUser(data.user);
  };

  const signOut = async () => {
    if (token) {
      await api.logout(token).catch(() => undefined);
    }
    await SecureStore.deleteItemAsync(TOKEN_KEY);
    setToken(undefined);
    setUser(undefined);
  };

  return (
    <AuthContext.Provider value={{ token, user, booting, signIn, signOut }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) throw new Error('useAuth must be used within an AuthProvider');
  return context;
};