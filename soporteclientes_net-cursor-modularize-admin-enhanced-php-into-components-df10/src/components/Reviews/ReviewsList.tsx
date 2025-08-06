// components/Reviews/ReviewsList.tsx
import React, { useState } from 'react';
import { 
  Star, 
  MessageSquare, 
  AlertCircle, 
  Filter, 
  Search, 
  Download,
  Languages,
  Calendar,
  Flag
} from 'lucide-react';
import { ReviewData, FilterOptions } from '../../types/hotel';
import { StarRating, StatCard } from '../shared';

interface ReviewsListProps {
  reviews: ReviewData[];
  totalReviews: number;
  averageRating: number;
  coveragePercentage: number;
  onRespondToReview?: (reviewId: string) => void;
  onCreateCase?: (reviewId: string) => void;
  onTranslateReview?: (reviewId: string) => void;
  filters?: FilterOptions;
  onFiltersChange?: (filters: FilterOptions) => void;
}

export const ReviewsList: React.FC<ReviewsListProps> = ({
  reviews,
  totalReviews,
  averageRating,
  coveragePercentage,
  onRespondToReview,
  onCreateCase,
  onTranslateReview,
  filters,
  onFiltersChange
}) => {
  const [searchTerm, setSearchTerm] = useState('');
  const [showFilters, setShowFilters] = useState(false);
  const [selectedReviews, setSelectedReviews] = useState<string[]>([]);

  const sentimentDistribution = {
    positive: reviews.filter(r => r.sentiment === 'positive').length,
    neutral: reviews.filter(r => r.sentiment === 'neutral').length,
    negative: reviews.filter(r => r.sentiment === 'negative').length
  };

  const responseRate = reviews.length > 0 
    ? (reviews.filter(r => r.hasResponse).length / reviews.length) * 100 
    : 0;

  const getSentimentColor = (sentiment: string) => {
    switch (sentiment) {
      case 'positive': return 'text-green-600 bg-green-50';
      case 'negative': return 'text-red-600 bg-red-50';
      default: return 'text-yellow-600 bg-yellow-50';
    }
  };

  const getPlatformColor = (platform: string) => {
    switch (platform.toLowerCase()) {
      case 'booking.com': return 'bg-blue-700';
      case 'expedia': return 'bg-blue-600';
      case 'google': return 'bg-red-500';
      case 'tripadvisor': return 'bg-green-600';
      default: return 'bg-gray-600';
    }
  };

  const filteredReviews = reviews.filter(review => {
    const matchesSearch = 
      review.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
      review.positive.toLowerCase().includes(searchTerm.toLowerCase()) ||
      review.negative.toLowerCase().includes(searchTerm.toLowerCase()) ||
      review.guest.toLowerCase().includes(searchTerm.toLowerCase());

    return matchesSearch;
  });

  const handleSelectReview = (reviewId: string) => {
    setSelectedReviews(prev => 
      prev.includes(reviewId) 
        ? prev.filter(id => id !== reviewId)
        : [...prev, reviewId]
    );
  };

  const handleSelectAll = () => {
    if (selectedReviews.length === filteredReviews.length) {
      setSelectedReviews([]);
    } else {
      setSelectedReviews(filteredReviews.map(r => r.id));
    }
  };

  return (
    <div className="space-y-6">
      {/* Stats Cards */}
      <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
        <StatCard title="Total Reseñas" value={totalReviews} />
        <StatCard title="Calificación Promedio" value={averageRating.toFixed(2)} />
        <StatCard title="Cobertura Total" value={coveragePercentage} isPercentage />
        
        <div className="bg-white rounded-lg p-4 shadow-sm">
          <h3 className="text-sm text-gray-600 mb-2">Distribución NPS</h3>
          <div className="space-y-1">
            <div className="flex items-center gap-2">
              <div className="w-3 h-3 bg-green-500 rounded-full"></div>
              <span className="text-xs">Positivas: {sentimentDistribution.positive}</span>
            </div>
            <div className="flex items-center gap-2">
              <div className="w-3 h-3 bg-yellow-500 rounded-full"></div>
              <span className="text-xs">Neutrales: {sentimentDistribution.neutral}</span>
            </div>
            <div className="flex items-center gap-2">
              <div className="w-3 h-3 bg-red-500 rounded-full"></div>
              <span className="text-xs">Negativas: {sentimentDistribution.negative}</span>
            </div>
          </div>
        </div>
        
        <StatCard 
          title="Tasa de Respuesta" 
          value={responseRate.toFixed(0)} 
          isPercentage 
          subtitle="Casos creados: 0"
        />
      </div>

      {/* Controls */}
      <div className="bg-white rounded-lg p-6 shadow-sm">
        <div className="flex flex-col md:flex-row gap-4 items-start md:items-center justify-between mb-4">
          <div className="flex-1 max-w-md">
            <div className="relative">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" size={20} />
              <input
                type="text"
                placeholder="Buscar en reseñas..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>
          </div>
          
          <div className="flex items-center gap-3">
            <button
              onClick={() => setShowFilters(!showFilters)}
              className={`flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
                showFilters ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              }`}
            >
              <Filter size={16} />
              Filtros
            </button>
            
            <button className="flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors">
              <Download size={16} />
              Exportar
            </button>
            
            {selectedReviews.length > 0 && (
              <div className="flex items-center gap-2">
                <span className="text-sm text-gray-600">
                  {selectedReviews.length} seleccionadas
                </span>
                <button className="px-3 py-1 bg-blue-100 text-blue-700 rounded-lg text-sm font-medium hover:bg-blue-200 transition-colors">
                  Responder todas
                </button>
              </div>
            )}
          </div>
        </div>

        {/* Bulk Actions */}
        {filteredReviews.length > 0 && (
          <div className="flex items-center gap-3 mb-4 p-3 bg-gray-50 rounded-lg">
            <input
              type="checkbox"
              checked={selectedReviews.length === filteredReviews.length}
              onChange={handleSelectAll}
              className="w-4 h-4 text-blue-600 rounded focus:ring-blue-500"
            />
            <span className="text-sm text-gray-600">
              Seleccionar todas ({filteredReviews.length})
            </span>
          </div>
        )}
      </div>

      {/* Reviews List */}
      <div className="space-y-4">
        {filteredReviews.length === 0 ? (
          <div className="bg-white rounded-lg p-8 shadow-sm text-center">
            <MessageSquare className="mx-auto text-gray-400 mb-4" size={48} />
            <h3 className="text-lg font-medium text-gray-900 mb-2">No se encontraron reseñas</h3>
            <p className="text-gray-600">
              {searchTerm ? 'Intenta modificar los términos de búsqueda' : 'No hay reseñas disponibles en este período'}
            </p>
          </div>
        ) : (
          filteredReviews.map((review) => (
            <div key={review.id} className="bg-white rounded-lg p-6 shadow-sm hover:shadow-md transition-shadow">
              <div className="flex items-start gap-4">
                <input
                  type="checkbox"
                  checked={selectedReviews.includes(review.id)}
                  onChange={() => handleSelectReview(review.id)}
                  className="w-4 h-4 text-blue-600 rounded focus:ring-blue-500 mt-1"
                />
                
                <div className="flex-1">
                  {/* Header */}
                  <div className="flex justify-between items-start mb-4">
                    <div className="flex-1">
                      <div className="flex items-center gap-3 mb-2">
                        <h4 className="font-semibold text-gray-900">{review.guest}</h4>
                        <span className="text-sm text-gray-600 flex items-center gap-1">
                          <Flag size={14} />
                          {review.country}
                        </span>
                        <span className="text-sm text-gray-600 flex items-center gap-1">
                          <Calendar size={14} />
                          {review.date}
                        </span>
                        <span className="text-sm text-gray-600">{review.tripType}</span>
                      </div>
                      
                      <div className="flex items-center gap-3 mb-2">
                        <span className="text-xs text-gray-500">ID: {review.reviewId}</span>
                        <div className={`px-2 py-1 rounded text-xs text-white ${getPlatformColor(review.platform)}`}>
                          {review.platform}
                        </div>
                        {review.language !== 'es' && (
                          <div className="flex items-center gap-1 px-2 py-1 bg-purple-100 text-purple-700 rounded text-xs">
                            <Languages size={12} />
                            {review.language.toUpperCase()}
                          </div>
                        )}
                        <div className={`px-2 py-1 rounded text-xs ${getSentimentColor(review.sentiment)}`}>
                          {review.sentiment === 'positive' ? 'Positiva' : 
                           review.sentiment === 'negative' ? 'Negativa' : 'Neutral'}
                        </div>
                      </div>
                      
                      <div className="flex items-center gap-3 mb-3">
                        <StarRating rating={review.rating} showValue />
                        <div className="flex gap-1">
                          {review.tags.map(tag => (
                            <span key={tag} className="px-2 py-1 bg-gray-100 text-gray-600 rounded text-xs">
                              {tag}
                            </span>
                          ))}
                        </div>
                      </div>
                      
                      <h5 className="font-medium text-gray-900 mb-3">{review.title}</h5>
                    </div>
                  </div>

                  {/* Content */}
                  {review.positive && (
                    <div className="mb-3 p-3 bg-green-50 rounded-lg border border-green-200">
                      <span className="text-green-600 font-medium text-sm">(+) Aspectos positivos: </span>
                      <span className="text-sm text-gray-700">{review.positive}</span>
                    </div>
                  )}

                  {review.negative && (
                    <div className="mb-4 p-3 bg-red-50 rounded-lg border border-red-200">
                      <span className="text-red-600 font-medium text-sm">(-) Aspectos a mejorar: </span>
                      <span className="text-sm text-gray-700">{review.negative}</span>
                    </div>
                  )}

                  {/* Response Status */}
                  {!review.hasResponse && (
                    <div className="flex items-center gap-2 p-3 bg-yellow-50 rounded-lg mb-4 border border-yellow-200">
                      <AlertCircle className="text-yellow-600 flex-shrink-0" size={16} />
                      <span className="text-sm text-yellow-800 font-medium">No respondida</span>
                      <span className="text-xs text-yellow-600">• Responder mejora tu IRO</span>
                    </div>
                  )}

                  {review.hasResponse && (
                    <div className="flex items-center gap-2 p-3 bg-green-50 rounded-lg mb-4 border border-green-200">
                      <MessageSquare className="text-green-600 flex-shrink-0" size={16} />
                      <span className="text-sm text-green-800 font-medium">Respondida</span>
                    </div>
                  )}

                  {/* Actions */}
                  <div className="flex flex-wrap gap-2">
                    <button className="px-4 py-2 border border-blue-300 text-blue-600 rounded-lg text-sm font-medium hover:bg-blue-50 transition-colors">
                      Integrar OTA
                    </button>
                    
                    {review.language !== 'es' && onTranslateReview && (
                      <button 
                        onClick={() => onTranslateReview(review.id)}
                        className="px-4 py-2 border border-gray-300 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors"
                      >
                        Traducir
                      </button>
                    )}
                    
                    {onRespondToReview && (
                      <button 
                        onClick={() => onRespondToReview(review.id)}
                        className="px-4 py-2 bg-yellow-500 text-white rounded-lg text-sm font-medium hover:bg-yellow-600 transition-colors flex items-center gap-1"
                      >
                        <MessageSquare size={16} />
                        {review.hasResponse ? 'Ver respuesta' : 'Generar respuesta'}
                      </button>
                    )}
                    
                    {onCreateCase && review.negative && (
                      <button 
                        onClick={() => onCreateCase(review.id)}
                        className="px-4 py-2 border border-gray-300 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors"
                      >
                        Crear Caso
                      </button>
                    )}
                  </div>
                </div>
              </div>
            </div>
          ))
        )}
      </div>

      {/* Pagination placeholder */}
      {filteredReviews.length > 0 && (
        <div className="flex justify-center items-center gap-4 py-4">
          <button className="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors disabled:opacity-50" disabled>
            Anterior
          </button>
          <span className="text-sm text-gray-600">Página 1 de 1</span>
          <button className="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors disabled:opacity-50" disabled>
            Siguiente
          </button>
        </div>
      )}
    </div>
  );
};