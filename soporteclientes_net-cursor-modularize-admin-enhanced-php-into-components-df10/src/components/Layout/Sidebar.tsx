// components/Layout/Sidebar.tsx
import React from 'react';
import { 
  BarChart3, 
  ExternalLink, 
  MessageSquare, 
  Settings, 
  HelpCircle, 
  Zap,
  TrendingUp,
  Users,
  Calendar
} from 'lucide-react';
import { MenuButton } from '../shared';

interface SidebarProps {
  activeSection: string;
  onSectionChange: (section: string) => void;
  pendingReviews?: number;
  unreadNotifications?: number;
}

export const Sidebar: React.FC<SidebarProps> = ({ 
  activeSection, 
  onSectionChange,
  pendingReviews = 0,
  unreadNotifications = 0
}) => {
  const menuItems = [
    {
      icon: BarChart3,
      text: 'Resumen',
      section: 'resumen',
      badge: unreadNotifications > 0 ? unreadNotifications : undefined
    },
    {
      icon: ExternalLink,
      text: 'OTAs',
      section: 'otas'
    },
    {
      icon: MessageSquare,
      text: 'Reseñas',
      section: 'reseñas',
      badge: pendingReviews > 0 ? pendingReviews : undefined
    },
    {
      icon: TrendingUp,
      text: 'Analytics',
      section: 'analytics'
    },
    {
      icon: Users,
      text: 'Competidores',
      section: 'competidores'
    },
    {
      icon: Calendar,
      text: 'Reportes',
      section: 'reportes'
    }
  ];

  const secondaryItems = [
    {
      icon: Zap,
      text: 'Automatización',
      section: 'automatizacion'
    },
    {
      icon: Settings,
      text: 'Configuración',
      section: 'configuracion'
    },
    {
      icon: HelpCircle,
      text: 'Ayuda',
      section: 'ayuda'
    }
  ];

  return (
    <div className="w-64 bg-slate-800 min-h-screen flex flex-col">
      {/* Main Navigation */}
      <nav className="flex-1 pt-8">
        <div className="px-4 mb-6">
          <h2 className="text-xs font-semibold text-gray-400 uppercase tracking-wide">
            Principal
          </h2>
        </div>
        
        <div className="space-y-1">
          {menuItems.map((item) => (
            <MenuButton
              key={item.section}
              icon={item.icon}
              text={item.text}
              section={item.section}
              isActive={activeSection === item.section}
              onClick={onSectionChange}
              badge={item.badge}
            />
          ))}
        </div>

        {/* Divider */}
        <div className="my-6 mx-4">
          <div className="h-px bg-slate-700"></div>
        </div>

        {/* Secondary Navigation */}
        <div className="px-4 mb-4">
          <h2 className="text-xs font-semibold text-gray-400 uppercase tracking-wide">
            Herramientas
          </h2>
        </div>
        
        <div className="space-y-1">
          {secondaryItems.map((item) => (
            <MenuButton
              key={item.section}
              icon={item.icon}
              text={item.text}
              section={item.section}
              isActive={activeSection === item.section}
              onClick={onSectionChange}
            />
          ))}
        </div>
      </nav>

      {/* Bottom Section */}
      <div className="p-4 border-t border-slate-700">
        {/* Quick Stats */}
        <div className="mb-4 p-3 bg-slate-700 rounded-lg">
          <div className="text-xs text-gray-400 mb-2">Estado del Sistema</div>
          <div className="flex items-center gap-2 text-sm">
            <div className="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
            <span className="text-green-400 font-medium">Conectado</span>
          </div>
          <div className="text-xs text-gray-400 mt-1">
            Último sync: hace 2 min
          </div>
        </div>

        {/* Upgrade prompt */}
        <div className="p-3 bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg text-white">
          <div className="text-sm font-medium mb-1">Potencia tu análisis</div>
          <div className="text-xs opacity-90 mb-2">
            Desbloquea análisis avanzado y más integraciones
          </div>
          <button className="w-full bg-white bg-opacity-20 hover:bg-opacity-30 px-3 py-1 rounded text-xs font-medium transition-colors">
            Actualizar Plan
          </button>
        </div>
      </div>
    </div>
  );
};