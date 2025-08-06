// components/OTAs/OTAsTable.tsx
import React, { useState } from 'react';
import { TrendingUp, TrendingDown, Settings, Eye, EyeOff } from 'lucide-react';
import { OTAData } from '../../types/hotel';
import { TrendIndicator } from '../shared';

interface OTAsTableProps {
  otas: OTAData[];
  selectedHotel: string;
  onToggleOTA?: (otaId: string) => void;
  onConfigureOTA?: (otaId: string) => void;
}

export const OTAsTable: React.FC<OTAsTableProps> = ({ 
  otas, 
  selectedHotel, 
  onToggleOTA,
  onConfigureOTA 
}) => {
  const [showInactiveOTAs, setShowInactiveOTAs] = useState(true);
  const [sortBy, setSortBy] = useState<'name' | 'rating' | 'reviews'>('rating');
  const [sortOrder, setSortOrder] = useState<'asc' | 'desc'>('desc');

  const sortedOTAs = [...otas].sort((a, b) => {
    let aValue: number | string = 0;
    let bValue: number | string = 0;

    switch (sortBy) {
      case 'name':
        aValue = a.name;
        bValue = b.name;
        break;
      case 'rating':
        aValue = a.rating || 0;
        bValue = b.rating || 0;
        break;
      case 'reviews':
        aValue = a.reviews || 0;
        bValue = b.reviews || 0;
        break;
    }

    if (sortOrder === 'asc') {
      return aValue < bValue ? -1 : aValue > bValue ? 1 : 0;
    } else {
      return aValue > bValue ? -1 : aValue < bValue ? 1 : 0;
    }
  });

  const filteredOTAs = showInactiveOTAs 
    ? sortedOTAs 
    : sortedOTAs.filter(ota => ota.isActive);

  const handleSort = (column: 'name' | 'rating' | 'reviews') => {
    if (sortBy === column) {
      setSortOrder(sortOrder === 'asc' ? 'desc' : 'asc');
    } else {
      setSortBy(column);
      setSortOrder('desc');
    }
  };

  const SortableHeader: React.FC<{
    column: 'name' | 'rating' | 'reviews';
    children: React.ReactNode;
    align?: 'left' | 'center';
  }> = ({ column, children, align = 'center' }) => (
    <th 
      className={`py-3 text-sm font-medium text-gray-600 cursor-pointer hover:text-gray-900 transition-colors ${
        align === 'left' ? 'text-left' : 'text-center'
      }`}
      onClick={() => handleSort(column)}
    >
      <div className={`flex items-center gap-1 ${align === 'center' ? 'justify-center' : ''}`}>
        {children}
        {sortBy === column && (
          sortOrder === 'asc' ? <TrendingUp size={14} /> : <TrendingDown size={14} />
        )}
      </div>
    </th>
  );

  return (
    <div className="space-y-6">
      <div className="bg-white rounded-lg p-6 shadow-sm">
        <div className="flex justify-between items-center mb-6">
          <div>
            <h3 className="text-lg font-semibold text-gray-900">
              Ranking por OTA's - {selectedHotel}
            </h3>
            <p className="text-sm text-gray-600 mt-1">
              Rendimiento por plataforma de reservas online
            </p>
          </div>
          
          <div className="flex items-center gap-3">
            <button
              onClick={() => setShowInactiveOTAs(!showInactiveOTAs)}
              className={`flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-colors ${
                showInactiveOTAs
                  ? 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                  : 'bg-blue-100 text-blue-700 hover:bg-blue-200'
              }`}
            >
              {showInactiveOTAs ? <EyeOff size={16} /> : <Eye size={16} />}
              {showInactiveOTAs ? 'Ocultar inactivas' : 'Mostrar todas'}
            </button>
            
            <button className="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors">
              Seleccionar Competidor
            </button>
          </div>
        </div>

        {/* Estadísticas rápidas */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
          <div className="text-center">
            <div className="text-2xl font-bold text-gray-900">
              {filteredOTAs.filter(ota => ota.isActive).length}
            </div>
            <div className="text-sm text-gray-600">OTAs Activas</div>
          </div>
          <div className="text-center">
            <div className="text-2xl font-bold text-gray-900">
              {(filteredOTAs
                .filter(ota => ota.rating)
                .reduce((sum, ota) => sum + (ota.rating || 0), 0) / 
                filteredOTAs.filter(ota => ota.rating).length || 0
              ).toFixed(2)}
            </div>
            <div className="text-sm text-gray-600">Rating Promedio</div>
          </div>
          <div className="text-center">
            <div className="text-2xl font-bold text-gray-900">
              {filteredOTAs.reduce((sum, ota) => sum + (ota.reviews || 0), 0)}
            </div>
            <div className="text-sm text-gray-600">Reseñas Totales</div>
          </div>
          <div className="text-center">
            <div className="text-2xl font-bold text-gray-900">
              {filteredOTAs.reduce((sum, ota) => sum + ota.totalReviews, 0)}
            </div>
            <div className="text-sm text-gray-600">Reseñas Acumuladas</div>
          </div>
        </div>
        
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead>
              <tr className="border-b border-gray-200">
                <SortableHeader column="name" align="left">
                  OTAs
                </SortableHeader>
                <SortableHeader column="rating">
                  Calificación
                </SortableHeader>
                <SortableHeader column="reviews">
                  Cantidad De Reseñas
                </SortableHeader>
                <th className="text-center py-3 text-sm font-medium text-gray-600">
                  Acumulado 2025
                </th>
                <th className="text-center py-3 text-sm font-medium text-gray-600">
                  Acciones
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {filteredOTAs.map((ota) => (
                <tr 
                  key={ota.id} 
                  className={`hover:bg-gray-50 transition-colors ${
                    !ota.isActive ? 'opacity-60' : ''
                  }`}
                >
                  <td className="py-4">
                    <div className="flex items-center gap-3">
                      <div className={`w-10 h-10 rounded-full ${ota.bgColor} flex items-center justify-center text-white font-bold shadow-sm`}>
                        {ota.logo}
                      </div>
                      <div>
                        <span className="text-sm font-medium text-gray-900">{ota.name}</span>
                        <div className="flex items-center gap-2 mt-1">
                          <div className={`w-2 h-2 rounded-full ${
                            ota.isActive ? 'bg-green-500' : 'bg-gray-400'
                          }`} />
                          <span className="text-xs text-gray-500">
                            {ota.isActive ? 'Activa' : 'Inactiva'}
                          </span>
                        </div>
                      </div>
                    </div>
                  </td>
                  
                  <td className="text-center py-4">
                    {ota.rating ? (
                      <div className="flex flex-col items-center">
                        <span className="text-lg font-semibold text-gray-900">
                          {ota.rating}
                        </span>
                        {ota.ratingChange !== null && (
                          <TrendIndicator 
                            value={ota.ratingChange} 
                            trend={ota.ratingChange > 0 ? 'up' : 'down'}
                            size="sm"
                          />
                        )}
                      </div>
                    ) : (
                      <span className="text-gray-400 text-sm">Sin datos</span>
                    )}
                  </td>
                  
                  <td className="text-center py-4">
                    {ota.reviews ? (
                      <div className="flex flex-col items-center">
                        <span className="text-lg font-semibold text-gray-900">
                          {ota.reviews}
                        </span>
                        {ota.reviewsChange !== null && (
                          <TrendIndicator 
                            value={ota.reviewsChange} 
                            trend={ota.reviewsChange > 0 ? 'up' : 'down'}
                            size="sm"
                          />
                        )}
                      </div>
                    ) : (
                      <span className="text-gray-400 text-sm">Sin datos</span>
                    )}
                  </td>
                  
                  <td className="text-center py-4">
                    <div className="flex flex-col items-center">
                      {ota.accumulated2025 ? (
                        <>
                          <span className="text-lg font-semibold text-gray-900">
                            {ota.accumulated2025}
                          </span>
                          <span className="text-xs text-gray-500">Promedio</span>
                        </>
                      ) : (
                        <span className="text-gray-400 text-sm">Sin datos</span>
                      )}
                      <div className="text-xs text-gray-500 mt-1">
                        {ota.totalReviews} Reseñas
                      </div>
                    </div>
                  </td>
                  
                  <td className="text-center py-4">
                    <div className="flex items-center justify-center gap-2">
                      {onToggleOTA && (
                        <button
                          onClick={() => onToggleOTA(ota.id)}
                          className={`px-3 py-1 rounded-lg text-xs font-medium transition-colors ${
                            ota.isActive
                              ? 'bg-red-100 text-red-700 hover:bg-red-200'
                              : 'bg-green-100 text-green-700 hover:bg-green-200'
                          }`}
                        >
                          {ota.isActive ? 'Desactivar' : 'Activar'}
                        </button>
                      )}
                      
                      {onConfigureOTA && (
                        <button
                          onClick={() => onConfigureOTA(ota.id)}
                          className="p-1 text-gray-400 hover:text-gray-600 transition-colors"
                          title="Configurar OTA"
                        >
                          <Settings size={16} />
                        </button>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        
        <div className="mt-6 text-center p-4 bg-blue-50 rounded-lg">
          <p className="text-sm text-blue-800 mb-2">
            ¿Necesitas integrar más OTAs a tu análisis?
          </p>
          <button className="text-blue-600 hover:text-blue-800 text-sm font-medium hover:underline transition-colors">
            Contáctanos para activar más OTA's
          </button>
        </div>
      </div>
    </div>
  );
};