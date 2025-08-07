// hooks/useLaravelHotelData.ts
import { useState, useEffect, useCallback } from 'react';
import { laravelApi, Hotel, HotelListResponse } from '../services/laravelApi';

export interface HotelFilters {
  search?: string;
  active?: boolean;
  page?: number;
  limit?: number;
}

export const useLaravelHotelData = () => {
  const [hotels, setHotels] = useState<Hotel[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [total, setTotal] = useState(0);
  const [filters, setFilters] = useState<HotelFilters>({});

  // Cargar hoteles
  const loadHotels = useCallback(async (newFilters?: HotelFilters) => {
    try {
      setLoading(true);
      setError(null);
      
      const currentFilters = newFilters || filters;
      
      // Usar legacy API si no está autenticado, APIs protegidas si está autenticado
      const response: HotelListResponse = laravelApi.isAuthenticated() 
        ? await laravelApi.getHotels(currentFilters)
        : await laravelApi.getLegacyHotels();
      
      if (response.success) {
        setHotels(response.hotels);
        setTotal(response.total);
        
        if (newFilters) {
          setFilters(newFilters);
        }
      } else {
        setError('Error al cargar hoteles');
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Error desconocido');
    } finally {
      setLoading(false);
    }
  }, [filters]);

  // Crear hotel
  const createHotel = useCallback(async (hotelData: Partial<Hotel>): Promise<boolean> => {
    try {
      setError(null);
      const response = await laravelApi.createHotel(hotelData);
      
      if (response.success) {
        await loadHotels(); // Recargar lista
        return true;
      } else {
        setError('Error al crear hotel');
        return false;
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Error al crear hotel');
      return false;
    }
  }, [loadHotels]);

  // Actualizar hotel
  const updateHotel = useCallback(async (id: number, hotelData: Partial<Hotel>): Promise<boolean> => {
    try {
      setError(null);
      const response = await laravelApi.updateHotel(id, hotelData);
      
      if (response.success) {
        await loadHotels(); // Recargar lista
        return true;
      } else {
        setError('Error al actualizar hotel');
        return false;
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Error al actualizar hotel');
      return false;
    }
  }, [loadHotels]);

  // Eliminar hotel
  const deleteHotel = useCallback(async (id: number): Promise<boolean> => {
    try {
      setError(null);
      const response = await laravelApi.deleteHotel(id);
      
      if (response.success) {
        await loadHotels(); // Recargar lista
        return true;
      } else {
        setError('Error al eliminar hotel');
        return false;
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Error al eliminar hotel');
      return false;
    }
  }, [loadHotels]);

  // Toggle estado del hotel
  const toggleHotelStatus = useCallback(async (id: number): Promise<boolean> => {
    try {
      setError(null);
      const response = await laravelApi.toggleHotelStatus(id);
      
      if (response.success) {
        await loadHotels(); // Recargar lista
        return true;
      } else {
        setError('Error al cambiar estado del hotel');
        return false;
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Error al cambiar estado del hotel');
      return false;
    }
  }, [loadHotels]);

  // Buscar hoteles
  const searchHotels = useCallback(async (searchTerm: string) => {
    await loadHotels({ ...filters, search: searchTerm, page: 1 });
  }, [filters, loadHotels]);

  // Filtrar por estado
  const filterByStatus = useCallback(async (active?: boolean) => {
    await loadHotels({ ...filters, active, page: 1 });
  }, [filters, loadHotels]);

  // Cambiar página
  const changePage = useCallback(async (page: number) => {
    await loadHotels({ ...filters, page });
  }, [filters, loadHotels]);

  // Clear error
  const clearError = useCallback(() => {
    setError(null);
  }, []);

  // Refresh data
  const refresh = useCallback(async () => {
    await loadHotels();
  }, [loadHotels]);

  // Estadísticas calculadas
  const statistics = {
    totalHotels: total,
    activeHotels: hotels.filter(h => h.activo).length,
    inactiveHotels: hotels.filter(h => !h.activo).length,
    hotelsWithReviews: hotels.filter(h => (h.total_reviews || 0) > 0).length,
    averageRating: hotels.length > 0 
      ? (hotels.reduce((sum, h) => sum + (h.avg_rating || 0), 0) / hotels.length).toFixed(1)
      : '0.0',
    totalReviews: hotels.reduce((sum, h) => sum + (h.total_reviews || 0), 0)
  };

  // Cargar datos iniciales
  useEffect(() => {
    loadHotels();
  }, []);

  return {
    // Data
    hotels,
    loading,
    error,
    total,
    filters,
    statistics,
    
    // Actions
    loadHotels,
    createHotel,
    updateHotel,
    deleteHotel,
    toggleHotelStatus,
    searchHotels,
    filterByStatus,
    changePage,
    clearError,
    refresh,
    
    // Utilities
    isAuthenticated: laravelApi.isAuthenticated()
  };
};