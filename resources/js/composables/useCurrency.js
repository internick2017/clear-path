import { computed, ref } from 'vue'

// Currency configuration (matches backend config)
const currencies = {
  'USD': {
    name: 'US Dollar',
    symbol: '$',
    code: 'USD',
    decimal_places: 2,
  },
  'BRL': {
    name: 'Real Brasileiro',
    symbol: 'R$',
    code: 'BRL',
    decimal_places: 2,
  },
  'EUR': {
    name: 'Euro',
    symbol: '€',
    code: 'EUR',
    decimal_places: 2,
  },
  'GBP': {
    name: 'British Pound',
    symbol: '£',
    code: 'GBP',
    decimal_places: 2,
  },
  'CAD': {
    name: 'Canadian Dollar',
    symbol: 'C$',
    code: 'CAD',
    decimal_places: 2,
  },
  'AUD': {
    name: 'Australian Dollar',
    symbol: 'A$',
    code: 'AUD',
    decimal_places: 2,
  },
  'JPY': {
    name: 'Japanese Yen',
    symbol: '¥',
    code: 'JPY',
    decimal_places: 0,
  },
  'MXN': {
    name: 'Peso Mexicano',
    symbol: '$',
    code: 'MXN',
    decimal_places: 2,
  },
  'ARS': {
    name: 'Peso Argentino',
    symbol: '$',
    code: 'ARS',
    decimal_places: 2,
  },
  'COP': {
    name: 'Peso Colombiano',
    symbol: '$',
    code: 'COP',
    decimal_places: 2,
  },
}

export function useCurrency() {
  const defaultCurrency = ref('USD')

  // Get decimal and thousands separators for a currency
  const getDecimalSeparator = (currency) => {
    const commaDecimalCurrencies = ['BRL', 'EUR', 'ARS', 'COP']
    return commaDecimalCurrencies.includes(currency) ? ',' : '.'
  }

  const getThousandsSeparator = (currency) => {
    const periodThousandsCurrencies = ['BRL', 'EUR', 'ARS', 'COP']
    return periodThousandsCurrencies.includes(currency) ? '.' : ','
  }

  // Format currency with symbol and proper separators
  const formatCurrency = (amount, currency = null) => {
    const currencyCode = currency || defaultCurrency.value
    const currencyConfig = currencies[currencyCode] || currencies['USD']
    
    const symbol = currencyConfig.symbol
    const decimalPlaces = currencyConfig.decimal_places
    const decimalSep = getDecimalSeparator(currencyCode)
    const thousandsSep = getThousandsSeparator(currencyCode)
    
    // JavaScript's toLocaleString with custom separators
    const num = Number(amount)
    let formatted = num.toFixed(decimalPlaces)
    
    // Add thousands separators
    const parts = formatted.split('.')
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousandsSep)
    
    // Join with correct decimal separator
    if (decimalPlaces > 0) {
      formatted = parts.join(decimalSep)
    } else {
      formatted = parts[0]
    }
    
    return `${symbol}${formatted}`
  }

  // Get currency symbol
  const getCurrencySymbol = (currency = null) => {
    const currencyCode = currency || defaultCurrency.value
    return currencies[currencyCode]?.symbol || '$'
  }

  // Get supported currencies for form options
  const getSupportedCurrencies = () => {
    return Object.entries(currencies).map(([code, config]) => ({
      code,
      name: config.name,
      symbol: config.symbol,
      display: `${code} - ${config.name} (${config.symbol})`
    }))
  }

  // Check if currency is supported
  const isCurrencySupported = (currency) => {
    return currency in currencies
  }

  // Set default currency for current session
  const setDefaultCurrency = (currency) => {
    if (isCurrencySupported(currency)) {
      defaultCurrency.value = currency
    }
  }

  // Format stored amounts (in base currency) for display
  // This function assumes the amount is already in the correct currency for display
  const formatStoredAmount = (amount, displayCurrency = null) => {
    // Since we don't have access to conversion rates in frontend,
    // we assume the backend has already converted the amount
    return formatCurrency(amount, displayCurrency || defaultCurrency.value)
  }

  return {
    defaultCurrency: computed(() => defaultCurrency.value),
    formatCurrency,
    formatStoredAmount,
    getCurrencySymbol,
    getSupportedCurrencies,
    isCurrencySupported,
    setDefaultCurrency,
    currencies: computed(() => currencies)
  }
}