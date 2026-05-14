<script setup>
import { usePage } from "@inertiajs/vue3";
import FlashToaster from "@/Components/Feedback/FlashToaster.vue";
import Sidebar from "@/Components/Navigation/Sidebar.vue";
import Topbar from "@/Components/Navigation/Topbar.vue";

const page = usePage();

console.log(page.props.page);
</script>

<template>
    <div class="flex min-h-screen bg-[#0b1016] font-sans text-zinc-100 antialiased">
        <FlashToaster />

        <Sidebar />
        
        <main class="w-full ml-56 relative">
            <Topbar 
                :title="page.props.page?.title ?? 'Dashboard'"
                :description="page.props.page?.description ?? ''"
            />
            <div class="page p-5">
                <slot />
            </div>
        </main>
    </div>
</template>
