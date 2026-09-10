<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from "@inertiajs/vue3";
import FgConfirmIdentityModal from '@/Components/Modals/FgConfirmIdentityModal.vue';

type IdentityMethod = {
    label: string;
    value: string;
};

type ConfirmIdentityConfig = {
    title: string;
    subtitle?: string;
    fields: {
        password: string;
        method: string;
        code: string;
    };
    methods?: Record<string, string>;
    actions: {
        cancel: string;
        confirm: string;
        passkey: string;
        resend_email_code: string;
    };
    messages: {
        email_sent_to: string;
        email_code_sent?: string;
    };
    errors: {
        invalid_code?: string;
        invalid_passkey: string;
    };
    routes?: {
        confirm?: string;
        method?: string;
        emailCode?: string;
        passkeyOptions?: string;
        passkeySubmit?: string;
    };
};

type BulkActionType = 'delete' | 'force_delete' | 'restore';

type BulkActionTranslations = {
    title: string;
    description: string;
    action_description?: string;
    confirm: string;
    ignored_title?: string;
    counters: {
        selected: string;
        actionable: string;
        ignored: string;
    };
    ignored_reasons: {
        already_trashed?: string;
        not_trashed?: string;
        missing_permission: string;
    };
};

type BulkConfig = {
    type: BulkActionType;
    translations: BulkActionTranslations;
    selectedRows: any[];
    actionableRows: any[];
};

const open = defineModel<boolean>('open', { default: false });

const props = withDefaults(defineProps<{
    methods: IdentityMethod[];
    selectedMethod?: string | null;
    maskedEmail?: string;
    identityConfig: ConfirmIdentityConfig;
    bulkConfig: BulkConfig;
}>(), {
    selectedMethod: null,
    maskedEmail: '',
});

const emit = defineEmits<{
    confirmed: [];
}>();

const page = usePage();

const selectedRows = computed(() => props.bulkConfig.selectedRows ?? []);
const actionableRows = computed(() => props.bulkConfig.actionableRows ?? []);

const selectedCount = computed(() => selectedRows.value.length);
const actionableCount = computed(() => actionableRows.value.length);
const ignoredCount = computed(() => Math.max(selectedCount.value - actionableCount.value, 0));

const actionableIds = computed(() =>
    new Set(actionableRows.value.map((row) => row.id))
);

const ignoredRows = computed(() =>
    selectedRows.value.filter((row) => !actionableIds.value.has(row.id))
);

const modalConfig = computed(() => ({
    title: props.bulkConfig.translations.title,
    subtitle: props.identityConfig.subtitle,
    fields: {
        password: props.identityConfig.fields.password,
        method: props.identityConfig.fields.method,
        code: props.identityConfig.fields.code,
    },
    methods: props.identityConfig.methods,
    actions: {
        cancel: props.identityConfig.actions.cancel,
        confirmButton: props.bulkConfig.translations.confirm,
        passkeyButton: props.bulkConfig.translations.confirm,
        resendEmailCode: props.identityConfig.actions.resend_email_code,
    },
    emailSentTo: props.identityConfig.messages.email_sent_to,
    errors: {
        invalidPasskey: props.identityConfig.errors.invalid_passkey,
    },
    routes: props.identityConfig.routes,
}));

const icon = computed(() => {
    if (props.bulkConfig.type === 'restore') return 'i-lucide-rotate-ccw';
    if (props.bulkConfig.type === 'force_delete') return 'i-lucide-trash-2';

    return 'i-lucide-trash';
});

const color = computed(() => {
    if (props.bulkConfig.type === 'restore') return 'success';

    return 'error';
});

const colorClass = computed(() => ({
    'text-error': color.value === 'error',
    'text-success': color.value === 'success',
}));

function isCurrentUser(row: any) {
    return Boolean(row?.id === page.props.auth.user.id);
}
function isTrashed(row: any) {
    return Boolean(row?.deleted_at || row?.is_deleted);
}
function hasPermission(row: any) {
    if (props.bulkConfig.type === 'delete') {
        return Boolean(row?.permissions?.delete) && Boolean(row?.id !== page.props.auth.user.id);
    }

    if (props.bulkConfig.type === 'force_delete') {
        return Boolean(row?.permissions?.force_delete) && Boolean(row?.id !== page.props.auth.user.id);
    }

    return Boolean(row?.permissions?.restore) && Boolean(row?.id !== page.props.auth.user.id);
}

