<script setup lang="ts">
import { SwitchRoot, SwitchThumb } from 'reka-ui'
import { cn } from '@/lib/utils'

interface Props {
  checked?: boolean
  disabled?: boolean
  id?: string
  name?: string
  value?: string | number
}

const props = withDefaults(defineProps<Props>(), {
  checked: false,
  disabled: false,
})

const emit = defineEmits<{
  'update:checked': [value: boolean]
}>()

const handleChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  emit('update:checked', target.checked)
}
</script>

<template>
  <SwitchRoot
    :checked="checked"
    :disabled="disabled"
    :id="id"
    :name="name"
    :value="value"
    @update:checked="emit('update:checked', $event)"
    :class="cn(
      'peer inline-flex h-[1.15rem] w-8 shrink-0 items-center rounded-full border-2 border-transparent shadow-xs transition-all outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50',
      checked 
        ? 'bg-green-500 border-green-500 dark:bg-green-600 dark:border-green-600' 
        : 'bg-red-500 border-red-500 dark:bg-red-600 dark:border-red-600',
    )"
  >
    <SwitchThumb
      :class="cn(
        'bg-white dark:bg-gray-200 pointer-events-none block size-4 rounded-full ring-0 transition-transform data-[state=checked]:translate-x-[calc(100%-2px)] data-[state=unchecked]:translate-x-0'
      )"
    />
  </SwitchRoot>
</template>
