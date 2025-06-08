<template>
  <div class="min-h-screen bg-background">
    <SidebarProvider :default-open="sidebarOpen">
      <AppSidebar />
      <SidebarInset>
        <header class="flex h-16 shrink-0 items-center gap-2 border-b px-4">
          <SidebarTrigger class="-ml-1" />
          <Separator orientation="vertical" class="mr-2 h-4" />
          <Breadcrumb>
            <BreadcrumbList>
              <BreadcrumbItem class="hidden md:block">
                <BreadcrumbLink href="/dashboard">
                  Dashboard
                </BreadcrumbLink>
              </BreadcrumbItem>
              <BreadcrumbSeparator v-if="breadcrumbs.length > 1" class="hidden md:block" />
              <template v-for="(item, index) in breadcrumbs" :key="index">
                <BreadcrumbItem>
                  <BreadcrumbPage v-if="index === breadcrumbs.length - 1">
                    {{ item.label }}
                  </BreadcrumbPage>
                  <BreadcrumbLink v-else :href="item.href">
                    {{ item.label }}
                  </BreadcrumbLink>
                </BreadcrumbItem>
                <BreadcrumbSeparator v-if="index < breadcrumbs.length - 1" />
              </template>
            </BreadcrumbList>
          </Breadcrumb>
          
          <div class="ml-auto flex items-center gap-2">
            <!-- Notifications -->
            <DropdownMenu>
              <DropdownMenuTrigger as-child>
                <Button variant="ghost" size="icon" class="relative">
                  <Bell class="h-4 w-4" />
                  <span v-if="unreadNotifications > 0" class="absolute -top-1 -right-1 h-5 w-5 rounded-full bg-red-500 text-xs text-white flex items-center justify-center">
                    {{ unreadNotifications > 9 ? '9+' : unreadNotifications }}
                  </span>
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end" class="w-80">
                <DropdownMenuLabel>Notifications</DropdownMenuLabel>
                <DropdownMenuSeparator />
                <div v-if="notifications.length === 0" class="p-4 text-center text-muted-foreground">
                  No notifications
                </div>
                <div v-else class="max-h-96 overflow-y-auto">
                  <div v-for="notification in notifications.slice(0, 5)" :key="notification.id" class="p-3 border-b last:border-b-0">
                    <div class="font-medium text-sm">{{ notification.title }}</div>
                    <div class="text-xs text-muted-foreground mt-1">{{ notification.message }}</div>
                    <div class="text-xs text-muted-foreground mt-1">{{ formatDate(notification.created_at) }}</div>
                  </div>
                </div>
                <DropdownMenuSeparator />
                <DropdownMenuItem as-child>
                  <Link href="/notifications" class="w-full">
                    View all notifications
                  </Link>
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
            
            <!-- User Menu -->
            <DropdownMenu>
              <DropdownMenuTrigger as-child>
                <Button variant="ghost" class="relative h-8 w-8 rounded-full">
                  <Avatar class="h-8 w-8">
                    <AvatarImage :src="$page.props.auth.user.profile_picture || ''" :alt="$page.props.auth.user.name" />
                    <AvatarFallback>{{ getUserInitials($page.props.auth.user.name) }}</AvatarFallback>
                  </Avatar>
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent class="w-56" align="end">
                <DropdownMenuLabel class="font-normal">
                  <div class="flex flex-col space-y-1">
                    <p class="text-sm font-medium leading-none">{{ $page.props.auth.user.name }}</p>
                    <p class="text-xs leading-none text-muted-foreground">{{ $page.props.auth.user.email }}</p>
                    <p class="text-xs leading-none text-muted-foreground">{{ userRoles }}</p>
                  </div>
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuGroup>
                  <DropdownMenuItem as-child>
                    <Link href="/profile" class="w-full">
                      <User class="mr-2 h-4 w-4" />
                      <span>Profile</span>
                    </Link>
                  </DropdownMenuItem>
                  <DropdownMenuItem as-child v-if="hasPermission('settings.view')">
                    <Link href="/settings" class="w-full">
                      <Settings class="mr-2 h-4 w-4" />
                      <span>Settings</span>
                    </Link>
                  </DropdownMenuItem>
                </DropdownMenuGroup>
                <DropdownMenuSeparator />
                <DropdownMenuItem as-child>
                  <Link href="/logout" method="post" class="w-full">
                    <LogOut class="mr-2 h-4 w-4" />
                    <span>Log out</span>
                  </Link>
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        </header>
        
        <div class="flex flex-1 flex-col gap-4 p-4">
          <slot />
        </div>
      </SidebarInset>
    </SidebarProvider>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { Bell, User, Settings, LogOut } from 'lucide-vue-next'
import { usePermissions } from '@/composables/usePermissions'
import { formatDate } from '@/lib/utils'
import type { AppPageProps } from '@/types'

import { SidebarProvider, SidebarInset, SidebarTrigger } from '@/components/ui/sidebar'
import { Separator } from '@/components/ui/separator'
import { 
  Breadcrumb, 
  BreadcrumbItem, 
  BreadcrumbLink, 
  BreadcrumbList, 
  BreadcrumbPage, 
  BreadcrumbSeparator 
} from '@/components/ui/breadcrumb'
import { Button } from '@/components/ui/button'
import { 
  DropdownMenu, 
  DropdownMenuContent, 
  DropdownMenuItem, 
  DropdownMenuLabel, 
  DropdownMenuSeparator, 
  DropdownMenuTrigger,
  DropdownMenuGroup
} from '@/components/ui/dropdown-menu'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'

import AppSidebar from '@/components/AppSidebar.vue'

interface Breadcrumb {
  label: string
  href?: string
}

interface Notification {
  id: number
  title: string
  message: string
  created_at: string
  read_at?: string
}

interface Role {
  name: string
}

interface Props {
  title?: string
  breadcrumbs?: Breadcrumb[]
}

const props = withDefaults(defineProps<Props>(), {
  title: '',
  breadcrumbs: () => []
})

const page = usePage()
const { hasPermission } = usePermissions()

const sidebarOpen = ref(true)

const notifications = computed((): Notification[] => {
  const notifs = (page.props as any).notifications
  return Array.isArray(notifs) ? notifs : []
})

const unreadNotifications = computed(() => 
  notifications.value.filter((n: Notification) => !n.read_at).length
)

const userRoles = computed(() => {
  const roles = (page.props as any).auth?.user?.roles
  return Array.isArray(roles) ? roles.map((r: string) => r).join(', ') : ''
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