const ignoredReasons = computed(() => {
    const currentUser = ignoredRows.value.filter((row) => isCurrentUser(row)).length;
    const reasons: { label: string; count: number }[] = [];
    const trans = props.bulkConfig.translations.ignored_reasons;

    if (props.bulkConfig.type === 'delete') {
        if (currentUser > 0 && trans.current_user) {
            reasons.push({
                label: trans.current_user,
                count: currentUser
            })
        }
        
        const alreadyTrashed = ignoredRows.value.filter((row) => !isCurrentUser(row) && isTrashed(row)).length;
        const missingPermission = ignoredRows.value.filter((row) =>
            !isCurrentUser(row) && !isTrashed(row) && !hasPermission(row)
        ).length;

        if (alreadyTrashed > 0 && trans.already_trashed) {
            reasons.push({
                label: trans.already_trashed,
                count: alreadyTrashed,
            });
        }

        if (missingPermission > 0) {
            reasons.push({
                label: trans.missing_permission,
                count: missingPermission,
            });
        }
    }

    if (props.bulkConfig.type === 'force_delete') {
        if (currentUser > 0 && trans.current_user) {
            reasons.push({
                label: trans.current_user,
                count: currentUser,
            });
        }
        const notTrashed = ignoredRows.value.filter((row) => !isCurrentUser(row) && !isTrashed(row)).length;
        const missingPermission = ignoredRows.value.filter((row) =>
            !isCurrentUser(row) && isTrashed(row) && !hasPermission(row)
        ).length;

        if (notTrashed > 0 && trans.not_trashed) {
            reasons.push({
                label: trans.not_trashed,
                count: notTrashed,
            });
        }

        if (missingPermission > 0) {
            reasons.push({
                label: trans.missing_permission,
                count: missingPermission,
            });
        }
    }

    if (props.bulkConfig.type === 'restore') {
        if (currentUser > 0 && trans.current_user) {
            reasons.push({
                label: trans.current_user,
                count: currentUser,
            });
        }
        const notTrashed = ignoredRows.value.filter((row) => !isCurrentUser(row) && !isTrashed(row)).length;
        const missingPermission = ignoredRows.value.filter((row) =>
            !isCurrentUser(row) && isTrashed(row) && !hasPermission(row)
        ).length;

        if (notTrashed > 0 && trans.not_trashed) {
            reasons.push({
                label: trans.not_trashed,
                count: notTrashed,
            });
        }

        if (missingPermission > 0) {
            reasons.push({
                label: trans.missing_permission,
                count: missingPermission,
            });
        }
    }

    return reasons;
});

const ignoredTitle = computed(() => {
    if (props.bulkConfig.translations.ignored_title) {
        return props.bulkConfig.translations.ignored_title.replace(':count', String(ignoredCount.value));
    }

    return `${ignoredCount.value} utilisateur(s) ignoré(s)`;
});
</script>

<template>
    <FgConfirmIdentityModal
        v-model:open="open"
        :methods="methods"
        :selected-method="selectedMethod"
        :masked-email="maskedEmail"
        :config="modalConfig"
        @confirmed="emit('confirmed')"
    >
        <template #before-form>
            <div class="flex flex-col gap-4">
                <div class="flex items-start gap-3">
                    <UIcon
                        :name="icon"
                        class="mt-0.5 size-5"
                        :class="colorClass"
                    />

                    <p class="text-sm text-muted">
                        {{ bulkConfig.translations.description }}
                    </p>
                </div>

                <div class="grid grid-cols-3 gap-2">
                    <div class="rounded-md border border-default p-3">
                        <p class="text-xs text-muted">
                            {{ bulkConfig.translations.counters.selected }}
                        </p>
                        <p class="text-lg font-semibold">{{ selectedCount }}</p>
                    </div>

                    <div class="rounded-md border border-default p-3">
                        <p class="text-xs text-muted">
                            {{ bulkConfig.translations.counters.actionable }}
                        </p>
                        <p class="text-lg font-semibold">{{ actionableCount }}</p>
                    </div>

                    <div class="rounded-md border border-default p-3">
                        <p class="text-xs text-muted">
                            {{ bulkConfig.translations.counters.ignored }}
                        </p>
                        <p class="text-lg font-semibold">{{ ignoredCount }}</p>
                    </div>
                </div>

                <UAlert
                    v-if="bulkConfig.translations.action_description"
                    color="info"
                    variant="soft"
                    icon="i-lucide-info"
                    :description="bulkConfig.translations.action_description"
                />

                <UAlert
                    v-if="ignoredCount > 0"
                    color="warning"
                    variant="soft"
                    icon="i-lucide-triangle-alert"
                >
                    <template #title>
                        {{ ignoredTitle }}
                    </template>

                    <template #description>
                        <ul class="mt-2 list-disc space-y-1 pl-4">
                            <li
                                v-for="reason in ignoredReasons"
                                :key="reason.label"
                            >
                                {{ reason.count }} - {{ reason.label }}
                            </li>
                        </ul>
                    </template>
                </UAlert>
            </div>
        </template>
    </FgConfirmIdentityModal>
</template>
