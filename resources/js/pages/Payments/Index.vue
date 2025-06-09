<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import { CreditCard, Eye, Edit, Trash2, Plus, Search, Filter } from 'lucide-vue-next'

import AppLayout from '@/layouts/AppLayout.vue'
import { usePermissions } from '@/composables/usePermissions'
import { formatDate } from '@/lib/utils'

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/components/ui/alert-dialog'

interface Payment {
  id: number
  receipt_number: string
  amount: string
  late_fee: string
  payment_method: string
  payment_date: string
  status: 'pending' | 'paid' | 'cancelled'
  created_at: string
  student: {
    id: number
    first_name: string
    last_name: string
    admission_no: string
  }
  fee_type: {
    id: number
    name: string
  }
  academic_year: {
    id: number
    name: string
  }
  collected_by: {
    id: number
    name: string
  }
}

interface FeeType {
  id: number
  name: string
}

interface AcademicYear {
  id: number
  name: string
}

interface Props {
  payments: {
    data: Payment[]
    meta: {
      current_page: number
      total: number
      per_page: number
      last_page: number
    }
  }
  feeTypes: FeeType[]
  academicYears: AcademicYear[]
  filters: {
    search?: string
    status?: string
    fee_type_id?: string
    academic_year_id?: string
  }
}

const props = defineProps<Props>()
const { hasPermission } = usePermissions()

const breadcrumbs = [
  { label: 'Payments' }
]

// Filter states
const search = ref(props.filters.search || '')
const selectedStatus = ref(props.filters.status || '')
const selectedFeeType = ref(props.filters.fee_type_id || '')
const selectedAcademicYear = ref(props.filters.academic_year_id || '')

// Watch for filter changes and update URL
watch([search, selectedStatus, selectedFeeType, selectedAcademicYear], () => {
  router.get(route('payments.index'), {
    search: search.value || undefined,
    status: selectedStatus.value || undefined,
    fee_type_id: selectedFeeType.value || undefined,
    academic_year_id: selectedAcademicYear.value || undefined,
  }, {
    preserveState: true,
    replace: true,
  })
}, { debounce: 300 })

const deletePayment = (id: number) => {
  router.delete(route('payments.destroy', id), {
    onSuccess: () => {
      // Success message will be handled by the backend
    }
  })
}

const clearFilters = () => {
  search.value = ''
  selectedStatus.value = ''
  selectedFeeType.value = ''
  selectedAcademicYear.value = ''
}

const getStatusVariant = (status: string) => {
  switch (status) {
    case 'paid':
      return 'default'
    case 'pending':
      return 'secondary'
    case 'cancelled':
      return 'destructive'
    default:
      return 'secondary'
  }
}

const getPaymentMethodLabel = (method: string) => {
  switch (method) {
    case 'cash':
      return 'Cash'
    case 'cheque':
      return 'Cheque'
    case 'bank_transfer':
      return 'Bank Transfer'
    case 'upi':
      return 'UPI'
    case 'card':
      return 'Card'
    default:
      return method.replace('_', ' ').toUpperCase()
  }
}

const formatCurrency = (amount: string) => {
  return `₹${parseFloat(amount).toLocaleString('en-IN')}`
}
</script>

