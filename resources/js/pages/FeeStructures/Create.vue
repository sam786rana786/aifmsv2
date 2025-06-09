<template>
  <div class="container mx-auto px-4 py-6">
    <div class="mb-6">
      <div class="flex items-center gap-2 text-sm text-gray-600 mb-2">
        <button @click="router.visit(route('fee-structures.index'))" class="hover:text-gray-900">
          Fee Structures
        </button>
        <span>/</span>
        <span class="text-gray-900">Create</span>
      </div>
      <h1 class="text-2xl font-bold text-gray-900">Create Fee Structure</h1>
      <p class="text-gray-600">Set up a new fee structure for a specific class and fee type</p>
    </div>

    <div class="max-w-2xl">
      <div class="bg-white rounded-lg shadow-sm border p-6">
        <form @submit.prevent="submit" class="space-y-6">
          <!-- Academic Year -->
          <div>
            <label for="academic_year_id" class="block text-sm font-medium text-gray-700 mb-2">
              Academic Year *
            </label>
            <Select v-model="form.academic_year_id" required>
              <SelectTrigger :class="{'border-red-500': form.errors.academic_year_id}">
                <SelectValue placeholder="Select Academic Year" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem 
                  v-for="year in academicYears" 
                  :key="year.id" 
                  :value="year.id.toString()"
                >
                  {{ year.name }}
                </SelectItem>
              </SelectContent>
            </Select>
            <p v-if="form.errors.academic_year_id" class="mt-1 text-sm text-red-600">
              {{ form.errors.academic_year_id }}
            </p>
          </div>

          <!-- Fee Type -->
          <div>
            <label for="fee_type_id" class="block text-sm font-medium text-gray-700 mb-2">
              Fee Type *
            </label>
            <Select v-model="form.fee_type_id" required>
              <SelectTrigger :class="{'border-red-500': form.errors.fee_type_id}">
                <SelectValue placeholder="Select Fee Type" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem 
                  v-for="feeType in feeTypes" 
                  :key="feeType.id" 
                  :value="feeType.id.toString()"
                >
                  {{ feeType.name }}
                </SelectItem>
              </SelectContent>
            </Select>
            <p v-if="form.errors.fee_type_id" class="mt-1 text-sm text-red-600">
              {{ form.errors.fee_type_id }}
            </p>
          </div>

          <!-- Class -->
          <div>
            <label for="class_id" class="block text-sm font-medium text-gray-700 mb-2">
              Class *
            </label>
            <Select v-model="form.class_id" required>
              <SelectTrigger :class="{'border-red-500': form.errors.class_id}">
                <SelectValue placeholder="Select Class" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem 
                  v-for="schoolClass in schoolClasses" 
                  :key="schoolClass.id" 
                  :value="schoolClass.id.toString()"
                >
                  {{ schoolClass.name }}
                </SelectItem>
              </SelectContent>
            </Select>
            <p v-if="form.errors.class_id" class="mt-1 text-sm text-red-600">
              {{ form.errors.class_id }}
            </p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Amount -->
            <div>
              <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">
                Amount *
              </label>
              <div class="relative">
                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">₹</span>
                <Input
                  id="amount"
                  v-model="form.amount"
                  type="number"
                  step="0.01"
                  min="0"
                  max="999999.99"
                  required
                  class="pl-8"
                  :class="{'border-red-500': form.errors.amount}"
                  placeholder="0.00"
                />
              </div>
              <p v-if="form.errors.amount" class="mt-1 text-sm text-red-600">
                {{ form.errors.amount }}
              </p>
            </div>

            <!-- Late Fee Amount -->
            <div>
              <label for="late_fee_amount" class="block text-sm font-medium text-gray-700 mb-2">
                Late Fee Amount
              </label>
              <div class="relative">
                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">₹</span>
                <Input
                  id="late_fee_amount"
                  v-model="form.late_fee_amount"
                  type="number"
                  step="0.01"
                  min="0"
                  max="999999.99"
                  class="pl-8"
                  :class="{'border-red-500': form.errors.late_fee_amount}"
                  placeholder="0.00"
                />
              </div>
              <p v-if="form.errors.late_fee_amount" class="mt-1 text-sm text-red-600">
                {{ form.errors.late_fee_amount }}
              </p>
              <p class="mt-1 text-sm text-gray-500">
                Optional late fee for overdue payments
              </p>
            </div>
          </div>

          <!-- Due Date -->
          <div>
            <label for="due_date" class="block text-sm font-medium text-gray-700 mb-2">
              Due Date *
            </label>
            <Input
              id="due_date"
              v-model="form.due_date"
              type="date"
              required
              :class="{'border-red-500': form.errors.due_date}"
            />
            <p v-if="form.errors.due_date" class="mt-1 text-sm text-red-600">
              {{ form.errors.due_date }}
            </p>
          </div>

          <!-- Description -->
          <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
              Description
            </label>
            <textarea
              id="description"
              v-model="form.description"
              rows="3"
              maxlength="1000"
              :class="{'border-red-500': form.errors.description}"
              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
              placeholder="Optional description for this fee structure"
            />
            <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">
              {{ form.errors.description }}
            </p>
            <p class="mt-1 text-sm text-gray-500">
              {{ form.description?.length || 0 }}/1000 characters
            </p>
          </div>

          <!-- Status -->
          <div>
            <div class="flex items-center gap-3">
              <input
                id="is_active"
                v-model="form.is_active"
                type="checkbox"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              />
              <label for="is_active" class="text-sm font-medium text-gray-700">
                Active
              </label>
            </div>
            <p class="mt-1 text-sm text-gray-500">
              Inactive fee structures won't be available for new payments
            </p>
          </div>

          <!-- Form Actions -->
          <div class="flex justify-between pt-6 border-t">
            <Button
              type="button"
              variant="outline"
              @click="router.visit(route('fee-structures.index'))"
            >
              Cancel
            </Button>
            <Button
              type="submit"
              :disabled="form.processing"
              class="bg-blue-600 hover:bg-blue-700"
            >
              <span v-if="form.processing">Creating...</span>
              <span v-else>Create Fee Structure</span>
            </Button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select'

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
  academicYears: AcademicYear[]
  feeTypes: FeeType[]
  schoolClasses: SchoolClass[]
}

const props = defineProps<Props>()

const form = useForm({
  academic_year_id: '',
  fee_type_id: '',
  class_id: '',
  amount: '',
  late_fee_amount: '',
  due_date: '',
  description: '',
  is_active: true,
})

const submit = () => {
  form.post(route('fee-structures.store'), {
    onSuccess: () => {
      router.visit(route('fee-structures.index'))
    }
  })
}
</script> 