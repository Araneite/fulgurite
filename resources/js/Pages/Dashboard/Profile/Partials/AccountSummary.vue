<script setup>
import Tag from "primevue/tag";
import ProfilePanel from "./ProfilePanel.vue";

defineProps({
    profile: { type: Object, required: true },
    primaryMethodLabel: { type: String, required: true },
    statusSeverity: { type: String, required: true },
});
</script>

<template>
    <ProfilePanel title="Compte">
        <dl class="space-y-4 p-4 text-sm">
            <div>
                <dt class="text-xs text-slate-500">Utilisateur</dt>
                <dd class="mt-1 font-semibold text-slate-100">{{ profile.username }}</dd>
            </div>

            <div>
                <dt class="text-xs text-slate-500">Nom affiché</dt>
                <dd class="mt-1 text-slate-200">{{ profile.username }}</dd>
            </div>

            <div>
                <dt class="text-xs text-slate-500">Rôle</dt>
                <dd class="mt-1">
                    <Tag :value="profile.role" severity="info" />
                </dd>
            </div>

            <div>
                <dt class="text-xs text-slate-500">Statut</dt>
                <dd class="mt-1">
                    <Tag :value="profile.active ? 'Actif' : 'Inactif'" :severity="statusSeverity" />
                </dd>
            </div>

            <div>
                <dt class="text-xs text-slate-500">Dernière connexion</dt>
                <dd class="mt-1 text-slate-200">{{ profile.last_login ?? "Jamais" }}</dd>
            </div>

            <div>
                <dt class="text-xs text-slate-500">Facteur principal</dt>
                <dd class="mt-1 font-semibold text-slate-100">{{ primaryMethodLabel }}</dd>
            </div>
        </dl>
    </ProfilePanel>
</template>
