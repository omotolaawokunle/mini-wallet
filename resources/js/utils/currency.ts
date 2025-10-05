export const formatCurrency = (amount: number | string | undefined): string => {
    if (amount === undefined || amount === null) return '$0.00'

    const numAmount = typeof amount === 'string' ? parseFloat(amount) : amount

    if (isNaN(numAmount)) return '$0.00'

    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(numAmount)
}

export const parseAmount = (value: string): number => {
    const cleaned = value.replace(/[^0-9.]/g, '')
    const parsed = parseFloat(cleaned)
    return isNaN(parsed) ? 0 : parsed
}

export const calculateCommission = (amount: number, percentage: number = 0.015): number => {
    return amount * percentage
}

export const calculateTotal = (amount: number, percentage: number = 0.015): number => {
    return amount + calculateCommission(amount, percentage)
}

