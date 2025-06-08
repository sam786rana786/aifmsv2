<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import {
  Users,
  DollarSign,
  GraduationCap,
  AlertCircle,
  BarChart3,
  Activity,
  UserPlus,
  CreditCard,
  Plus,
  FileText,
  CheckCircle,
  Clock,
  Download
} from 'lucide-vue-next'

import { usePermissions } from '@/composables/usePermissions'
import { useSettings } from '@/composables/useSettings'
import { formatCurrency, formatDate } from '@/lib/utils'

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Separator } from '@/components/ui/separator'

interface DashboardStats {
  total_students: number
  students_change: number
  total_collection: number
  collection_change: number
  pending_fees: number
  defaulter_count: number
  active_classes: number
  academic_years: number
}

interface Activity {
  id: number
  description: string
  created_at: string
  user: {
    name: string
    profile_picture?: string
  }
}

interface Payment {
  id: number
  amount: number
  created_at: string
  student: {
    name: string
  }
  fee_type: {
    name: string
  }
}

interface PendingApproval {
  id: number
  type: string
  title: string
  description: string
}

interface Props {
  stats?: DashboardStats
  recentActivities?: Activity[]
  recentPayments?: Payment[]
  pendingApprovals?: PendingApproval[]
}

const props = withDefaults(defineProps<Props>(), {
  stats: () => ({
    total_students: 0,
    students_change: 0,
    total_collection: 0,
    collection_change: 0,
    pending_fees: 0,
    defaulter_count: 0,
    active_classes: 0,
    academic_years: 0
  }),
  recentActivities: () => [],
  recentPayments: () => [],
  pendingApprovals: () => []
})

const page = usePage()
const { hasPermission, hasAnyPermission } = usePermissions()
const { schoolName } = useSettings()

const breadcrumbs = [
  { label: 'Dashboard' }
]

