// components/Layout/Header.tsx
import React from 'react';
import { ChevronDown, Bell, User, Settings, LogOut } from 'lucide-react';
import { Hotel, FilterOptions } from '../../types/hotel';

interface HeaderProps {
  hotels: Hotel[];
  selectedHotel: string;
  onHotelChange: (hotelName: string) => void;
  dateRange: FilterOptions['dateRange'];
  onDateRangeChange: (range: FilterOptions['dateRange']) => void;
  onExportReport?: () => void;
  notifications?: number;
}

export const Header: React.FC<HeaderProps> = ({
  hotels,
  selectedHotel,
  onHotelChange,
  dateRange,
  onDateRangeChange,
  onExportReport,
  notifications = 0
}) => {
  const dateRangeOptions = [
    { value: '7', label: 'Últimos 7 días' },
    { value: '30', label: 'Últimos 30 días' },
    { value: '60', label: 'Últimos 60 días' },
    { value: '90', label: 'Últimos 90 días' },
    { value: '365', label: 'Último año' }
  ];

  return (
    <header className="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between items-center h-16">
          {/* Logo y selector de hotel */}
          <div className="flex items-center gap-6">
            {/* Logo */}
            <div className="text-xl font-bold text-gray-900 flex items-center gap-2">
              <div className="w-8 h-8 bg-gradient-to-br from-blue-600 to-cyan-500 rounded-lg flex items-center justify-center">
                <span className="text-white font-bold text-sm">FS</span>
              </div>
              <div>
                <span className="text-blue-600">Fidelity</span>
                <span className="text-cyan-500">Suite</span>
              </div>
            </div>
            
            {/* Hotel Selector */}
            <div className="relative">
              <select 
                value={selectedHotel}
                onChange={(e) => onHotelChange(e.target.value)}
                className="appearance-none bg-white border border-gray-300 rounded-lg px-4 py-2 pr-10 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors hover:border-gray-400"
              >
                {hotels.map((hotel) => (
                  <option key={hotel.id} value={hotel.name}>
                    {hotel.name}
                  </option>
                ))}
              </select>
              <ChevronDown className="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 pointer-events-none" size={16} />
            </div>

            {/* Quick Stats */}
            <div className="hidden lg:flex items-center gap-4 text-sm text-gray-600">
              <div className="flex items-center gap-1">
                <div className="w-2 h-2 bg-green-500 rounded-full"></div>
                <span>Sistema activo</span>
              </div>
              <div className="h-4 w-px bg-gray-300"></div>
              <span>Última sync: hace 5 min</span>
            </div>
          </div>

          {/* Controles de la derecha */}
          <div className="flex items-center gap-4">
            {/* Selector de período */}
            <div className="relative">
              <select 
                value={dateRange}
                onChange={(e) => onDateRangeChange(e.target.value as FilterOptions['dateRange'])}
                className="appearance-none bg-gray-50 border border-gray-300 rounded-lg px-3 py-2 pr-8 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors hover:bg-gray-100"
              >
                {dateRangeOptions.map((option) => (
                  <option key={option.value} value={option.value}>
                    {option.label}
                  </option>
                ))}
              </select>
              <ChevronDown className="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 pointer-events-none" size={14} />
            </div>
            
            {/* Botón de reporte */}
            {onExportReport && (
              <button 
                onClick={onExportReport}
                className="px-4 py-2 border border-blue-300 text-blue-600 rounded-lg text-sm font-medium hover:bg-blue-50 transition-colors"
              >
                Exportar Reporte
              </button>
            )}

            {/* Notificaciones */}
            <div className="relative">
              <button className="p-2 text-gray-400 hover:text-gray-600 transition-colors relative">
                <Bell size={20} />
                {notifications > 0 && (
                  <span className="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                    {notifications > 9 ? '9+' : notifications}
                  </span>
                )}
              </button>
            </div>

            {/* Menu de usuario */}
            <div className="relative group">
              <button className="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-100 transition-colors">
                <div className="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                  <User size={16} className="text-gray-600" />
                </div>
                <ChevronDown size={14} className="text-gray-400" />
              </button>
              
              {/* Dropdown menu */}
              <div className="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                <div className="p-2">
                  <div className="px-3 py-2 border-b border-gray-100 mb-1">
                    <div className="text-sm font-medium text-gray-900">Usuario</div>
                    <div className="text-xs text-gray-500">admin@hotel.com</div>
                  </div>
                  
                  <button className="w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                    <Settings size={16} />
                    Configuración
                  </button>
                  
                  <button className="w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                    <LogOut size={16} />
                    Cerrar sesión
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </header>
  );
};