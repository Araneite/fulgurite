<script setup>
import Button from "primevue/button";
import Select from "primevue/select";
import TextInput from "@/Components/Forms/TextInput.vue";
import ProfilePanel from "./ProfilePanel.vue";

defineProps({
    form: { type: Object, required: true },
    profile: { type: Object, required: true },
    localeOptions: { type: Array, required: true },
    startPageOptions: { type: Array, required: true },
});

defineEmits(["submit"]);
</script>

<template>
    <form @submit.prevent="$emit('submit')">
        <ProfilePanel title="Profil utilisateur">
            <div class="grid gap-4 p-4 md:grid-cols-2">
                <TextInput
                    v-model="form.first_name"
                    name="first_name"
                    label="Prénom"
                    :error="form.errors.first_name"
                />

                <TextInput
                    v-model="form.last_name"
                    name="last_name"
                    label="Nom"
                    :error="form.errors.last_name"
                />

                <TextInput
                    :model-value="profile.email"
                    name="email"
                    label="Email"
                    readonly
                    selectable
                />

                <TextInput
                    v-model="form.phone"
                    name="phone"
                    label="Téléphone"
                    :error="form.errors.phone"
                />

                <TextInput
                    v-model="form.job_title"
                    name="job_title"
                    label="Fonction"
                    :error="form.errors.job_title"
                />

                <div class="profile-field">
                    <label for="preferred_locale">Langue</label>
                    <Select
                        id="preferred_locale"
                        v-model="form.preferred_locale"
                        :options="localeOptions"
                        option-label="label"
                        option-value="value"
                    />
                    <small v-if="form.errors.preferred_locale">{{ form.errors.preferred_locale }}</small>
                </div>

                <div class="profile-field">
                    <label for="preferred_start_page">Page d’accueil</label>
                    <Select
                        id="preferred_start_page"
                        v-model="form.preferred_start_page"
                        :options="startPageOptions"
                        option-label="label"
                        option-value="value"
                    />
                    <small v-if="form.errors.preferred_start_page">{{ form.errors.preferred_start_page }}</small>
                </div>

                <div class="profile-field">
                    <label for="theme">Thème</label>
                    <Select
                        id="theme"
                        :model-value="'dark'"
                        :options="[{ label: 'Sombre', value: 'dark' }]"
                        option-label="label"
                        option-value="value"
                        disabled
                    />
                </div>

                <div class="md:col-span-2">
                    <TextInput
                        v-model="form.preferred_timezone"
                        name="preferred_timezone"
                        label="Fuseau horaire"
                        :error="form.errors.preferred_timezone"
                    />
                    <p class="mt-2 text-xs text-slate-500">
                        Laissez vide pour utiliser le fuseau global de l’application.
                    </p>
                </div>
            </div>

            <div class="flex justify-end border-t border-slate-800 p-4">
                <Button label="Enregistrer le profil" type="submit" :loading="form.processing" />
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
