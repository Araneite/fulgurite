<script setup lang="ts">
import {computed, ref} from 'vue';
import { useForm } from '@inertiajs/vue3';
import * as z from 'zod';

import PhoneInput from '@/Components/Forms/PhoneInput.vue';
import FgPasswordConfirmationInput from '@/Components/Forms/FgPasswordConfirmationInput.vue';

const props = defineProps<{
    __: any;
    countries: any[];
    roles: any[];
    rolesLoading: boolean;
}>();

const emit = defineEmits<{
    created: [];
}>();

const toast = useToast();

const schema = z.object({
    username: z.string(props.__.errors.inputs.string).min(1),
    email: z.email(props.__.errors.inputs.email_invalid),
    first_name: z.string().nullable().optional(),
    last_name: z.string().nullable().optional(),
    job_title: z.string().nullable().optional(),
    phone: z.object({
        extension: z.string().nullable().optional(),
        number: z.string().nullable().optional(),
    }),
    password: z.string().min(6),
    password_confirmation: z.string().min(6),
    active: z.boolean(),
    roles: z.array(z.any()).optional(),
    admin_notes: z.string().nullable().optional(),
}).refine((data) => data.password === data.password_confirmation, {
    path: ['password_confirmation'],
    message: props.__.resources.users.validation.password.confirmed,
});

const form = useForm({
    username: '',
    email: '',
    first_name: '',
    last_name: '',
    job_title: '',
    phone: {
        extension: '',
        number: '',
    },
    password: '',
    password_confirmation: '',
    active: true,
    roles: [],
    admin_notes: '',
});

const passwordValid = ref(false);

const canSubmit = computed(() => {
    return form.username.trim() !== ''
        && form.email.trim() !== ''
        && form.password.trim() !== ''
        && form.password_confirmation.trim() !== ''
        && passwordValid.value;
});

function open() {
    form.defaults({
        username: '',
        email: '',
        first_name: '',
        last_name: '',
        job_title: '',
        phone: {
            extension: '',
            number: '',
        },
        password: '',
        password_confirmation: '',
        active: true,
        roles: [],
        admin_notes: '',
    });

    form.reset();
    form.clearErrors();
}

function close() {
    form.reset();
    form.clearErrors();
}

function submit() {
    form.clearErrors();

    const result = schema.safeParse(form.data());

    if (!result.success) {
        const errors = result.error.flatten().fieldErrors;

        Object.entries(errors).forEach(([field, messages]) => {
            form.setError(field, messages?.[0] ?? '');
        });

        return;
    }

    form
        .transform(() => ({
            ...result.data,
            roles: (result.data.roles ?? []).map((role: any) => ({
                id: role.id ?? role.value,
            })),
        }))
        .post('/users', {
            only: ['users', 'filters', 'flash'],
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                close();
                emit('created');
            },
            onError: (errors) => {
                if (errors?.action) {
                    toast.add({
                        title: errors.action,
                        color: 'error',
                    });
                }
            },
        });
}

defineExpose({
    open,
    close,
    submit,
    processing: computed(() => form.processing),
    disabled: computed(() => !canSubmit.value),
    dirty: computed(() => form.isDirty),
});
</script>

<template>
    <UForm
        id="quick-create-form"
        class="flex flex-col gap-4"
        :schema="schema"
        :state="form"
        @submit="submit"
    >
        <UFormField :label="props.__.resources.users.fields.username" :error="form.errors.username" required name="username">
            <UInput v-model="form.username" required class="w-full" size="lg" />
        </UFormField>

        <UFormField :label="props.__.resources.users.fields.email" :error="form.errors.email" required name="email">
            <UInput v-model="form.email" required class="w-full" size="lg" />
        </UFormField>

        <FgPasswordConfirmationInput
            v-model:password="form.password"
            v-model:confirmation="form.password_confirmation"
            :password-label="props.__.resources.users.fields.password"
            :confirmation-label="props.__.resources.users.fields.password_confirmation"
            :password-error="form.errors.password"
            :confirmation-error="form.errors.password_confirmation"
            :validation="props.__.resources.users.validation.password"
            @valid="passwordValid = $event"
        />

        <UFormField :label="props.__.resources.users.fields.first_name" :error="form.errors.first_name" name="first_name">
            <UInput v-model="form.first_name" class="w-full" />
        </UFormField>

        <UFormField :label="props.__.resources.users.fields.last_name" :error="form.errors.last_name" name="last_name">
            <UInput v-model="form.last_name" class="w-full" />
        </UFormField>

        <UFormField :label="props.__.resources.users.fields.job_title" :error="form.errors.job_title" name="job_title">
            <UInput v-model="form.job_title" class="w-full" />
        </UFormField>

        <UFormField
            :label="props.__.resources.users.fields.phone.label"
            :error="form.errors['phone.extension'] || form.errors['phone.number']"
            name="phone"
        >
            <PhoneInput
                v-model="form.phone"
                :countries="countries"
                size="lg"
                :placeholder="props.__.resources.users.fields.phone.label"
            />
        </UFormField>

        <UFormField :label="props.__.resources.users.fields.roles" :error="form.errors.roles" name="roles">
            <USelectMenu
                v-model="form.roles"
                :filter-fields="['label', 'project']"
                :items="roles"
                :loading="rolesLoading"
                multiple
                class="w-full"
            />
        </UFormField>

        <UFormField :label="props.__.resources.users.fields.active" :error="form.errors.active" name="active">
            <USwitch v-model="form.active" color="success" />
        </UFormField>

        <UFormField
            :label="props.__.resources.users.fields.admin_notes"
            :help="props.__.forms.users.help.admin_notes"
            :error="form.errors.admin_notes"
            name="admin_notes"
        >
            <UTextarea v-model="form.admin_notes" :ui="{ base: 'peer min-h-37.5' }" class="w-full" />
        </UFormField>
    </UForm>
</template>
