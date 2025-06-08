<script setup lang="ts">
import { DialogClose, type DialogCloseProps } from 'reka-ui'
import { type HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

interface Props extends DialogCloseProps {
  class?: HTMLAttributes['class']
}

const props = defineProps<Props>()
</script>

<template>
  <DialogClose
    data-slot="alert-dialog-cancel"
    v-bind="props"
    :class="cn(
      'inline-flex h-10 items-center justify-center whitespace-nowrap rounded-md border border-input bg-background px-4 py-2 text-sm font-medium ring-offset-background transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50',
      props.class
    )"
  >
    <slot />
  </DialogClose>
</template> 