// types/hotel.ts
export interface Hotel {
  id: string;
  name: string;
  type: 'hotel' | 'competitor' | 'average';
}

export interface IROData {
  score: number;
  change: number;
  trend: 'up' | 'down';
  calificacion: ComponentScore;
  cobertura: ComponentScore;
  reseñas: ComponentScore;
}

export interface ComponentScore {
  value: number;
  trend: 'up' | 'down';
}

export interface SemanticData {
  score: number;
  status: 'excellent' | 'good' | 'regular' | 'bad' | 'very_bad';
  change: number;
  message: string;
}

export interface ReputationStats {
  calificacionesOTAs: StatItem;
  cantidadReseñas: StatItem;
  coberturaReseñas: StatItem;
  nps: StatItem;
}

export interface StatItem {
  period: number;
  accumulated: number;
  change: number;
  accChange: number;
}

export interface OTAData {
  id: string;
  name: string;
  logo: string;
  rating: number | null;
  ratingChange: number | null;
  reviews: number | null;
  reviewsChange: number | null;
  accumulated2025: number | null;
  totalReviews: number;
  bgColor: string;
  isActive: boolean;
}

export interface ReviewData {
  id: string;
  guest: string;
  country: string;
  date: string;
  tripType: string;
  reviewId: string;
  platform: string;
  rating: number;
  title: string;
  positive: string;
  negative: string;
  hasResponse: boolean;
  platformColor: string;
  language: string;
  sentiment: 'positive' | 'neutral' | 'negative';
  tags: string[];
}

export interface DashboardData {
  iro: IROData;
  semantico: SemanticData;
  stats: ReputationStats;
  otas: OTAData[];
  reviews: ReviewData[];
}

export interface FilterOptions {
  dateRange: '7' | '30' | '60' | '90' | '365';
  platform: string[];
  rating: number[];
  sentiment: string[];
  language: string[];
}

export interface RecommendationItem {
  id: string;
  title: string;
  description: string;
  priority: 'high' | 'medium' | 'low';
  category: 'iro' | 'semantic' | 'reviews' | 'otas';
  action: string;
}

// Enums para mejor tipado
export enum OTAPlatform {
  BOOKING = 'booking',
  EXPEDIA = 'expedia',
  GOOGLE = 'google',
  TRIPADVISOR = 'tripadvisor',
  DESPEGAR = 'despegar'
}

export enum ReviewSentiment {
  POSITIVE = 'positive',
  NEUTRAL = 'neutral',
  NEGATIVE = 'negative'
}

export enum TrendDirection {
  UP = 'up',
  DOWN = 'down',
  STABLE = 'stable'
}