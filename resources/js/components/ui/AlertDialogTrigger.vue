<script setup lang="ts">
import { DialogTrigger, type DialogTriggerProps } from 'reka-ui'
import { type HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

interface Props extends DialogTriggerProps {
  class?: HTMLAttributes['class']
}

const props = defineProps<Props>()
</script>

<template>
  <DialogTrigger
    data-slot="alert-dialog-trigger"
    v-bind="props"
    :class="cn(props.class)"
  >
    <slot />
  </DialogTrigger>
</template> 