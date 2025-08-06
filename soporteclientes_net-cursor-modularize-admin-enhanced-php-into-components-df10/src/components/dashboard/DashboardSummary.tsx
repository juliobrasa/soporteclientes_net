// components/Dashboard/DashboardSummary.tsx
import React from 'react';
import { Award, AlertCircle, TrendingUp, TrendingDown } from 'lucide-react';
import { DashboardData } from '../../types/hotel';
import { CircularProgress, StatusBadge } from '../shared';

interface DashboardSummaryProps {
  data: DashboardData;
}

export const DashboardSummary: React.FC<DashboardSummaryProps> = ({ data }) => {
  const { iro, semantico, stats } = data;

  const getIROColor = (score: number) => {
    if (score >= 80) return "#22c55e"; // Verde
    if (score >= 60) return "#fbbf24"; // Amarillo
    if (score >= 40) return "#f97316"; // Naranja
    return "#ef4444"; // Rojo
  };

  const getIROStatus = (score: number) => {
    if (score >= 80) return "Excelente";
    if (score >= 60) return "Bueno";
    if (score >= 40) return "Regular";
    return "Malo";
  };

  const getSemanticColor = (score: number) => {
    if (score >= 80) return "#22c55e";
    if (score >= 60) return "#fbbf24";
    if (score >= 40) return "#f97316";
    return "#ef4444";
  };

  return (
    <div className="space-y-6">
      {/* Información y enlaces */}
      <div className="bg-yellow-100 border border-yellow-300 rounded-lg p-4">
        <p className="text-yellow-800">
          Conoce el IRO con{' '}
          <a href="#" className="text-blue-600 underline hover:text-blue-800 transition-colors">
            este video
          </a>{' '}
          y cuéntanos qué te parece esta sección{' '}
          <a href="#" className="text-blue-600 underline hover:text-blue-800 transition-colors">
            aquí
          </a>
        </p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* IRO Score */}
        <div className="bg-white rounded-lg p-6 shadow-sm hover:shadow-md transition-shadow">
          <div className="flex justify-between items-start mb-4">
            <div>
              <h3 className="text-lg font-semibold text-gray-900">
                Índice de Reputación Online (IRO)
              </h3>
              <StatusBadge 
                status={iro.score >= 60 ? 'regular' : 'bad'} 
                text={getIROStatus(iro.score)}
              />
            </div>
          </div>
          
          <div className="flex items-center justify-center mb-6">
            <CircularProgress 
              percentage={iro.score} 
              color={getIROColor(iro.score)}
              className="drop-shadow-sm"
            />
          </div>

          <div className="space-y-3">
            <div className="flex justify-between items-center">
              <span className="text-sm text-gray-600">Calificación</span>
              <div className="flex items-center gap-2">
                <div className="w-24 bg-gray-200 rounded-full h-2">
                  <div 
                    className="bg-blue-500 h-2 rounded-full transition-all duration-500" 
                    style={{width: `${iro.calificacion.value}%`}}
                  />
                </div>
                <span className="text-sm font-medium">{iro.calificacion.value}%</span>
                {iro.calificacion.trend === 'up' ? (
                  <TrendingUp size={14} className="text-green-600" />
                ) : (
                  <TrendingDown size={14} className="text-red-600" />
                )}
              </div>
            </div>
            
            <div className="flex justify-between items-center">
              <span className="text-sm text-gray-600">Cobertura</span>
              <div className="flex items-center gap-2">
                <div className="w-24 bg-gray-200 rounded-full h-2">
                  <div 
                    className="bg-blue-500 h-2 rounded-full transition-all duration-500" 
                    style={{width: `${iro.cobertura.value}%`}}
                  />
                </div>
                <span className="text-sm font-medium">{iro.cobertura.value}%</span>
                {iro.cobertura.trend === 'up' ? (
                  <TrendingUp size={14} className="text-green-600" />
                ) : (
                  <TrendingDown size={14} className="text-red-600" />
                )}
              </div>
            </div>
            
            <div className="flex justify-between items-center">
              <span className="text-sm text-gray-600">Reseñas</span>
              <div className="flex items-center gap-2">
                <div className="w-24 bg-gray-200 rounded-full h-2">
                  <div 
                    className="bg-blue-500 h-2 rounded-full transition-all duration-500" 
                    style={{width: `${iro.reseñas.value}%`}}
                  />
                </div>
                <span className="text-sm font-medium">{iro.reseñas.value}%</span>
                {iro.reseñas.trend === 'up' ? (
                  <TrendingUp size={14} className="text-green-600" />
                ) : (
                  <TrendingDown size={14} className="text-red-600" />
                )}
              </div>
            </div>
          </div>

          <div className="mt-4 text-center">
            <span className={`text-sm font-medium ${
              iro.change >= 0 ? 'text-green-600' : 'text-red-600'
            }`}>
              {iro.change >= 0 ? '+' : ''}{iro.change}% respecto al período anterior
            </span>
          </div>
        </div>

        {/* Índice Semántico */}
        <div className="bg-white rounded-lg p-6 shadow-sm hover:shadow-md transition-shadow">
          <div className="flex justify-between items-start mb-4">
            <div>
              <h3 className="text-lg font-semibold text-gray-900">Índice Semántico</h3>
              <StatusBadge status={semantico.status} />
            </div>
          </div>
          
          <div className="flex items-center justify-center mb-6">
            <CircularProgress 
              percentage={semantico.score} 
              color={getSemanticColor(semantico.score)}
              className="drop-shadow-sm"
            />
          </div>

          <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4">
            <div className="flex gap-2">
              <AlertCircle className="text-yellow-600 flex-shrink-0 mt-0.5" size={16} />
              <p className="text-sm text-yellow-800">{semantico.message}</p>
            </div>
          </div>

          <div className="text-center">
            <span className={`text-sm font-medium ${
              semantico.change >= 0 ? 'text-green-600' : 'text-red-600'
            }`}>
              {semantico.change >= 0 ? '+' : ''}{semantico.change}% respecto al período anterior
            </span>
          </div>
        </div>

        {/* Recomendaciones */}
        <div className="bg-white rounded-lg p-6 shadow-sm hover:shadow-md transition-shadow">
          <div className="flex items-center gap-2 mb-4">
            <Award className="text-yellow-500" size={20} />
            <h3 className="text-lg font-semibold text-gray-900">Recomendaciones</h3>
            <span className="text-sm text-gray-500">1 / 3</span>
          </div>
          
          <p className="text-sm text-gray-700 mb-4">
            Revisa tus calificaciones por OTA para ver cuáles están afectando negativamente 
            tu reputación y así para tomar acciones de mejora.
          </p>
          
          <div className="space-y-2">
            <button className="w-full text-left p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
              <div className="text-sm font-medium text-blue-900">Acción prioritaria</div>
              <div className="text-xs text-blue-700">Responder reseñas pendientes</div>
            </button>
            
            <button className="text-blue-600 text-sm font-medium hover:underline">
              SIGUIENTE RECOMENDACIÓN →
            </button>
          </div>
        </div>
      </div>

      {/* Tabla de dimensiones */}
      <div className="bg-white rounded-lg p-6 shadow-sm">
        <h3 className="text-lg font-semibold text-gray-900 mb-6">
          Dimensiones de la reputación online
        </h3>
        
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead>
              <tr className="border-b border-gray-200">
                <th className="text-left py-3 text-sm font-medium text-gray-600">Métrica</th>
                <th className="text-center py-3 text-sm font-medium text-gray-600">Período</th>
                <th className="text-center py-3 text-sm font-medium text-gray-600">Acumulado 2025</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              <tr className="hover:bg-gray-50 transition-colors">
                <td className="py-3 text-sm text-gray-900 font-medium">Calificaciones en OTAs</td>
                <td className="text-center py-3">
                  <div className="flex items-center justify-center gap-1">
                    <span className="text-sm font-medium">{stats.calificacionesOTAs.period}</span>
                    <span className={`text-xs ${
                      stats.calificacionesOTAs.change >= 0 ? 'text-green-500' : 'text-red-500'
                    }`}>
                      {stats.calificacionesOTAs.change >= 0 ? '+' : ''}{stats.calificacionesOTAs.change}%
                    </span>
                  </div>
                </td>
                <td className="text-center py-3">
                  <div className="flex items-center justify-center gap-1">
                    <span className="text-sm font-medium">{stats.calificacionesOTAs.accumulated}</span>
                    <span className="text-green-500 text-xs">+{stats.calificacionesOTAs.accChange}%</span>
                  </div>
                </td>
              </tr>
              
              <tr className="hover:bg-gray-50 transition-colors">
                <td className="py-3 text-sm text-gray-900 font-medium">Cantidad de reseñas</td>
                <td className="text-center py-3">
                  <div className="flex items-center justify-center gap-1">
                    <span className="text-sm font-medium">{stats.cantidadReseñas.period}</span>
                    <span className="text-green-500 text-xs">+{stats.cantidadReseñas.change}%</span>
                  </div>
                </td>
                <td className="text-center py-3">
                  <div className="flex items-center justify-center gap-1">
                    <span className="text-sm font-medium">{stats.cantidadReseñas.accumulated}</span>
                    <span className="text-green-500 text-xs">+{stats.cantidadReseñas.accChange}%</span>
                  </div>
                </td>
              </tr>
              
              <tr className="hover:bg-gray-50 transition-colors">
                <td className="py-3 text-sm text-gray-900 font-medium">Cobertura de reseñas</td>
                <td className="text-center py-3">
                  <div className="flex items-center justify-center gap-1">
                    <span className="text-sm font-medium">{stats.coberturaReseñas.period}%</span>
                    <span className="text-red-500 text-xs">{stats.coberturaReseñas.change}%</span>
                  </div>
                </td>
                <td className="text-center py-3">
                  <div className="flex items-center justify-center gap-1">
                    <span className="text-sm font-medium">{stats.coberturaReseñas.accumulated}%</span>
                    <span className="text-green-500 text-xs">+{stats.coberturaReseñas.accChange}%</span>
                  </div>
                </td>
              </tr>
              
              <tr className="hover:bg-gray-50 transition-colors">
                <td className="py-3 text-sm text-gray-900 font-medium">NPS</td>
                <td className="text-center py-3">
                  <div className="flex items-center justify-center gap-1">
                    <span className="text-sm font-medium">+{stats.nps.period}</span>
                    <span className="text-red-500 text-xs">{stats.nps.change}</span>
                  </div>
                </td>
                <td className="text-center py-3">
                  <div className="flex items-center justify-center gap-1">
                    <span className="text-sm font-medium">+{stats.nps.accumulated}</span>
                    <span className="text-green-500 text-xs">+{stats.nps.accChange}</span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};