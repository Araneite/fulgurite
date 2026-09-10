import { computed, onBeforeUnmount, ref, type MaybeRefOrGetter, toValue } from 'vue';
import { extractShortcuts } from '@nuxt/ui/composables';

export type ShortcutScope = 'global' | 'page' | 'component';

type ShortcutHandler = (event?: KeyboardEvent) => void | Promise<void>;

type ShortcutDisabled = boolean | (() => boolean);

type ShortcutVisible = boolean | (() => boolean);

export type ShortcutDefinition = {
    key: string;
    label?: string;
    description?: string;
    displayKey?: string;
    group?: string;
    order?: number;
    scope: ShortcutScope;
    ownerId?: string;
    disabled?: ShortcutDisabled;
    visible?: ShortcutVisible;
    usingInput?: boolean | string;
    handler: ShortcutHandler;
};

export type RegisteredShortcut = ShortcutDefinition & {
    id: string;
    source?: string;
};

const shortcuts = ref<RegisteredShortcut[]>([]);
const activePageScope = ref<string | null>(null);
const activeComponentScopes = ref<string[]>([]);

let idCounter = 0;

function normalizeKey(key: string) {
    return key.trim().toLowerCase();
}

function nextId() {
    idCounter += 1;

    return `shortcut-${idCounter}`;
}

function isDisabled(shortcut: RegisteredShortcut) {
    if (typeof shortcut.disabled === 'function') {
        return shortcut.disabled();
    }

    return shortcut.disabled === true;
}

function isVisible(shortcut: RegisteredShortcut) {
    if (typeof shortcut.visible === 'function') {
        return shortcut.visible();
    }

    return shortcut.visible !== false;
}

function ensureValidShortcut(shortcut: ShortcutDefinition) {
    if (!shortcut.key.trim()) {
        throw new Error('[Shortcuts] A shortcut key is required.');
    }

    if (!shortcut.handler) {
        throw new Error(`[Shortcuts] Shortcut "${shortcut.key}" requires a handler.`);
    }

    if (shortcut.scope !== 'global' && !shortcut.ownerId) {
        throw new Error(`[Shortcuts] Shortcut "${shortcut.key}" requires ownerId for scope "${shortcut.scope}".`);
    }
}

function ensureNoConflict(nextShortcut: ShortcutDefinition, ignoreId?: string) {
    const key = normalizeKey(nextShortcut.key);

    const existing = shortcuts.value.filter((shortcut) => {
        return shortcut.id !== ignoreId && normalizeKey(shortcut.key) === key;
    });

    const globalConflict = existing.find((shortcut) => shortcut.scope === 'global');

    if (globalConflict) {
        throw new Error(`[Shortcuts] "${nextShortcut.key}" is already reserved globally by "${globalConflict.label ?? globalConflict.id}".`);
    }

    if (nextShortcut.scope === 'global' && existing.length > 0) {
        const conflict = existing[0];

        throw new Error(`[Shortcuts] Cannot register global shortcut "${nextShortcut.key}" because it is already used by "${conflict.label ?? conflict.id}".`);
    }

    if (nextShortcut.scope === 'page') {
        const pageConflict = existing.find((shortcut) => {
            return shortcut.scope === 'page' && shortcut.ownerId === nextShortcut.ownerId;
        });

        if (pageConflict) {
            throw new Error(`[Shortcuts] "${nextShortcut.key}" is already used on page "${nextShortcut.ownerId}".`);
        }
    }

    if (nextShortcut.scope === 'component') {
        const componentConflict = existing.find((shortcut) => {
            return shortcut.scope === 'component' && shortcut.ownerId === nextShortcut.ownerId;
        });

        if (componentConflict) {
            throw new Error(`[Shortcuts] "${nextShortcut.key}" is already used on component "${nextShortcut.ownerId}".`);
        }
    }
}

function resolveShortcutForKey(key: string) {
    const normalizedKey = normalizeKey(key);
    const candidates = shortcuts.value.filter((shortcut) => normalizeKey(shortcut.key) === normalizedKey);

    for (const componentId of [...activeComponentScopes.value].reverse()) {
        const shortcut = candidates.find((item) => item.scope === 'component' && item.ownerId === componentId);

        if (shortcut && !isDisabled(shortcut)) {
            return shortcut;
        }
    }

    if (activePageScope.value) {
        const shortcut = candidates.find((item) => {
            return item.scope === 'page' && item.ownerId === activePageScope.value;
        });

        if (shortcut && !isDisabled(shortcut)) {
            return shortcut;
        }
    }

    const globalShortcut = candidates.find((item) => item.scope === 'global');

    if (globalShortcut && !isDisabled(globalShortcut)) {
        return globalShortcut;
    }

    return null;
}

