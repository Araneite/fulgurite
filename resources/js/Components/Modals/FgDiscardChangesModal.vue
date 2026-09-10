<script setup lang="ts">
const open = defineModel<boolean>('open', { default: false });

defineProps({
    title: { type: String, required: true },
    description: { type: String, required: true },
    keepLabel: { type: String , required: true },
    discardLabel: { type: String, required: true },
    discardColor: { type: String, default: 'error' },
    keepColor: { type: String, default: 'neutral' },
});

const emit = defineEmits<{
    keep: [],
    discard: []
}>();
</script>

<template>
    <UModal v-model:open="open">
        <template #header>
            <h2 class="text-lg font-semibold">
                {{ title }}
            </h2>
        </template>
        
        <template #body>
            <p class="text-muted">
                {{ description }}
            </p>
        </template>
        
        <template #footer>
            <div class="flex justify-end gap-2 w-full">
                <UButton 
                    :color="keepColor"
                    variant="ghost"
                    :label="keepLabel"
                    type="button"
                    @click="emit('keep')"
                    class="cursor-pointer"
                />
                
                <UButton 
                    :color="discardColor"
                    :label="discardLabel"
                    type="button"
                    @click="emit('discard')"
                    class="cursor-pointer"
                />
            </div>
        </template>
    </UModal>
</template>

<style scoped>

</style>
