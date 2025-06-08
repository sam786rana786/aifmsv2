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
    data-slot="alert-dialog-action"
    v-bind="props"
    :class="cn(
      'inline-flex h-10 items-center justify-center whitespace-nowrap rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground ring-offset-background transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50',
      props.class
    )"
  >
    <slot />
  </DialogClose>
</template> 