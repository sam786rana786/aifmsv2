<script setup lang="ts">
import { type HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

interface Props {
  class?: HTMLAttributes['class']
}

const props = defineProps<Props>()
</script>

<template>
  <div
    data-slot="alert-dialog-footer"
    :class="cn('flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-2', props.class)"
  >
    <slot />
  </div>
</template> 