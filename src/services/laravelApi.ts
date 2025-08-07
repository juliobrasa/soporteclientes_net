// services/laravelApi.ts

export interface User {
  id: number;
  name: string;
  email: string;
  is_admin: boolean;
  created_at?: string;
  updated_at?: string;
}

export interface Hotel {
  id: number;
  nombre_hotel: string;
  hoja_destino: string;
  url_booking: string;
  max_reviews: number;
  activo: boolean;
  total_reviews?: number;
  avg_rating?: number;
  status_text?: string;
  created_at?: string;
  updated_at?: string;
}

export interface LoginResponse {
  success: boolean;
  user: User;
  token: string;
  token_type: string;
}

export interface ApiResponse<T> {
  success: boolean;
  data?: T;
  message?: string;
  error?: string;
}

export interface HotelListResponse {
  success: boolean;
  hotels: Hotel[];
  total: number;
  message: string;
}

class LaravelApiService {
  private baseUrl: string;
  private token: string | null = null;

  constructor() {
    // En desarrollo apuntamos a Laravel serve, en producción a la API
    this.baseUrl = process.env.NODE_ENV === 'production' 
      ? '/api' 
      : 'http://localhost:8000/api';
      
    // Recuperar token del localStorage si existe
    this.token = localStorage.getItem('auth_token');
  }

  private getHeaders(): HeadersInit {
    const headers: HeadersInit = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };

    if (this.token) {
      headers['Authorization'] = `Bearer ${this.token}`;
    }

    return headers;
  }

  private async handleResponse<T>(response: Response): Promise<T> {
    if (!response.ok) {
      if (response.status === 401) {
        this.logout(); // Auto logout on 401
        throw new Error('Sesión expirada');
      }
      
      const errorData = await response.json().catch(() => ({}));
      throw new Error(errorData.message || `HTTP Error: ${response.status}`);
    }

    return response.json();
  }

  // ================================================================
  // AUTENTICACIÓN
  // ================================================================

  async login(email: string, password: string): Promise<LoginResponse> {
    const response = await fetch(`${this.baseUrl}/auth/login`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify({ email, password }),
    });

    const data = await this.handleResponse<LoginResponse>(response);
    
    if (data.success && data.token) {
      this.token = data.token;
      localStorage.setItem('auth_token', data.token);
      localStorage.setItem('user_data', JSON.stringify(data.user));
    }

    return data;
  }

  async logout(): Promise<void> {
    try {
      if (this.token) {
        await fetch(`${this.baseUrl}/auth/logout`, {
          method: 'POST',
          headers: this.getHeaders(),
        });
      }
    } catch (error) {
      console.warn('Error during logout:', error);
    } finally {
      this.token = null;
      localStorage.removeItem('auth_token');
      localStorage.removeItem('user_data');
    }
  }

  async getCurrentUser(): Promise<ApiResponse<User>> {
    if (!this.token) {
      throw new Error('No hay sesión activa');
    }

    const response = await fetch(`${this.baseUrl}/auth/me`, {
      method: 'GET',
      headers: this.getHeaders(),
    });

    return this.handleResponse<ApiResponse<User>>(response);
  }

  // ================================================================
  // HOTELES
  // ================================================================

  async getHotels(params?: {
    search?: string;
    active?: boolean;
    page?: number;
    limit?: number;
  }): Promise<HotelListResponse> {
    const queryParams = new URLSearchParams();
    
    if (params?.search) queryParams.append('search', params.search);
    if (params?.active !== undefined) queryParams.append('active', params.active.toString());
    if (params?.page) queryParams.append('page', params.page.toString());
    if (params?.limit) queryParams.append('limit', params.limit.toString());

    const url = `${this.baseUrl}/hotels${queryParams.toString() ? '?' + queryParams.toString() : ''}`;

    const response = await fetch(url, {
      method: 'GET',
      headers: this.getHeaders(),
    });

    return this.handleResponse<HotelListResponse>(response);
  }

  async getHotel(id: number): Promise<ApiResponse<Hotel>> {
    const response = await fetch(`${this.baseUrl}/hotels/${id}`, {
      method: 'GET',
      headers: this.getHeaders(),
    });

    return this.handleResponse<ApiResponse<Hotel>>(response);
  }

  async createHotel(hotelData: Partial<Hotel>): Promise<ApiResponse<Hotel>> {
    const response = await fetch(`${this.baseUrl}/hotels`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(hotelData),
    });

    return this.handleResponse<ApiResponse<Hotel>>(response);
  }

  async updateHotel(id: number, hotelData: Partial<Hotel>): Promise<ApiResponse<Hotel>> {
    const response = await fetch(`${this.baseUrl}/hotels/${id}`, {
      method: 'PUT',
      headers: this.getHeaders(),
      body: JSON.stringify(hotelData),
    });

    return this.handleResponse<ApiResponse<Hotel>>(response);
  }

  async deleteHotel(id: number): Promise<ApiResponse<void>> {
    const response = await fetch(`${this.baseUrl}/hotels/${id}`, {
      method: 'DELETE',
      headers: this.getHeaders(),
    });

    return this.handleResponse<ApiResponse<void>>(response);
  }

  async toggleHotelStatus(id: number): Promise<ApiResponse<Hotel>> {
    const response = await fetch(`${this.baseUrl}/hotels/${id}/toggle-status`, {
      method: 'POST',
      headers: this.getHeaders(),
    });

    return this.handleResponse<ApiResponse<Hotel>>(response);
  }

  // ================================================================
  // LEGACY COMPATIBILITY (para migración gradual)
  // ================================================================

  async getLegacyHotels(): Promise<HotelListResponse> {
    const response = await fetch(`${this.baseUrl}/legacy/hotels`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    });

    return this.handleResponse<HotelListResponse>(response);
  }

  // ================================================================
  // UTILITIES
  // ================================================================

  isAuthenticated(): boolean {
    return !!this.token;
  }

  getStoredUser(): User | null {
    const userData = localStorage.getItem('user_data');
    return userData ? JSON.parse(userData) : null;
  }

  // Test API connection
  async testConnection(): Promise<{ success: boolean; message: string; timestamp?: string }> {
    try {
      const response = await fetch(`${this.baseUrl}/test`);
      const data = await response.json();
      return {
        success: true,
        message: data.message || 'API conectada correctamente',
        timestamp: data.timestamp
      };
    } catch (error) {
      return {
        success: false,
        message: `Error de conexión: ${error instanceof Error ? error.message : 'Unknown error'}`
      };
    }
  }
}

// Exportar instancia singleton
export const laravelApi = new LaravelApiService();
export default laravelApi;