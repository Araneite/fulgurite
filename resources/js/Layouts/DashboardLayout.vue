<script setup>
import FlashToaster from "@/Components/Feedback/FlashToaster.vue";
import Splitter from 'primevue/splitter';
import SplitterPanel from "primevue/splitterpanel";
import Sidebar from "@/Components/Navigation/Sidebar.vue";

const navItems = [
    { label: "Dashboard", href: "/" },
    { label: "Profil", href: "/profile" },
    { label: "Paramètres", href: "/profile" },
];
</script>

<template>
    <div class="flex min-h-screen bg-[#0b1016] font-sans text-zinc-100 antialiased">
        <FlashToaster />

        <Sidebar />
        
        <main class="w-full">
            <slot />
        </main>
    </div>
</template>
