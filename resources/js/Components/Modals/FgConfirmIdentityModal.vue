<script setup lang="ts">
import { computed, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { Passkeys } from '@laravel/passkeys';

type IdentityMethod = {
    label: string;
    value: string;
}

type ConfirmIdentityModalConfig = {
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
        confirmButton: string;
        passkeyButton: string;
        resendEmailCode: string;
    };
    emailSentTo: string;
    errors: {
        invalidPasskey: string;
    };
    routes?: {
        confirm?: string;
        method?: string;
        emailCode?: string;
        passkeyOptions?: string;
        passkeySubmit?: string;
    };
};

const open = defineModel<boolean>('open', { default: false });

const toast = useToast();

const props = withDefaults(defineProps<{
    methods: IdentityMethod[];
    selectedMethod?: string | null;
    maskedEmail?: string;
    config: ConfirmIdentityModalConfig;
}>(), {
    selectedMethod: null,
    maskedEmail: '',
});

const emit = defineEmits<{
    confirmed: [];
}>();

const form = useForm({
    password: '',
    method: props.selectedMethod,
    code: '',
    passkey: ''
});

const routes = computed(() => ({
    confirm: props.config.routes?.confirm ?? '/user/confirm-identity',
    method: props.config.routes?.method ?? '/user/confirm-identity/method',
    emailCode: props.config.routes?.emailCode ?? '/user/confirm-identity/email-code',
    passkeyOptions: props.config.routes?.passkeyOptions ?? '/user/confirm-identity/passkey/options',
    passkeySubmit:  props.config.routes?.passkeySubmit ?? '/user/confirm-identity/passkey/verify'
}));

const availableMethods = computed(() => {
    return (props.methods ?? []).map((method) => {
        if (typeof method === 'string') {
            return {
                label: props.config.methods?.[method] ?? method,
                value: method,
            };
        }

        return method;
    });
});
const hasMethods = computed(() => availableMethods.value.length > 0);
const hasMethodSwitcher = computed(() => availableMethods.value.length > 1);
const isPasskey = computed(() => form.method === 'passkey');
const requiresOtp = computed(() => ['email', 'one_time_code'].includes(String(form.method)));
const requiresPassword = computed(() => !isPasskey.value);

const defaultMethod = computed(() => {
    const methods = availableMethods.value;

    if (
        props.selectedMethod
        && methods.some((method) => method.value === props.selectedMethod)
    ) {
        return props.selectedMethod;
    }

    return methods[0]?.value ?? null;
});
const canSubmit = computed(() => {
    if (form.processing) return false;
    
    if (requiresPassword.value && !form.password) return false;
    
    if (!hasMethods.value) return true;
    
    if (isPasskey.value) return false;
    
    if (requiresOtp.value) {
        return Array.isArray(form.code)
            ? form.code.join('').length === 6
            : String(form.code ?? '').length === 6;
    }
    
    return true;
});

watch(
    () => open.value,
    (open) => {
        if (!open) {
            form.reset();
            form.clearErrors();
            return;
        }

        form.method = defaultMethod.value;
        form.password = '';
        form.code = '';
        form.clearErrors();
        
        if (form.method === 'email') sendEmailCode();
    }
);

function closeModal() {
    if (form.processing) return;
    
    open.value = false;
}

function submit() {
    form.post(routes.value.confirm, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            emit('confirmed');
            open.value = false;
        },
        onError: () => {
            form.reset('code');
        },
    });
}

function showToastError(errors: Record<string, any>) {
    if (!errors?.toast) return;

    try {
        const error = JSON.parse(errors.toast);

        toast.add({
            title: error.title,
            description: error.description,
            color: 'error',
        });
    } catch {
        toast.add({
            title: errors.toast,
            color: 'error',
        });
    }
}

