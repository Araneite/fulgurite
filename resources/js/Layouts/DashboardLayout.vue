<script setup>
import { Link } from "@inertiajs/vue3";
import FlashToaster from "@/Components/Feedback/FlashToaster.vue";

const navItems = [
    { label: "Dashboard", href: "/" },
    { label: "Profil", href: "/profile" },
    { label: "Paramètres", href: "/profile" },
];
</script>

<template>
    <div class="flex min-h-screen bg-[#0b1016] font-sans text-zinc-100 antialiased">
        <FlashToaster />

        <aside class="hidden min-h-screen w-56 shrink-0 border-r border-slate-800 bg-[#121820] md:block">
            <Link href="/" class="flex h-16 items-center gap-3 border-b border-slate-800 px-5">
                <img :src="'/assets/img/fulgurite-logo.svg'" alt="Fulgurite" class="size-9">
                <span class="font-semibold text-white">Fulgurite</span>
            </Link>

            <nav class="space-y-6 px-3 py-5">
                <div>
                    <p class="mb-2 px-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Vue d’ensemble</p>

                    <Link
                        v-for="item in navItems"
                        :key="item.label"
                        :href="item.href"
                        class="block rounded-md px-3 py-2 text-sm text-slate-400 hover:bg-slate-800/70 hover:text-white"
                    >
                        {{ item.label }}
                    </Link>
                </div>
            </nav>
        </aside>

        <main class="min-w-0 flex-1">
            <slot />
        </main>
    </div>
</template>
