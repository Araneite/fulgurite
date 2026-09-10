<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';

defineOptions({
    layout: AuthLayout
});

const props = defineProps({
    token: { type: String, required: true },
    invitation: { type: Object, required: true },
    __: { type: Object, required: true },
});

const form = useForm({
    username: props.invitation.username ?? '',
    password: '',
    password_confirmation: '',
}).dontRemember('password', 'password_confirmation');

const invitationAction = computed(() => `/invitations/accept/${props.token}`);

function submit() {
    form.post(invitationAction, {
        preserveScroll: true,
        onError: () => {
            form.reset('password', 'password_confirmation');
        },
    });
}
</script>

<template>
    <div class="w-full max-w-md">
        <UCard
            :ui="{
                root: 'bg-darknight/80 ring-white/10',
                body: 'p-6 sm:p-7',
                header: 'p-6 sm:p-7 border-white/10',
                footer: 'p-6 sm:p-7 border-white/10',
            }"
        >
            <template #header>
                <div class="flex flex-col items-center text-center">
                    <img :src="'/assets/img/fulgurite-logo.svg'" alt="Fulgurite" class="size-14">
                    
                    <h1 class="mt-4 text-xl font-semibold text-white">
                        {{ __.title }}
                    </h1>
                    
                    <p class="mt-2 text-sm text-text-400">
                        {{ __.subtitle }}
                    </p>
                </div>
            </template>
            
            <UAlert
                color="primary"
                variant="soft"
                icon="i-lucide-mail-check"
                :title="__.invitation_title"
                :description="`${__.invitation_description}`.replace(':email', invitation.email)"
                class="mb-5"
            />

            <div class="mb-5 grid gap-2 rounded-lg border border-white/10 bg-white/5 p-4 text-sm">
                <div class="flex items-center justify-between gap-4">
                    <span class="text-text-400">{{ __.fields.email }}</span>
                    <span class="truncate font-medium text-white">{{ invitation.email }}</span>
                </div>

                <div v-if="invitation.inviter" class="flex items-center justify-between gap-4">
                    <span class="text-text-400">{{ __.fields.inviter }}</span>
                    <span class="truncate font-medium text-white">{{ invitation.inviter }}</span>
                </div>

                <div v-if="invitation.expires_at" class="flex items-center justify-between gap-4">
                    <span class="text-text-400">{{ __.fields.expires_at }}</span>
                    <span class="truncate font-medium text-white">{{ invitation.expires_at }}</span>
                </div>
            </div>

            <UForm :state="form" class="flex flex-col gap-4" @submit.prevent="submit">
                <UFormField
                    name="username"
                    :label="__.fields.username"
                    :error="form.errors.username"
                    required
                >
                    <UInput
                        v-model="form.username"
                        icon="i-lucide-user"
                        autocomplete="username"
                        class="w-full"
                        autofocus
                    />
                </UFormField>

                <UFormField
                    name="password"
                    :label="__.fields.password"
                    :error="form.errors.password"
                    required
                >
                    <UInput
                        v-model="form.password"
                        type="password"
                        icon="i-lucide-lock"
                        autocomplete="new-password"
                        class="w-full"
                    />
                </UFormField>

                <UFormField
                    name="password_confirmation"
                    :label="__.fields.password_confirmation"
                    :error="form.errors.password_confirmation"
                    required
                >
                    <UInput
                        v-model="form.password_confirmation"
                        type="password"
                        icon="i-lucide-lock-keyhole"
                        autocomplete="new-password"
                        class="w-full"
                    />
                </UFormField>

                <UButton
                    type="submit"
                    color="primary"
                    icon="i-lucide-check"
                    :loading="form.processing"
                    :label="__.submit"
                    block
                    class="mt-2"
                />
            </UForm>

            <template #footer>
                <p class="text-center text-xs text-text-500">
                    {{ __.footer }}
                    <Link href="/login" class="text-primary hover:underline">
                        {{ __.login_link }}
                    </Link>
                </p>
            </template>
        </UCard>
    </div>
</template>

<style scoped>

</style>