function changeMethod() {
    form.code = '';
    form.clearErrors();
    
    router.post(routes.value.method, {
        method: form.method,
    }, {
        preserveScroll: true,
        preserveState: true,
        onError: (errors)=> {
            showToastError(errors);

            if (errors.method) {
                form.setError('method', errors.method);
            }

            form.method = defaultMethod.value;
        }
    });
}

function sendEmailCode() {
    router.post(routes.value.emailCode, {}, {
        preserveScroll: true,
        preserveState: true,
    });
}
function resendEmailCode() {
    sendEmailCode();
}

async function verifyPasskey() {
    form.clearErrors();
    
    try {
        await Passkeys.verify({
            routes: {
                options: routes.value.passkeyOptions,
                submit: routes.value.passkeySubmit,
            },
        });
        
        emit('confirmed');
        open.value = false;
    } catch (error: any) {
        form.setError('passkey', error?.message ?? props.config.errors.invalidPasskey);
    }
}
</script>

<template>
    <UModal
        v-model:open="open"
        @update:open="(value: any) => value ? open = true : closeModal()"
    >
        <template #header>
            <h2 class="text-lg font-semibold">
                {{ config.title }}
            </h2>
        </template>
        
        <template #body>
            <div class="flex flex-col gap-4">
                <slot name="before-form" />
                
                <form class="flex flex-col gap-4" @submit.prevent="submit">
                    <p v-if="config.subtitle" class="text-sm text-muted">
                        {{ config.subtitle }}
                    </p>
                    
                    <UFormField
                        v-if="requiresPassword"
                        :label="config.fields.password"
                        required
                        :error="form.errors.password"
                    >
                        <UInput 
                            v-model="form.password"
                            type="password"
                            auto-complete=" current-password"
                            class="w-full"
                        />
                    </UFormField>
                    
                    <UFormField
                        v-if="hasMethodSwitcher"
                        :label="config.fields.method"
                        required
                        :error="form.errors.method"
                    >
                        <USelect
                            v-model="form.method"
                            :items="availableMethods"
                            label-key="label"
                            value-key="value"
                            class="w-full"
                            @update:model-value="changeMethod"
                        />
                    </UFormField>
                    
                    <UAlert
                        v-if="form.method === 'email'"
                        color="info"
                        variant="soft"
                        icon="i-lucide-mail"
                        :title="`${config.emailSentTo} ${maskedEmail}`"
                    />
                    
                    <div v-if="requiresOtp" class="flex flex-col gap-3">
                        <UFormField
                            :label="config.fields.code"
                            required
                            :error="form.errors.code"
                        >
                            <UPinInput
                                v-model="form.code"
                                otp
                                :length="6"
                                class="flex items-center justify-center"
                            />
                        </UFormField>
                    </div>
                    
                    <p v-if="form.errors.passkey" class="text-sm text-error">
                        {{ form.errors.passkey }}
                    </p>
                </form>
            </div>
        </template>
        
        <template #footer>
            <div class="flex justify-end gap-2 w-full">
                <UButton 
                    color="neutral"
                    variant="ghost"
                    :label="config.actions.cancel"
                    type="button"
                    :disabled="form.processing"
                    @click="closeModal"
                />
                
                <UButton 
                    v-if="requiresOtp || !hasMethods"
                    type="button"
                    :label="config.actions.confirmButton"
                    :loading="form.processing"
                    :disabled="!canSubmit"
                    @click="submit"
                />

                <UButton
                    v-else-if="isPasskey"
                    type="button"
                    :label="config.actions.passkeyButton"
                    :loading="form.processing"
                    :disabled="form.processing"
                    icon="i-lucide-key-round"
                    @click="verifyPasskey"
                />
                
                <UButton 
                    v-if="form.method === 'email'"
                    type="button"
                    color="neutral"
                    :label="config.actions.resendEmailCode"
                    @click="resendEmailCode"
                />
            </div>
        </template>
    </UModal>
</template>

<style scoped>

</style>
