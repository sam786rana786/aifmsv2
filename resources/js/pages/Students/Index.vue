<template>
  <Head title="Students" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Students</h1>
          <p class="text-muted-foreground">
            Manage student records and information
          </p>
        </div>
        <Button v-if="hasPermission('students.create')" as-child>
          <Link href="/students/create">
            <Plus class="mr-2 h-4 w-4" />
            Add Student
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
          <div class="grid gap-4 md:grid-cols-4">
            <!-- Search -->
            <div class="space-y-2">
              <label class="text-sm font-medium">Search</label>
              <div class="relative">
                <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                <Input
                  v-model="search"
                  placeholder="Search by name, admission no..."
                  class="pl-9"
                />
              </div>
            </div>

            <!-- Class Filter -->
            <div class="space-y-2">
              <label class="text-sm font-medium">Class</label>
              <Select v-model="selectedClass">
                <SelectTrigger>
                  <SelectValue placeholder="All classes" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">All classes</SelectItem>
                  <SelectItem v-for="cls in classes" :key="cls.id" :value="cls.id.toString()">
                    {{ cls.name }}
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

      <!-- Students Table -->
      <Card>
        <CardHeader>
          <CardTitle>Students</CardTitle>
          <CardDescription>
            Total: {{ props.students.meta.total }} students
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="props.students.data.length === 0" class="text-center py-8">
            <Users class="mx-auto h-12 w-12 text-muted-foreground" />
            <h3 class="mt-2 text-sm font-semibold text-gray-900">No students found</h3>
            <p class="mt-1 text-sm text-gray-500">
              {{ search || selectedClass || selectedAcademicYear ? 'Try adjusting your filters' : 'Get started by adding a new student.' }}
            </p>
            <div class="mt-6">
              <Button v-if="hasPermission('students.create')" as-child>
                <Link href="/students/create">
                  <Plus class="mr-2 h-4 w-4" />
                  Add Student
                </Link>
              </Button>
            </div>
          </div>

          <div v-else>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Admission No</TableHead>
                  <TableHead>Name</TableHead>
                  <TableHead>Class</TableHead>
                  <TableHead>Academic Year</TableHead>
                  <TableHead>Contact</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Joined</TableHead>
                  <TableHead v-if="hasPermission('students.view') || hasPermission('students.edit') || hasPermission('students.delete')" class="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="student in props.students.data" :key="student.id">
                  <TableCell class="font-medium">{{ student.admission_no }}</TableCell>
                  <TableCell>
                    <div>
                      <div class="font-medium">{{ student.first_name }} {{ student.last_name }}</div>
                      <div class="text-sm text-muted-foreground capitalize">{{ student.gender }}</div>
                    </div>
                  </TableCell>
                  <TableCell>{{ student.class.name }}</TableCell>
                  <TableCell>{{ student.academic_year.name }}</TableCell>
                  <TableCell>
                    <div class="text-sm">
                      <div v-if="student.phone">{{ student.phone }}</div>
                      <div v-if="student.email" class="text-muted-foreground">{{ student.email }}</div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <Badge v-if="student.is_active" variant="default">
                      Active
                    </Badge>
                    <Badge v-else variant="secondary">
                      Inactive
                    </Badge>
                  </TableCell>
                  <TableCell>{{ formatDate(student.created_at) }}</TableCell>
                  <TableCell v-if="hasPermission('students.view') || hasPermission('students.edit') || hasPermission('students.delete')" class="text-right">
                    <div class="flex items-center justify-end gap-2">
                      <Button v-if="hasPermission('students.view')" variant="ghost" size="sm" as-child>
                        <Link :href="`/students/${student.id}`">
                          <Eye class="h-4 w-4" />
                        </Link>
                      </Button>
                      <Button v-if="hasPermission('students.edit')" variant="ghost" size="sm" as-child>
                        <Link :href="`/students/${student.id}/edit`">
                          <Edit class="h-4 w-4" />
                        </Link>
                      </Button>
                      <AlertDialog v-if="hasPermission('students.delete')">
                        <AlertDialogTrigger as-child>
                          <Button variant="ghost" size="sm">
                            <Trash2 class="h-4 w-4" />
                          </Button>
                        </AlertDialogTrigger>
                        <AlertDialogContent>
                          <AlertDialogHeader>
                            <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                            <AlertDialogDescription>
                              This action cannot be undone. This will permanently delete the student "{{ student.first_name }} {{ student.last_name }}" and all associated data.
                            </AlertDialogDescription>
                          </AlertDialogHeader>
                          <AlertDialogFooter>
                            <AlertDialogCancel>Cancel</AlertDialogCancel>
                            <AlertDialogAction @click="deleteStudent(student.id)">
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

<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import { Users, Eye, Edit, Trash2, Plus, Search, Filter } from 'lucide-vue-next'

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

interface Student {
  id: number
  admission_no: string
  first_name: string
  last_name: string
  gender: string
  phone: string
  email: string
  is_active: boolean
  created_at: string
  school: {
    id: number
    name: string
  }
  academic_year: {
    id: number
    name: string
  }
  class: {
    id: number
    name: string
  }
}

interface SchoolClass {
  id: number
  name: string
}

interface AcademicYear {
  id: number
  name: string
}

interface Props {
  students: {
    data: Student[]
    meta: {
      current_page: number
      total: number
      per_page: number
      last_page: number
    }
  }
  classes: SchoolClass[]
  academicYears: AcademicYear[]
  filters: {
    search?: string
    class_id?: string
    academic_year_id?: string
  }
}

const props = defineProps<Props>()
const { hasPermission } = usePermissions()

const breadcrumbs = [
  { label: 'Students' }
]

// Filter states
const search = ref(props.filters.search || '')
const selectedClass = ref(props.filters.class_id || '')
const selectedAcademicYear = ref(props.filters.academic_year_id || '')

// Watch for filter changes and update URL
watch([search, selectedClass, selectedAcademicYear], () => {
  router.get(route('students.index'), {
    search: search.value || undefined,
    class_id: selectedClass.value || undefined,
    academic_year_id: selectedAcademicYear.value || undefined,
  }, {
    preserveState: true,
    replace: true,
  })
}, { debounce: 300 })

const deleteStudent = (id: number) => {
  router.delete(route('students.destroy', id), {
    onSuccess: () => {
      // Success message will be handled by the backend
    }
  })
}

const clearFilters = () => {
  search.value = ''
  selectedClass.value = ''
  selectedAcademicYear.value = ''
}
</script>
