<script setup>
import Button from "primevue/button";
import Message from "primevue/message";
import MultiSelect from "primevue/multiselect";
import Select from "primevue/select";
import Tag from "primevue/tag";
import ProfilePanel from "./ProfilePanel.vue";

defineProps({
    form: { type: Object, required: true },
    profile: { type: Object, required: true },
    methodStates: { type: Array, required: true },
    configuredMethodOptions: { type: Array, required: true },
    primaryOptions: { type: Array, required: true },
    totpSetupProcessing: { type: Boolean, default: false },
});

defineEmits(["submit", "normalize", "start-totp", "disable-totp"]);
</script>

<template>
    <form @submit.prevent="$emit('submit')">
        <ProfilePanel title="Authentification à double facteur">
            <div class="space-y-5 p-4">
                <Message severity="info" :closable="false">
                    Seules les méthodes déjà configurées peuvent être autorisées pour la connexion.
                </Message>

                <div class="grid gap-3 md:grid-cols-3">
                    <div
                        v-for="method in methodStates"
                        :key="method.value"
                        class="rounded-lg border p-4"
                        :class="method.configured ? 'border-slate-700 bg-[#0f151d]' : 'border-slate-800 bg-[#0b1016] opacity-70'"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-100">{{ method.label }}</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ method.configured ? "Configurée sur ce compte" : "Non configurée" }}
                                </p>
                            </div>

                            <Tag
                                :value="method.active ? 'Autorisée' : (method.configured ? 'Disponible' : 'Indisponible')"
                                :severity="method.active ? 'success' : (method.configured ? 'info' : 'secondary')"
                            />
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Button
                        v-if="!profile.totp_enabled"
                        type="button"
                        label="Configurer le code à usage unique"
                        :loading="totpSetupProcessing"
                        @click="$emit('start-totp')"
                    />

                    <Button
                        v-else
                        type="button"
                        label="Désactiver le code à usage unique"
                        severity="danger"
                        outlined
                        @click="$emit('disable-totp')"
                    />
                </div>

                <div class="profile-field">
                    <label for="second_factor_methods">Méthodes autorisées</label>
                    <MultiSelect
                        id="second_factor_methods"
                        v-model="form.second_factor_methods"
                        :options="configuredMethodOptions"
                        option-label="label"
                        option-value="value"
                        display="chip"
                        class="w-full"
                        placeholder="Sélectionner les méthodes configurées"
                        @change="$emit('normalize')"
                    />
                    <small v-if="form.errors.second_factor_methods">{{ form.errors.second_factor_methods }}</small>
                </div>

                <div class="profile-field">
                    <label for="primary_second_factor">Méthode principale</label>
                    <Select
                        id="primary_second_factor"
                        v-model="form.primary_second_factor"
                        :options="primaryOptions"
                        option-label="label"
                        option-value="value"
                        placeholder="Aucun facteur principal"
                    />
                    <p class="text-xs text-slate-500">
                        Cette méthode sera proposée en premier après la saisie du mot de passe.
                    </p>
                    <small v-if="form.errors.primary_second_factor">{{ form.errors.primary_second_factor }}</small>
                </div>
            </div>

            <div class="flex justify-end border-t border-slate-800 p-4">
                <Button label="Enregistrer la sécurité" type="submit" :loading="form.processing" />
            </div>
        </ProfilePanel>
    </form>
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

.profile-field small {
    color: rgb(248 113 113);
}
</style>
