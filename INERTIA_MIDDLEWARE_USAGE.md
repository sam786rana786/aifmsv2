# Inertia Middleware & Permissions Usage Guide

Based on Context7 Laravel Inertia documentation, this guide shows how to use the updated HandleInertiaRequests middleware and usePermissions composable for role-based UI in AIFMS v2.

## Updated HandleInertiaRequests Middleware

The middleware now provides comprehensive authentication data structure:

```php
// app/Http/Middleware/HandleInertiaRequests.php
'auth' => [
    'user' => [
        'id' => 1,
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'school_id' => 1,
        'employee_id' => 'EMP001',
        'phone' => '+1234567890',
        'profile_picture' => 'avatar.jpg',
        'school' => [
            'id' => 1,
            'name' => 'Demo School',
            'code' => 'DEMO'
        ]
    ],
    'permissions' => [
        'all' => ['academic_years.view', 'academic_years.create', ...],
        'by_module' => [
            'academic_years' => ['view' => true, 'create' => true],
            'students' => ['view' => true, 'create' => false]
        ],
        'can' => [
            'academic_years' => ['view' => true, 'create' => true],
            'students' => ['view' => true, 'create' => false]
        ]
    ],
    'roles' => [
        'all' => ['School Admin'],
        'is_super_admin' => false,
        'is_school_admin' => true,
        'is_accountant' => false,
        // ... other role flags
    ]
]
```

## Frontend Usage Examples

### 1. Basic Permission Checking in Vue Components

```vue
<template>
  <div>
    <!-- Show button only if user can create academic years -->
    <button v-if="can.createAcademicYears" @click="createYear">
      Create Academic Year
    </button>
    
    <!-- Show different content based on role -->
    <div v-if="is.superAdmin">
      Super Admin Dashboard
    </div>
    <div v-else-if="is.schoolAdmin">
      School Admin Dashboard
    </div>
    
    <!-- Resource-based permission checking -->
    <table v-if="canAccess('academic_years', 'view')">
      <tr v-for="year in academicYears" :key="year.id">
        <td>{{ year.name }}</td>
        <td>
          <button v-if="canAccess('academic_years', 'edit')" @click="edit(year)">
            Edit
          </button>
          <button v-if="canAccess('academic_years', 'delete')" @click="delete(year)">
            Delete
          </button>
        </td>
      </tr>
    </table>
  </div>
</template>

<script setup>
import { usePermissions } from '@/composables/usePermissions'

const { can, is, canAccess } = usePermissions()

// Component logic here
</script>
```

### 2. Navigation Menu with Permission-Based Visibility

```vue
<template>
  <nav>
    <ul>
      <!-- Academic Management -->
      <li v-if="can.viewAcademicYears || can.viewClasses || can.viewStudents">
        <a href="#">Academic Management</a>
        <ul>
          <li v-if="can.viewAcademicYears">
            <Link href="/academic-years">Academic Years</Link>
          </li>
          <li v-if="can.viewClasses">
            <Link href="/classes">Classes</Link>
          </li>
          <li v-if="can.viewStudents">
            <Link href="/students">Students</Link>
          </li>
        </ul>
      </li>
      
      <!-- Fee Management -->
      <li v-if="can.viewPayments || can.viewFeeTypes">
        <a href="#">Fee Management</a>
        <ul>
          <li v-if="can.viewPayments">
            <Link href="/payments">Payments</Link>
          </li>
          <li v-if="can.viewFeeTypes">
            <Link href="/fee-types">Fee Types</Link>
          </li>
        </ul>
      </li>
      
      <!-- Admin Only -->
      <li v-if="is.superAdmin || is.schoolAdmin">
        <a href="#">Administration</a>
        <ul>
          <li v-if="can.viewUsers">
            <Link href="/users">Users</Link>
          </li>
          <li v-if="can.viewSettings">
            <Link href="/settings">Settings</Link>
          </li>
        </ul>
      </li>
    </ul>
  </nav>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { usePermissions } from '@/composables/usePermissions'

const { can, is } = usePermissions()
</script>
```

### 3. Conditional Form Fields Based on Permissions

