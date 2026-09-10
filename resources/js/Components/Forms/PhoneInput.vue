<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: Object,
        default: () => ({
            extension: '',
            number: '',
        }),
    },
    countries: {
        type: Array,
        default: () => [
            { name: 'France', extension: '33', flag: '🇫🇷' },
            { name: 'Belgique', extension: '32', flag: '🇧🇪' },
            { name: 'Suisse', extension: '41', flag: '🇨🇭' },
            { name: 'Luxembourg', extension: '352', flag: '🇱🇺' },
            { name: 'Canada', extension: '1', flag: '🇨🇦' },
            { name: 'États-Unis', extension: '1', flag: '🇺🇸' },
            { name: 'Royaume-Uni', extension: '44', flag: '🇬🇧' },
            { name: 'Allemagne', extension: '49', flag: '🇩🇪' },
            { name: 'Espagne', extension: '34', flag: '🇪🇸' },
            { name: 'Italie', extension: '39', flag: '🇮🇹' },
            { name: 'Portugal', extension: '351', flag: '🇵🇹' },
            { name: 'Maroc', extension: '212', flag: '🇲🇦' },
            { name: 'Algérie', extension: '213', flag: '🇩🇿' },
            { name: 'Tunisie', extension: '216', flag: '🇹🇳' },
        ]
    },
    size: { type: String, default: 'md', validator: value => ['xs', 'sm', 'md', 'lg', 'xl'].includes(value) },
    label: { type: String, default: '' },
    placeholder: { type: String, default: 'Numéro' },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const extension = ref(props.modelValue?.extension ?? '');
const number = ref(props.modelValue?.number ?? '');
const countryOpen = ref(false);
const countrySearch = ref('');

const selectedCountry = computed(() => {
    return props.countries.find((country) => country.extension === extension.value) ?? null;
});

const filteredCountries = computed(() => {
    const search = countrySearch.value.trim().toLowerCase();

    if (!search) {
        return props.countries;
    }

    return props.countries.filter((country) => {
        return country.name.toLowerCase().includes(search)
            || country.extension.includes(search)
            || `+${country.extension}`.includes(search);
    });
});

const wrapperClasses = computed(() => {
    return {
        xs: 'rounded-sm',
        sm: 'rounded-md',
        md: 'rounded-md',
        lg: 'rounded-lg',
        xl: 'rounded-lg',
    }[props.size];
});

const countryButtonClasses = computed(() => {
    return {
        xs: 'w-24 px-1 text-xs',
        sm: 'w-28 px-2 text-sm',
        md: 'w-32 px-2.5 text-sm',
        lg: 'w-36 px-3 text-base',
        xl: 'w-40 px-3.5 text-base',
    }[props.size];
});

const countryItemClasses = computed(() => {
    return {
        xs: 'gap-2 px-2 py-1 text-xs',
        sm: 'gap-2 px-2.5 py-1.5 text-sm',
        md: 'gap-3 px-3 py-1.5 text-sm',
        lg: 'gap-3 px-3.5 py-2 text-base',
        xl: 'gap-3 px-4 py-2.5 text-base',
    }[props.size];
});
const fieldHeightClasses = computed(() => {
    return {
        xs: 'h-7',
        sm: 'h-8',
        md: 'h-9',
        lg: 'h-10',
        xl: 'h-11',
    }[props.size];
});

function emitValue() {
    emit('update:modelValue', {
        extension: extension.value,
        number: number.value,
        full: extension.value && number.value
            ? `+${extension.value}${number.value}`
            : '',
    });
}

function selectCountry(country) {
    extension.value = country.extension;
    countryOpen.value = false;
    countrySearch.value = '';
    emitValue();
}

function updateNumber(value) {
    number.value = String(value ?? '');
    emitValue();
}

watch(
    () => props.modelValue,
    (value) => {
        const nextExtension = value?.extension ?? '';
        const nextNumber = value?.number ?? '';

        if (nextExtension !== extension.value) {
            extension.value = nextExtension;
        }

        if (nextNumber !== number.value) {
            number.value = nextNumber;
        }
    },
    { deep: true }
);
</script>

<template>
    <div class="flex w-full flex-col gap-1">
        <label v-if="label" class="text-sm font-medium text-white/70">
            {{ label }}
        </label>

        <div
            class="flex w-full overflow-hidden rounded-md border border-white/10 focus-within:ring-2 focus-within:ring-primary/60"
            :class="[wrapperClasses, fieldHeightClasses, { 'opacity-60': disabled }]"
        >
            <UPopover v-model:open="countryOpen">
                <UButton
                    type="button"
                    color="neutral"
                    variant="none"
                    :disabled="disabled"
                    class="h-full w-1/3 justify-between rounded-none border-0 bg-transparent px-3"
                    :class="countryButtonClasses"
                    trailing-icon="i-lucide-chevron-down"
                >
                    <span class="flex items-center gap-2">
                        <span v-if="selectedCountry">{{ selectedCountry.flag }}</span>
                        <span>{{ extension ? `+${extension}` : '+...' }}</span>
                    </span>
                </UButton>

                <template #content>
                    <div class="w-80 space-y-2 p-2">
                        <UInput
                            :size="size"
                            v-model="countrySearch"
                            placeholder="Rechercher un pays ou indicatif..."
                            icon="i-lucide-search"
                            autofocus
                        />

                        <div class="max-h-72 overflow-y-auto">
                            <button
                                v-for="country in filteredCountries"
                                :key="`${country.name}-${country.extension}`"
                                type="button"
                                class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-left text-sm hover:bg-white/10"
                                :class="countryItemClasses"
                                @click="selectCountry(country)"
                            >
                                <span class="text-lg">{{ country.flag }}</span>

                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-white">
                                        {{ country.name }}
                                    </span>
                                    <span class="text-xs text-white/50">
                                        +{{ country.extension }}
                                    </span>
                                </span>
                            </button>

                            <p
                                v-if="filteredCountries.length === 0"
                                class="px-3 py-4 text-center text-sm text-white/50"
                            >
                                Aucun résultat
                            </p>
                        </div>
                    </div>
                </template>
            </UPopover>

            <div class="w-px bg-white/10" />

            <UInput
                :model-value="number"
                variant="none"
                :size="size"
                maxLength="10"
                type="tel"
                inputmode="tel"
                :placeholder="placeholder"
                :disabled="disabled"
                class="min-w-0 flex-1 h-full"
                :ui="{
                    root: 'h-full',
                    base: 'rounded-none border-0 bg-transparent h-full'
                }"
                @update:model-value="updateNumber"
            />
        </div>
    </div>
</template>
