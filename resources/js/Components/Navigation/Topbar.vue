<script setup>
import { Link } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const props = defineProps({
    title: {
        type: String,
        required: true
    },
    description: {
        type: String,
        default: ""
    }
})

const search = ref("");
const hasNotif = true;
</script>

<template>
    <nav class="topbar w-full sticky flex items-center justify-between px-6 py-4 bg-night border-b border-b-white/10 top-0 min-h-19.25 max-h-19.25">
        <div class="w-1/2">
            <hgroup class="page">
                <h1 class="font-bold text-white" :class="description !== '' ? 'text-lg' : 'text-xl'">{{ title }}</h1>
                <p v-if="description !== ''" class="text-sm font-normal text-white/70 min-h-5 max-h-5">{{ description }}</p>
            </hgroup>
        </div>
        
        <div class="flex items-center justify-end w-1/2 gap-10">
<!--            <Button type="button" label="Search" icon="pi pi-search" severity="secondary" badgeSeverity="contrast" badge="ctrl + K" size="small"/>-->
            <!-- Todo : Search in app -->
            
            <Link href="notifications">
                <i v-if="!hasNotif" style="font-size: 1.2rem" class="pi pi-bell"></i>
<!--                <OverlayBadge v-if="hasNotif" severity="contrast">-->
<!--                    <i class="pi pi-bell" style="font-size: 1.2rem"></i>-->
<!--                </OverlayBadge>-->
            </Link>
        </div>
    </nav>
</template>

<style scoped>

</style>
