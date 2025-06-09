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
import { Alert, AlertDescription } from '@/components/ui/alert'

const { hasPermission } = usePermissions()

const breadcrumbs = [
  { label: 'Academic Years', href: '/academic-years' },
  { label: 'Create' }
]

const form = useForm({
  name: '',
  start_date: '',
  end_date: '',
  is_current: false,
})

const submit = () => {
  form.post(route('academic-years.store'), {
    onSuccess: () => {
      router.visit('/academic-years')
    }
  })
}
</script>

<template>
  <Head title="Create Academic Year" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Create Academic Year</h1>
          <p class="text-muted-foreground">
            Add a new academic year to the system
          </p>
        </div>
      </div>

      <!-- Create Form -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <CalendarDays class="h-5 w-5" />
            Academic Year Details
          </CardTitle>
          <CardDescription>
            Fill in the details for the new academic year
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
                <span v-if="form.processing">Creating...</span>
                <span v-else>Create Academic Year</span>
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