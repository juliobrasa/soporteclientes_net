// components/HotelReputationFramework.tsx
import React, { useState } from 'react';
import { useHotelData, useNotifications } from '../hooks/useHotelData';
import { Header } from './Layout/Header';
import { Sidebar } from './Layout/Sidebar';
import { DashboardSummary } from './Dashboard/DashboardSummary';
import { OTAsTable } from './OTAs/OTAsTable';
import { ReviewsList } from './Reviews/ReviewsList';
import { LoadingSpinner } from './shared';

export const HotelReputationFramework: React.FC = () => {
  const [activeSection, setActiveSection] = useState('resumen');
  
  const {
    selectedHotel,
    setSelectedHotel,
    dateRange,
    setDateRange,
    loading,
    data,
    filteredReviews,
    activeOTAs,
    calculatedStats,
    hotels,
    respondToReview,
    exportData
  } = useHotelData();

  const { notifications, addNotification } = useNotifications();

  // Handlers
  const handleExportReport = () => {
    try {
      const exportedData = exportData('pdf');
      addNotification('success', 'Reporte exportado', 'El reporte se ha generado correctamente');
      console.log('Datos exportados:', exportedData);
    } catch (error) {
      addNotification('error', 'Error al exportar', 'No se pudo generar el reporte');
    }
  };

  const handleRespondToReview = async (reviewId: string) => {
    try {
      await respondToReview(reviewId, 'Respuesta generada automáticamente');
      addNotification('success', 'Respuesta enviada', 'La respuesta se ha publicado correctamente');
    } catch (error) {
      addNotification('error', 'Error al responder', 'No se pudo enviar la respuesta');
    }
  };

  const handleCreateCase = (reviewId: string) => {
    addNotification('info', 'Caso creado', `Se ha creado un caso para la reseña ${reviewId}`);
  };

  const handleTranslateReview = (reviewId: string) => {
    addNotification('info', 'Traduciendo', 'La traducción estará lista en unos momentos');
  };

  const handleToggleOTA = (otaId: string) => {
    addNotification('info', 'OTA actualizada', 'La configuración de la OTA se ha modificado');
  };

  const handleConfigureOTA = (otaId: string) => {
    addNotification('info', 'Configuración', `Abriendo configuración para ${otaId}`);
  };

  // Calcular estadísticas para el sidebar
  const pendingReviews = filteredReviews.filter(r => !r.hasResponse).length;
  const unreadNotifications = notifications.filter(n => 
    new Date().getTime() - n.timestamp.getTime() < 3600000 // Últimas 24 horas
  ).length;

  // Renderizar contenido según la sección activa
  const renderContent = () => {
    if (loading) {
      return (
        <div className="flex items-center justify-center h-64">
          <div className="text-center">
            <LoadingSpinner size="lg" />
            <p className="mt-4 text-gray-600">Cargando datos...</p>
          </div>
        </div>
      );
    }

    switch (activeSection) {
      case 'resumen':
        return <DashboardSummary data={data} />;
        
      case 'otas':
        return (
          <OTAsTable
            otas={data.otas}
            selectedHotel={selectedHotel}
            onToggleOTA={handleToggleOTA}
            onConfigureOTA={handleConfigureOTA}
          />
        );
        
      case 'reseñas':
        return (
          <ReviewsList
            reviews={filteredReviews}
            totalReviews={calculatedStats.totalReviews}
            averageRating={calculatedStats.averageRating}
            coveragePercentage={calculatedStats.responseRate}
            onRespondToReview={handleRespondToReview}
            onCreateCase={handleCreateCase}
            onTranslateReview={handleTranslateReview}
          />
        );

      case 'analytics':
        return (
          <div className="bg-white rounded-lg p-8 shadow-sm text-center">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Analytics Avanzado</h3>
            <p className="text-gray-600 mb-4">
              Análisis detallado de tendencias, predicciones y benchmarking competitivo
            </p>
            <button className="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
              Próximamente
            </button>
          </div>
        );

      case 'competidores':
        return (
          <div className="bg-white rounded-lg p-8 shadow-sm text-center">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Análisis Competitivo</h3>
            <p className="text-gray-600 mb-4">
              Comparación con hoteles similares en tu zona
            </p>
            <button className="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
              Configurar Competidores
            </button>
          </div>
        );

      case 'reportes':
        return (
          <div className="bg-white rounded-lg p-8 shadow-sm text-center">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Centro de Reportes</h3>
            <p className="text-gray-600 mb-4">
              Reportes programados y personalizados
            </p>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
              <button className="p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <h4 className="font-medium mb-2">Reporte Semanal</h4>
                <p className="text-sm text-gray-600">Resumen automático</p>
              </button>
              <button className="p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <h4 className="font-medium mb-2">Análisis Mensual</h4>
                <p className="text-sm text-gray-600">Tendencias y evolución</p>
              </button>
              <button className="p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <h4 className="font-medium mb-2">Reporte Personalizado</h4>
                <p className="text-sm text-gray-600">A medida</p>
              </button>
            </div>
          </div>
        );

      case 'automatizacion':
        return (
          <div className="bg-white rounded-lg p-8 shadow-sm text-center">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Automatización</h3>
            <p className="text-gray-600 mb-4">
              Respuestas automáticas y flujos de trabajo
            </p>
            <button className="px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
              Configurar Automatización
            </button>
          </div>
        );

      case 'configuracion':
        return (
          <div className="bg-white rounded-lg p-8 shadow-sm text-center">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Configuración</h3>
            <p className="text-gray-600 mb-4">
              Ajustes de cuenta, notificaciones e integraciones
            </p>
            <button className="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
              Abrir Configuración
            </button>
          </div>
        );

      case 'ayuda':
        return (
          <div className="bg-white rounded-lg p-8 shadow-sm text-center">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Centro de Ayuda</h3>
            <p className="text-gray-600 mb-4">
              Documentación, tutoriales y soporte
            </p>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
              <button className="p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <h4 className="font-medium mb-2">Documentación</h4>
                <p className="text-sm text-gray-600">Guías completas</p>
              </button>
              <button className="p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <h4 className="font-medium mb-2">Soporte</h4>
                <p className="text-sm text-gray-600">Contactar equipo</p>
              </button>
            </div>
          </div>
        );

      default:
        return <DashboardSummary data={data} />;
    }
  };

  return (
    <div className="min-h-screen bg-gray-100">
      {/* Notificaciones */}
      {notifications.length > 0 && (
        <div className="fixed top-4 right-4 z-50 space-y-2">
          {notifications.slice(0, 3).map((notification) => (
            <div
              key={notification.id}
              className={`p-4 rounded-lg shadow-lg max-w-sm ${
                notification.type === 'success' ? 'bg-green-50 border border-green-200' :
                notification.type === 'error' ? 'bg-red-50 border border-red-200' :
                notification.type === 'warning' ? 'bg-yellow-50 border border-yellow-200' :
                'bg-blue-50 border border-blue-200'
              }`}
            >
              <div className="flex justify-between items-start">
                <div>
                  <h4 className={`font-medium text-sm ${
                    notification.type === 'success' ? 'text-green-800' :
                    notification.type === 'error' ? 'text-red-800' :
                    notification.type === 'warning' ? 'text-yellow-800' :
                    'text-blue-800'
                  }`}>
                    {notification.title}
                  </h4>
                  <p className={`text-sm mt-1 ${
                    notification.type === 'success' ? 'text-green-600' :
                    notification.type === 'error' ? 'text-red-600' :
                    notification.type === 'warning' ? 'text-yellow-600' :
                    'text-blue-600'
                  }`}>
                    {notification.message}
                  </p>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Header */}
      <Header
        hotels={hotels}
        selectedHotel={selectedHotel}
        onHotelChange={setSelectedHotel}
        dateRange={dateRange}
        onDateRangeChange={setDateRange}
        onExportReport={handleExportReport}
        notifications={unreadNotifications}
      />

      <div className="flex">
        {/* Sidebar */}
        <Sidebar
          activeSection={activeSection}
          onSectionChange={setActiveSection}
          pendingReviews={pendingReviews}
          unreadNotifications={unreadNotifications}
        />

        {/* Main Content */}
        <div className="flex-1 p-8">
          {renderContent()}
        </div>
      </div>
    </div>
  );
};