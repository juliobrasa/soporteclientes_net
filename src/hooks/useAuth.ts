// hooks/useAuth.ts
import { useState, useEffect, useCallback } from 'react';
import { laravelApi, User, LoginResponse } from '../services/laravelApi';

export interface AuthState {
  user: User | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  error: string | null;
}

export const useAuth = () => {
  const [authState, setAuthState] = useState<AuthState>({
    user: null,
    isAuthenticated: false,
    isLoading: true,
    error: null
  });

  // Inicializar el estado de autenticación
  useEffect(() => {
    const initAuth = async () => {
      try {
        setAuthState(prev => ({ ...prev, isLoading: true, error: null }));

        if (laravelApi.isAuthenticated()) {
          // Verificar si el token sigue siendo válido
          const response = await laravelApi.getCurrentUser();
          if (response.success && response.data) {
            setAuthState({
              user: response.data,
              isAuthenticated: true,
              isLoading: false,
              error: null
            });
          } else {
            // Token inválido, limpiar
            await laravelApi.logout();
            setAuthState({
              user: null,
              isAuthenticated: false,
              isLoading: false,
              error: 'Sesión expirada'
            });
          }
        } else {
          // No hay token, estado no autenticado
          setAuthState({
            user: null,
            isAuthenticated: false,
            isLoading: false,
            error: null
          });
        }
      } catch (error) {
        console.error('Error initializing auth:', error);
        setAuthState({
          user: null,
          isAuthenticated: false,
          isLoading: false,
          error: error instanceof Error ? error.message : 'Error de autenticación'
        });
      }
    };

    initAuth();
  }, []);

  // Login
  const login = useCallback(async (email: string, password: string): Promise<LoginResponse> => {
    try {
      setAuthState(prev => ({ ...prev, isLoading: true, error: null }));

      const response = await laravelApi.login(email, password);
      
      if (response.success) {
        setAuthState({
          user: response.user,
          isAuthenticated: true,
          isLoading: false,
          error: null
        });
      } else {
        setAuthState(prev => ({
          ...prev,
          isLoading: false,
          error: 'Credenciales incorrectas'
        }));
      }

      return response;
    } catch (error) {
      const errorMessage = error instanceof Error ? error.message : 'Error de login';
      setAuthState(prev => ({
        ...prev,
        isLoading: false,
        error: errorMessage
      }));
      throw error;
    }
  }, []);

  // Logout
  const logout = useCallback(async () => {
    try {
      setAuthState(prev => ({ ...prev, isLoading: true }));
      await laravelApi.logout();
      setAuthState({
        user: null,
        isAuthenticated: false,
        isLoading: false,
        error: null
      });
    } catch (error) {
      console.error('Error during logout:', error);
      // Aún así limpiar el estado local
      setAuthState({
        user: null,
        isAuthenticated: false,
        isLoading: false,
        error: null
      });
    }
  }, []);

  // Limpiar error
  const clearError = useCallback(() => {
    setAuthState(prev => ({ ...prev, error: null }));
  }, []);

  // Refresh user data
  const refreshUser = useCallback(async () => {
    if (!laravelApi.isAuthenticated()) return;

    try {
      const response = await laravelApi.getCurrentUser();
      if (response.success && response.data) {
        setAuthState(prev => ({ ...prev, user: response.data! }));
      }
    } catch (error) {
      console.error('Error refreshing user:', error);
    }
  }, []);

  return {
    ...authState,
    login,
    logout,
    clearError,
    refreshUser
  };
};