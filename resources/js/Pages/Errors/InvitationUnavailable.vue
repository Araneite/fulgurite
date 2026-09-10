<script setup>
import AuthLayout from "@/Layouts/AuthLayout.vue";

defineOptions({
    layout: AuthLayout,
});

const props = defineProps({
    reason: { type: String, required: true},
    __: { type: Object, required: true },
});

const iconByReason = {
    expired: "i-lucide-clock-alert",
    accepted: "i-lucide-check-circle-2",
    revoked: "i-lucide-ban",
    not_found: "i-lucide-link-2-off",
};
</script>

<template>
    <div class="w-full max-w-md">
        <UCard
            :ui="{
                root: 'bg-darknight/80 ring-white/10',
                body: 'p-6 sm:p-7',
                header: 'p-6 sm:p-7 border-white/10',
                footer: 'p-6 sm:p-7 border-white/10'
            }"
        >
            <template #header>
                <div class="flex flex-col items-center text-center">
                    <img :src="'/assets/img/fulgurite-logo.svg'" alt="Fulgurite" class="size-14">

                    <div class="mt-5 flex size-12 items-center justify-center rounded-full bg-red-500/10 text-red-400">
                        <UIcon :name="iconByReason[props.reason] ?? iconByReason.not_found" class="size-6" />
                    </div>

                    <h1 class="mt-4 text-xl font-semibold text-white">
                        {{ props.__.title }}
                    </h1>

                    <p class="mt-2 text-sm leading-6 text-text-400">
                        {{ props.__.description }}
                    </p>
                </div>
            </template>

            <UAlert
                color="error"
                variant="soft"
                :icon="iconByReason[props.reason] ?? iconByReason.not_found"
                :title="props.__.alert_title"
                :description="props.__.alert_description"
            />

            <template #footer>
                <div class="flex flex-col gap-3">
                    <UButton
                        to="/login"
                        icon="i-lucide-log-in"
                        color="primary"
                        :label="props.__.login_link"
                        block
                    />

                    <p class="text-center text-xs leading-5 text-text-500">
                        {{ props.__.footer }}
                    </p>
                </div>
            </template>
        </UCard>
    </div>
</template>
