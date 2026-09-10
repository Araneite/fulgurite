<script setup lang="ts">
import { ref, computed } from 'vue'

const open = defineModel<boolean>('open', { required: true });

withDefaults(defineProps<{
    title: string;
    description?: string;
    dismissible?: boolean;
    closeLabel: string;
    submitLabel: string;
    submitColor?: string;
    loading?: boolean;
    disabled?: boolean;
}>(), {
    description: '',
    dismissible: true,
    submitColor: 'success',
    loading: false,
    disabled: false
});

const emit = defineEmits<{
    close:  [];
    submit: [];
    'update:open': [value: boolean];
}>();

function handleOpenChange(value: boolean) {
    emit('update:open', value);
}
</script>

<template>
    <UDrawer
        :open="open"
        :dismissible="dismissible"
        direction="right"
        class="min-w-80 w-80 md:w-90 lg:w-100"
        :title="title"
        :description="description"
        :ui="{ header: 'border-b border-white/10 pb-3' }"
        @update:open="handleOpenChange"
    >
        <template #body>
            <slot />
        </template>
        
        <template #footer>
            <div class="flex justify-end gap-4 w-full">
                <UButton 
                    color="neutral"
                    variant="ghost"
                    :label="closeLabel"
                    @click="$emit('close')"
                />
                
                <UButton 
                    :color="submitColor"
                    :label="submitLabel"
                    :loading="loading"
                    :disabled="disabled || loading"
                    @click="$emit('submit')"
                />
            </div>
        </template>
    </UDrawer>
</template>

<style scoped>

</style>
