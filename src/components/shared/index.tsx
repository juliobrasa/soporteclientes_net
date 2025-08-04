// components/shared/index.tsx
import React from 'react';
import { TrendingUp, TrendingDown, Star } from 'lucide-react';

// Tipos para props
interface StatCardProps {
  title: string;
  value: string | number;
  change?: number | null;
  trend?: 'up' | 'down' | 'stable';
  isPercentage?: boolean;
  subtitle?: string;
  className?: string;
}

interface CircularProgressProps {
  percentage: number;
  size?: number;
  strokeWidth?: number;
  color?: string;
  className?: string;
}

interface MenuButtonProps {
  icon: React.ComponentType<any>;
  text: string;
  section: string;
  isActive: boolean;
  onClick: (section: string) => void;
  badge?: number;
}

interface StarRatingProps {
  rating: number;
  maxRating?: number;
  size?: number;
  showValue?: boolean;
  readonly?: boolean;
}

interface TrendIndicatorProps {
  value: number;
  trend: 'up' | 'down' | 'stable';
  size?: 'sm' | 'md' | 'lg';
}

// Componente de tarjeta de estadística
export const StatCard: React.FC<StatCardProps> = ({ 
  title, 
  value, 
  change, 
  trend, 
  isPercentage = false, 
  subtitle,
  className = ""
}) => (
  <div className={`bg-white rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow ${className}`}>
    <h3 className="text-sm text-gray-600 mb-2">{title}</h3>
    <div className="flex items-center justify-between">
      <div>
        <span className="text-2xl font-semibold text-gray-900">
          {value}{isPercentage ? '%' : ''}
        </span>
        {subtitle && <p className="text-xs text-gray-500 mt-1">{subtitle}</p>}
      </div>
      {change !== null && change !== undefined && trend && (
        <TrendIndicator value={change} trend={trend} />
      )}
    </div>
  </div>
);

// Componente de progreso circular
export const CircularProgress: React.FC<CircularProgressProps> = ({ 
  percentage, 
  size = 120, 
  strokeWidth = 8, 
  color = "#22d3ee",
  className = ""
}) => {
  const radius = (size - strokeWidth) / 2;
  const circumference = radius * 2 * Math.PI;
  const offset = circumference - (percentage / 100) * circumference;

  return (
    <div className={`relative ${className}`}>
      <svg width={size} height={size} className="transform -rotate-90">
        <circle
          cx={size / 2}
          cy={size / 2}
          r={radius}
          stroke="#e5e7eb"
          strokeWidth={strokeWidth}
          fill="none"
        />
        <circle
          cx={size / 2}
          cy={size / 2}
          r={radius}
          stroke={color}
          strokeWidth={strokeWidth}
          fill="none"
          strokeDasharray={circumference}
          strokeDashoffset={offset}
          strokeLinecap="round"
          className="transition-all duration-500 ease-in-out"
        />
      </svg>
      <div className="absolute inset-0 flex items-center justify-center">
        <span className="text-3xl font-bold text-gray-900">{percentage}%</span>
      </div>
    </div>
  );
};

// Componente de botón de menú
export const MenuButton: React.FC<MenuButtonProps> = ({ 
  icon: Icon, 
  text, 
  section, 
  isActive, 
  onClick,
  badge 
}) => (
  <button
    onClick={() => onClick(section)}
    className={`w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-slate-700 transition-colors relative ${
      isActive ? 'bg-cyan-500 text-white' : 'text-gray-300'
    }`}
  >
    <Icon size={20} />
    <span className="flex-1">{text}</span>
    {badge && badge > 0 && (
      <span className="bg-red-500 text-white text-xs rounded-full px-2 py-1 min-w-[20px] text-center">
        {badge > 99 ? '99+' : badge}
      </span>
    )}
  </button>
);

