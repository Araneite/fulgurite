<script setup>
defineProps({
    id: {type: String, default: null},
    name: {type: String, required: true},
    label: {type: String, required: true},
    error: {type: String, default: null}
});

const model = defineModel({
    type:Boolean, default: false
});
</script>

<template>
    <label class="flex items-center gap-2 has-focus-visible:ring-2 has-focus-visible:ring-primary-500" :for="id ?? name">
        <input
            :id="id ?? name"
            v-model="model"
            type="checkbox"
            :name="name"
        >

        <svg viewBox="0 0 64 64" height="1em" width="1em" aria-hidden="true">
            <path d="M 0 16 V 56 A 8 8 90 0 0 8 64 H 56 A 8 8 90 0 0 64 56 V 8 A 8 8 90 0 0 56 0 H 8 A 8 8 90 0 0 0 8 V 16 L 32 48 L 64 16 V 8 A 8 8 90 0 0 56 0 H 8 A 8 8 90 0 0 0 8 V 56 A 8 8 90 0 0 8 64 H 56 A 8 8 90 0 0 64 56 V 16" pathLength="575.0541381835938" class="path"></path>
        </svg>
        <p class="label">{{ label }}</p>
    </label>

    <p v-if="error" class="mt-2 text-sm text-red-400">
        {{ error }}
    </p>
</template>

<style scoped>

</style>
