<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";

const props = defineProps({
    item: {
        type: Object,
        required: true,
    },
    depth: {
        type: Number,
        default: 0,
    },
});

const page = usePage();

const count = computed(()=> props.item.items?.length ?? 0)
const hasChildren = computed(() => Array.isArray(props.item.items) && props.item.items.length > 0);

const normalizeUrl = (url) => {
    if (!url) {
        return null;
    }

    return url !== "/" ? url.replace(/\/$/, "") : "/";
};

const isActiveUrl = (url) => {
    const normalized = normalizeUrl(url);
    const current = normalizeUrl(page.url);

    if (!normalized || !current) {
        return false;
    }

    return normalized === "/"
        ? current === "/"
        : current === normalized || current.startsWith(`${normalized}/`);
};

const hasActiveChild = (item) => {
    return (item.items ?? []).some((child) => {
        return isActiveUrl(child.url) || hasActiveChild(child);
    });
};

const isActive = computed(() => isActiveUrl(props.item.url));
const childIsActive = computed(() => hasActiveChild(props.item));
const isOpen = ref(Boolean(props.item.open ?? childIsActive.value));

watch(childIsActive, (active) => {
    if (active) {
        isOpen.value = true;
    }
});


const paddingLeft = computed(() => `${12 + props.depth * 18}px`);
const height = computed(()=> `${(props.depth+count.value) * 36 + 16}px`)
</script>

<template>
    <div>
        <button
            v-if="hasChildren"
            type="button"
            class="flex min-h-9 w-full items-center gap-3 rounded-md pr-3 text-left text-sm text-white/80 hover:bg-white/5 hover:text-white transition-all duration-300 cursor-pointer"
            :class="{ 'bg-[#1d2a3b] text-white': isOpen || childIsActive }"
            :style="{ paddingLeft }"
            @click="isOpen = !isOpen"
        >
            <span
                v-if="item.icon"
                :class="['pi', item.icon]"
                class="text-sm text-current"
            />

            <span class="min-w-0 flex-1 truncate">
                {{ item.label }}
            </span>

            <span
                style="font-size: .8rem;"
                class="pi text-current pi-chevron-down transition duration-300 "
                :class="isOpen ? 'rotate-180' : 'pi-chevron-down'"
            />
        </button>

        <Link
            v-else
            :href="item.url"
            class="flex min-h-9 items-center gap-3 rounded-md pr-3 text-sm text-white/80 hover:bg-white/5 hover:text-white duration-200"
            :class="{ 'bg-moonlight text-white': isActive }"
            :style="{ paddingLeft }"
        >
            <span
                v-if="item.icon"
                :class="['pi', item.icon]"
                class="text-sm text-current"
            />

            <span class="min-w-0 truncate">
                {{ item.label }}
            </span>
        </Link>

        <div
            class="ml-4 border-l border-slate-700/70 transition-height duration-300 overflow-hidden"
            :class="hasChildren && isOpen ? 'py-2' : 'h-0'"
            :style="hasChildren && isOpen ? 'height: ' + height : ''"
        >
            <SidebarItem
                v-if="hasChildren"
                v-for="child in item.items"
                :key="child.key ?? child.url ?? child.label"
                :item="child"
                :depth="depth + 1"
            />
        </div>
    </div>
</template>
