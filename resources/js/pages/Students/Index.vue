<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Students</h1>
          <p class="text-muted-foreground">
            Manage student information and academic records
          </p>
        </div>
        <div class="flex items-center gap-2">
          <Button v-if="hasPermission('students.create')" variant="outline" size="sm">
            <Upload class="mr-2 h-4 w-4" />
            Import Students
          </Button>
          <Button v-if="hasPermission('students.view')" variant="outline" size="sm">
            <Download class="mr-2 h-4 w-4" />
            Export
          </Button>
          <Button v-if="hasPermission('students.create')" as-child>
            <Link href="/students/create">
              <Plus class="mr-2 h-4 w-4" />
              Add Student
            </Link>
          </Button>
        </div>
      </div>

      <!-- Filters -->
      <Card>
        <CardContent class="pt-6">
          <div class="flex flex-col gap-4 md:flex-row md:items-center">
            <div class="flex-1">
              <div class="relative">
                <Search class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input
                  v-model="searchQuery"
                  placeholder="Search students by name, roll number, or admission number..."
                  class="pl-8"
                />
              </div>
            </div>
            <div class="flex gap-2">
              <Button variant="outline" size="sm" @click="resetFilters">
                <X class="mr-2 h-4 w-4" />
                Reset
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Students Table -->
      <Card>
        <CardHeader>
          <CardTitle>All Students</CardTitle>
          <CardDescription>
            Complete list of students in your school
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div class="rounded-md border">
            <div class="p-4">
              <h3 class="text-lg font-semibold">Student Management</h3>
              <p class="text-sm text-muted-foreground">View and manage all student records</p>
            </div>
          </div>

          <!-- Empty State -->
          <div class="text-center py-12">
            <Users class="mx-auto h-12 w-12 text-muted-foreground" />
            <h3 class="mt-4 text-lg font-semibold">No students found</h3>
            <p class="text-muted-foreground">
              Get started by adding your first student.
            </p>
            <Button v-if="hasPermission('students.create')" class="mt-4" as-child>
              <Link href="/students/create">
                <Plus class="mr-2 h-4 w-4" />
                Add Student
              </Link>
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import {
  Plus,
  Search,
  Upload,
  Download,
  X,
  Users
} from 'lucide-vue-next'

import { usePermissions } from '@/composables/usePermissions'

import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'

const { hasPermission } = usePermissions()

const searchQuery = ref('')

const breadcrumbs = [
  { label: 'Students' }
]

function resetFilters() {
  searchQuery.value = ''
}
</script> 