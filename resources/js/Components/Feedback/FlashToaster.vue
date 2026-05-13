<script setup>
import { watch } from "vue";
import { usePage } from "@inertiajs/vue3";
import { useToast } from "primevue/usetoast";
import Toast from "primevue/toast";

const page = usePage();
const toast = useToast();

let lastFlashKey = null;

const severities = {
    success: {
        severity: "success",
        summary: "Succès",
        life: 4500,
    },
    error: {
        severity: "error",
        summary: "Erreur",
        life: 6000,
    },
    info: {
        severity: "info",
        summary: "Information",
        life: 4500,
    },
    warning: {
        severity: "warn",
        summary: "Attention",
        life: 5500,
    },
};

function normalizeMessage(message) {
    if (!message) {
        return null;
    }

    if (typeof message === "string") {
        return message;
    }

    if (message.message) {
        return message.message;
    }

    return String(message);
}

function showFlash(flash) {
    if (!flash) {
        return;
    }

    Object.entries(severities).forEach(([key, options]) => {
        const detail = normalizeMessage(flash[key]);

        if (!detail) {
            return;
        }

        const flashKey = `${key}:${detail}`;

        if (flashKey === lastFlashKey) {
            return;
        }

        lastFlashKey = flashKey;

        toast.add({
            severity: options.severity,
            summary: options.summary,
            detail,
            life: options.life,
        });
    });
}

watch(
    () => page.props.flash,
    (flash) => showFlash(flash),
    { deep: true, immediate: true }
);
</script>

<template>
    <Toast position="top-right" />
</template>