// Componente de calificación con estrellas
export const StarRating: React.FC<StarRatingProps> = ({ 
  rating, 
  maxRating = 5, 
  size = 16,
  showValue = false,
  readonly = true
}) => (
  <div className="flex items-center gap-2">
    <div className="flex">
      {[...Array(maxRating)].map((_, i) => (
        <Star
          key={i}
          size={size}
          className={`${
            i < Math.floor(rating) 
              ? 'text-yellow-400 fill-current' 
              : i < rating 
                ? 'text-yellow-400 fill-current opacity-50'
                : 'text-gray-300'
          } ${!readonly ? 'cursor-pointer hover:text-yellow-400' : ''}`}
        />
      ))}
    </div>
    {showValue && (
      <span className="text-sm font-medium text-gray-700">
        {rating.toFixed(1)} / {maxRating}
      </span>
    )}
  </div>
);

// Componente indicador de tendencia
export const TrendIndicator: React.FC<TrendIndicatorProps> = ({ 
  value, 
  trend, 
  size = 'md' 
}) => {
  const sizeClasses = {
    sm: 'text-xs',
    md: 'text-sm',
    lg: 'text-base'
  };

  const iconSize = {
    sm: 12,
    md: 16,
    lg: 20
  };

  if (trend === 'stable') {
    return (
      <div className={`flex items-center gap-1 text-gray-500 ${sizeClasses[size]}`}>
        <span>=</span>
        <span>{Math.abs(value)}%</span>
      </div>
    );
  }

  return (
    <div className={`flex items-center gap-1 ${
      trend === 'up' ? 'text-green-600' : 'text-red-600'
    } ${sizeClasses[size]}`}>
      {trend === 'up' ? (
        <TrendingUp size={iconSize[size]} />
      ) : (
        <TrendingDown size={iconSize[size]} />
      )}
      <span>{Math.abs(value)}%</span>
    </div>
  );
};

// Componente de progreso de barra
export const ProgressBar: React.FC<{
  value: number;
  max?: number;
  color?: string;
  height?: string;
  showValue?: boolean;
  className?: string;
}> = ({ 
  value, 
  max = 100, 
  color = 'bg-blue-500', 
  height = 'h-2',
  showValue = false,
  className = ""
}) => {
  const percentage = (value / max) * 100;
  
  return (
    <div className={`flex items-center gap-2 ${className}`}>
      <div className={`flex-1 bg-gray-200 rounded-full ${height}`}>
        <div 
          className={`${color} ${height} rounded-full transition-all duration-300`}
          style={{ width: `${Math.min(percentage, 100)}%` }}
        />
      </div>
      {showValue && (
        <span className="text-sm font-medium text-gray-700 min-w-[3rem] text-right">
          {value}{max === 100 ? '%' : `/${max}`}
        </span>
      )}
    </div>
  );
};

// Componente de badge de estado
export const StatusBadge: React.FC<{
  status: 'excellent' | 'good' | 'regular' | 'bad' | 'very_bad';
  text?: string;
}> = ({ status, text }) => {
  const statusConfig = {
    excellent: { color: 'bg-green-100 text-green-800', label: 'Excelente' },
    good: { color: 'bg-blue-100 text-blue-800', label: 'Bueno' },
    regular: { color: 'bg-yellow-100 text-yellow-800', label: 'Regular' },
    bad: { color: 'bg-orange-100 text-orange-800', label: 'Malo' },
    very_bad: { color: 'bg-red-100 text-red-800', label: 'Muy Malo' }
  };

  const config = statusConfig[status];

  return (
    <span className={`inline-flex px-2 py-1 text-xs font-medium rounded-full ${config.color}`}>
      {text || config.label}
    </span>
  );
};

// Componente de loading spinner
export const LoadingSpinner: React.FC<{ size?: 'sm' | 'md' | 'lg' }> = ({ size = 'md' }) => {
  const sizeClasses = {
    sm: 'w-4 h-4',
    md: 'w-8 h-8',
    lg: 'w-12 h-12'
  };

  return (
    <div className={`animate-spin rounded-full border-2 border-gray-300 border-t-blue-600 ${sizeClasses[size]}`} />
  );
};