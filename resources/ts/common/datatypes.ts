export const BUSINESS_TYPE = {
  HOTEL: 1,
  PAINTING: 2,
  BEAUTY: 3,
  SAUNA: 4,
  REAL_ESTATE: 5,
} as const;

export type BusinessType = typeof BUSINESS_TYPE[keyof typeof BUSINESS_TYPE];
