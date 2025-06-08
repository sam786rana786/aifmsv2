<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3'
import { User } from '@/types'

import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'

interface Props {
  mustVerifyEmail: boolean
  status?: string
}

const props = defineProps<Props>()
const page = usePage()

const user = (page.props as any).auth.user as User

const form = useForm({
  name: user.name,
  email: user.email,
  phone: user.phone || '',
  employee_id: user.employee_id || '',
})

const breadcrumbs = [
  { label: 'Profile' }
]

const submit = () => {
  form.patch(route('profile.update'))
}
</script>

<template>
  <Head title="Profile" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6">
      <!-- Header -->
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Profile</h1>
        <p class="text-muted-foreground">
          Manage your account settings and personal information.
        </p>
      </div>

      <!-- Profile Information -->
      <Card>
        <CardHeader>
          <CardTitle>Profile Information</CardTitle>
          <CardDescription>
            Update your account's profile information and email address.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label for="name">Name</Label>
                <Input
                  id="name"
                  v-model="form.name"
                  type="text"
                  required
                  autofocus
                  autocomplete="name"
                />
                <div v-if="form.errors.name" class="text-sm text-red-600">
                  {{ form.errors.name }}
                </div>
              </div>

              <div class="space-y-2">
                <Label for="email">Email</Label>
                <Input
                  id="email"
                  v-model="form.email"
                  type="email"
                  required
                  autocomplete="username"
                />
                <div v-if="form.errors.email" class="text-sm text-red-600">
                  {{ form.errors.email }}
                </div>
              </div>

              <div class="space-y-2">
                <Label for="phone">Phone</Label>
                <Input
                  id="phone"
                  v-model="form.phone"
                  type="tel"
                  autocomplete="tel"
                />
                <div v-if="form.errors.phone" class="text-sm text-red-600">
                  {{ form.errors.phone }}
                </div>
              </div>

              <div class="space-y-2">
                <Label for="employee_id">Employee ID</Label>
                <Input
                  id="employee_id"
                  v-model="form.employee_id"
                  type="text"
                />
                <div v-if="form.errors.employee_id" class="text-sm text-red-600">
                  {{ form.errors.employee_id }}
                </div>
              </div>
            </div>

            <div v-if="props.mustVerifyEmail && !user.email_verified_at" class="mt-4">
              <p class="text-sm text-gray-800">
                Your email address is unverified.
                <button
                  type="button"
                  class="underline text-sm text-gray-600 hover:text-gray-900"
                  @click="$inertia.post(route('verification.send'))"
                >
                  Click here to re-send the verification email.
                </button>
              </p>
            </div>

            <div class="flex items-center gap-4">
              <Button type="submit" :disabled="form.processing">
                Save
              </Button>
              <div v-if="form.recentlySuccessful" class="text-sm text-green-600">
                Saved.
              </div>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template> 