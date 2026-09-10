<script setup lang="ts">
const open = defineModel<boolean>('open', { default: false });

withDefaults(defineProps<{
    title: string;
    description: string;
    cancelLabel: string;
    confirmLabel: string;
    totalCount: number;
    actionnableCount: number;
    ignoredCount?: number;
    ignoredDescription?: string;
    actionDescription?: string;
    icon?: string;
    color?: 'primary' | 'neutral' | 'error' | 'warning' | 'success' | 'info';
    loading?: boolean;
}>(), {
    ignoredCount: 0,
    ignoredDescription: '',
    actionDescription: '',
    icon: 'i-lucide-list-check',
    color: 'primary',
    loading: false,
});

const emit = defineEmits<{
    cancel: [];
    confirm: [];
}>();

function close() {
    emit('cancel');
    open.value = false;
}

function confirm() {
    emit('confirm');
}
</script>

<template>
    <UModal
        v-model:open="open"
        @update:open="(value: boolean)=> value ? open = true : close()"
    >
        <template #header>
            <div class="flex items-center gap-3">
                <UIcon 
                    :name="icon"
                    class="size-5"
                    :class="{
                        'text-error': color === 'error',
                        'text-warning': color === 'warning',
                        'text-success': color === 'success',
                        'text-info': color === 'info',
                        'text-primary': color === 'primary',
                        'text-muted': color === 'neutral',
                    }"
                />
                
                <h2 class="text-lg font-semibold">
                    {{ title }}
                </h2>
            </div>
        </template>
        
        <template #body>
            <div class="flex flex-col gap-4">
                <p class="text-sm text-muted">
                    {{ description }}
                </p>
                
                <div class="grid grid-cols-3 gap-2">
                    <div class="rounded-md border border-default p-3">
                        <p class="text-xs text-muted">Sélectionnés</p>
                        <p class="text-lg font-semibold">{{ totalCount }}</p>
                    </div>
                    
                    <div class="rounded-md border border-default p-3">
                        <p class="text-xs text-muted">Traités</p>
                        <p class="text-lg font-semibold">{{ actionnableCount }}</p>
                    </div>

                    <div class="rounded-md border border-default p-3">
                        <p class="text-xs text-muted">Ignorés</p>
                        <p class="text-lg font-semibold">{{ ignoredCount }}</p>
                    </div>
                </div>
                
                <UAlert
                    v-if="actionDescription"
                    color="info"
                    variant="soft"
                    icon="i-lucide-info"
                    :description="actionDescription"
                />
                
                <UAlert 
                    v-if="ignoredCount > 0"
                    color="warning"
                    variant="soft"
                    icon="i-lucide=triangle-alert"
                    :title="ignoredDescription"
                />
            </div>
        </template>
        
        <template #footer>
            <div class="flex justify-end gap-2 w-full">
                <UButton 
                    color="neutral"
                    variant="ghost"
                    :label="cancelLabel"
                    type="button"
                    :disabled="loading"
                    @click="close"
                />
                
                <UButton 
                    :color="color"
                    :label="confirmLabel"
                    type="button"
                    :loading="loading"
                    :disabled="actionnableCount === 0"
                    @click="confirm"
                />
            </div>
        </template>
    </UModal>
</template>

<style scoped>

</style>
