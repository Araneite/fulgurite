<script setup>
import { computed, ref } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import Webpass from "@laragear/webpass";
import Button from "primevue/button";
import Dialog from "primevue/dialog";
import InputOtp from "primevue/inputotp";
import Message from "primevue/message";
import { useToast } from "primevue/usetoast";

import ProfilePanel from "./Partials/ProfilePanel.vue";
import ProfileIdentityForm from "./Partials/ProfileIdentityForm.vue";
import PasswordUpdateForm from "./Partials/PasswordUpdateForm.vue";
import TwoFactorSettings from "./Partials/TwoFactorSettings.vue";
import SecurityKeysPanel from "./Partials/SecurityKeysPanel.vue";
import AccountSummary from "./Partials/AccountSummary.vue";
import SecurityConfirmationModal from "./Partials/SecurityConfirmationModal.vue";

defineOptions({
    layout: DashboardLayout,
});

const toast = useToast();

const props = defineProps({
    profile: { type: Object, required: true },
    securityKeys: { type: Array, required: true },
    methodOptions: { type: Array, required: true },
    localeOptions: { type: Array, required: true },
    startPageOptions: { type: Array, required: true },
});

const securityKeyName = ref("");
const securityKeyError = ref(null);
const securityKeyProcessing = ref(false);

const totpDialogVisible = ref(false);
const totpQrCode = ref(null);
const totpSecret = ref(null);
const totpSetupError = ref(null);
const totpSetupProcessing = ref(false);

const pendingSensitiveAction = ref(null);
const confirmationModalVisible = ref(false);

const profileForm = useForm({
    first_name: props.profile.first_name ?? "",
    last_name: props.profile.last_name ?? "",
    phone: props.profile.phone ?? "",
    phone_extension: props.profile.phone_extension ?? "",
    job_title: props.profile.job_title ?? "",
    preferred_locale: props.profile.preferred_locale ?? "fr_FR",
    preferred_timezone: props.profile.preferred_timezone ?? "",
    preferred_start_page: props.profile.preferred_start_page ?? "dashboard",
});

const passwordForm = useForm({
    current_password: "",
    password: "",
    password_confirmation: "",
    confirmation: null,
});

const twoFactorForm = useForm({
    second_factor_methods: props.profile.second_factor_methods ?? [],
    primary_second_factor: props.profile.primary_second_factor ?? null,
    confirmation: null,
});

const totpConfirmForm = useForm({
    code: "",
    confirmation: null,
});

const enabledSecurityKeys = computed(() => props.securityKeys.filter((key) => key.enabled));

const configuredMethodValues = computed(() => {
    const values = new Set();

    if (props.profile.email) {
        values.add("email");
    }

    if (props.profile.totp_enabled) {
        values.add("one_time_code");
    }

    if (enabledSecurityKeys.value.length > 0) {
        values.add("passkey");
    }

    return values;
});

const configuredMethodOptions = computed(() => {
    return props.methodOptions
        .filter((method) => configuredMethodValues.value.has(method.value))
        .map((method) => ({
            ...method,
            configured: true,
        }));
});

const methodStates = computed(() => {
    return props.methodOptions.map((method) => ({
        ...method,
        configured: configuredMethodValues.value.has(method.value),
        active: (twoFactorForm.second_factor_methods ?? []).includes(method.value),
    }));
});

const primaryOptions = computed(() => {
    return configuredMethodOptions.value.filter((method) => {
        return twoFactorForm.second_factor_methods.includes(method.value);
    });
});

const primaryMethodLabel = computed(() => {
    return props.methodOptions.find((method) => method.value === props.profile.primary_second_factor)?.label ?? "Aucun";
});

const hasPrimarySecondFactor = computed(() => {
    return Boolean(props.profile.primary_second_factor && props.profile.primary_second_factor !== "0");
});

const securityConfirmationMethods = computed(() => {
    return configuredMethodOptions.value.filter((method) => {
        return ["email", "one_time_code"].includes(method.value);
    });
});

const statusSeverity = computed(() => props.profile.active ? "success" : "danger");

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? "";
}

function updateProfile() {
    profileForm.put("/profile", {
        preserveScroll: true,
    });
}

function requireSecurityConfirmation(action) {
    pendingSensitiveAction.value = action;
    confirmationModalVisible.value = true;
}

