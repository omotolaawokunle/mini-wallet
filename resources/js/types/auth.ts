export interface User {
    id: number
    name: string
    email: string
    balance: number
    is_flagged: boolean
    flagged_at: string | null
    flagged_reason: string | null
    created_at: string
    updated_at: string
}

export interface LoginCredentials {
    email: string
    password: string
}

export interface RegisterCredentials {
    name: string
    email: string
    password: string
    password_confirmation: string
}

export interface AuthResponse {
    success: boolean
    message: string
    data?: {
        user: User
    }
    errors?: ValidationErrors[]
}

export interface ValidationError {
    field: string
    message: string
}

export interface FormErrors {
    [key: string]: string
}

export interface ValidationErrors {
    [key: string]: string[]
}

