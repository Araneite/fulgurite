<script setup>
import { computed, onBeforeUnmount, ref, useAttrs } from 'vue';

defineOptions({
    inheritAttrs: false,
})

const props = defineProps({
    label: { type: String, default: null },
    icon: { type: String, default: null },
    href: { type: String, default: null },
    split: { type: Boolean, default: false },
    splitClass: { type: String, default: ''},
    actions: { type: Array, default: ()=> []},
    severity: { type: String, default: 'primary', validator: (value)=> ['primary', 'secondary', 'success', 'info', 'warning', 'danger'].includes(value) },
    disabled: { type: Boolean, default: false },
    menuLabel: { type: String, default: 'Action supplémentaire'},
});

const emit = defineEmits([
    'click', 'action-click', 'menu-open',
    'menu-close'
]);

const attrs = useAttrs();
const root = ref(null);
const isMenuOpen = ref(false);

const isLink = computed(()=> Boolean(props.href));
const buttonType = computed(()=> attrs.type ?? 'button');

const severityClasses = computed(()=> ({
    primary: 'border-transparent bg-primary-500 text-text-800 hover:bg-primary-600 focus-visible:bg-primary-600',
    secondary: 'border-primary/60 bg-transparent text-current hover:border-primary/80 hover:bg-primary/10 focus-visible:border-primary/80 focus-visible:bg-primary/10',
    tertiary: 'border-white/20 bg-transparent text-current hover:bg-white/5',
    danger: 'border-transparent bg-red-600 text-white hover:bg-red-700 focus-visible:bg-red-700',
}[props.severity]));

const baseButtonClasses = computed(()=> [
    'inline-flex min-h-11 items-center justify-center gap-2 border px-4 py-2.5 text-[0.95rem] font-bold leading-none no-underline outline-none transition-colors duration-200',
    'focus-visible:ring-1 focus-visible:ring-primary',
    props.disabled ? 'cursor-not-allowed opacity-55' : 'cursor-pointer',
    severityClasses.value,
]);

const mainButtonClasses = computed(()=> [
    ...baseButtonClasses.value,
    props.split ? 'rounded-l-lg rounded-r-none' : 'rounded-lg',
    attrs.class
]);

const splitButtonClasses = computed(()=> [
    ...baseButtonClasses.value,
    'w-11 rounded-l-none rounded-r-lg border-l-black/20 px-0',
]);

const forwardedAttrs = computed(()=> {
    const { class: __class, type: __type, ...rest } = attrs;
    
    return rest;
}) 

const visibleActions = computed(()=> {
    return props.actions.filter((action)=> action.visible !== false);
});

const iconClass = computed(()=> normalizeIcon(props.icon));

function normalizeIcon(icon) {
    if (!icon) return null;
    if (icon.startsWith('bi ')) return icon;
    if (icon.startsWith('bi-')) return `bi ${icon}`;
    
    return `bi bi-${icon}`;
}

function openMenu() {
    if (isMenuOpen.value) return;
    
    isMenuOpen.value = true;
    emit('menu-open');
    
    document.addEventListener('click', handleOutsideClick);
    document.addEventListener('keydown', handleKeydown);
}

function closeMenu() {
    if (!isMenuOpen.value) return;
    
    isMenuOpen.value = false;
    emit('menu-close');
    
    document.removeEventListener('click', handleOutsideClick);
    document.removeEventListener('keydown', handleKeydown);
}

function toggleMenu() {
    isMenuOpen.value ? closeMenu() : openMenu();
}

function handleOutsideClick(event) {
    if (!root.value || root.value.contains(event.target)) return;
    
    closeMenu();
}

function handleKeydown(event) {
    if (event.key === 'Escape') closeMenu();
}

function handleMainClick(event) {
    if (props.disabled) {
        event.preventDefault();
        return;
    }
    
    emit('click', event);
}

function handleActionClick(action, event) {
    if (action.disabled) {
        event.preventDefault();
        return;
    }
    
    emit('qction-click', action, event);
    
    if (typeof action.command === 'function') {
        action.command({
            originalEvent: event,
            item: action,
        })
    }
    
    closeMenu();
}

onBeforeUnmount(()=> {
    document.removeEventListener('click', handleOutsideClick);
    document.removeEventListerner('keydown', handleKeydown);
})
</script>

<template>
    <div
        ref="root"
        class="relative inline-flex align-middle"
    >
        <component
            :is="isLink ? 'a' : 'button'"
            v-bind="forwardedAttrs"
            :href="isLink && !disabled ? href : undefined"
            :type="isLink ? undefined : buttonType"
            :aria-disabled="disabled || undefined"
            :disabled="!isLink ? disabled : undefined"
            :class="mainButtonClasses"
            @click="handleMainClick"
        >
            <span
                v-if="iconClass"
                :class="iconClass"
                aria-hidden="true"
            />
            
            <span
                v-if="$slots.default || label"
                class="min-w-0"
            >
                <slot>{{ label }}</slot>
            </span>
        </component>
        
        <button
            v-if="split"
            type="button"
            :class="splitButtonClasses"
            :disabled="disabled || visibleActions.length === 0"
            :aria-label="menuLabel"
            :aria-expanded="isMenuOpen" 
            aria-haspopup="menu"
            @click.stop="toggleMenu"
        >
            <span 
                class="bi bi-chevron-down"
                aria-hidden="true"
            />
        </button>
        
        <div
            v-if="split && isMenuOpen"
            class="absolute right-0 top-[calc(100%+0.35rem)] z-50 min-w-52 overflow-hidden rounded-lg border border-white/15 bg-night p-1.5 text-white shadow-[0_18px_45px_rgb(0_0_0/32%)]"
            role="menu"
        >
            <template
                v-for="action in visibleActions"
                :key="action.key ?? action.label ?? action.href"
            >
                <div
                    v-if="action.separator"
                    class="my-1 h-px bg-white/10"
                    role="separator"
                />
                
                <component
                    :is="action.href ? 'a' : 'button'"
                    v-else
                    :href="action.disabled ? undefined : action.href"
                    :target="action.target"
                    :rel="action.target  === '_blank' ? 'noopener noreferrer' : undefined"
                    type="button"
                    class="flex w-full items-center gap-2.5 rounded-md bordr-0 bg-transparent px-3 py-2.5 text-left text-inherit no-underline outline-none hover:bg-white/7 focus-visible:bg-white/7"
                    :class="[
                        action.class,
                        {
                            'text-red-400': action.danger,
                            'pointer-events-none cursor-not-allowed opacity-50' : action.disabled,
                            'cursor-pointer': !action.disabled
                        }
                    ]"
                    :disabled="!action.href ? action.disabled : undefined"
                    :aria-disabled="action.disabled || undefined"
                    role="menuitem"
                    @click="handleActionClick(action, $event)"
                >
                    <span
                        v-if="normalizeIcon(action.icon)"
                        :class="normalizeIcon(action.icon)"
                        aria-hidden="true"
                    />
                    
                    <span class="min-w-0 flex-1">
                        <slot
                            name="action"
                            :action="action"
                        >
                            {{ action.label }}    
                        </slot>
                    </span>
                </component>
            </template>
        </div>
    </div>
</template>

<style scoped>

</style>