```vue
<template>
  <form @submit.prevent="submit">
    <div>
      <label>Student Name</label>
      <input v-model="form.name" :disabled="!can.editStudents" />
    </div>
    
    <!-- Only show sensitive fields to authorized users -->
    <div v-if="is.superAdmin || is.schoolAdmin">
      <label>School Assignment</label>
      <select v-model="form.school_id">
        <option v-for="school in schools" :key="school.id" :value="school.id">
          {{ school.name }}
        </option>
      </select>
    </div>
    
    <!-- Fee-related fields only for fee managers -->
    <div v-if="is.feeManager || is.accountant || is.superAdmin">
      <label>Fee Concession</label>
      <input v-model="form.concession_amount" type="number" />
    </div>
    
    <button 
      type="submit" 
      :disabled="!canAccess('students', form.id ? 'edit' : 'create')"
    >
      {{ form.id ? 'Update' : 'Create' }} Student
    </button>
  </form>
</template>

<script setup>
import { usePermissions } from '@/composables/usePermissions'

const { can, is, canAccess } = usePermissions()

// Form logic here
</script>
```

### 4. Advanced Permission Checking

```vue
<script setup>
import { usePermissions } from '@/composables/usePermissions'

const { 
  hasPermission, 
  hasAnyPermission, 
  hasAllPermissions,
  canAccess,
  user
} = usePermissions()

// Check specific permission
const canDeleteStudents = hasPermission('students.delete')

// Check if user has ANY of these permissions
const canManageAcademics = hasAnyPermission([
  'academic_years.create',
  'classes.create',
  'students.create'
])

// Check if user has ALL of these permissions
const canFullyManageUsers = hasAllPermissions([
  'users.view',
  'users.create',
  'users.edit',
  'users.delete'
])

// Dynamic permission checking
const canManageResource = (resource: string) => {
  return canAccess(resource, 'view') && canAccess(resource, 'edit')
}

// School-based logic
const isOwnSchool = (schoolId: number) => {
  return user.value?.school_id === schoolId || is.superAdmin
}
</script>
```

### 5. Permission-Based Route Guards

```javascript
// router/index.js or similar
import { usePermissions } from '@/composables/usePermissions'

export function canAccessRoute(routeName) {
  const { canAccess, is } = usePermissions()
  
  const routePermissions = {
    'academic-years.index': () => canAccess('academic_years', 'view'),
    'students.create': () => canAccess('students', 'create'),
    'reports.index': () => canAccess('reports', 'view'),
    'settings.index': () => is.superAdmin || is.schoolAdmin,
    'users.index': () => canAccess('users', 'view'),
  }
  
  const checker = routePermissions[routeName]
  return checker ? checker() : false
}
```

## Performance Benefits

### 1. Pre-computed Permissions
The middleware pre-computes permission checks on the backend, reducing frontend computation:

```javascript
// Instead of checking every time:
const canEdit = hasPermission('students.edit') // Backend call simulation

// Use pre-computed values:
const canEdit = can.editStudents // Direct boolean access
```

### 2. Module-based Organization
Permissions are organized by module for efficient checking:

```javascript
// Direct module access
const academicPermissions = permissionsByModule.academic_years
// { view: true, create: true, edit: false, delete: false }
```

### 3. Role-based Shortcuts
Quick role checking without string comparisons:

```javascript
// Instead of:
const isAdmin = hasRole('Super Admin') // String comparison

// Use:
const isAdmin = is.superAdmin // Direct boolean access
```

## Best Practices

### 1. Use Specific Permission Checks in Templates
```vue
<!-- Good: Specific permission -->
<button v-if="can.createStudents">Create Student</button>

<!-- Avoid: Generic role check -->
<button v-if="is.schoolAdmin">Create Student</button>
```

### 2. Combine Permissions with Business Logic
```vue
<template>
  <button 
    v-if="canCreateStudent && hasAvailableSlots"
    @click="createStudent"
  >
    Create Student
  </button>
</template>

<script setup>
const { can } = usePermissions()
const canCreateStudent = can.createStudents
const hasAvailableSlots = computed(() => studentCount.value < maxStudents.value)
</script>
```

### 3. Handle Loading States
```vue
<template>
  <div v-if="user">
    <!-- Permission-based content -->
  </div>
  <div v-else>
    Loading...
  </div>
</template>

<script setup>
const { user } = usePermissions()
</script>
```

## Migration from Old System

### Before (Old Pattern)
```javascript
// Old way
const permissions = page.props.auth?.user?.permissions || []
const roles = page.props.auth?.user?.roles || []
const hasPermission = (perm) => permissions.includes(perm)
const hasRole = (role) => roles.some(r => r.name === role)
```

### After (New Pattern)
```javascript
// New way
const { can, is, hasPermission, hasRole } = usePermissions()
// Direct access to pre-computed values with better performance
```

This new structure provides better performance, type safety, and easier frontend development while maintaining full backward compatibility. 