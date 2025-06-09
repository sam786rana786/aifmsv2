<template>
  <div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Fee Structures</h1>
        <p class="text-gray-600">Manage fee structures for different classes and fee types</p>
      </div>
      <div class="flex gap-3">
        <Button 
          @click="router.visit(route('fee-structures.create'))"
          class="bg-blue-600 hover:bg-blue-700"
        >
          <Plus class="w-4 h-4 mr-2" />
          Add Fee Structure
        </Button>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border">
      <!-- Filters -->
      <div class="p-4 border-b bg-gray-50">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
          <div>
            <Input
              v-model="filters.search"
              placeholder="Search fee structures..."
              class="w-full"
            />
          </div>
          <div>
            <Select v-model="filters.academic_year_id">
              <SelectTrigger>
                <SelectValue placeholder="Select Academic Year" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="">All Academic Years</SelectItem>
                <SelectItem 
                  v-for="year in academicYears" 
                  :key="year.id" 
                  :value="year.id.toString()"
                >
                  {{ year.name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div>
            <Select v-model="filters.fee_type_id">
              <SelectTrigger>
                <SelectValue placeholder="Select Fee Type" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="">All Fee Types</SelectItem>
                <SelectItem 
                  v-for="feeType in feeTypes" 
                  :key="feeType.id" 
                  :value="feeType.id.toString()"
                >
                  {{ feeType.name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div>
            <Select v-model="filters.class_id">
              <SelectTrigger>
                <SelectValue placeholder="Select Class" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="">All Classes</SelectItem>
                <SelectItem 
                  v-for="schoolClass in schoolClasses" 
                  :key="schoolClass.id" 
                  :value="schoolClass.id.toString()"
                >
                  {{ schoolClass.name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="flex gap-2">
            <Button 
              @click="applyFilters" 
              variant="outline"
              class="flex-1"
            >
              <Search class="w-4 h-4 mr-2" />
              Filter
            </Button>
            <Button 
              @click="clearFilters" 
              variant="outline"
              class="flex-1"
            >
              <X class="w-4 h-4 mr-2" />
              Clear
            </Button>
          </div>
        </div>
      </div>

      <!-- Fee Structures Table -->
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Academic Year
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Fee Type
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Class
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Amount
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Late Fee
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Due Date
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Status
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-if="feeStructures.data.length === 0">
              <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                <div class="flex flex-col items-center">
                  <FileText class="w-12 h-12 text-gray-300 mb-2" />
                  <p>No fee structures found</p>
                  <p class="text-sm">Create your first fee structure to get started</p>
                </div>
              </td>
            </tr>
            <tr v-for="feeStructure in feeStructures.data" :key="feeStructure.id" class="hover:bg-gray-50">
              <td class="px-4 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">
                  {{ feeStructure.academic_year?.name }}
                </div>
              </td>
              <td class="px-4 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">
                  {{ feeStructure.fee_type?.name }}
                </div>
              </td>
              <td class="px-4 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">
                  {{ feeStructure.school_class?.name }}
                </div>
              </td>
              <td class="px-4 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">
                  ₹{{ formatCurrency(feeStructure.amount) }}
                </div>
              </td>
              <td class="px-4 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">
                  {{ feeStructure.late_fee_amount ? `₹${formatCurrency(feeStructure.late_fee_amount)}` : 'N/A' }}
                </div>
              </td>
              <td class="px-4 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">
                  {{ formatDate(feeStructure.due_date) }}
                </div>
              </td>
              <td class="px-4 py-4 whitespace-nowrap">
                <Badge 
                  :variant="feeStructure.is_active ? 'default' : 'secondary'"
                  :class="feeStructure.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                >
                  {{ feeStructure.is_active ? 'Active' : 'Inactive' }}
                </Badge>
              </td>
              <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex items-center gap-2">
                  <Button
                    @click="router.visit(route('fee-structures.show', feeStructure.id))"
                    variant="ghost"
                    size="sm"
                  >
                    <Eye class="w-4 h-4" />
                  </Button>
                  <Button
                    @click="router.visit(route('fee-structures.edit', feeStructure.id))"
                    variant="ghost"
                    size="sm"
                  >
                    <Edit class="w-4 h-4" />
                  </Button>
                  <Button
                    @click="confirmDelete(feeStructure)"
                    variant="ghost"
                    size="sm"
                    class="text-red-600 hover:text-red-700"
                  >
                    <Trash2 class="w-4 h-4" />
                  </Button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="feeStructures.links.length > 3" class="px-4 py-3 border-t">
        <div class="flex justify-between items-center">
          <div class="text-sm text-gray-700">
            Showing {{ feeStructures.from }} to {{ feeStructures.to }} of {{ feeStructures.total }} results
          </div>
          <div class="flex gap-1">
            <Button
              v-for="link in feeStructures.links"
              :key="link.label"
              @click="link.url && router.visit(link.url)"
              :variant="link.active ? 'default' : 'outline'"
              :disabled="!link.url"
              size="sm"
              v-html="link.label"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Dialog -->
    <AlertDialog :open="showDeleteDialog" @update:open="showDeleteDialog = $event">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Delete Fee Structure</AlertDialogTitle>
          <AlertDialogDescription>
            Are you sure you want to delete this fee structure? This action cannot be undone.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>Cancel</AlertDialogCancel>
          <AlertDialogAction
            @click="deleteFeeStructure"
            class="bg-red-600 hover:bg-red-700"
          >
            Delete
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Badge } from '@/Components/ui/badge'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select'
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/Components/ui/alert-dialog'
import { 
  Plus, 
  Search, 
  X, 
  Eye, 
  Edit, 
  Trash2, 
  FileText 
} from 'lucide-vue-next'

interface FeeStructure {
  id: number
  amount: string
  late_fee_amount: string | null
  due_date: string
  is_active: boolean
  academic_year?: { name: string }
  fee_type?: { name: string }
  school_class?: { name: string }
}

interface AcademicYear {
  id: number
  name: string
}

interface FeeType {
  id: number
  name: string
}

interface SchoolClass {
  id: number
  name: string
}

interface Props {
  feeStructures: {
    data: FeeStructure[]
    links: Array<{ label: string; url: string | null; active: boolean }>
    from: number
    to: number
    total: number
  }
  academicYears: AcademicYear[]
  feeTypes: FeeType[]
  schoolClasses: SchoolClass[]
  filters: {
    search?: string
    academic_year_id?: string
    fee_type_id?: string
    class_id?: string
  }
}

const props = defineProps<Props>()

const filters = reactive({
  search: props.filters.search || '',
  academic_year_id: props.filters.academic_year_id || '',
  fee_type_id: props.filters.fee_type_id || '',
  class_id: props.filters.class_id || '',
})

const showDeleteDialog = ref(false)
const feeStructureToDelete = ref<FeeStructure | null>(null)

const formatCurrency = (amount: string | number): string => {
  const num = typeof amount === 'string' ? parseFloat(amount) : amount
  return num.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('en-IN', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const applyFilters = () => {
  router.get(route('fee-structures.index'), filters, {
    preserveState: true,
    preserveScroll: true,
  })
}

const clearFilters = () => {
  filters.search = ''
  filters.academic_year_id = ''
  filters.fee_type_id = ''
  filters.class_id = ''
  applyFilters()
}

const confirmDelete = (feeStructure: FeeStructure) => {
  feeStructureToDelete.value = feeStructure
  showDeleteDialog.value = true
}

const deleteFeeStructure = () => {
  if (feeStructureToDelete.value) {
    router.delete(route('fee-structures.destroy', feeStructureToDelete.value.id), {
      onSuccess: () => {
        showDeleteDialog.value = false
        feeStructureToDelete.value = null
      }
    })
  }
}

// Auto-apply filters on search input
watch(() => filters.search, () => {
  const timeoutId = setTimeout(() => {
    applyFilters()
  }, 500)
  
  return () => clearTimeout(timeoutId)
})
</script> 