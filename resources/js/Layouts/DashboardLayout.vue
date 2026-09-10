<script setup lang="ts">
import { computed, watch, ref } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import { useToast } from '@nuxt/ui/composables';
import { fr, en, en_gb } from '@nuxt/ui/locale'
import { defineShortcuts } from "@nuxt/ui/composables";

import { useShortcutRegistry } from "@/Composables/useKeyboardShortcutRegistry";
import Sidebar from "@/Components/Navigation/Sidebar.vue";
import Topbar from "@/Components/Navigation/Topbar.vue";
import FgKeyboardShortcutsModal from "@/Components/Modals/FgKeyboardShortcutsModal.vue";

const { nuxtShortcuts, registerShortcuts } = useShortcutRegistry();

defineShortcuts(nuxtShortcuts);

registerShortcuts([
    {
        key: 'meta_k',
        label: 'Recherche globale',
        scope: 'global',
        handler: () => {
            // openGlobalSearch();
        },
    },
    {
        key: 'g-u',
        label: 'Aller aux utilisateurs',
        scope: 'global',
        handler: () => {
            router.get('/users');
        },
    },
    {
        key: '?',
        label: 'Afficher les raccourcis clavier',
        description: 'Ouvre l’aide contextuelle des raccourcis disponibles.',
        scope: 'global',
        group: 'Aide',
        order: 100,
        handler: () => {
            keyboardShortcutsOpen.value = true;
        },
    },
], 'dashboard-global');

const page = usePage();
const toast = useToast();

const flash = computed(()=> page.props.flash ?? {});

const flashToastingConfig = {
    success: { color: 'success', icon: 'i-lucide-circle-check' },
    error: { color: 'error', icon: 'i-lucide-circle-x' },
    warning: { color: 'warning', icon: 'i-lucide-triangle-alert' },
    info: { color: 'info', icon: 'i-lucide-info' },
} as const;

watch(
    flash,
    (value)=> {
        Object.entries(flashToastingConfig).forEach(([type, config])=> {
            const message = value?.[type];
            
            if (!message) return;
            
            const payload
                = typeof message === 'object'
                ? message
                : { title: String(message) };
            
            toast.add({
                title: payload.title,
                description: payload.description,
                color: config.color,
                icon: config.icon,
            })
        })
    },
    { deep: true }
)

const uiLocales = {
    fr,
    'fr_FR': fr,
    en,
    'en_GB': en_gb,
    'en_US': en,
}

const uiLocale = computed(() => {
    return uiLocales[String(page.props.locale)] ?? fr;
})

// === Modals === 
const keyboardShortcutsOpen = ref(false);

</script>

<template>
    <UApp :locale="uiLocale">
        <div class="flex h-screen bg-[#0b1016] font-sans text-zinc-100 antialiased overflow-hidden">
            <FgKeyboardShortcutsModal v-model:open="keyboardShortcutsOpen" />
    
            <Sidebar />
            
            <main class="w-full ml-56 relative flex flex-1 flex-col overflow-hidden min-w-0 ">
                <Topbar 
                    :title="page.props.page?.title ?? 'Dashboard'"
                    :description="page.props.page?.description ?? ''"
                />
                <div class="page min-h-0 flex-1 overflow-x-hidden p-5 max-h-[calc(100vh-77px)]">
                    <slot />
                </div>
            </main>
        </div>
    </UApp>
</template>
