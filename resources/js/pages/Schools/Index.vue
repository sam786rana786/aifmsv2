<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { Building, Eye, Edit, Trash2, Plus } from 'lucide-vue-next'

import AppLayout from '@/layouts/AppLayout.vue'
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

interface School {
  id: number
  name: string
  code: string
  email: string
  phone: string
  city: string
  state: string
  is_active: boolean
  created_at: string
}

interface Props {
  schools: {
    data: School[]
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
  { label: 'Schools' }
]

const deleteSchool = (id: number) => {
  router.delete(route('schools.destroy', id), {
    onSuccess: () => {
      // Success message will be handled by the backend
    }
  })
}
</script>

<template>
  <Head title="Schools" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Schools</h1>
          <p class="text-muted-foreground">
            Manage schools and their information
          </p>
        </div>
        <Button v-if="hasPermission('schools.create')" as-child>
          <Link href="/schools/create">
            <Plus class="mr-2 h-4 w-4" />
            Add School
          </Link>
        </Button>
      </div>

      <!-- Schools Table -->
      <Card>
        <CardHeader>
          <CardTitle>Schools</CardTitle>
          <CardDescription>
            List of all schools in the system
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="props.schools.data.length === 0" class="text-center py-8">
            <Building class="mx-auto h-12 w-12 text-muted-foreground" />
            <h3 class="mt-2 text-sm font-semibold text-gray-900">No schools</h3>
            <p class="mt-1 text-sm text-gray-500">Get started by creating a new school.</p>
            <div class="mt-6">
              <Button v-if="hasPermission('schools.create')" as-child>
                <Link href="/schools/create">
                  <Plus class="mr-2 h-4 w-4" />
                  Add School
                </Link>
              </Button>
            </div>
          </div>

          <div v-else>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Name</TableHead>
                  <TableHead>Code</TableHead>
                  <TableHead>Email</TableHead>
                  <TableHead>Phone</TableHead>
                  <TableHead>Location</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Created</TableHead>
                  <TableHead v-if="hasPermission('schools.view') || hasPermission('schools.edit') || hasPermission('schools.delete')" class="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="school in props.schools.data" :key="school.id">
                  <TableCell class="font-medium">{{ school.name }}</TableCell>
                  <TableCell>{{ school.code }}</TableCell>
                  <TableCell>{{ school.email }}</TableCell>
                  <TableCell>{{ school.phone }}</TableCell>
                  <TableCell>{{ school.city }}, {{ school.state }}</TableCell>
                  <TableCell>
                    <Badge v-if="school.is_active" variant="default">
                      Active
                    </Badge>
                    <Badge v-else variant="secondary">
                      Inactive
                    </Badge>
                  </TableCell>
                  <TableCell>{{ formatDate(school.created_at) }}</TableCell>
                  <TableCell v-if="hasPermission('schools.view') || hasPermission('schools.edit') || hasPermission('schools.delete')" class="text-right">
                    <div class="flex items-center justify-end gap-2">
                      <Button v-if="hasPermission('schools.view')" variant="ghost" size="sm" as-child>
                        <Link :href="`/schools/${school.id}`">
                          <Eye class="h-4 w-4" />
                        </Link>
                      </Button>
                      <Button v-if="hasPermission('schools.edit')" variant="ghost" size="sm" as-child>
                        <Link :href="`/schools/${school.id}/edit`">
                          <Edit class="h-4 w-4" />
                        </Link>
                      </Button>
                      <AlertDialog v-if="hasPermission('schools.delete')">
                        <AlertDialogTrigger as-child>
                          <Button variant="ghost" size="sm">
                            <Trash2 class="h-4 w-4" />
                          </Button>
                        </AlertDialogTrigger>
                        <AlertDialogContent>
                          <AlertDialogHeader>
                            <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                            <AlertDialogDescription>
                              This action cannot be undone. This will permanently delete the school "{{ school.name }}" and all associated data.
                            </AlertDialogDescription>
                          </AlertDialogHeader>
                          <AlertDialogFooter>
                            <AlertDialogCancel>Cancel</AlertDialogCancel>
                            <AlertDialogAction @click="deleteSchool(school.id)">
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