function getUserInitials(name: string): string {
  return name
    .split(' ')
    .map(word => word.charAt(0))
    .join('')
    .toUpperCase()
    .slice(0, 2)
}
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Dashboard</h1>
                    <p class="text-muted-foreground">
                        Welcome back, {{ $page.props.auth.user.name }}! Here's what's happening at {{ schoolName }}.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Button variant="outline" size="sm">
                        <Download class="mr-2 h-4 w-4" />
                        Export Report
                    </Button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Students</CardTitle>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total_students }}</div>
                        <p class="text-xs text-muted-foreground">
                            <span :class="stats.students_change >= 0 ? 'text-green-600' : 'text-red-600'">
                                {{ stats.students_change >= 0 ? '+' : '' }}{{ stats.students_change }}%
                            </span>
                            from last month
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Fee Collection</CardTitle>
                        <DollarSign class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatCurrency(stats.total_collection) }}</div>
                        <p class="text-xs text-muted-foreground">
                            <span :class="stats.collection_change >= 0 ? 'text-green-600' : 'text-red-600'">
                                {{ stats.collection_change >= 0 ? '+' : '' }}{{ stats.collection_change }}%
                            </span>
                            from last month
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Pending Fees</CardTitle>
                        <AlertCircle class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatCurrency(stats.pending_fees) }}</div>
                        <p class="text-xs text-muted-foreground">
                            {{ stats.defaulter_count }} students with pending payments
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Active Classes</CardTitle>
                        <GraduationCap class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.active_classes }}</div>
                        <p class="text-xs text-muted-foreground">
                            Across {{ stats.academic_years }} academic years
                        </p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-7">
                <!-- Fee Collection Chart -->
                <Card class="col-span-4">
                    <CardHeader>
                        <CardTitle>Fee Collection Overview</CardTitle>
                        <CardDescription>
                            Monthly fee collection for the current academic year
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="pl-2">
                        <div class="h-[200px] w-full">
                            <!-- Chart placeholder - would integrate with Chart.js -->
                            <div class="flex h-full items-center justify-center rounded-md border border-dashed">
                                <div class="text-center">
                                    <BarChart3 class="mx-auto h-12 w-12 text-muted-foreground" />
                                    <p class="mt-2 text-sm text-muted-foreground">Fee collection chart will be displayed here</p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Recent Activities -->
                <Card class="col-span-3">
                    <CardHeader>
                        <CardTitle>Recent Activities</CardTitle>
                        <CardDescription>
                            Latest actions performed in the system
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div v-for="activity in recentActivities" :key="activity.id" class="flex items-center gap-4">
                                <Avatar class="h-8 w-8">
                                    <AvatarImage :src="activity.user.profile_picture || ''" :alt="activity.user.name" />
                                    <AvatarFallback>{{ getUserInitials(activity.user.name) }}</AvatarFallback>
                                </Avatar>
                                <div class="flex-1 space-y-1">
                                    <p class="text-sm font-medium leading-none">{{ activity.description }}</p>
                                    <div class="flex items-center gap-2">
                                        <p class="text-xs text-muted-foreground">{{ activity.user.name }}</p>
                                        <Separator orientation="vertical" class="h-3" />
                                        <p class="text-xs text-muted-foreground">{{ formatDate(activity.created_at) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="recentActivities.length === 0" class="text-center py-4">
                            <Activity class="mx-auto h-8 w-8 text-muted-foreground" />
                            <p class="mt-2 text-sm text-muted-foreground">No recent activities</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <!-- Quick Actions -->
                <Card>
                    <CardHeader>
                        <CardTitle>Quick Actions</CardTitle>
                        <CardDescription>
                            Frequently used actions for faster workflow
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="grid gap-2">
                        <Button v-if="hasPermission('students.create')" variant="outline" as-child class="justify-start">
                            <Link href="/students/create">
                                <UserPlus class="mr-2 h-4 w-4" />
                                Add New Student
                            </Link>
                        </Button>
                        <Button v-if="hasPermission('payments.create')" variant="outline" as-child class="justify-start">
                            <Link href="/payments/create">
                                <CreditCard class="mr-2 h-4 w-4" />
                                Record Payment
                            </Link>
                        </Button>
                        <Button v-if="hasPermission('fee_structures.create')" variant="outline" as-child class="justify-start">
                            <Link href="/fee-structures/create">
                                <Plus class="mr-2 h-4 w-4" />
                                Create Fee Structure
                            </Link>
                        </Button>
                        <Button v-if="hasPermission('reports.view')" variant="outline" as-child class="justify-start">
                            <Link href="/reports/defaulters">
                                <FileText class="mr-2 h-4 w-4" />
                                View Defaulters Report
                            </Link>
                        </Button>
                    </CardContent>
                </Card>

                <!-- Recent Payments -->
                <Card>
                    <CardHeader>
                        <CardTitle>Recent Payments</CardTitle>
                        <CardDescription>
                            Latest fee payments received
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div v-for="payment in recentPayments" :key="payment.id" class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center">
                                        <CheckCircle class="h-4 w-4 text-green-600" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium">{{ payment.student.name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ payment.fee_type.name }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium">{{ formatCurrency(payment.amount) }}</p>
                                    <p class="text-xs text-muted-foreground">{{ formatDate(payment.created_at) }}</p>
                                </div>
                            </div>
                        </div>
                        <div v-if="recentPayments.length === 0" class="text-center py-4">
                            <CreditCard class="mx-auto h-8 w-8 text-muted-foreground" />
                            <p class="mt-2 text-sm text-muted-foreground">No recent payments</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Pending Approvals (if user has approval permissions) -->
            <Card v-if="hasAnyPermission(['concessions.approve', 'users.approve']) && pendingApprovals.length > 0">
                <CardHeader>
                    <CardTitle>Pending Approvals</CardTitle>
                    <CardDescription>
                        Items requiring your approval
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        <div v-for="approval in pendingApprovals" :key="`${approval.type}-${approval.id}`" 
                             class="flex items-center justify-between p-3 rounded-lg border">
                            <div class="flex items-center gap-3">
                                <div class="h-8 w-8 rounded-full bg-yellow-100 flex items-center justify-center">
                                    <Clock class="h-4 w-4 text-yellow-600" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium">{{ approval.title }}</p>
                                    <p class="text-xs text-muted-foreground">{{ approval.description }}</p>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <Button size="sm" variant="outline">View</Button>
                                <Button size="sm">Approve</Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