function runSensitiveAction(confirmation) {
    confirmationModalVisible.value = false;

    if (!pendingSensitiveAction.value) {
        return;
    }

    pendingSensitiveAction.value(confirmation);
    pendingSensitiveAction.value = null;
}

function updatePassword() {
    requireSecurityConfirmation((confirmation) => {
        passwordForm.confirmation = confirmation;

        passwordForm.put("/profile/password", {
            preserveScroll: true,
            onSuccess: () => {
                passwordForm.reset();
            },
        });
    });
}

function normalizeTwoFactorSelection() {
    twoFactorForm.second_factor_methods = twoFactorForm.second_factor_methods.filter((method) => {
        return configuredMethodValues.value.has(method);
    });

    if (!twoFactorForm.second_factor_methods.includes(twoFactorForm.primary_second_factor)) {
        twoFactorForm.primary_second_factor = twoFactorForm.second_factor_methods[0] ?? null;
    }
}

function updateTwoFactor() {
    normalizeTwoFactorSelection();

    requireSecurityConfirmation((confirmation) => {
        twoFactorForm.confirmation = confirmation;

        twoFactorForm.put("/profile/two-factor", {
            preserveScroll: true,
        });
    });
}

async function startTotpSetup() {
    requireSecurityConfirmation(async (confirmation) => {
        totpSetupError.value = null;
        totpSetupProcessing.value = true;

        try {
            const response = await fetch("/profile/totp/setup", {
                method: "POST",
                headers: {
                    "Accept": "application/json",
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken(),
                    "X-Requested-With": "XMLHttpRequest",
                },
                body: JSON.stringify({ confirmation }),
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                totpSetupError.value = data.message ?? "Impossible de démarrer la configuration A2F.";
                return;
            }

            totpQrCode.value = data.qr_code_svg;
            totpSecret.value = data.secret;
            totpConfirmForm.reset();
            totpConfirmForm.clearErrors();
            totpDialogVisible.value = true;
        } finally {
            totpSetupProcessing.value = false;
        }
    });
}

function confirmTotpSetup() {
    requireSecurityConfirmation((confirmation) => {
        totpConfirmForm.confirmation = confirmation;

        totpConfirmForm.post("/profile/totp/confirm", {
            preserveScroll: true,
            onSuccess: () => {
                totpDialogVisible.value = false;
                totpQrCode.value = null;
                totpSecret.value = null;
                router.reload({ only: ["profile", "methodOptions"] });
            },
        });
    });
}

function disableTotp() {
    requireSecurityConfirmation((confirmation) => {
        router.delete("/profile/totp", {
            data: { confirmation },
            preserveScroll: true,
            onSuccess: () => router.reload({ only: ["profile", "methodOptions"] }),
        });
    });
}

async function registerSecurityKey() {
    requireSecurityConfirmation(async (confirmation) => {
        securityKeyError.value = null;
        securityKeyProcessing.value = true;

        try {
            if (Webpass.isUnsupported()) {
                securityKeyError.value = "Ce navigateur ne supporte pas les clés de sécurité.";
                return;
            }

            const result = await Webpass.attest(
                {
                    path: "/profile/security-keys/options",
                    body: { confirmation },
                },
                {
                    path: "/profile/security-keys",
                    body: {
                        alias: securityKeyName.value?.trim() || null,
                        confirmation,
                    },
                }
            );

            if (!result.success) {
                const message = result.error ?? "La clé de sécurité n’a pas pu être ajoutée.";
                securityKeyError.value = message;

                toast.add({
                    severity: "error",
                    summary: "Erreur",
                    detail: message,
                    life: 6000,
                });

                return;
            }

            securityKeyName.value = "";

            toast.add({
                severity: "success",
                summary: "Succès",
                detail: result.data?.message ?? "Clé de sécurité ajoutée.",
                life: 4500,
            });

            router.reload({ only: ["securityKeys", "profile"] });
        } finally {
            securityKeyProcessing.value = false;
        }
    });
}

function deleteSecurityKey(id) {
    requireSecurityConfirmation((confirmation) => {
        router.delete(`/profile/security-keys/${id}`, {
            data: { confirmation },
            preserveScroll: true,
            onSuccess: () => router.reload({ only: ["securityKeys", "profile"] }),
        });
    });
}
</script>

<template>
    <div class="min-h-full w-full bg-[#0b1016] text-slate-200">

        <main class="grid gap-4 p-6 xl:grid-cols-[minmax(0,1fr)_420px]">
            <section class="space-y-4">
                <ProfileIdentityForm
                    :form="profileForm"
                    :profile="profile"
                    :locale-options="localeOptions"
                    :start-page-options="startPageOptions"
                    @submit="updateProfile"
                />

                <PasswordUpdateForm
                    :form="passwordForm"
                    @submit="updatePassword"
                />

                <TwoFactorSettings
                    :form="twoFactorForm"
                    :profile="profile"
                    :method-states="methodStates"
                    :configured-method-options="configuredMethodOptions"
                    :primary-options="primaryOptions"
                    :totp-setup-processing="totpSetupProcessing"
                    @submit="updateTwoFactor"
                    @normalize="normalizeTwoFactorSelection"
                    @start-totp="startTotpSetup"
                    @disable-totp="disableTotp"
                />

                <SecurityKeysPanel
                    v-model:name="securityKeyName"
                    :security-keys="securityKeys"
                    :error="securityKeyError"
                    :processing="securityKeyProcessing"
                    @register="registerSecurityKey"
                    @delete="deleteSecurityKey"
                />
            </section>

            <aside class="space-y-4">
                <AccountSummary
                    :profile="profile"
                    :primary-method-label="primaryMethodLabel"
                    :status-severity="statusSeverity"
                />

                <ProfilePanel title="Sessions actives" badge="1">
                    <div class="p-4">
                        <table class="w-full text-left text-xs">
                            <thead class="text-slate-500">
                            <tr>
                                <th class="py-2">IP</th>
                                <th class="py-2">Connexion</th>
                                <th class="py-2">Activité</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr class="border-t border-slate-800">
                                <td class="py-3 font-semibold text-slate-300">Session actuelle</td>
                                <td class="py-3 text-slate-500">{{ profile.last_login ?? "-" }}</td>
                                <td class="py-3 text-slate-500">Maintenant</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </ProfilePanel>

                <ProfilePanel title="Activité récente">
                    <div class="divide-y divide-slate-800 text-sm">
                        <div class="grid grid-cols-[120px_1fr] gap-4 p-4">
                            <span class="text-slate-500">{{ profile.last_login ?? "-" }}</span>
                            <span class="text-slate-300">Connexion réussie</span>
                        </div>
                    </div>
                </ProfilePanel>
            </aside>
        </main>

        <Dialog
            v-model:visible="totpDialogVisible"
            modal
            header="Configurer l’authentification par code"
            class="w-[min(520px,95vw)]"
        >
            <div class="flex flex-col gap-5">
                <Message v-if="totpSetupError" severity="error" :closable="false">
                    {{ totpSetupError }}
                </Message>

                <div class="flex justify-center rounded-md bg-white p-4" v-html="totpQrCode"></div>

                <div class="rounded-md border border-slate-800 bg-[#0b1016] p-3">
                    <p class="text-xs font-semibold uppercase text-slate-500">Secret</p>
                    <p class="mt-1 break-all font-mono text-sm text-slate-200">{{ totpSecret }}</p>
                </div>

                <form class="flex flex-col items-center gap-4" @submit.prevent="confirmTotpSetup">
                    <label class="text-sm text-slate-400">
                        Entrez le code généré par votre application
                    </label>

                    <InputOtp
                        v-model="totpConfirmForm.code"
                        :length="6"
                        integer-only
                        autofocus
                    />

                    <p v-if="totpConfirmForm.errors.code" class="text-sm text-red-400">
                        {{ totpConfirmForm.errors.code }}
                    </p>

                    <Button
                        type="submit"
                        label="Activer"
                        :loading="totpConfirmForm.processing"
                        class="w-full"
                    />
                </form>
            </div>
        </Dialog>

        <SecurityConfirmationModal
            v-model:visible="confirmationModalVisible"
            :has-primary-second-factor="hasPrimarySecondFactor"
            :primary-method="profile.primary_second_factor"
            :method-options="securityConfirmationMethods"
            @confirmed="runSensitiveAction"
        />
    </div>
</template>
