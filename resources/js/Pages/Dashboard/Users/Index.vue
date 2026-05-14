<script setup>
import AppLayout from "@/Layouts/DashboardLayout.vue";

defineOptions({
    layout: AppLayout,
});

defineProps({
    activeUsersCount: Number,
    inactiveUsersCount: Number,
    trashedUsersCount: Number,
});
</script>

<template>
    <div class="w-full space-y-8">

        <section class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-lg border border-white/10 bg-zinc-900 p-4">
                <p class="text-sm text-zinc-400">Actifs</p>
                <p class="mt-2 text-2xl font-semibold text-white">{{ activeUsersCount }}</p>
            </div>

            <div class="rounded-lg border border-white/10 bg-zinc-900 p-4">
                <p class="text-sm text-zinc-400">Inactifs</p>
                <p class="mt-2 text-2xl font-semibold text-white">{{ inactiveUsersCount }}</p>
            </div>

            <div class="rounded-lg border border-white/10 bg-zinc-900 p-4">
                <p class="text-sm text-zinc-400">Supprimes</p>
                <p class="mt-2 text-2xl font-semibold text-white">{{ trashedUsersCount }}</p>
            </div>
        </section>
    </div>
</template>

<style scoped>

</style>
