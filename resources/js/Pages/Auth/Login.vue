<script setup>
import { onMounted, ref } from "vue";
import { Link, useForm } from "@inertiajs/vue3";
import AuthLayout from "@/Layouts/AuthLayout.vue";
import TextInput from "@/Components/Forms/TextInput.vue";
import PasswordInput from "@/Components/Forms/PasswordInput.vue";
import CheckboxInput from "@/Components/Forms/CheckboxInput.vue";

defineOptions({
    layout: AuthLayout,
});

const props = defineProps({
    resetPasswordUrl: {
        type: String,
        required: true,
    },
    trans: {
        type: Object,
        required: true,
    },
});

const form = useForm("LoginForm", {
    login: "",
    password: "",
    remember: false,
}).dontRemember("password");

const isInitialLoading = ref(true);
const passwordInput = ref(null);

onMounted(() => {
    window.setTimeout(() => {
        isInitialLoading.value = false;
    }, 450);
});

function submit() {
    form.post("/login", {
        preserveScroll: true,
        onError: () => {
            form.reset("password");
        },
    });
}
</script>

<template>
    <div class="relative">
        <form
            class="box classic-form"
            @submit.prevent="submit"
            @mousemove="passwordInput?.trackPoint($event)"
            @mouseleave="passwordInput?.reset()"
        >
            <div class="form-header">
                <div class="logo">
                    <img :src="'/assets/img/fulgurite-logo.svg'" alt="Fulgurite" class="logo-icon">
                    <h2>Fulgurite</h2>
                </div>

                <p class="mt-3 text-center text-sm text-text-400">
                    Interface de sauvegarde pour Restic
                </p>
            </div>

            <TextInput
                v-model="form.login"
                :label="trans.fields.login"
                type="text"
                name="login"
                autocomplete="username"
                :error="form.errors.login"
                autofocus
                required
            />

            <PasswordInput
                ref="passwordInput"
                v-model="form.password"
                name="password"
                :label="trans.fields.password"
                autocomplete="current-password"
                :error="form.errors.password"
                required
            />

            <div class="input-group checkbox flex items-center justify-center">
                <CheckboxInput
                    v-model="form.remember"
                    name="remember"
                    :label="trans.remember_me"
                    :error="form.errors.remember"
                />
            </div>

            <p v-if="form.errors.auth" class="text-sm text-red-400 -mt-4">
                {{ form.errors.auth }}
            </p>

            <Link :href="resetPasswordUrl" class="link link-secondary -mt-4">
                {{ trans.reset_password }}
            </Link>

            <button class="btn btn-primary" type="submit" :disabled="form.processing">
                {{ trans.login_button }}
            </button>
        </form>

        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isInitialLoading"
                class="fixed inset-0 z-50 flex items-center justify-center bg-midnight/85 px-6 backdrop-blur-sm"
                role="status"
                aria-live="polite"
            >
                <div class="flex flex-col items-center gap-5 text-center">
                    <img :src="'/assets/img/fulgurite-logo.svg'" alt="" class="size-16">
                    <div class="size-12 rounded-full border-4 border-white/15 border-t-primary-500 animate-spin"></div>
                    <p class="text-sm font-medium text-text-300">Chargement</p>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
</style>
