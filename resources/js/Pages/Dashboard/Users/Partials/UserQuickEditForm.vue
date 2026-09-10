<script setup lang="ts">
import { nextTick, ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import * as z from 'zod';

import PhoneInput from '@/Components/Forms/PhoneInput.vue';
import { clone, hasDirtyFields, isFieldDirty } from '@/utils/snapshot-form.js';

const props = defineProps<{
    __: any;
    countries: any[];
    user: any | null;
}>();

const emit = defineEmits<{
    saved: [];
    dirty: [value: boolean];
    closeRequested: [];
}>();

const toast = useToast();

const initialForm = ref({});

const dirtyFields = [
    'username', 'email', 'job_title',
    'phone.extension', 'phone.number',
    'admin_notes'
];

const schema = z.object({
    id: z.coerce.number().int(),
    email: z.email(props.__.errors.inputs.email_invalid),
    username: z.string(props.__.errors.inputs.string).min(1),
    job_title: z.string(props.__.errors.inputs.string).nullable().optional(),
    phone: z.object({
        extension: z.string().nullable().optional(),
        number: z.string().nullable().optional(),
    }),
    admin_notes: z.string(props.__.errors.inputs.string).nullable().optional(),
});

const form = useForm({
    id: '',
    email: '',
    username: '',
    job_title: '',
    phone: {
        extension:  '',
        number: '',
    },
    admin_notes: ''
});

const hasUnsavedChanges = computed(() => {
    return hasDirtyFields(form, initialForm.value, dirtyFields);
});

const drawerDescription = computed(() => {
    return `${props.__.forms.users.edit.description} "${form.username}"`;
})

function snapshot() {
    initialForm.value = clone({
        email: form.email,
        username: form.username,
        job_title: form.job_title,
        phone: {
            extension: form.phone.extension,
            number: form.phone.number,
        },
        admin_notes: form.admin_notes,
    });
}

function fillForm(user: any) {
    const data = {
        id: user.id ?? '',
        email: user.email ?? '',
        username: user.username ?? '',
        job_title: user.job_title ?? '',
        phone: {
            extension: user.phone?.extension ? String(user.phone.extension) : '',
            number: user.phone?.phone ? String(user.phone.phone) : '',
        },
        admin_notes: user.admin_notes ?? '',
    };

    form.defaults(data);
    form.id = data.id;
    form.email = data.email;
    form.username = data.username;
    form.job_title = data.job_title;
    form.phone = data.phone;
    form.admin_notes = data.admin_notes;
    form.clearErrors();

    nextTick(snapshot);
}

function open(user: any) {
    fillForm(user);
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
        
        Object.entries(errors).forEach(([field, messages])=> {
            form.setError(field , messages?.[0] ?? '');
        });
        
        return;
    }
    
    form.transform(()=> result.data)
        .patch(`/users/${form.id}/quick-edit`, {
            only: ['users', 'filters', 'flash'],
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                close();
                emit('saved');
            },
            onError: (errors) => {
                if (errors?.action) {
                    toast.add({
                        title: errors.action,
                        color: 'error',
                    });
                }
            },
        })
}

watch(
    () => props.user,
    (user) => {
        if (!user) return;
        fillForm(user);
    },
    { immediate: true, flush: 'post' }
);

defineExpose({
    open,
    close,
    submit,
    title: computed(()=> props.__.forms.users.edit.title),
    description: drawerDescription,
    processing: computed(() => form.processing),
    disabled: computed(() => false),
    dirty: hasUnsavedChanges
})
</script>

