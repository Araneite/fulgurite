<script setup lang="ts">
import { computed } from 'vue';
import { useShortcutRegistry, type RegisteredShortcut, type ShortcutScope } from "@/Composables/useKeyboardShortcutRegistry";

const open = defineModel<boolean>('open', { default: false });

const { visibleShortcuts } = useShortcutRegistry();

const sectionConfig: Record<ShortcutScope, {
    title: string;
    description: string;
    icon: string;
}> = {
    global: {
        title: "Global",
        description: "Shortcuts available in all app.",
        icon: 'i-lucide-globe-2',
    },
    page: {
        title: "Page",
        description: "Shortcuts available in this page.",
        icon: 'i-lucide-file-text',
    },
    component: {
        title: "Component",
        description: "Shortcuts available in component in this page.",
        icon: 'i-lucide-component',
    },
};

const sections = computed(() => {
    return (['global', 'page', 'component'] as ShortcutScope[]).map((scope)=> {
        const shortcuts = visibleShortcuts.value.filter((shortcut)=> shortcut.scope === scope);
        
        return {
            scope,
            ...sectionConfig[scope],
            shortcuts
        };
    });
});

const totalShortcuts = computed(() => visibleShortcuts.value.length);

function close() {
    open.value = false;
}

function formatKey(shortcut: RegisteredShortcut) {
    if (shortcut.displayKey) return shortcut.displayKey;
    
    return shortcut.key
        .split('-')
        .map((sequence)=> {
            return sequence
                .split('_')
                .map(formatKeyToken)
                .join(' + ');
        })
        .join(' puis ');
}

function formatKeyToken(token: string) {
    const normalized = token.toLowerCase();

    const tokens: Record<string, string> = {
        meta: '⌘',
        command: '⌘',
        cmd: '⌘',
        ctrl: 'Ctrl',
        control: 'Ctrl',
        alt: 'Alt',
        option: 'Alt',
        shift: 'Shift',
        enter: 'Entrée',
        escape: 'Échap',
        esc: 'Échap',
        backspace: 'Retour',
        delete: 'Suppr',
        space: 'Espace',
        tab: 'Tab',
        up: '↑',
        down: '↓',
        left: '←',
        right: '→',
        arrowup: '↑',
        arrowdown: '↓',
        arrowleft: '←',
        arrowright: '→',
    };

    return tokens[normalized] ?? normalized.toUpperCase();
}

function groupedShortcuts(shortcuts: RegisteredShortcut[]) {
    return shortcuts.reduce<Record<string, RegisteredShortcut[]>>((groups, shortcut) => {
        const group = shortcut.group ?? 'Raccourcis';

        groups[group] ??= [];
        groups[group].push(shortcut);

        return groups;
    }, {});
}
</script>

<template>
    <UModal
        v-model:open="open"
        :ui="{ content: 'sm:max-w-3xl' }"
    >
        <template #header>
            <div class="flex w-full items-start justify-between gap-4">
                <div class="flex items-start gap-3">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-md border border-primary/25 bg-primary/10 text-primary">
                        <UIcon name="i-lucide-keyboard" class="size-5" />
                    </div>

                    <div>
                        <h2 class="text-lg font-semibold text-white">
                            Raccourcis clavier
                        </h2>
                        <p class="mt-1 text-sm text-muted">
                            {{ totalShortcuts }} raccourci(s) disponible(s) dans le contexte actuel.
                        </p>
                    </div>
                </div>

                <UButton
                    icon="i-lucide-x"
                    color="neutral"
                    variant="ghost"
                    size="sm"
                    aria-label="Fermer"
                    @click="close"
                />
            </div>
        </template>

        <template #body>
            <div class="max-h-[65vh] overflow-y-auto pr-1">
                <div
                    v-if="totalShortcuts === 0"
                    class="flex flex-col items-center justify-center rounded-md border border-dashed border-default px-6 py-12 text-center"
                >
                    <UIcon name="i-lucide-keyboard-off" class="mb-3 size-8 text-muted" />
                    <p class="font-medium text-white">
                        Aucun raccourci disponible
                    </p>
                    <p class="mt-1 text-sm text-muted">
                        Les raccourcis apparaîtront ici dès qu’une page ou un composant en enregistrera.
                    </p>
                </div>

                <div v-else class="grid gap-4">
                    <section
                        v-for="section in sections"
                        :key="section.scope"
                        class="rounded-md border border-default bg-white/[0.03]"
                    >
                        <header class="flex items-center justify-between gap-3 border-b border-default px-4 py-3">
                            <div class="flex items-center gap-3">
                                <UIcon :name="section.icon" class="size-4 text-primary" />

                                <div>
                                    <h3 class="text-sm font-semibold text-white">
                                        {{ section.title }}
                                    </h3>
                                    <p class="text-xs text-muted">
                                        {{ section.description }}
                                    </p>
                                </div>
                            </div>

                            <UBadge
                                :label="String(section.shortcuts.length)"
                                color="neutral"
                                variant="soft"
                            />
                        </header>

                        <div v-if="section.shortcuts.length === 0" class="px-4 py-5 text-sm text-muted">
                            Aucun raccourci actif pour cette section.
                        </div>

                        <div v-else class="divide-y divide-default">
                            <div
                                v-for="(groupShortcuts, group) in groupedShortcuts(section.shortcuts)"
                                :key="group"
                                class="px-4 py-3"
                            >
                                <p class="mb-2 text-xs font-medium uppercase tracking-wide text-muted">
                                    {{ group }}
                                </p>

                                <ul class="grid gap-2">
                                    <li
                                        v-for="shortcut in groupShortcuts"
                                        :key="shortcut.id"
                                        class="flex items-center justify-between gap-4 rounded-md px-2 py-2 transition-colors hover:bg-white/[0.04]"
                                        :class="{ 'opacity-50': shortcut.disabled === true }"
                                    >
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-medium text-white">
                                                {{ shortcut.label ?? shortcut.key }}
                                            </p>
                                            <p
                                                v-if="shortcut.description"
                                                class="mt-0.5 truncate text-xs text-muted"
                                            >
                                                {{ shortcut.description }}
                                            </p>
                                        </div>

                                        <div class="flex shrink-0 items-center gap-1">
                                            <kbd
                                                v-for="part in formatKey(shortcut).split(' + ')"
                                                :key="`${shortcut.id}-${part}`"
                                                class="min-w-7 rounded-md border border-white/15 bg-night px-2 py-1 text-center text-xs font-semibold text-sandstone shadow-sm"
                                            >
                                                {{ part }}
                                            </kbd>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </template>

        <template #footer>
            <div class="flex w-full items-center justify-between gap-3">
                <p class="text-xs text-muted">
                    Les raccourcis affichés dépendent de la page et du composant actuellement actifs.
                </p>

                <UButton
                    label="Fermer"
                    color="neutral"
                    variant="ghost"
                    @click="close"
                />
            </div>
        </template>
    </UModal>
</template>

<style scoped>

</style>