export function useShortcutRegistry() {
    function registerShortcut(shortcut: ShortcutDefinition, source?: string) {
        const normalizedShortcut = {
            ...shortcut,
            key: normalizeKey(shortcut.key),
        };

        ensureValidShortcut(normalizedShortcut);
        ensureNoConflict(normalizedShortcut);

        const registered: RegisteredShortcut = {
            ...normalizedShortcut,
            id: nextId(),
            source,
        };

        shortcuts.value = [...shortcuts.value, registered];

        return () => unregisterShortcut(registered.id);
    }

    function registerShortcuts(
        nextShortcuts: ShortcutDefinition[],
        source?: string,
        options: { replaceSource?: boolean } = {}
    ) {
        if (source && options.replaceSource !== false) {
            unregisterShortcutsBySource(source);
        }

        const disposers = nextShortcuts.map((shortcut) => registerShortcut(shortcut, source));

        return () => disposers.forEach((dispose) => dispose());
    }

    function unregisterShortcutsBySource(source: string) {
        shortcuts.value = shortcuts.value.filter((shortcut) => shortcut.source !== source);
    }

    function registerNuxtUiItems(options: {
        items: MaybeRefOrGetter<any[] | any[][]>;
        scope: ShortcutScope;
        ownerId?: string;
        source?: string;
        separator?: '_' | '-';
        replaceSource?: boolean;
    }) {
        const extracted = extractShortcuts(toValue(options.items), options.separator ?? '_');

        return registerShortcuts(
            Object.entries(extracted).map(([key, handler]) => ({
                key,
                scope: options.scope,
                ownerId: options.ownerId,
                handler: handler as ShortcutHandler,
            })),
            options.source,
            { replaceSource: options.replaceSource }
        );
    }

    function unregisterShortcut(id: string) {
        shortcuts.value = shortcuts.value.filter((shortcut) => shortcut.id !== id);
    }

    function setActivePageShortcutScope(ownerId: string | null) {
        activePageScope.value = ownerId;

        return () => {
            if (activePageScope.value === ownerId) {
                activePageScope.value = null;
            }
        };
    }

    function activateComponentShortcutScope(ownerId: string) {
        activeComponentScopes.value = [
            ...activeComponentScopes.value.filter((id) => id !== ownerId),
            ownerId,
        ];

        return () => deactivateComponentShortcutScope(ownerId);
    }

    function deactivateComponentShortcutScope(ownerId: string) {
        activeComponentScopes.value = activeComponentScopes.value.filter((id) => id !== ownerId);
    }

    const visibleShortcuts = computed(() => {
        return shortcuts.value
            .filter(isVisible)
            .filter((shortcut) => {
                if (shortcut.scope === 'global') return true;
                if (shortcut.scope === 'page') return shortcut.ownerId === activePageScope.value;
                if (shortcut.scope === 'component') return activeComponentScopes.value.includes(String(shortcut.ownerId));

                return false;
            })
            .sort((a, b) => {
                return (a.order ?? 999) - (b.order ?? 999)
                    || String(a.group ?? '').localeCompare(String(b.group ?? ''))
                    || String(a.label ?? a.key).localeCompare(String(b.label ?? b.key));
            });
    });

    const nuxtShortcuts = computed(() => {
        const keys = [...new Set(shortcuts.value.map((shortcut) => normalizeKey(shortcut.key)))];

        return keys.reduce<Record<string, any>>((acc, key) => {
            acc[key] = {
                usingInput: false,
                handler: (event: KeyboardEvent) => {
                    const shortcut = resolveShortcutForKey(key);

                    if (!shortcut) return;

                    shortcut.handler(event);
                },
            };

            return acc;
        }, {});
    });

    return {
        shortcuts,
        visibleShortcuts,
        activePageScope,
        activeComponentScopes,
        nuxtShortcuts,
        registerShortcut,
        registerShortcuts,
        registerNuxtUiItems,
        unregisterShortcut,
        unregisterShortcutsBySource,
        setActivePageShortcutScope,
        activateComponentShortcutScope,
        deactivateComponentShortcutScope,
    };
}

export function usePageShortcutScope(ownerId: string) {
    const { setActivePageShortcutScope } = useShortcutRegistry();

    const dispose = setActivePageShortcutScope(ownerId);

    onBeforeUnmount(dispose);

    return dispose;
}

export function useComponentShortcutScope(ownerId: string) {
    const {
        activateComponentShortcutScope,
        deactivateComponentShortcutScope,
    } = useShortcutRegistry();

    function activate() {
        activateComponentShortcutScope(ownerId);
    }

    function deactivate() {
        deactivateComponentShortcutScope(ownerId);
    }

    onBeforeUnmount(deactivate);

    return {
        activate,
        deactivate,
    };
}
