<script setup>
import { Link } from "@inertiajs/vue3";
</script>

<template>
    <UApp>
        <div class="flex h-min-screen bg-midnight font-sans text-zinc-100 antialiased textured">
            
    
            <main class="flex h-screen w-full flex-col">
                <nav class="h-22 w-full border-b border-white/10 bg-darknight/80 light:bg-sandstone flex items-center justify-between">
                    <Link href="/" class="flex items-center gap-4 px-5 font-semibold tracking-wide text-white light:text-black">
                        <img :src="'/assets/img/fulgurite-logo.svg'" alt="Fulgurite" class="size-10">
                        <p class="text-xl">Fulgur<span class="text-primary">ite</span></p>
                    </Link>
    
                    <div class="flex h-full max-w 6xl items-center justify-between px-6 py-4">
                        <div class="text-xs text-zinc-400">Interface interne</div>
                    </div>
                </nav>
    
                <div class="flex h-full w-full items-center justify-center gap-6 p-4">
                    <slot />
                </div>
            </main>
        </div>
    </UApp>
</template>
