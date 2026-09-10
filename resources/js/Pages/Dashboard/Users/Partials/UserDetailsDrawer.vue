<script setup lang="ts">
import {
    computed,
    nextTick,
    onBeforeUnmount,
    ref,
    watch,
} from 'vue';
import { router } from '@inertiajs/vue3';

type AccountStatus =
    | 'active'
    | 'locked'
    | 'suspended'
    | 'expired'
    | 'trashed';

type UserStatus = {
    value: AccountStatus;
    label: string;
};

type BadgeColor =
    | 'success'
    | 'warning'
    | 'error'
    | 'neutral'
    | 'info';

type UserContact = {
    first_name: string | null;
    last_name: string | null;
    job_title: string | null;
    phone: {
        extension: string | number | null;
        number: string | number | null;
    } | null;
};

type UserRole = {
    id: number;
    name: string;
};

type SensitiveUserDetails = {
    roles: UserRole[];
    last_login: string | null;
    expire_at: string | null;
    suspended_until: string | null;
    suspension_reason: string | null;
    admin_notes: string | null;
};

type UserDetails = {
    id: number;
    username: string;
    email: string;

    status: UserStatus;
    active: boolean;
    is_deleted: boolean;

    created_at: string | null;
    updated_at: string | null;

    contact: UserContact | null;

    /*
     * Cette propriété est absente lorsque le serveur refuse
     * l'accès aux informations sensibles.
     */
    sensitive?: SensitiveUserDetails;

    permissions: {
        view_sensitive: boolean;
        update: boolean;
    };
};

type UserDetailsDrawerLabels = {
    title: string;
    description: string;
    open_full_page: string;
    loading: string;
    unknown_status: string;
    not_provided: string;
    sections: {
        contact: string;
        information: string;
        administration: string;
    };
    fields: {
        id: string;
        created_at: string;
        updated_at: string;
        roles: string;
        last_login: string;
        expire_at: string;
        suspended_until: string;
        suspension_reason: string;
        admin_notes: string;
    };
    errors: {
        title: string;
        unknown: string;
        load: string;
    };
    actions: {
        view_details: string;
        retry: string;
        close: string;
    };
};

const open = defineModel<boolean>('open', {
    required: true,
});

const props = withDefaults(defineProps<{
    userId: number;
    locale?: string;
    labels: UserDetailsDrawerLabels;
}>(), {
    locale: 'fr-FR',
});

const emit = defineEmits<{
    closed: [];
}>();

const user = ref<UserDetails | null>(null);
const loading = ref(false);
const error = ref<string | null>(null);

let abortController: AbortController | null = null;

const statusConfiguration: Record<
    AccountStatus,
    {
        color: BadgeColor;
        icon: string;
    }
> = {
    active: {
        color: 'success',
        icon: 'i-lucide-circle-check',
    },
    locked: {
        color: 'neutral',
        icon: 'i-lucide-circle-pause',
    },
    suspended: {
        color: 'warning',
        icon: 'i-lucide-shield-alert',
    },
    expired: {
        color: 'error',
        icon: 'i-lucide-clock-alert',
    },
    trashed: {
        color: 'error',
        icon: 'i-lucide-trash-2',
    },
};

const title = computed(() => {
    return user.value?.username ?? props.labels.title;
});

const currentStatus = computed(() => {
    const status = user.value?.status;
    
    if (!status || !statusConfiguration[status.value]) {
        return {
            label: props.labels.unknown_status,
            color: 'neutral' as BadgeColor,
            icon: 'i-lucide-circle-help',
        };
    }

    return {
        label: status.label,
        ...statusConfiguration[status.value],
    };
});

const fullName = computed(() => {
    const contact = user.value?.contact;

    if (!contact) {
        return null;
    }

    const name = [
        contact.first_name,
        contact.last_name,
    ]
        .filter(Boolean)
        .join(' ');

    return name || null;
});

const initials = computed(() => {
    const source = fullName.value ?? user.value?.username ?? '';

    return source
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map(part => part.charAt(0).toUpperCase())
        .join('');
});

const phoneNumber = computed(() => {
    const phone = user.value?.contact?.phone;

    if (!phone?.number) {
        return null;
    }

    const extension = phone.extension
        ? String(phone.extension).replace(/^\+/, '')
        : '';

    const number = String(phone.number);

    return extension
        ? `+${extension}${number}`
        : number;
});

const canDisplaySensitiveData = computed(() => {
    return Boolean(
        user.value?.permissions.view_sensitive
        && user.value?.sensitive,
    );
});

