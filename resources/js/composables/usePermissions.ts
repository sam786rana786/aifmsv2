import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

interface AuthData {
  user: any
  permissions: {
    all: string[]
    by_module: Record<string, Record<string, boolean>>
    can: Record<string, Record<string, boolean>>
  }
  roles: {
    all: string[]
    is_super_admin: boolean
    is_school_admin: boolean
    is_accountant: boolean
    is_receptionist: boolean
    is_teacher: boolean
    is_transport_manager: boolean
    is_fee_manager: boolean
    is_data_entry_operator: boolean
  }
}

interface RoleChecks {
  is_super_admin?: boolean
  is_school_admin?: boolean
  is_accountant?: boolean
  is_receptionist?: boolean
  is_teacher?: boolean
  is_transport_manager?: boolean
  is_fee_manager?: boolean
  is_data_entry_operator?: boolean
}

export function usePermissions() {
  const page = usePage()
  
  const auth = computed((): AuthData | null => {
    return (page.props as any).auth || null
  })
  
  const user = computed(() => {
    return auth.value?.user || null
  })
  
  const userPermissions = computed(() => {
    return auth.value?.permissions?.all || []
  })
  
  const userRoles = computed(() => {
    return auth.value?.roles?.all || []
  })
  
  const permissionsByModule = computed(() => {
    return auth.value?.permissions?.by_module || {}
  })
  
  const canPermissions = computed(() => {
    return auth.value?.permissions?.can || {}
  })
  
  const roleChecks = computed((): RoleChecks => {
    return auth.value?.roles || {}
  })
  
  // Primary permission checking method
  const hasPermission = (permission: string): boolean => {
    if (roleChecks.value.is_super_admin === true) return true
    return userPermissions.value.includes(permission)
  }
  
  // Role checking method
  const hasRole = (role: string): boolean => {
    return userRoles.value.includes(role)
  }
  
  // Check multiple permissions (OR logic)
  const hasAnyPermission = (permissions: string[]): boolean => {
    if (roleChecks.value.is_super_admin === true) return true
    return permissions.some(permission => hasPermission(permission))
  }
  
  // Check multiple permissions (AND logic)
  const hasAllPermissions = (permissions: string[]): boolean => {
    if (roleChecks.value.is_super_admin === true) return true
    return permissions.every(permission => hasPermission(permission))
  }
  
  // Resource-based permission checking
  const canAccess = (resource: string, action: string = 'view'): boolean => {
    if (roleChecks.value.is_super_admin === true) return true
    
    // First try using pre-computed can permissions for better performance
    const resourcePermissions = canPermissions.value[resource]
    if (resourcePermissions && typeof resourcePermissions[action] === 'boolean') {
      return resourcePermissions[action]
    }
    
    // Fallback to module-based checking
    const modulePermissions = permissionsByModule.value[resource]
    if (modulePermissions && typeof modulePermissions[action] === 'boolean') {
      return modulePermissions[action]
    }
    
    // Final fallback to direct permission checking
    return hasPermission(`${resource}.${action}`)
  }
  
  // Quick access to common permissions for UI elements
  const can = computed(() => ({
    // Academic Management
    viewAcademicYears: canPermissions.value.academic_years?.view || false,
    createAcademicYears: canPermissions.value.academic_years?.create || false,
    editAcademicYears: canPermissions.value.academic_years?.edit || false,
    deleteAcademicYears: canPermissions.value.academic_years?.delete || false,
    
    viewClasses: canPermissions.value.classes?.view || false,
    createClasses: canPermissions.value.classes?.create || false,
    editClasses: canPermissions.value.classes?.edit || false,
    deleteClasses: canPermissions.value.classes?.delete || false,
    
    viewStudents: canPermissions.value.students?.view || false,
    createStudents: canPermissions.value.students?.create || false,
    editStudents: canPermissions.value.students?.edit || false,
    deleteStudents: canPermissions.value.students?.delete || false,
    
    // Fee Management
    viewFeeTypes: canPermissions.value.fee_types?.view || false,
    createFeeTypes: canPermissions.value.fee_types?.create || false,
    editFeeTypes: canPermissions.value.fee_types?.edit || false,
    deleteFeeTypes: canPermissions.value.fee_types?.delete || false,
    
    viewPayments: canPermissions.value.payments?.view || false,
    createPayments: canPermissions.value.payments?.create || false,
    editPayments: canPermissions.value.payments?.edit || false,
    deletePayments: canPermissions.value.payments?.delete || false,
    
    // Transport Management
    viewTransportRoutes: canPermissions.value.transport_routes?.view || false,
    createTransportRoutes: canPermissions.value.transport_routes?.create || false,
    editTransportRoutes: canPermissions.value.transport_routes?.edit || false,
    deleteTransportRoutes: canPermissions.value.transport_routes?.delete || false,
    
    // User Management
    viewUsers: canPermissions.value.users?.view || false,
    createUsers: canPermissions.value.users?.create || false,
    editUsers: canPermissions.value.users?.edit || false,
    deleteUsers: canPermissions.value.users?.delete || false,
    
    // Reports & Analytics
    viewReports: canPermissions.value.reports?.view || false,
    exportReports: canPermissions.value.reports?.export || false,
    viewAnalytics: canPermissions.value.analytics?.view || false,
    
    // Settings
    viewSettings: canPermissions.value.settings?.view || false,
    editSettings: canPermissions.value.settings?.edit || false,
  }))
  
  // Role-based computed properties for easier template usage
  const is = computed(() => ({
    superAdmin: roleChecks.value.is_super_admin === true,
    schoolAdmin: roleChecks.value.is_school_admin === true,
    accountant: roleChecks.value.is_accountant === true,
    receptionist: roleChecks.value.is_receptionist === true,
    teacher: roleChecks.value.is_teacher === true,
    transportManager: roleChecks.value.is_transport_manager === true,
    feeManager: roleChecks.value.is_fee_manager === true,
    dataEntryOperator: roleChecks.value.is_data_entry_operator === true,
  }))
  
  // Legacy computed properties for backward compatibility
  const isSuperAdmin = computed(() => is.value.superAdmin)
  const isSchoolAdmin = computed(() => is.value.schoolAdmin)
  const isAccountant = computed(() => is.value.accountant)
  const isReceptionist = computed(() => is.value.receptionist)
  const isTeacher = computed(() => is.value.teacher)
  const isTransportManager = computed(() => is.value.transportManager)
  const isFeeManager = computed(() => is.value.feeManager)
  const isDataEntryOperator = computed(() => is.value.dataEntryOperator)
  
  return {
    // Core data
    auth,
    user,
    userPermissions,
    userRoles,
    permissionsByModule,
    canPermissions,
    roleChecks,
    
    // Permission checking methods
    hasPermission,
    hasRole,
    hasAnyPermission,
    hasAllPermissions,
    canAccess,
    
    // Quick access computed properties
    can,
    is,
    
    // Legacy computed properties (for backward compatibility)
    isSuperAdmin,
    isSchoolAdmin,
    isAccountant,
    isReceptionist,
    isTeacher,
    isTransportManager,
    isFeeManager,
    isDataEntryOperator,
  }
} 