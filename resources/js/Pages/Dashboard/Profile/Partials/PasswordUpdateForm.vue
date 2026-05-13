<script setup>
import { ref } from "vue";
import Button from "primevue/button";
import PasswordInput from "@/Components/Forms/PasswordInput.vue";
import ProfilePanel from "./ProfilePanel.vue";

defineProps({
    form: { type: Object, required: true },
});

defineEmits(["submit"]);

const currentPasswordInput = ref(null);
const newPasswordInput = ref(null);
const confirmationPasswordInput = ref(null);

function trackPasswordEyes(event) {
    currentPasswordInput.value?.trackPoint(event);
    newPasswordInput.value?.trackPoint(event);
    confirmationPasswordInput.value?.trackPoint(event);
}

function resetPasswordEyes() {
    currentPasswordInput.value?.reset();
    newPasswordInput.value?.reset();
    confirmationPasswordInput.value?.reset();
}
</script>

<template>
    <form
        @submit.prevent="$emit('submit')"
        @mousemove="trackPasswordEyes"
        @mouseleave="resetPasswordEyes"
    >
        <ProfilePanel title="Changer le mot de passe">
            <div class="grid gap-4 p-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <PasswordInput
                        ref="currentPasswordInput"
                        v-model="form.current_password"
                        name="current_password"
                        label="Mot de passe actuel"
                        autocomplete="current-password"
                        :error="form.errors.current_password"
                        required
                    />
                </div>

                <PasswordInput
                    ref="newPasswordInput"
                    v-model="form.password"
                    name="password"
                    label="Nouveau mot de passe"
                    autocomplete="new-password"
                    :error="form.errors.password"
                    required
                />

                <PasswordInput
                    ref="confirmationPasswordInput"
                    v-model="form.password_confirmation"
                    name="password_confirmation"
                    label="Confirmation"
                    autocomplete="new-password"
                    :error="form.errors.password_confirmation"
                    required
                />
            </div>

            <div class="flex justify-end border-t border-slate-800 p-4">
                <Button label="Mettre à jour le mot de passe" type="submit" :loading="form.processing" />
            </div>
        </ProfilePanel>
    </form>
</template>