const hasAdministrativeDetails = computed(() => {
    const sensitive = user.value?.sensitive;

    if (!sensitive) {
        return false;
    }

    return Boolean(
        (sensitive.roles?.length ?? 0) > 0
        || sensitive.last_login
        || sensitive.expire_at
        || sensitive.suspended_until
        || sensitive.suspension_reason
        || sensitive.admin_notes,
    );
});

function formatDate(value: string | null): string {
    if (!value) {
        return props.labels.not_provided;
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return props.labels.not_provided;
    }

    return new Intl.DateTimeFormat(
        props.locale.replace('_', '-'),
        {
            dateStyle: 'medium',
            timeStyle: 'short',
        },
    ).format(date);
}

function openDetailsPage(): void {
    router.get(`/users/${props.userId}`);
}

async function loadUserDetails(): Promise<void> {
    abortController?.abort();

    const currentController = new AbortController();
    abortController = currentController;

    loading.value = true;
    error.value = null;
    user.value = null;

    try {
        const response = await fetch(
            `/users/${encodeURIComponent(props.userId)}/details`,
            {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: currentController.signal,
            },
        );

        if (!response.ok) {
            const body = await response.json().catch(() => null);

            throw new Error(
                body?.message
                ?? props.labels.errors.load.replace(':status', String(response.status)),
            );
        }

        const body = await response.json() as {
            data: UserDetails;
        };

        user.value = body.data;
    } catch (exception) {
        if (
            exception instanceof DOMException
            && exception.name === 'AbortError'
        ) {
            return;
        }

        error.value = exception instanceof Error
            ? exception.message
            : props.labels.errors.unknown;
    } finally {
        if (abortController === currentController) {
            loading.value = false;
        }
    }
}

async function handleAnimationEnd(isOpen: boolean): Promise<void> {
    if (isOpen) {
        return;
    }

    /*
     * Attend la fin du traitement interne de vaul-vue avant
     * d'autoriser le parent à détruire le composant.
     */
    await nextTick();

    emit('closed');
}

watch(
    () => props.userId,
    () => loadUserDetails(),
    { immediate: true },
);

onBeforeUnmount(() => {
    abortController?.abort();
});
</script>

