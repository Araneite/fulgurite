<script setup>
defineProps({
    title: { type: String, required: true },
    badge: { type: [String, Number], default: null },
});
</script>

<template>
    <section class="overflow-hidden rounded-lg border border-slate-800 bg-[#121820]">
        <div class="flex items-center justify-between border-b border-slate-800 px-4 py-3">
            <h2 class="text-sm font-bold text-slate-200">{{ title }}</h2>

            <span
                v-if="badge !== null"
                class="rounded-full bg-blue-500/20 px-2 py-0.5 text-xs text-blue-300"
            >
                {{ badge }}
            </span>
        </div>

        <slot />
    </section>
</template>
