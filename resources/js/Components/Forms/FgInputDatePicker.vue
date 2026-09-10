<script setup lang="ts">
import { computed, useTemplateRef } from 'vue';

const model = defineModel();

const props = defineProps({
    placeholder: { type: String, required: false, default: undefined },
    disabled: { type: Boolean, required: false, default: false },
    ui: { type: Object, required: false, default:   () => ({}) },
    size: { type: String, required: false, default: 'sm' },
    icon: { type: String, required: false, default: 'i-lucide-calendar'},
    __: { type: Object, required: true }
});

const inputDate = useTemplateRef('inputDate');
</script>

<template>
    <UInputDate
        ref="inputDate"
        v-model="model"
        :placeholder="placeholder"
        :disabled="disabled"
        :ui="ui"
    >
        <template #trailing>
            <UPopover :reference="inputDate?.inputsRef[3]?.$el">
                <UButton
                    color="neutral"
                    variant="link"
                    :size="size"
                    :icon="icon"
                    :aria-label="__.date_picker.aria_label"
                    class="px-0"
                />
                
                <template #content>
                    <UCalendar v-model="model" class="p-2" />
                </template>
            </UPopover>
        </template>
    </UInputDate>
</template>

<style scoped>

</style>
