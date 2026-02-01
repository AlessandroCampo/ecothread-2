// ============================================
// Blockchain Types
// ============================================
export interface OnChainProduct {
  productId: string
  creator: string
  createdAt: number
  eventCount: number
  bump: number
  pdaAddress?: string
}

export interface OnChainEvent {
  product: string
  eventIndex: number
  eventType: string
  registrant: string
  timestamp: number
  documentHash: string
  documentUri: string
  metadataHash: string
  bump: number
  pdaAddress?: string
}

// ============================================
// Database Models
// ============================================
export interface Product {
  id: string
  name: string
  description?: string
  product_type?: string
  collection_year?: number
  image_path?: string
  creator_wallet: string
  pda_address?: string
  tx_signature?: string
  creation_timestamp?: number
  status: 'draft' | 'active'
  is_on_chain: boolean
  created_at?: string
  updated_at?: string
  // Relations
  events?: Event[]
  passport?: Passport | null
}

export interface Event {
  id: number
  product_id: string
  index: number
  event_type: string
  trust_level?: string
  title?: string
  description?: string
  location?: string
  document_name?: string
  document_path?: string
  document_hash?: string
  document_uri?: string
  document_gateway_url?: string
  document_mime_type?:string
  registrant_wallet: string
  pda_address?: string
  tx_signature?: string
  timestamp?: number
  status: 'draft' | 'confirmed'
  is_on_chain: boolean
  created_at?: string
  updated_at?: string
    metadata?: Record<string, any>
}

export interface Passport {
  id: number
  passport_number: string
  product_id: string
  status: 'pending' | 'verified' | 'rejected' | 'suspended'
  verification_result?: Record<string, any>
  verified_at?: string
  expires_at?: string
  requested_by_wallet: string
  verified_by?: string
  rejection_reason?: string
  created_at?: string
  updated_at?: string
  // Relations
  product?: Product
}

// ============================================
// Enum Types (from backend)
// ============================================
export interface EnumItem {
  value: string
  label: string
  icon?: string
  color?: string
  description?: string
}

export type EventType = string
export type TrustLevel = string
export type ProductType = string

// ============================================
// API Responses
// ============================================
export interface ApiResponse<T = any> {
  success: boolean
  data?: T
  error?: string
  message?: string
}

export interface PaginatedResponse<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}

// ============================================
// Component Props (combined types)
// ============================================
export interface TimelineEvent extends Partial<Event> {
  type: string
  verified: boolean
  hashMatch?: boolean
  registrant?: string
  onChainData?: OnChainEvent | null
  offChainData?: Event | null
}

// ============================================
// Document Verification
// ============================================
export interface DocumentVerification {
  valid: boolean
  document_uri?: string
  expected_hash?: string
  calculated_hash?: string
  hash_match?: boolean
  file_accessible?: boolean
  error?: string
  verified_at?: string
}