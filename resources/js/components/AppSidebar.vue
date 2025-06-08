<script setup lang="ts">
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { 
  LayoutDashboard, 
  GraduationCap, 
  DollarSign, 
  Bus, 
  Users, 
  Building2, 
  BarChart3, 
  Bell, 
  Activity, 
  Settings, 
  Database,
  ChevronRight,
  ChevronsUpDown,
  User,
  LogOut
} from 'lucide-vue-next'

import { usePermissions } from '@/composables/usePermissions'
import { useSettings } from '@/composables/useSettings'

import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarGroup,
  SidebarGroupLabel,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarMenuSub,
  SidebarMenuSubButton,
  SidebarMenuSubItem,
} from '@/components/ui/sidebar'
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'

const page = usePage()
const { hasPermission, hasAnyPermission, isSuperAdmin } = usePermissions()
const { appName, schoolName, enableTransport } = useSettings()

const unreadNotifications = computed(() => {
  const notifications = page.props.notifications
  if (!Array.isArray(notifications)) return 0
  return notifications.filter((n: any) => !n.read_at).length
})

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
  <Sidebar collapsible="icon">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
            <Link href="/dashboard">
              <div class="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground">
                <GraduationCap class="size-4" />
              </div>
              <div class="grid flex-1 text-left text-sm leading-tight">
                <span class="truncate font-semibold">{{ appName }}</span>
                <span class="truncate text-xs">{{ schoolName }}</span>
              </div>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
      <SidebarGroup>
        <SidebarGroupLabel>Main</SidebarGroupLabel>
        <SidebarMenu>
          <!-- Dashboard -->
          <SidebarMenuItem>
            <SidebarMenuButton as-child>
              <Link href="/dashboard">
                <LayoutDashboard class="size-4" />
                <span>Dashboard</span>
              </Link>
            </SidebarMenuButton>
          </SidebarMenuItem>

          <!-- Academic Management -->
          <SidebarMenuItem v-if="hasAnyPermission(['academic_years.view', 'classes.view', 'students.view'])">
            <Collapsible class="group/collapsible">
              <SidebarMenuButton as-child>
                <CollapsibleTrigger>
                  <GraduationCap class="size-4" />
                  <span>Academic</span>
                  <ChevronRight class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                </CollapsibleTrigger>
              </SidebarMenuButton>
              <CollapsibleContent>
                <SidebarMenuSub>
                  <SidebarMenuSubItem v-if="hasPermission('academic_years.view')">
                    <SidebarMenuSubButton as-child>
                      <Link href="/academic-years">Academic Years</Link>
                    </SidebarMenuSubButton>
                  </SidebarMenuSubItem>
                  <SidebarMenuSubItem v-if="hasPermission('classes.view')">
                    <SidebarMenuSubButton as-child>
                      <Link href="/classes">Classes</Link>
                    </SidebarMenuSubButton>
                  </SidebarMenuSubItem>
                  <SidebarMenuSubItem v-if="hasPermission('students.view')">
                    <SidebarMenuSubButton as-child>
                      <Link href="/students">Students</Link>
                    </SidebarMenuSubButton>
                  </SidebarMenuSubItem>
                  <SidebarMenuSubItem v-if="hasPermission('student_promotions.view')">
                    <SidebarMenuSubButton as-child>
                      <Link href="/student-promotions">Promotions</Link>
                    </SidebarMenuSubButton>
                  </SidebarMenuSubItem>
                </SidebarMenuSub>
              </CollapsibleContent>
            </Collapsible>
          </SidebarMenuItem>

          <!-- Fee Management -->
          <SidebarMenuItem v-if="hasAnyPermission(['fee_types.view', 'fee_structures.view', 'payments.view', 'concessions.view'])">
            <Collapsible class="group/collapsible">
              <SidebarMenuButton as-child>
                <CollapsibleTrigger>
                  <DollarSign class="size-4" />
                  <span>Fee Management</span>
                  <ChevronRight class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                </CollapsibleTrigger>
              </SidebarMenuButton>
              <CollapsibleContent>
                <SidebarMenuSub>
                  <SidebarMenuSubItem v-if="hasPermission('fee_types.view')">
                    <SidebarMenuSubButton as-child>
                      <Link href="/fee-types">Fee Types</Link>
                    </SidebarMenuSubButton>
                  </SidebarMenuSubItem>
                  <SidebarMenuSubItem v-if="hasPermission('fee_structures.view')">
                    <SidebarMenuSubButton as-child>
                      <Link href="/fee-structures">Fee Structures</Link>
                    </SidebarMenuSubButton>
                  </SidebarMenuSubItem>
                  <SidebarMenuSubItem v-if="hasPermission('payments.view')">
                    <SidebarMenuSubButton as-child>
                      <Link href="/payments">Payments</Link>
                    </SidebarMenuSubButton>
                  </SidebarMenuSubItem>
                  <SidebarMenuSubItem v-if="hasPermission('concessions.view')">
                    <SidebarMenuSubButton as-child>
                      <Link href="/concessions">Concessions</Link>
                    </SidebarMenuSubButton>
                  </SidebarMenuSubItem>
                  <SidebarMenuSubItem v-if="hasPermission('previous_year_balances.view')">
                    <SidebarMenuSubButton as-child>
                      <Link href="/previous-year-balances">Previous Balances</Link>
                    </SidebarMenuSubButton>
                  </SidebarMenuSubItem>
                </SidebarMenuSub>
              </CollapsibleContent>
            </Collapsible>
          </SidebarMenuItem>

          <!-- Transport Management -->
          <SidebarMenuItem v-if="enableTransport && hasAnyPermission(['transport_routes.view', 'transport_assignments.view'])">
            <Collapsible class="group/collapsible">
              <SidebarMenuButton as-child>
                <CollapsibleTrigger>
                  <Bus class="size-4" />
                  <span>Transport</span>
                  <ChevronRight class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                </CollapsibleTrigger>
              </SidebarMenuButton>
              <CollapsibleContent>
                <SidebarMenuSub>
                  <SidebarMenuSubItem v-if="hasPermission('transport_routes.view')">
                    <SidebarMenuSubButton as-child>
                      <Link href="/transport-routes">Routes</Link>
                    </SidebarMenuSubButton>
                  </SidebarMenuSubItem>
                  <SidebarMenuSubItem v-if="hasPermission('transport_assignments.view')">
                    <SidebarMenuSubButton as-child>
                      <Link href="/transport-assignments">Assignments</Link>
                    </SidebarMenuSubButton>
                  </SidebarMenuSubItem>
                </SidebarMenuSub>
              </CollapsibleContent>
            </Collapsible>
          </SidebarMenuItem>

          <!-- User Management -->
          <SidebarMenuItem v-if="hasAnyPermission(['users.view', 'roles.view', 'permissions.view'])">
            <Collapsible class="group/collapsible">
              <SidebarMenuButton as-child>
                <CollapsibleTrigger>
                  <Users class="size-4" />
                  <span>User Management</span>
                  <ChevronRight class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                </CollapsibleTrigger>
              </SidebarMenuButton>
              <CollapsibleContent>
                <SidebarMenuSub>
                  <SidebarMenuSubItem v-if="hasPermission('users.view')">
                    <SidebarMenuSubButton as-child>
                      <Link href="/users">Users</Link>
                    </SidebarMenuSubButton>
                  </SidebarMenuSubItem>
                  <SidebarMenuSubItem v-if="hasPermission('roles.view')">
                    <SidebarMenuSubButton as-child>
                      <Link href="/roles">Roles</Link>
                    </SidebarMenuSubButton>
                  </SidebarMenuSubItem>
                  <SidebarMenuSubItem v-if="hasPermission('permissions.view')">
                    <SidebarMenuSubButton as-child>
                      <Link href="/permissions">Permissions</Link>
                    </SidebarMenuSubButton>
                  </SidebarMenuSubItem>
                </SidebarMenuSub>
              </CollapsibleContent>
            </Collapsible>
          </SidebarMenuItem>

          <!-- School Management (Super Admin only) -->
          <SidebarMenuItem v-if="isSuperAdmin && hasPermission('schools.view')">
            <SidebarMenuButton as-child>
              <Link href="/schools">
                <Building2 class="size-4" />
                <span>Schools</span>
              </Link>
            </SidebarMenuButton>
          </SidebarMenuItem>

          <!-- Reports -->
          <SidebarMenuItem v-if="hasAnyPermission(['reports.view', 'analytics.view'])">
            <Collapsible class="group/collapsible">
              <SidebarMenuButton as-child>
                <CollapsibleTrigger>
                  <BarChart3 class="size-4" />
                  <span>Reports</span>
                  <ChevronRight class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                </CollapsibleTrigger>
              </SidebarMenuButton>
              <CollapsibleContent>
                <SidebarMenuSub>
                  <SidebarMenuSubItem v-if="hasPermission('reports.view')">
                    <SidebarMenuSubButton as-child>
                      <Link href="/reports/fee-collection">Fee Collection</Link>
                    </SidebarMenuSubButton>
                  </SidebarMenuSubItem>
                  <SidebarMenuSubItem v-if="hasPermission('reports.view')">
                    <SidebarMenuSubButton as-child>
                      <Link href="/reports/student-list">Student List</Link>
                    </SidebarMenuSubButton>
                  </SidebarMenuSubItem>
                  <SidebarMenuSubItem v-if="hasPermission('reports.view')">
                    <SidebarMenuSubButton as-child>
                      <Link href="/reports/defaulters">Defaulters</Link>
                    </SidebarMenuSubButton>
                  </SidebarMenuSubItem>
                  <SidebarMenuSubItem v-if="hasPermission('analytics.view')">
                    <SidebarMenuSubButton as-child>
                      <Link href="/analytics">Analytics</Link>
                    </SidebarMenuSubButton>
                  </SidebarMenuSubItem>
                </SidebarMenuSub>
              </CollapsibleContent>
            </Collapsible>
          </SidebarMenuItem>

          <!-- Notifications -->
          <SidebarMenuItem v-if="hasPermission('notifications.view')">
            <SidebarMenuButton as-child>
              <Link href="/notifications">
                <Bell class="size-4" />
                <span>Notifications</span>
                <Badge v-if="unreadNotifications > 0" variant="destructive" class="ml-auto">
                  {{ unreadNotifications > 9 ? '9+' : unreadNotifications }}
                </Badge>
              </Link>
            </SidebarMenuButton>
          </SidebarMenuItem>

          <!-- Activity Logs -->
          <SidebarMenuItem v-if="hasPermission('activity_logs.view')">
            <SidebarMenuButton as-child>
              <Link href="/activity-logs">
                <Activity class="size-4" />
                <span>Activity Logs</span>
              </Link>
            </SidebarMenuButton>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarGroup>

      <!-- Settings Group -->
      <SidebarGroup v-if="hasAnyPermission(['settings.view', 'backups.view'])">
        <SidebarGroupLabel>Settings</SidebarGroupLabel>
        <SidebarMenu>
          <SidebarMenuItem v-if="hasPermission('settings.view')">
            <SidebarMenuButton as-child>
              <Link href="/settings">
                <Settings class="size-4" />
                <span>Settings</span>
              </Link>
            </SidebarMenuButton>
          </SidebarMenuItem>
          <SidebarMenuItem v-if="hasPermission('backups.view')">
            <SidebarMenuButton as-child>
              <Link href="/backups">
                <Database class="size-4" />
                <span>Backups</span>
              </Link>
            </SidebarMenuButton>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarGroup>
        </SidebarContent>

        <SidebarFooter>
      <SidebarMenu>
        <SidebarMenuItem>
          <DropdownMenu>
            <DropdownMenuTrigger as-child>
              <SidebarMenuButton>
                <Avatar class="h-6 w-6">
                  <AvatarImage :src="$page.props.auth.user.profile_picture || ''" :alt="$page.props.auth.user.name" />
                  <AvatarFallback>{{ getUserInitials($page.props.auth.user.name) }}</AvatarFallback>
                </Avatar>
                <span class="truncate">{{ $page.props.auth.user.name }}</span>
                <ChevronsUpDown class="ml-auto size-4" />
              </SidebarMenuButton>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-56">
              <DropdownMenuLabel class="font-normal">
                <div class="flex flex-col space-y-1">
                  <p class="text-sm font-medium leading-none">{{ $page.props.auth.user.name }}</p>
                  <p class="text-xs leading-none text-muted-foreground">{{ $page.props.auth.user.email }}</p>
                </div>
              </DropdownMenuLabel>
              <DropdownMenuSeparator />
              <DropdownMenuItem as-child>
                <Link href="/profile" class="w-full">
                  <User class="mr-2 h-4 w-4" />
                  <span>Profile</span>
                </Link>
              </DropdownMenuItem>
              <DropdownMenuItem as-child>
                <Link href="/logout" method="post" class="w-full">
                  <LogOut class="mr-2 h-4 w-4" />
                  <span>Log out</span>
                </Link>
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </SidebarMenuItem>
      </SidebarMenu>
        </SidebarFooter>
    </Sidebar>
</template>
