<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { CalendarDays, Edit, Trash2, ArrowLeft } from 'lucide-vue-next'

import AppLayout from '@/layouts/AppLayout.vue'
import { usePermissions } from '@/composables/usePermissions'
import { formatDate } from '@/lib/utils'

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
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
  updated_at: string
}

interface Props {
  academicYear: AcademicYear
}

const props = defineProps<Props>()
const { hasPermission } = usePermissions()

const breadcrumbs = [
  { label: 'Academic Years', href: '/academic-years' },
  { label: props.academicYear.name }
]

const deleteAcademicYear = () => {
  router.delete(route('academic-years.destroy', props.academicYear.id), {
    onSuccess: () => {
      router.visit('/academic-years')
    }
  })
}
</script>

<template>
  <Head :title="academicYear.name" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <div class="flex items-center gap-4 mb-2">
            <Button variant="ghost" size="sm" as-child>
              <Link href="/academic-years">
                <ArrowLeft class="h-4 w-4 mr-2" />
                Back to Academic Years
              </Link>
            </Button>
          </div>
          <h1 class="text-3xl font-bold tracking-tight">{{ academicYear.name }}</h1>
          <p class="text-muted-foreground">
            Academic year details and information
          </p>
        </div>
        <div class="flex items-center gap-2">
          <Button v-if="hasPermission('academic_years.edit')" variant="outline" size="sm" as-child>
            <Link :href="`/academic-years/${academicYear.id}/edit`">
              <Edit class="h-4 w-4 mr-2" />
              Edit
            </Link>
          </Button>
          <AlertDialog v-if="hasPermission('academic_years.delete') && !academicYear.is_current">
            <AlertDialogTrigger as-child>
              <Button variant="destructive" size="sm">
                <Trash2 class="h-4 w-4 mr-2" />
                Delete
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
                <AlertDialogAction @click="deleteAcademicYear">
                  Delete
                </AlertDialogAction>
              </AlertDialogFooter>
            </AlertDialogContent>
          </AlertDialog>
        </div>
      </div>

      <!-- Academic Year Details -->
      <div class="grid gap-6 md:grid-cols-2">
        <!-- Basic Information -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <CalendarDays class="h-5 w-5" />
              Basic Information
            </CardTitle>
            <CardDescription>
              Core details of the academic year
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div>
              <label class="text-sm font-medium text-muted-foreground">Name</label>
              <p class="text-lg font-semibold">{{ academicYear.name }}</p>
            </div>
            
            <div>
              <label class="text-sm font-medium text-muted-foreground">Status</label>
              <div class="mt-1">
                <Badge v-if="academicYear.is_current" variant="default">
                  Current Academic Year
                </Badge>
                <Badge v-else variant="secondary">
                  Inactive
                </Badge>
              </div>
            </div>

            <div>
              <label class="text-sm font-medium text-muted-foreground">Start Date</label>
              <p class="text-lg">{{ formatDate(academicYear.start_date) }}</p>
            </div>

            <div>
              <label class="text-sm font-medium text-muted-foreground">End Date</label>
              <p class="text-lg">{{ formatDate(academicYear.end_date) }}</p>
            </div>
          </CardContent>
        </Card>

        <!-- System Information -->
        <Card>
          <CardHeader>
            <CardTitle>System Information</CardTitle>
            <CardDescription>
              Timestamps and system details
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div>
              <label class="text-sm font-medium text-muted-foreground">Created At</label>
              <p class="text-lg">{{ formatDate(academicYear.created_at) }}</p>
            </div>

            <div>
              <label class="text-sm font-medium text-muted-foreground">Last Updated</label>
              <p class="text-lg">{{ formatDate(academicYear.updated_at) }}</p>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Actions -->
      <Card v-if="hasPermission('academic_years.edit') || hasPermission('academic_years.delete')">
        <CardHeader>
          <CardTitle>Actions</CardTitle>
          <CardDescription>
            Available actions for this academic year
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div class="flex items-center gap-4">
            <Button v-if="hasPermission('academic_years.edit')" variant="outline" as-child>
              <Link :href="`/academic-years/${academicYear.id}/edit`">
                <Edit class="h-4 w-4 mr-2" />
                Edit Academic Year
              </Link>
            </Button>
            
            <AlertDialog v-if="hasPermission('academic_years.delete') && !academicYear.is_current">
              <AlertDialogTrigger as-child>
                <Button variant="destructive">
                  <Trash2 class="h-4 w-4 mr-2" />
                  Delete Academic Year
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
                  <AlertDialogAction @click="deleteAcademicYear">
                    Delete
                  </AlertDialogAction>
                </AlertDialogFooter>
              </AlertDialogContent>
            </AlertDialog>

            <div v-if="academicYear.is_current" class="text-sm text-muted-foreground">
              Current academic years cannot be deleted
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template> 