<template>
  <Head title="Payments" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Payments</h1>
          <p class="text-muted-foreground">
            Manage fee payments and transactions
          </p>
        </div>
        <Button v-if="hasPermission('payments.create')" as-child>
          <Link href="/payments/create">
            <Plus class="mr-2 h-4 w-4" />
            Record Payment
          </Link>
        </Button>
      </div>

      <!-- Filters -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Filter class="h-5 w-5" />
            Filters
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid gap-4 md:grid-cols-5">
            <!-- Search -->
            <div class="space-y-2">
              <label class="text-sm font-medium">Search</label>
              <div class="relative">
                <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                <Input
                  v-model="search"
                  placeholder="Search by student, receipt..."
                  class="pl-9"
                />
              </div>
            </div>

            <!-- Status Filter -->
            <div class="space-y-2">
              <label class="text-sm font-medium">Status</label>
              <Select v-model="selectedStatus">
                <SelectTrigger>
                  <SelectValue placeholder="All statuses" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">All statuses</SelectItem>
                  <SelectItem value="paid">Paid</SelectItem>
                  <SelectItem value="pending">Pending</SelectItem>
                  <SelectItem value="cancelled">Cancelled</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <!-- Fee Type Filter -->
            <div class="space-y-2">
              <label class="text-sm font-medium">Fee Type</label>
              <Select v-model="selectedFeeType">
                <SelectTrigger>
                  <SelectValue placeholder="All fee types" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">All fee types</SelectItem>
                  <SelectItem v-for="feeType in feeTypes" :key="feeType.id" :value="feeType.id.toString()">
                    {{ feeType.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <!-- Academic Year Filter -->
            <div class="space-y-2">
              <label class="text-sm font-medium">Academic Year</label>
              <Select v-model="selectedAcademicYear">
                <SelectTrigger>
                  <SelectValue placeholder="All years" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">All years</SelectItem>
                  <SelectItem v-for="year in academicYears" :key="year.id" :value="year.id.toString()">
                    {{ year.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <!-- Clear Filters -->
            <div class="flex items-end">
              <Button variant="outline" @click="clearFilters" class="w-full">
                Clear Filters
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Payments Table -->
      <Card>
        <CardHeader>
          <CardTitle>Payments</CardTitle>
          <CardDescription>
            Total: {{ props.payments.meta.total }} payments
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="props.payments.data.length === 0" class="text-center py-8">
            <CreditCard class="mx-auto h-12 w-12 text-muted-foreground" />
            <h3 class="mt-2 text-sm font-semibold text-gray-900">No payments found</h3>
            <p class="mt-1 text-sm text-gray-500">
              {{ search || selectedStatus || selectedFeeType || selectedAcademicYear ? 'Try adjusting your filters' : 'Get started by recording a new payment.' }}
            </p>
            <div class="mt-6">
              <Button v-if="hasPermission('payments.create')" as-child>
                <Link href="/payments/create">
                  <Plus class="mr-2 h-4 w-4" />
                  Record Payment
                </Link>
              </Button>
            </div>
          </div>

          <div v-else>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Receipt No</TableHead>
                  <TableHead>Student</TableHead>
                  <TableHead>Fee Type</TableHead>
                  <TableHead>Amount</TableHead>
                  <TableHead>Method</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Date</TableHead>
                  <TableHead>Collected By</TableHead>
                  <TableHead v-if="hasPermission('payments.view') || hasPermission('payments.edit') || hasPermission('payments.delete')" class="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="payment in props.payments.data" :key="payment.id">
                  <TableCell class="font-medium">{{ payment.receipt_number }}</TableCell>
                  <TableCell>
                    <div>
                      <div class="font-medium">{{ payment.student.first_name }} {{ payment.student.last_name }}</div>
                      <div class="text-sm text-muted-foreground">{{ payment.student.admission_no }}</div>
                    </div>
                  </TableCell>
                  <TableCell>{{ payment.fee_type.name }}</TableCell>
                  <TableCell>
                    <div>
                      <div class="font-medium">{{ formatCurrency(payment.amount) }}</div>
                      <div v-if="parseFloat(payment.late_fee) > 0" class="text-sm text-muted-foreground">
                        + {{ formatCurrency(payment.late_fee) }} late fee
                      </div>
                    </div>
                  </TableCell>
                  <TableCell>{{ getPaymentMethodLabel(payment.payment_method) }}</TableCell>
                  <TableCell>
                    <Badge :variant="getStatusVariant(payment.status)">
                      {{ payment.status.toUpperCase() }}
                    </Badge>
                  </TableCell>
                  <TableCell>{{ formatDate(payment.payment_date) }}</TableCell>
                  <TableCell>{{ payment.collected_by.name }}</TableCell>
                  <TableCell v-if="hasPermission('payments.view') || hasPermission('payments.edit') || hasPermission('payments.delete')" class="text-right">
                    <div class="flex items-center justify-end gap-2">
                      <Button v-if="hasPermission('payments.view')" variant="ghost" size="sm" as-child>
                        <Link :href="`/payments/${payment.id}`">
                          <Eye class="h-4 w-4" />
                        </Link>
                      </Button>
                      <Button v-if="hasPermission('payments.edit')" variant="ghost" size="sm" as-child>
                        <Link :href="`/payments/${payment.id}/edit`">
                          <Edit class="h-4 w-4" />
                        </Link>
                      </Button>
                      <AlertDialog v-if="hasPermission('payments.delete') && payment.status !== 'paid'">
                        <AlertDialogTrigger as-child>
                          <Button variant="ghost" size="sm">
                            <Trash2 class="h-4 w-4" />
                          </Button>
                        </AlertDialogTrigger>
                        <AlertDialogContent>
                          <AlertDialogHeader>
                            <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                            <AlertDialogDescription>
                              This action cannot be undone. This will permanently delete the payment record "{{ payment.receipt_number }}".
                            </AlertDialogDescription>
                          </AlertDialogHeader>
                          <AlertDialogFooter>
                            <AlertDialogCancel>Cancel</AlertDialogCancel>
                            <AlertDialogAction @click="deletePayment(payment.id)">
                              Delete
                            </AlertDialogAction>
                          </AlertDialogFooter>
                        </AlertDialogContent>
                      </AlertDialog>
                    </div>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
 