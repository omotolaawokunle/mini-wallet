import type { FormErrors } from '@/types/auth'

/**
 * Extracts Laravel validation errors from API response
 * @param err - The error object from axios
 * @returns FormErrors object with field names as keys and error messages as values
 */
export const extractValidationErrors = (err: any): FormErrors => {
  if (err.response?.data?.errors) {
    return Object.entries(err.response.data.errors).reduce(
      (acc, [key, messages]) => {
        acc[key] = (messages as string[])[0] || 'Validation error'
        return acc
      },
      {} as FormErrors
    )
  }
  return {}
}

/**
 * Gets the first error message from Laravel validation response
 * @param err - The error object from axios
 * @returns The first error message or a default message
 */
export const getFirstErrorMessage = (err: any): string => {
  if (err.response?.data?.message) {
    return err.response.data.message
  }

  if (err.response?.data?.errors) {
    const errors = Object.values(err.response.data.errors)
    if (errors.length > 0) {
      const firstError = errors[0]
      return Array.isArray(firstError) ? firstError[0] : 'An error occurred'
    }
  }

  return 'An error occurred. Please try again.'
}