<template>
    <UDrawer
        v-model:open="open"
        direction="right"
        :title="title"
        :description="labels.description"
        class="w-full sm:max-w-md"
        :ui="{
            header: 'border-b border-default',
            body: 'p-0',
            footer: 'border-t border-default',
        }"
        @animation-end="handleAnimationEnd"
    >
        <template #header>
            <div class="flex w-full items-start justify-between gap-4">
                <div class="min-w-0">
                    <h2 class="truncate font-semibold">
                        {{ title }}
                    </h2>

                    <p class="mt-1 text-sm text-muted">
                        {{ labels.description }}
                    </p>
                </div>

                <UTooltip :text="labels.open_full_page">
                    <UButton
                        type="button"
                        icon="i-lucide-external-link"
                        color="neutral"
                        variant="ghost"
                        :aria-label="labels.open_full_page"
                        class="shrink-0 cursor-pointer"
                        @click="openDetailsPage"
                    />
                </UTooltip>
            </div>
        </template>
        
        <template #body>
            <!-- Chargement -->
            <div
                v-if="loading"
                class="flex min-h-64 items-center justify-center"
            >
                <div class="flex flex-col items-center gap-3 text-muted">
                    <UIcon
                        name="i-lucide-loader-circle"
                        class="size-7 animate-spin"
                    />

                    <span>{{ labels.loading }}</span>
                </div>
            </div>

            <!-- Erreur -->
            <div
                v-else-if="error"
                class="flex min-h-64 items-center justify-center p-6"
            >
                <UAlert
                    color="error"
                    variant="subtle"
                    icon="i-lucide-circle-alert"
                    :title="labels.errors.title"
                    :description="error"
                >
                    <template #actions>
                        <UButton
                            color="error"
                            variant="soft"
                            icon="i-lucide-refresh-cw"
                            :label="labels.actions.retry"
                            @click="loadUserDetails"
                        />
                    </template>
                </UAlert>
            </div>

            <!-- Contenu -->
            <div
                v-else-if="user"
                class="divide-y divide-default"
            >
                <!-- Identité et état -->
                <section class="flex items-start gap-4 p-6">
                    <UAvatar
                        :alt="user.username"
                        :text="initials"
                        size="xl"
                    />

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="truncate text-lg font-semibold">
                                {{ fullName ?? user.username }}
                            </p>

                            <UBadge
                                :color="currentStatus.color"
                                variant="subtle"
                                :icon="currentStatus.icon"
                                :label="currentStatus.label"
                            />
                        </div>

                        <p class="truncate text-sm text-muted">
                            @{{ user.username }}
                        </p>

                        <p
                            v-if="user.contact?.job_title"
                            class="mt-1 text-sm"
                        >
                            {{ user.contact.job_title }}
                        </p>
                    </div>
                </section>

                <!-- Coordonnées -->
                <section class="space-y-4 p-6">
                    <h3 class="font-semibold">
                        {{ labels.sections.contact }}
                    </h3>

                    <div class="space-y-3 text-sm">
                        <a
                            :href="`mailto:${user.email}`"
                            class="flex items-center gap-3 hover:underline"
                        >
                            <UIcon
                                name="i-lucide-mail"
                                class="size-4 shrink-0 text-muted"
                            />

                            <span class="break-all">
                                {{ user.email }}
                            </span>
                        </a>

                        <a
                            v-if="phoneNumber"
                            :href="`tel:${phoneNumber}`"
                            class="flex items-center gap-3 hover:underline"
                        >
                            <UIcon
                                name="i-lucide-phone"
                                class="size-4 shrink-0 text-muted"
                            />

                            <span>{{ phoneNumber }}</span>
                        </a>
                    </div>
                </section>

                <!-- Informations générales -->
                <section class="space-y-4 p-6">
                    <h3 class="font-semibold">
                        {{ labels.sections.information }}
                    </h3>

                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted">
                                {{ labels.fields.id }}
                            </dt>

                            <dd>{{ user.id }}</dd>
                        </div>

                        <div class="flex justify-between gap-4">
                            <dt class="text-muted">
                                {{ labels.fields.created_at }}
                            </dt>

                            <dd class="text-right">
                                {{ formatDate(user.created_at) }}
                            </dd>
                        </div>

                        <div class="flex justify-between gap-4">
                            <dt class="text-muted">
                                {{ labels.fields.updated_at }}
                            </dt>

                            <dd class="text-right">
                                {{ formatDate(user.updated_at) }}
                            </dd>
                        </div>
                    </dl>
                </section>

                <!-- Informations administratives sensibles -->
                <section
                    v-if="
                        canDisplaySensitiveData
                        && hasAdministrativeDetails
                        && user.sensitive
                    "
                    class="space-y-4 p-6"
                >
                    <div class="flex items-center gap-2">
                        <UIcon
                            name="i-lucide-shield"
                            class="size-4 text-muted"
                        />

                        <h3 class="font-semibold">
                            {{ labels.sections.administration }}
                        </h3>
                    </div>

                    <div v-if="user.sensitive.roles.length">
                        <p class="mb-2 text-sm text-muted">
                            {{ labels.fields.roles }}
                        </p>

                        <div class="flex flex-wrap gap-2">
                            <UBadge
                                v-for="role in user.sensitive.roles"
                                :key="role.id"
                                color="info"
                                variant="subtle"
                                :label="role.name"
                            />
                        </div>
                    </div>

                    <dl class="space-y-3 text-sm">
                        <div
                            v-if="user.sensitive.last_login"
                            class="flex justify-between gap-4"
                        >
                            <dt class="text-muted">
                                {{ labels.fields.last_login }}
                            </dt>

                            <dd class="text-right">
                                {{ formatDate(user.sensitive.last_login) }}
                            </dd>
                        </div>

                        <div
                            v-if="user.sensitive.expire_at"
                            class="flex justify-between gap-4"
                        >
                            <dt class="text-muted">
                                {{ labels.fields.expire_at }}
                            </dt>

                            <dd class="text-right">
                                {{ formatDate(user.sensitive.expire_at) }}
                            </dd>
                        </div>

                        <div
                            v-if="user.sensitive.suspended_until"
                            class="flex justify-between gap-4"
                        >
                            <dt class="text-muted">
                                {{ labels.fields.suspended_until }}
                            </dt>

                            <dd class="text-right">
                                {{ formatDate(user.sensitive.suspended_until) }}
                            </dd>
                        </div>
                    </dl>

                    <UAlert
                        v-if="user.sensitive.suspension_reason"
                        color="warning"
                        variant="subtle"
                        icon="i-lucide-shield-alert"
                        :title="labels.fields.suspension_reason"
                        :description="user.sensitive.suspension_reason"
                    />

                    <div v-if="user.sensitive.admin_notes">
                        <p class="mb-2 text-sm font-medium">
                            {{ labels.fields.admin_notes }}
                        </p>

                        <p class="whitespace-pre-wrap text-sm text-muted">
                            {{ user.sensitive.admin_notes }}
                        </p>
                    </div>
                </section>
            </div>
        </template>

        <template #footer>
            <div class="flex w-full justify-end mt-4">
                <UButton
                    color="neutral"
                    variant="outline"
                    :label="labels.actions.close"
                    @click="open = false"
                />
            </div>
        </template>
    </UDrawer>
</template>
