<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import Webpass from "@laragear/webpass";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import Message from "primevue/message";

const props = defineProps({
    credentials: {
        type: Array,
        required: true,
    },
});

const alias = ref("");
const error = ref(null);
const processing = ref(false);

async function registerSecurityKey() {
    error.value = null;
    processing.value = true;

    try {
        if (Webpass.isUnsupported()) {
            error.value = "Ce navigateur ne supporte pas les clés de sécurité.";
            return;
        }

        const result = await Webpass.attest(
            "/security-keys/options",
            "/security-keys",
            { alias: alias.value }
        );

        if (!result.success) {
            error.value = result.error ?? "La clé de sécurité n’a pas pu être ajoutée.";
            return;
        }

        alias.value = "";
        router.reload({ only: ["credentials"] });
    } finally {
        processing.value = false;
    }
}

function deleteSecurityKey(id) {
    router.delete(`/security-keys/${id}`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <section class="flex w-full max-w-3xl flex-col gap-6">
        <div class="flex flex-col gap-2">
            <h1 class="text-xl font-semibold text-white">
                Clés de sécurité
            </h1>

            <p class="text-sm text-text-400">
                Gérez les clés d’accès, passkeys et clés physiques utilisées comme second facteur.
            </p>
        </div>

        <Message v-if="error" severity="error" :closable="false">
            {{ error }}
        </Message>

        <div class="flex gap-3">
            <InputText
                v-model="alias"
                class="w-full"
                placeholder="Nom de la clé"
            />

            <Button
                label="Ajouter"
                :loading="processing"
                @click="registerSecurityKey"
            />
        </div>

        <div class="flex flex-col gap-3">
            <div
                v-for="credential in credentials"
                :key="credential.id"
                class="flex items-center justify-between rounded-md border border-white/10 bg-white/5 p-4"
            >
                <div>
                    <p class="font-medium text-white">
                        {{ credential.alias ?? "Clé de sécurité" }}
                    </p>

                    <p class="text-sm text-text-400">
                        {{ credential.origin }} · {{ credential.created_at }}
                    </p>
                </div>

                <Button
                    severity="danger"
                    variant="text"
                    label="Supprimer"
                    @click="deleteSecurityKey(credential.id)"
                />
            </div>

            <Message v-if="credentials.length === 0" severity="secondary" :closable="false">
                Aucune clé de sécurité enregistrée.
            </Message>
        </div>
    </section>
</template>