<template>
    <UForm class="flex flex-col gap-4" :schema="schema" :state="form" id="quick-edit-form" @submit="submit">
        <!-- Username Input -->
        <UFormField
            :label="props.__.resources.users.fields.username"
            :help="props.__.forms.users.help.username"
            :error="form.errors.username"
            required
            name="username"
        >
            <template #label>
                <UTooltip :text="props.__.global.forms.unsaved_change"  v-if="isFieldDirty(form, initialForm, 'username')">
                            <span class="inline-flex items-center gap-1">
                                <span>{{ props.__.resources.users.fields.username }}</span>
                    
                                <span

                                    class="inline-block size-1.5 shrink-0 rounded-full bg-warning"
                                />
                            </span>
                </UTooltip>
                <span v-else>{{ props.__.resources.users.fields.username}}</span>
            </template>
            <UInput
                v-model="form.username"
                placeholder=" "
                :ui="{ base: 'peer' }"
                required
                class="w-full"
                size="lg"
            />
        </UFormField>

        <!-- Email Input -->
        <UFormField
            :label="props.__.resources.users.fields.email"
            :help="props.__.forms.users.help.email"
            :error="form.errors.email"
            required
            name="email"
        >
            <template #label>
                <UTooltip :text="props.__.global.forms.unsaved_change"  v-if="isFieldDirty(form, initialForm, 'email')">
                            <span class="inline-flex items-center gap-1">
                                <span>{{ props.__.resources.users.fields.email }}</span>
                    
                                <span

                                    class="inline-block size-1.5 shrink-0 rounded-full bg-warning"
                                />
                            </span>
                </UTooltip>
                <span v-else>{{ props.__.resources.users.fields.email}}</span>
            </template>
            <UInput
                v-model="form.email"
                placeholder=" "
                :ui="{ base: 'peer' }"
                required
                class="w-full"
                size="lg"
            />
        </UFormField>

        <!-- Job Input -->
        <UFormField
            :label="props.__.resources.users.fields.job_title"
            :error="form.errors.job_title"
            name="job_title"
        >
            <template #label>
                <UTooltip :text="props.__.global.forms.unsaved_change"  v-if="isFieldDirty(form, initialForm, 'job_title')">
                            <span class="inline-flex items-center gap-1">
                                <span>{{ props.__.resources.users.fields.job_title }}</span>
                    
                                <span

                                    class="inline-block size-1.5 shrink-0 rounded-full bg-warning"
                                />
                            </span>
                </UTooltip>
                <span v-else>{{ props.__.resources.users.fields.job_title}}</span>
            </template>

            <UInput v-model="form.job_title" label="Poste" placeholder=" " :ui="{ base: 'peer'}" class="w-full" />
        </UFormField>

        <!-- Phone Input -->
        <UFormField
            name="phone"
            :error="form.errors['phone.extension'] || form.errors['phone.number']"
        >
            <template #label>
                <UTooltip
                    v-if="isFieldDirty(form, initialForm, 'phone.extension') || isFieldDirty(form, initialForm, 'phone.number')"
                    :text="props.__.global.forms.unsaved_change"
                >
            <span class="inline-flex items-center gap-1">
                <span>{{ props.__.resources.users.fields.phone.label }}</span>
                <span class="inline-block size-1.5 shrink-0 rounded-full bg-warning" />
            </span>
                </UTooltip>

                <span v-else>
            {{ props.__.resources.users.fields.phone.label }}
        </span>
            </template>

            <PhoneInput
                v-model="form.phone"
                :countries="countries"
                size="lg"
                :placeholder="props.__.resources.users.fields.phone.label"
            />
        </UFormField>

        <!-- Admin note Input -->
        <UFormField
            :label='props.__.resources.users.fields.admin_notes'
            :help="props.__.forms.users.help.admin_notes"
            :error="form.errors.admin_notes"
            name="admin_notes"
        >
            <template #label>
                        <span class="inline-flex items-center gap-1">
                            <span>{{ props.__.resources.users.fields.admin_notes }}</span>
                
                            <span
                                v-if="isFieldDirty(form, initialForm, 'admin_notes')"
                                class="inline-block size-1.5 shrink-0 rounded-full bg-warning"
                            />
                        </span>
            </template>
            <UTextarea v-model="form.admin_notes" placeholder=" " :ui="{ base: 'peer min-h-37.5'}" class="w-full"/>
        </UFormField>
    </UForm>
</template>

<style scoped>

</style>
