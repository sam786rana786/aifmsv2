<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3'
import { CalendarDays } from 'lucide-vue-next'

import AppLayout from '@/layouts/AppLayout.vue'
import { usePermissions } from '@/composables/usePermissions'

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Checkbox } from '@/components/ui/checkbox'

interface AcademicYear {
  id: number
  name: string
  start_date: string
  end_date: string
  is_current: boolean
}

interface Props {
  academicYear: AcademicYear
}

const props = defineProps<Props>()
const { hasPermission } = usePermissions()

const breadcrumbs = [
  { label: 'Academic Years', href: '/academic-years' },
  { label: props.academicYear.name, href: `/academic-years/${props.academicYear.id}` },
  { label: 'Edit' }
]

const form = useForm({
  name: props.academicYear.name,
  start_date: props.academicYear.start_date,
  end_date: props.academicYear.end_date,
  is_current: props.academicYear.is_current,
})

const submit = () => {
  form.put(route('academic-years.update', props.academicYear.id), {
    onSuccess: () => {
      router.visit('/academic-years')
    }
  })
}
</script>

<template>
  <Head :title="`Edit ${academicYear.name}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Edit Academic Year</h1>
          <p class="text-muted-foreground">
            Modify the details of {{ academicYear.name }}
          </p>
        </div>
      </div>

      <!-- Edit Form -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <CalendarDays class="h-5 w-5" />
            Academic Year Details
          </CardTitle>
          <CardDescription>
            Update the details for this academic year
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-6">
            <!-- Name -->
            <div class="space-y-2">
              <Label for="name">Academic Year Name</Label>
              <Input
                id="name"
                v-model="form.name"
                type="text"
                placeholder="e.g., 2024-2025"
                :class="{ 'border-red-500': form.errors.name }"
                required
              />
              <p v-if="form.errors.name" class="text-sm text-red-500">
                {{ form.errors.name }}
              </p>
            </div>

            <!-- Start Date -->
            <div class="space-y-2">
              <Label for="start_date">Start Date</Label>
              <Input
                id="start_date"
                v-model="form.start_date"
                type="date"
                :class="{ 'border-red-500': form.errors.start_date }"
                required
              />
              <p v-if="form.errors.start_date" class="text-sm text-red-500">
                {{ form.errors.start_date }}
              </p>
            </div>

            <!-- End Date -->
            <div class="space-y-2">
              <Label for="end_date">End Date</Label>
              <Input
                id="end_date"
                v-model="form.end_date"
                type="date"
                :class="{ 'border-red-500': form.errors.end_date }"
                required
              />
              <p v-if="form.errors.end_date" class="text-sm text-red-500">
                {{ form.errors.end_date }}
              </p>
            </div>

            <!-- Is Current -->
            <div class="flex items-center space-x-2">
              <Checkbox
                id="is_current"
                v-model:checked="form.is_current"
              />
              <Label for="is_current">Set as current academic year</Label>
            </div>
            <p v-if="form.errors.is_current" class="text-sm text-red-500">
              {{ form.errors.is_current }}
            </p>

            <!-- Form Actions -->
            <div class="flex items-center gap-4">
              <Button type="submit" :disabled="form.processing">
                <span v-if="form.processing">Updating...</span>
                <span v-else>Update Academic Year</span>
              </Button>
              <Button type="button" variant="outline" @click="router.visit('/academic-years')">
                Cancel
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template> 