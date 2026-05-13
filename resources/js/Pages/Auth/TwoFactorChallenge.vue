<script setup>
import { computed } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import AuthLayout from "@/Layouts/AuthLayout.vue";
import Webpass from "@laragear/webpass";
import Button from "primevue/button";
import InputOtp from "primevue/inputotp";
import Message from "primevue/message";
import Select from "primevue/select";

defineOptions({
    layout: AuthLayout,
});

const props = defineProps({
    methods: {
        type: Array,
        required: true,
    },
    selectedMethod: {
        type: String,
        required: true,
    },
    maskedEmail: {
        type: String,
        required: true,
    },
    trans: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    method: props.selectedMethod,
    code: "",
});

const availableMethods = computed(() => props.methods ?? []);
const hasMethodSwitcher = computed(() => availableMethods.value.length > 1);
const requiresOtp = computed(() => ["email", "one_time_code"].includes(form.method));

const selectedMethodLabel = computed(() => {
    return availableMethods.value.find((method) => method.value === form.method)?.label ?? "";
});

function submit() {
    form.post("/a2f", {
        preserveScroll: true,
        onError: () => {
            form.reset("code");
        },
    });
}

function changeMethod() {
    form.code = "";
    form.clearErrors();

    router.post("/a2f/method", {
        method: form.method,
    }, {
        preserveScroll: true,
        preserveState: true,
    });
}

function resendEmailCode() {
    router.post("/a2f/email-code", {}, {
        preserveScroll: true,
        preserveState: true,
    });
}

async function verifyPasskey() {
    form.clearErrors();

    if (Webpass.isUnsupported()) {
        form.setError("passkey", "Ce navigateur ne supporte pas les clés d’accès.");
        return;
    }

    const result = await Webpass.assert(
        "/a2f/passkey/options",
        "/a2f/passkey/verify"
    );

    if (!result.success) {
        form.setError("passkey", result.error ?? trans.errors.invalid_passkey);
        return;
    }

    window.location.href = result.redirect ?? "/";
}
</script>

<template>
    <form class="box classic-form" @submit.prevent="submit">
        <div class="form-header">
            <div class="logo">
                <img :src="'/assets/img/fulgurite-logo.svg'" alt="Fulgurite" class="logo-icon">
                <h2>Fulgurite</h2>
            </div>

            <p class="mt-3 text-center text-sm text-text-400">
                {{ trans.subtitle }}
            </p>
        </div>

        <div v-if="hasMethodSwitcher" class="flex w-full flex-col gap-2">
            <label for="two_factor_method" class="text-sm text-text-300">
                {{ trans.fields.method }}
            </label>

            <Select
                id="two_factor_method"
                v-model="form.method"
                :options="availableMethods"
                option-label="label"
                option-value="value"
                class="w-full"
                @change="changeMethod"
            />
        </div>

        <Message v-if="form.method === 'email'" severity="info" :closable="false">
            {{ trans.email_sent_to }} {{ maskedEmail }}
        </Message>

        <div v-if="requiresOtp" class="flex flex-col items-center gap-3">
            <label for="two_factor_code" class="text-sm text-text-300">
                {{ selectedMethodLabel }}
            </label>

            <InputOtp
                id="two_factor_code"
                v-model="form.code"
                :length="6"
                integer-only
                autofocus
            />

            <p v-if="form.errors.code" class="text-sm text-red-400">
                {{ form.errors.code }}
            </p>
        </div>

        <p v-if="form.errors.passkey" class="text-sm text-red-400">
            {{ form.errors.passkey }}
        </p>

        <Button
            v-if="requiresOtp"
            type="submit"
            :label="trans.confirm_button"
            :loading="form.processing"
            class="w-full"
        />

        <Button
            v-else
            type="button"
            :label="trans.passkey_button"
            :loading="form.processing"
            class="w-full"
            @click="verifyPasskey"
        />

        <Button
            v-if="form.method === 'email'"
            type="button"
            severity="secondary"
            variant="text"
            :label="trans.resend_email_code"
            class="w-full"
            @click="resendEmailCode"
        />
    </form>
</template>

<style scoped>
</style>
