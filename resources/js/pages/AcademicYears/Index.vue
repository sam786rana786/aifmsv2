<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { CalendarDays, Eye, Edit, Trash2, Plus } from 'lucide-vue-next'

import AppLayout from '@/Layouts/AppLayout.vue'
import { usePermissions } from '@/composables/usePermissions'
import { formatDate } from '@/lib/utils'

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
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

interface AcademicYear {
  id: number
  name: string
  start_date: string
  end_date: string
  is_current: boolean
  created_at: string
}

interface Props {
  academicYears: {
    data: AcademicYear[]
    meta: {
      current_page: number
      total: number
      per_page: number
      last_page: number
    }
  }
}

const props = defineProps<Props>()
const { hasPermission } = usePermissions()

const breadcrumbs = [
  { label: 'Academic Years' }
]

const deleteAcademicYear = (id: number) => {
  router.delete(route('academic-years.destroy', id), {
    onSuccess: () => {
      // Success message will be handled by the backend
    }
  })
}
</script>

<template>
  <Head title="Academic Years" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Academic Years</h1>
          <p class="text-muted-foreground">
            Manage academic years and session periods
          </p>
        </div>
        <Button v-if="hasPermission('academic_years.create')" as-child>
          <Link href="/academic-years/create">
            <Plus class="mr-2 h-4 w-4" />
            Add Academic Year
          </Link>
        </Button>
      </div>

      <!-- Academic Years Table -->
      <Card>
        <CardHeader>
          <CardTitle>Academic Years</CardTitle>
          <CardDescription>
            List of all academic years in the system
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="props.academicYears.data.length === 0" class="text-center py-8">
            <CalendarDays class="mx-auto h-12 w-12 text-muted-foreground" />
            <h3 class="mt-2 text-sm font-semibold text-gray-900">No academic years</h3>
            <p class="mt-1 text-sm text-gray-500">Get started by creating a new academic year.</p>
            <div class="mt-6">
              <Button v-if="hasPermission('academic_years.create')" as-child>
                <Link href="/academic-years/create">
                  <Plus class="mr-2 h-4 w-4" />
                  Add Academic Year
                </Link>
              </Button>
            </div>
          </div>

          <div v-else>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Name</TableHead>
                  <TableHead>Start Date</TableHead>
                  <TableHead>End Date</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Created</TableHead>
                  <TableHead v-if="hasPermission('academic_years.view') || hasPermission('academic_years.edit') || hasPermission('academic_years.delete')" class="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="academicYear in props.academicYears.data" :key="academicYear.id">
                  <TableCell class="font-medium">{{ academicYear.name }}</TableCell>
                  <TableCell>{{ formatDate(academicYear.start_date) }}</TableCell>
                  <TableCell>{{ formatDate(academicYear.end_date) }}</TableCell>
                  <TableCell>
                    <Badge v-if="academicYear.is_current" variant="default">
                      Current
                    </Badge>
                    <Badge v-else variant="secondary">
                      Inactive
                    </Badge>
                  </TableCell>
                  <TableCell>{{ formatDate(academicYear.created_at) }}</TableCell>
                  <TableCell v-if="hasPermission('academic_years.view') || hasPermission('academic_years.edit') || hasPermission('academic_years.delete')" class="text-right">
                    <div class="flex items-center justify-end gap-2">
                      <Button v-if="hasPermission('academic_years.view')" variant="ghost" size="sm" as-child>
                        <Link :href="`/academic-years/${academicYear.id}`">
                          <Eye class="h-4 w-4" />
                        </Link>
                      </Button>
                      <Button v-if="hasPermission('academic_years.edit')" variant="ghost" size="sm" as-child>
                        <Link :href="`/academic-years/${academicYear.id}/edit`">
                          <Edit class="h-4 w-4" />
                        </Link>
                      </Button>
                      <AlertDialog v-if="hasPermission('academic_years.delete') && !academicYear.is_current">
                        <AlertDialogTrigger as-child>
                          <Button variant="ghost" size="sm">
                            <Trash2 class="h-4 w-4" />
                          </Button>
                        </AlertDialogTrigger>
                        <AlertDialogContent>
                          <AlertDialogHeader>
                            <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                            <AlertDialogDescription>
                              This action cannot be undone. This will permanently delete the academic year "{{ academicYear.name }}".
                            </AlertDialogDescription>
                          </AlertDialogHeader>
                          <AlertDialogFooter>
                            <AlertDialogCancel>Cancel</AlertDialogCancel>
                            <AlertDialogAction @click="deleteAcademicYear(academicYear.id)">
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