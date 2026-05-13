<script setup>
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import Message from "primevue/message";
import Tag from "primevue/tag";
import ProfilePanel from "./ProfilePanel.vue";

defineProps({
    securityKeys: { type: Array, required: true },
    error: { type: String, default: null },
    processing: { type: Boolean, default: false },
});

const name = defineModel("name", {
    type: String,
    default: "",
});

defineEmits(["register", "delete"]);
</script>

<template>
    <ProfilePanel title="Clés hardware WebAuthn" :badge="securityKeys.length">
        <div class="space-y-4 p-4">
            <p class="text-sm text-slate-500">
                Utilisez une clé USB, Touch ID ou Windows Hello comme second facteur résistant au phishing.
            </p>

            <Message v-if="error" severity="error" :closable="false">
                {{ error }}
            </Message>

            <div class="overflow-hidden rounded-md border border-slate-800">
                <table class="w-full text-left text-sm">
                    <thead class="bg-[#111820] text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Nom</th>
                        <th class="px-4 py-3">Ajoutée le</th>
                        <th class="px-4 py-3">Statut</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                    </thead>

                    <tbody>
                    <tr
                        v-for="credential in securityKeys"
                        :key="credential.id"
                        class="border-t border-slate-800"
                    >
                        <td class="px-4 py-3 font-medium text-slate-200">
                            {{ credential.alias ?? "Clé de sécurité" }}
                        </td>
                        <td class="px-4 py-3 text-slate-400">{{ credential.created_at }}</td>
                        <td class="px-4 py-3">
                            <Tag
                                :value="credential.enabled ? 'Active' : 'Désactivée'"
                                :severity="credential.enabled ? 'success' : 'danger'"
                            />
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Button
                                label="Supprimer"
                                severity="danger"
                                size="small"
                                outlined
                                @click="$emit('delete', credential.id)"
                            />
                        </td>
                    </tr>

                    <tr v-if="securityKeys.length === 0">
                        <td colspan="4" class="px-4 py-6 text-center text-slate-500">
                            Aucune clé enregistrée.
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row">
                <InputText v-model="name" placeholder="Nom de la clé" class="sm:w-64" />
                <Button label="Ajouter une clé" :loading="processing" @click="$emit('register')" />
            </div>

            <p class="text-xs text-slate-500">WebAuthn nécessite HTTPS ou localhost.</p>
        </div>
    </ProfilePanel>
</template>
