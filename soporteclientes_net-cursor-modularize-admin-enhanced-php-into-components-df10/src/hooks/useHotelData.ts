// hooks/useHotelData.ts
import { useState, useEffect, useMemo } from 'react';
import { 
  Hotel, 
  DashboardData, 
  FilterOptions, 
  ReviewData, 
  OTAData 
} from '../types/hotel';
import { hotels, dashboardData, generateMockReviews } from '../data/mockData';

export const useHotelData = () => {
  const [selectedHotel, setSelectedHotel] = useState<string>(hotels[0].name);
  const [dateRange, setDateRange] = useState<FilterOptions['dateRange']>('30');
  const [filters, setFilters] = useState<FilterOptions>({
    dateRange: '30',
    platform: [],
    rating: [],
    sentiment: [],
    language: []
  });
  const [loading, setLoading] = useState(false);
  const [data, setData] = useState<DashboardData>(dashboardData);

  // Simular carga de datos cuando cambian los filtros
  useEffect(() => {
    const loadData = async () => {
      setLoading(true);
      
      // Simular API call
      await new Promise(resolve => setTimeout(resolve, 500));
      
      // Generar datos adicionales si es necesario
      const additionalReviews = generateMockReviews(10);
      const updatedData = {
        ...dashboardData,
        reviews: [...dashboardData.reviews, ...additionalReviews]
      };
      
      setData(updatedData);
      setLoading(false);
    };

    loadData();
  }, [selectedHotel, dateRange, filters]);

  // Datos filtrados de reseñas
  const filteredReviews = useMemo(() => {
    let filtered = data.reviews;

    // Filtrar por plataforma
    if (filters.platform.length > 0) {
      filtered = filtered.filter(review => 
        filters.platform.includes(review.platform)
      );
    }

    // Filtrar por rating
    if (filters.rating.length > 0) {
      filtered = filtered.filter(review => 
        filters.rating.includes(review.rating)
      );
    }

    // Filtrar por sentimiento
    if (filters.sentiment.length > 0) {
      filtered = filtered.filter(review => 
        filters.sentiment.includes(review.sentiment)
      );
    }

    // Filtrar por idioma
    if (filters.language.length > 0) {
      filtered = filtered.filter(review => 
        filters.language.includes(review.language)
      );
    }

    return filtered;
  }, [data.reviews, filters]);

  // OTAs activas
  const activeOTAs = useMemo(() => 
    data.otas.filter(ota => ota.isActive), 
    [data.otas]
  );

  // Estadísticas calculadas
  const calculatedStats = useMemo(() => {
    const totalReviews = filteredReviews.length;
    const averageRating = totalReviews > 0 
      ? filteredReviews.reduce((sum, review) => sum + review.rating, 0) / totalReviews 
      : 0;
    
    const sentimentDistribution = {
      positive: filteredReviews.filter(r => r.sentiment === 'positive').length,
      neutral: filteredReviews.filter(r => r.sentiment === 'neutral').length,
      negative: filteredReviews.filter(r => r.sentiment === 'negative').length
    };

    const responseRate = totalReviews > 0
      ? (filteredReviews.filter(r => r.hasResponse).length / totalReviews) * 100
      : 0;

    return {
      totalReviews,
      averageRating,
      sentimentDistribution,
      responseRate
    };
  }, [filteredReviews]);

  // Funciones para actualizar filtros
  const updateFilter = (key: keyof FilterOptions, value: any) => {
    setFilters(prev => ({
      ...prev,
      [key]: value
    }));
  };

  const clearFilters = () => {
    setFilters({
      dateRange: '30',
      platform: [],
      rating: [],
      sentiment: [],
      language: []
    });
  };

  // Función para simular respuesta a reseña
  const respondToReview = async (reviewId: string, response: string) => {
    setLoading(true);
    
    // Simular API call
    await new Promise(resolve => setTimeout(resolve, 1000));
    
    setData(prev => ({
      ...prev,
      reviews: prev.reviews.map(review => 
        review.id === reviewId 
          ? { ...review, hasResponse: true }
          : review
      )
    }));
    
    setLoading(false);
    return true;
  };

  // Función para exportar datos
  const exportData = (format: 'csv' | 'pdf' | 'excel') => {
    // Aquí implementarías la lógica de exportación
    console.log(`Exportando datos en formato ${format}`);
    
    const dataToExport = {
      hotel: selectedHotel,
      dateRange,
      stats: calculatedStats,
      reviews: filteredReviews,
      otas: activeOTAs
    };
    
    return dataToExport;
  };

  return {
    // Estado
    selectedHotel,
    setSelectedHotel,
    dateRange,
    setDateRange,
    filters,
    loading,
    data,
    
    // Datos procesados
    filteredReviews,
    activeOTAs,
    calculatedStats,
    
    // Acciones
    updateFilter,
    clearFilters,
    respondToReview,
    exportData,
    
    // Datos de configuración
    hotels,
    
    // Utilidades
    refreshData: () => {
      setData({ ...dashboardData });
    }
  };
};

// Hook para gestión de notificaciones
export const useNotifications = () => {
  const [notifications, setNotifications] = useState<Array<{
    id: string;
    type: 'success' | 'error' | 'warning' | 'info';
    title: string;
    message: string;
    timestamp: Date;
  }>>([]);

  const addNotification = (
    type: 'success' | 'error' | 'warning' | 'info',
    title: string,
    message: string
  ) => {
    const notification = {
      id: Date.now().toString(),
      type,
      title,
      message,
      timestamp: new Date()
    };
    
    setNotifications(prev => [notification, ...prev]);
    
    // Auto-remove después de 5 segundos
    setTimeout(() => {
      removeNotification(notification.id);
    }, 5000);
  };

  const removeNotification = (id: string) => {
    setNotifications(prev => prev.filter(n => n.id !== id));
  };

  const clearNotifications = () => {
    setNotifications([]);
  };

  return {
    notifications,
    addNotification,
    removeNotification,
    clearNotifications
  };
};