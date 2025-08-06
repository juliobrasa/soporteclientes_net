// data/mockData.ts
import { Hotel, DashboardData, OTAData, ReviewData } from '../types/hotel';

export const hotels: Hotel[] = [
  { id: '1', name: 'Hotel Terracaribe Cancun', type: 'hotel' },
  { id: '2', name: 'Hotel Plaza Kokai Cancún', type: 'competitor' },
  { id: '3', name: 'Top 10% myHotel', type: 'competitor' },
  { id: '4', name: 'Suites Cancun Center', type: 'competitor' },
  { id: '5', name: 'Promedio myHotel', type: 'average' }
];

export const dashboardData: DashboardData = {
  iro: {
    score: 74,
    change: 9,
    trend: 'up',
    calificacion: { value: 77, trend: 'up' },
    cobertura: { value: 82, trend: 'up' },
    reseñas: { value: 56, trend: 'down' }
  },
  semantico: {
    score: 29,
    status: 'bad',
    change: -50,
    message: 'Cuidado, tu propiedad tiene bastantes menciones negativas en los comentarios.'
  },
  stats: {
    calificacionesOTAs: { period: 3.85, accumulated: 4, change: -4, accChange: 10 },
    cantidadReseñas: { period: 30, accumulated: 341, change: 11, accChange: 231 },
    coberturaReseñas: { period: 67, accumulated: 62, change: -5, accChange: 99 },
    nps: { period: 17, accumulated: 27, change: -9, accChange: 16 }
  },
  otas: [
    {
      id: 'expedia',
      name: 'Expedia Group',
      logo: 'E',
      rating: 5,
      ratingChange: 66.67,
      reviews: 1,
      reviewsChange: -66.67,
      accumulated2025: 3.85,
      totalReviews: 20,
      bgColor: 'bg-blue-600',
      isActive: true
    },
    {
      id: 'booking',
      name: 'Booking.com',
      logo: 'B',
      rating: 3.98,
      ratingChange: 0.25,
      reviews: 23,
      reviewsChange: 35.29,
      accumulated2025: 3.97,
      totalReviews: 262,
      bgColor: 'bg-blue-700',
      isActive: true
    },
    {
      id: 'google',
      name: 'Google',
      logo: 'G',
      rating: 3.17,
      ratingChange: -30.63,
      reviews: 6,
      reviewsChange: -14.29,
      accumulated2025: 4.19,
      totalReviews: 54,
      bgColor: 'bg-red-500',
      isActive: true
    },
    {
      id: 'tripadvisor',
      name: 'TripAdvisor',
      logo: 'T',
      rating: null,
      ratingChange: null,
      reviews: null,
      reviewsChange: null,
      accumulated2025: null,
      totalReviews: 0,
      bgColor: 'bg-green-600',
      isActive: false
    },
    {
      id: 'despegar',
      name: 'Despegar Group',
      logo: 'D',
      rating: null,
      ratingChange: null,
      reviews: null,
      reviewsChange: null,
      accumulated2025: 4.05,
      totalReviews: 5,
      bgColor: 'bg-purple-600',
      isActive: false
    }
  ],
  reviews: [
    {
      id: '1',
      guest: 'Gabriel',
      country: 'Mexico',
      date: '01 ago 2025',
      tripType: 'Viajo En Pareja',
      reviewId: '29053315',
      platform: 'Booking.com',
      rating: 5,
      title: 'Recomendadísimo en todos los aspectos',
      positive: 'Excelente atención y predisposición de parte de todo el staff, desde recepción, limpieza y la gente del restaurante.',
      negative: 'Que tienen tortugas en cautiverio, en un espacio súper pequeño y no en muy buen estado. Me gustaría que estén al aire libre, en su hábitat natural.',
      hasResponse: false,
      platformColor: 'bg-blue-700',
      language: 'es',
      sentiment: 'positive',
      tags: ['staff', 'servicio', 'animales']
    },
    {
      id: '2',
      guest: 'Seenu',
      country: 'Mexico',
      date: '01 ago 2025',
      tripType: 'Viajo Con Amigos',
      reviewId: '29053314',
      platform: 'Booking.com',
      rating: 4.5,
      title: 'Excellent',
      positive: 'Location is safe and secure',
      negative: '',
      hasResponse: false,
      platformColor: 'bg-blue-700',
      language: 'en',
      sentiment: 'positive',
      tags: ['ubicación', 'seguridad']
    }
  ]
};

// Funciones helper para generar datos adicionales
export const generateMockReviews = (count: number): ReviewData[] => {
  const guestNames = ['Carlos', 'Maria', 'John', 'Sophie', 'Luis', 'Anna'];
  const countries = ['Mexico', 'USA', 'Spain', 'France', 'Colombia', 'Argentina'];
  const platforms = ['Booking.com', 'Expedia', 'Google'];
  const tripTypes = ['Viajo En Pareja', 'Viajo Con Amigos', 'Viajo Solo', 'Viaje Familiar'];
  
  return Array.from({ length: count }, (_, i) => ({
    id: `mock-${i + 3}`,
    guest: guestNames[Math.floor(Math.random() * guestNames.length)],
    country: countries[Math.floor(Math.random() * countries.length)],
    date: `${Math.floor(Math.random() * 30) + 1} jul 2025`,
    tripType: tripTypes[Math.floor(Math.random() * tripTypes.length)],
    reviewId: `${29053300 + i}`,
    platform: platforms[Math.floor(Math.random() * platforms.length)],
    rating: Math.floor(Math.random() * 5) + 1,
    title: 'Review generada automáticamente',
    positive: 'Aspectos positivos de la estancia',
    negative: Math.random() > 0.6 ? 'Algunos aspectos a mejorar' : '',
    hasResponse: Math.random() > 0.7,
    platformColor: 'bg-blue-700',
    language: Math.random() > 0.5 ? 'es' : 'en',
    sentiment: Math.random() > 0.7 ? 'negative' : Math.random() > 0.3 ? 'positive' : 'neutral',
    tags: ['servicio', 'limpieza', 'ubicación'].slice(0, Math.floor(Math.random() * 3) + 1)
  }));
};