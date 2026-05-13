<script setup>
import { computed, ref, watch } from "vue";
import Button from "primevue/button";
import Dialog from "primevue/dialog";
import InputOtp from "primevue/inputotp";
import Select from "primevue/select";
import PasswordInput from "@/Components/Forms/PasswordInput.vue";

const props = defineProps({
    visible: { type: Boolean, default: false },
    hasPrimarySecondFactor: { type: Boolean, default: false },
    primaryMethod: { type: String, default: null },
    methodOptions: { type: Array, default: () => [] },
});

const emit = defineEmits(["update:visible", "confirmed"]);

const passwordInput = ref(null);

const form = ref({
    current_password: "",
    two_factor_method: null,
    two_factor_code: "",
});

const selectedMethodRequiresCode = computed(() => {
    return props.hasPrimarySecondFactor && ["email", "one_time_code"].includes(form.value.two_factor_method);
});

const canConfirm = computed(() => {
    if (!form.value.current_password) {
        return false;
    }

    if (selectedMethodRequiresCode.value && form.value.two_factor_code.length !== 6) {
        return false;
    }

    return true;
});

watch(
    () => props.visible,
    (visible) => {
        if (!visible) {
            resetForm();
            return;
        }

        form.value.two_factor_method = props.primaryMethod && props.methodOptions.some((method) => method.value === props.primaryMethod)
            ? props.primaryMethod
            : props.methodOptions[0]?.value ?? null;
    }
);

function close() {
    emit("update:visible", false);
}

function resetForm() {
    form.value = {
        current_password: "",
        two_factor_method: null,
        two_factor_code: "",
    };
}

function confirm() {
    if (!canConfirm.value) {
        return;
    }

    emit("confirmed", {
        current_password: form.value.current_password,
        two_factor_method: form.value.two_factor_method,
        two_factor_code: form.value.two_factor_code,
    });

    resetForm();
}

function trackPasswordEye(event) {
    passwordInput.value?.trackPoint(event);
}

function resetPasswordEye() {
    passwordInput.value?.reset();
}
</script>

<template>
    <Dialog
        :visible="visible"
        modal
        header="Confirmation de sécurité"
        class="w-[min(460px,95vw)]"
        @update:visible="emit('update:visible', $event)"
    >
        <form
            class="space-y-5"
            @submit.prevent="confirm"
            @mousemove="trackPasswordEye"
            @mouseleave="resetPasswordEye"
        >
            <p class="text-sm text-slate-400">
                Confirmez votre identité avant d’exécuter cette action sensible.
            </p>

            <PasswordInput
                ref="passwordInput"
                v-model="form.current_password"
                name="security_current_password"
                label="Mot de passe actuel"
                autocomplete="current-password"
                required
            />

            <div v-if="hasPrimarySecondFactor && methodOptions.length > 0" class="profile-field">
                <label for="security_two_factor_method">Méthode de vérification</label>
                <Select
                    id="security_two_factor_method"
                    v-model="form.two_factor_method"
                    :options="methodOptions"
                    option-label="label"
                    option-value="value"
                    class="w-full"
                />
            </div>

            <div v-if="selectedMethodRequiresCode" class="flex flex-col items-center gap-3">
                <label class="text-sm text-slate-400">
                    Code de vérification
                </label>

                <InputOtp
                    v-model="form.two_factor_code"
                    :length="6"
                    integer-only
                />
            </div>

            <div class="flex justify-end gap-2 border-t border-slate-800 pt-4">
                <Button type="button" label="Annuler" severity="secondary" outlined @click="close" />
                <Button type="submit" label="Confirmer" :disabled="!canConfirm" />
            </div>
        </form>
    </Dialog>
</template>

<style scoped>
.profile-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.profile-field label {
    font-size: 12px;
    font-weight: 600;
    color: rgb(148 163 184);
}
</style>
