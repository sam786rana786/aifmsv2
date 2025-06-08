import { ref, computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'

interface Setting {
  key: string
  value: any
  type: 'string' | 'number' | 'boolean' | 'json'
  is_encrypted?: boolean
  school_id?: number
}

export function useSettings() {
  const page = usePage()
  
  const settings = computed(() => {
    return page.props.settings || {}
  })
  
  const getSetting = (key: string, defaultValue: any = null): any => {
    const setting = settings.value[key]
    if (setting === undefined || setting === null) {
      return defaultValue
    }
    
    // Handle different value types
    if (typeof setting === 'object' && setting.value !== undefined) {
      return setting.value
    }
    
    return setting
  }
  
  const getSystemSetting = (key: string, defaultValue: any = null): any => {
    return getSetting(`system.${key}`, defaultValue)
  }
  
  const getSchoolSetting = (key: string, defaultValue: any = null): any => {
    const schoolId = page.props.auth?.user?.school_id
    if (!schoolId) return defaultValue
    return getSetting(`school.${schoolId}.${key}`, defaultValue)
  }
  
  const updateSetting = async (key: string, value: any, isSchoolSpecific: boolean = false) => {
    try {
      await router.post('/settings/update', {
        key,
        value,
        is_school_specific: isSchoolSpecific
      }, {
        preserveState: true,
        preserveScroll: true,
      })
    } catch (error) {
      console.error('Failed to update setting:', error)
      throw error
    }
  }
  
  // Common settings getters with defaults
  const appName = computed(() => getSystemSetting('app_name', 'AIFMS v2'))
  const appLogo = computed(() => getSystemSetting('app_logo', '/images/logo.png'))
  const timezone = computed(() => getSystemSetting('timezone', 'UTC'))
  const dateFormat = computed(() => getSystemSetting('date_format', 'Y-m-d'))
  const currency = computed(() => getSystemSetting('currency', 'USD'))
  const currencySymbol = computed(() => getSystemSetting('currency_symbol', '$'))
  
  // School-specific settings
  const schoolName = computed(() => getSchoolSetting('name', 'School'))
  const schoolAddress = computed(() => getSchoolSetting('address', ''))
  const schoolPhone = computed(() => getSchoolSetting('phone', ''))
  const schoolEmail = computed(() => getSchoolSetting('email', ''))
  const lateFeePercentage = computed(() => getSchoolSetting('late_fee_percentage', 5))
  const academicYearStart = computed(() => getSchoolSetting('academic_year_start', '01-04'))
  const workingDays = computed(() => getSchoolSetting('working_days', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']))
  
  // Fee settings
  const enableOnlinePayments = computed(() => getSchoolSetting('enable_online_payments', false))
  const paymentGateway = computed(() => getSchoolSetting('payment_gateway', 'stripe'))
  const feeReminderDays = computed(() => getSchoolSetting('fee_reminder_days', [7, 3, 1]))
  
  // Transport settings
  const enableTransport = computed(() => getSchoolSetting('enable_transport', false))
  const transportFeeStructure = computed(() => getSchoolSetting('transport_fee_structure', 'distance_based'))
  
  // Notification settings
  const enableSmsNotifications = computed(() => getSchoolSetting('enable_sms_notifications', false))
  const enableEmailNotifications = computed(() => getSchoolSetting('enable_email_notifications', true))
  const smsProvider = computed(() => getSchoolSetting('sms_provider', 'twilio'))
  
  return {
    settings,
    getSetting,
    getSystemSetting,
    getSchoolSetting,
    updateSetting,
    
    // System settings
    appName,
    appLogo,
    timezone,
    dateFormat,
    currency,
    currencySymbol,
    
    // School settings
    schoolName,
    schoolAddress,
    schoolPhone,
    schoolEmail,
    lateFeePercentage,
    academicYearStart,
    workingDays,
    
    // Feature settings
    enableOnlinePayments,
    paymentGateway,
    feeReminderDays,
    enableTransport,
    transportFeeStructure,
    enableSmsNotifications,
    enableEmailNotifications,
    smsProvider,
  }
} 