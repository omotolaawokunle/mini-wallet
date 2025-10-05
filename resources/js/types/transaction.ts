import type { User, ValidationErrors, FormErrors as AuthFormErrors } from './auth'

export type FormErrors = AuthFormErrors

export interface Transaction {
    id: number
    sender_id: number
    receiver_id: number
    sender?: User
    receiver?: User
    amount: number
    commission_fee: number
    type: 'Debit' | 'Credit'
    created_at: string
}

export interface TransferFormData {
    receiver_id: string
    amount: string
}

export interface TransferPayload {
    receiver_id: number
    amount: number
}

export interface TransactionResponse {
    success: boolean
    message: string
    data?: Transaction
    errors?: ValidationErrors[]
}

export interface TransactionListResponse {
    success: boolean
    message: string
    data?: Transaction[]
    meta?: {
        current_page: number
        last_page: number
        per_page: number
        total: number
    }
}


