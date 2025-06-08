<script setup lang="ts">
import { DialogTitle, type DialogTitleProps } from 'reka-ui'
import { type HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

interface Props extends DialogTitleProps {
  class?: HTMLAttributes['class']
}

const props = defineProps<Props>()
</script>

<template>
  <DialogTitle
    data-slot="alert-dialog-title"
    v-bind="props"
    :class="cn('text-lg font-semibold', props.class)"
  >
    <slot />
  </DialogTitle>
</template> 