<script setup lang="ts">
import {computed, ref, watch, onMounted, onBeforeUnmount, nextTick, h, resolveComponent} from 'vue';
import {defineShortcuts, extractShortcuts, useToast} from "@nuxt/ui/composables";
import {router, usePage, useForm} from '@inertiajs/vue3';
import { useClipboard } from '@vueuse/core';
import { today, fromDate, getLocalTimeZone, toCalendarDate} from '@internationalized/date';
import { vMaska } from 'maska/vue';
import * as z from 'zod';

import AppLayout from '@/Layouts/DashboardLayout.vue';
import PrimaryDataTable from "@/Components/DataTable/PrimaryDataTable.vue";
import SecondaryDataTable from "@/Components/DataTable/SecondaryDataTable.vue";
import { countries } from '@/utils/data/countries.js';
import { csrfHeaders } from '@/utils/http.js';
import { getHeader } from '@/utils/data/table.js';
import { serializeDate } from '@/utils/date.js';
import { clone, isFieldDirty } from '@/utils/snapshot-form.js';
import { truncate } from '@/utils/string.js';
import FgInputDatePicker from '@/Components/Forms/FgInputDatePicker.vue';
import FgDiscardChangesModal from "@/Components/Modals/FgDiscardChangesModal.vue";
import UserDrawerShell from "@/Pages/Dashboard/Users/Partials/UserDrawerShell.vue";
import UserQuickCreateForm from "@/Pages/Dashboard/Users/Partials/UserQuickCreateForm.vue";
import UserQuickEditForm from "@/Pages/Dashboard/Users/Partials/UserQuickEditForm.vue";
import FgConfirmIdentityModal from "@/Components/Modals/FgConfirmIdentityModal.vue";
import FgConfirmBulkActionModal from "@/Components/Modals/FgConfirmBulkActionModal.vue";
import UserDetailsDrawer from '@/Pages/Dashboard/Users/Partials/UserDetailsDrawer.vue';

import {
    usePageShortcutScope,
    useShortcutRegistry,
} from '@/Composables/useKeyboardShortcutRegistry';

// === Global ===
defineOptions({
    layout: AppLayout,
});


const page = usePage();
const toast = useToast();

const props = defineProps({
    users: { type: Object, required: true },
    invitations: { type: Object, required: true },
    filters: { type: Object, required: true },
    fields: { type: Object, required: true },
    tableName: { type: String, required: false },
    locale: { type: String, required: false },
    __: { type: Object, required: true },
    phoneCountries: { type: Object, required: true },
    availableForcedActions: { type: Object, required: false, default: []},
    availableLocales: { type: Object, required: true },
    availableTimezones: { type: Object, required: true },
    availableStartPages: { type: Object, required: true },
    permissions: { type: Object, required: true}
});

const UButton = resolveComponent('UButton');
const UDropdownMenu = resolveComponent('UDropdownMenu');

const tableHeaderComponent = {
    UDropdownMenu,
    UButton
}

const usersLoading = ref(false);
const rowSelection = ref({});
const invitationsLoading = ref(false);
const invitationsRowSelection = ref({});

const currentUserId = computed(() => page.props.auth?.user?.id ?? null);

const permissions = computed(() =>  {
    return props.permissions ?? {
        view: true,
        create: false,
        edit: false,
        delete: false,
        restore: false,
        forceDelete: false,
        view_sensitive: false,
    }
});

function reloadUsers(params = {}) {
    usersLoading.value = true;
    
    router.get('/users', {
        ...props.filters,
        ...params
    }, {
        only: ['users', 'filters'],
        preserveScroll: true,
        preserveState: true,
        replace: true,
        async: true,
        onFinish: () => {
            usersLoading.value = false;
        }
    })
}
function reloadInvitations(params = {}) {
    invitationsLoading.value = true;
    
    router.get('/users', {
        ...props.filters,
        ...params,
    }, {
        only: ['invitations', 'filters'],
        preserveScroll: true,
        preserveState: true,
        replace: true,
        async: true,
        onFinish: () => {
            invitationsLoading.value = false;
        }
    })
}

// --- Shortcuts ---
usePageShortcutScope('dashboard.users.index');
const { registerShortcuts } = useShortcutRegistry();

const disposeUserPageShortcuts = registerShortcuts([
    {
        key: 'c',
        label: 'Créer un utilisateur',
        description: 'Ouvre le formulaire de création rapide.',
        scope: 'page',
        ownerId: 'dashboard.users.index',
        group: 'Utilisateurs',
        order: 10,
        handler: () => {
            openQuickCreate();
        },
    },
    {
        key: 'meta_f',
        label: 'Rechercher dans les utilisateurs',
        description: 'Place le focus dans le champ de recherche.',
        scope: 'page',
        ownerId: 'dashboard.users.index',
        group: 'Navigation',
        order: 20,
        handler: () => {
            document.querySelector<HTMLInputElement>('[data-users-search]')?.focus();
        },
    },
], 'dashboard-users-page');

onBeforeUnmount(disposeUserPageShortcuts);

// === Global Modals ===

// --- UserDetails drawer ---
const detailsDrawerOpen = ref(false);
const detailsUserId = ref<number | null>(null);

function openUserDetails(row: any): void {
    const selectedUser = row.original ?? row;
    
    if (!selectedUser?.id || selectedUser.permissions?.view === false) return;
    
    detailsUserId.value = selectedUser.id;
    detailsDrawerOpen.value = true;
}

function destroyDetailsDrawer(): void {
    if (!detailsDrawerOpen.value) {
        detailsUserId.value = null;
    }
}

// --- Job ---
const discardModalOpen = ref(false);
const discardModalConfig = ref({
    title: '',
    description: '',
    keepLabel: '',
    discardLabel: '',
    discardColor: '',
    keepColor: '',
    onKeep: null as null | (() => void | Promise<void>),
    onDiscard: null as null | (() => void | Promise<void>),
})

function openDiscardModal(config: {
    title?: string;
    description?: string;
    keepLabel?: string;
    discardLabel?: string;
    discardColor?: string;
    keepColor?: string;
    onKeep?: () => void | Promise<void>;
    onDiscard?: () => void | Promise<void>;
}) {
    discardModalConfig.value = {
        title: config.title ?? props.__.modals.discard.title,
        description: config.description ?? props.__.modals.discard.description,
        keepLabel: config.keepLabel ?? props.__.actions.keep,
        discardLabel: config.discardLabel ?? props.__.actions.discard,
        discardColor: config.discardColor ?? 'error',
        keepColor:  config.keepColor ?? 'neutral',
        onKeep: config.onKeep ?? null,
        onDiscard: config.onDiscard ?? null,
    };
    
    discardModalOpen.value = true;
}
function closeDiscardModal() {
    discardModalOpen.value = false;
    
}
async function requestDiscardModal(action: () => void| Promise<void>, config = discardModalConfig.value) {
    openDiscardModal({
        ...config,
        onDiscard: async () => await action(),
        onKeep: () => closeDiscardModal()
    })
}

async function handleDiscardKeep() {
    const callback = discardModalConfig.value.onKeep;
    
    discardModalOpen.value = false;
    
    if (callback) {
        await callback();
    }
}
async function handleDiscardDiscard() {
    const callback = discardModalConfig.value.onDiscard;
    
    discardModalOpen.value = false;
    
    if (callback) {
        await callback();
    }
}

// --- Identity Modal ---
const identityModalOpen = ref(false);
const identityModalConfig = ref({
    title: '',
    subtitle: '',
    fields: {
        password: '',
        method: '',
        code: '',
    },
    methods: {} as Record<string, string>,
    actions: {
        cancel: '',
        confirmButton: '',
        passkeyButton: '',
        resendEmailCode: '',
    },
    emailSentTo: '',
    errors: {
        invalidPasskey: '',
    },
    onConfirmed: null as null | (() => void | Promise<void>),
})

function openIdentityModal(config: {
    title?: string;
    subtitle?: string;
    onConfirmed?: () => void |  Promise<void>;
}) {
    const trans = props.__.global.confirm_identity;
    
    identityModalConfig.value = {
        title: config.title ?? trans.title,
        subtitle: config.subtitle ?? trans.subtitle,
        fields: {
            password: trans.fields.password,
            method: trans.fields.method,
            code: trans.fields.code,
        },
        actions: {
            cancel: trans.actions.cancel,
            confirmButton: trans.actions.confirm,
            passkeyButton: trans.actions.passkey,
            resendEmailCode: trans.actions.resend_email_code,
        },
        methods: trans.methods,
        emailSentTo: trans.messages.email_sent_to,
        errors: {
            invalidPasskey: trans.errors.invalid_passkey,
        },
        onConfirmed: config.onConfirmed ?? null,
    };
    
    identityModalOpen.value = true;
}
function requestIdentityConfirmation(action: () => void | Promise<void>) {
    openIdentityModal({
        onConfirmed: action,
    });
}
async function runPendingSensitiveAction() {
    const action = identityModalConfig.value.onConfirmed;
    
    identityModalConfig.value.onConfirmed = null;
    
    if (action) {
        await action();
    }
}

// --- Bulk Action modal ---
const bulkActionModalOpen = ref(false);
const bulkActionLoading = ref(false);

const bulkActionConfig = ref({
    type: 'delete' as 'delete' | 'force_delete' | 'restore',
    translations: {} as any,
    selectedRows: [] as any[],
    actionableRows: [] as any[],
});

function getSelectedUsers() {
    return props.users.data.filter((user: any) => rowSelection.value[user.id]);
}

function openBulkActionModal(type: 'delete' | 'force_delete' | 'restore', actionableRows: any[]) {
    bulkActionConfig.value = {
        type,
        translations: props.__.modals.bulk_actions[type],
        selectedRows: getSelectedUsers(),
        actionableRows,
    };

    bulkActionModalOpen.value = true;
}

function handleBulkDelete(rows: any[]) {
    openBulkActionModal('delete', rows);
}

function handleBulkForceDelete(rows: any[]) {
    openBulkActionModal('force_delete', rows);
}

function handleBulkRestore(rows: any[]) {
    openBulkActionModal('restore', rows);
}

function confirmBulkAction() {
    const config = bulkActionConfig.value;
    const ids = config.actionableRows.map((row) => row.id);

    if (!ids.length || bulkActionLoading.value) return;

    bulkActionLoading.value = true;

    const options = {
        preserveScroll: true,
        preserveState: true,
        only: ['users', 'filters', 'flash'],
        onSuccess: () => {
            rowSelection.value = {};
            bulkActionModalOpen.value = false;
        },
        onFinish: () => {
            bulkActionLoading.value = false;
        },
    };

    if (config.type === 'delete') {
        router.delete('/users/bulk/delete', {
            ...options,
            data: { ids },
        });

        return;
    }

    if (config.type === 'force_delete') {
        router.delete('/users/bulk/force-delete', {
            ...options,
            data: { ids },
        });

        return;
    }

    if (config.type === 'restore') {
        router.patch('/users/bulk/restore', { ids }, options);
    }
}
// === End Global Modals ===

// === Users modals ===
const rolesModalOpen = ref(false);
const rolesModalUser = ref<any | null>(null);

function openRolesModal(user: any) {
    rolesModalUser.value = user;
    rolesModalOpen.value = true;
}
// === End Users modals ===
// === columns === 

const expandedAdminNotes = ref<Record<string, boolean>>({});

function toggleAdminNote(userId: number | string): void {
    const key = String(userId);

    expandedAdminNotes.value[key] = !expandedAdminNotes.value[key];
}

function isAdminNoteExpanded(userId: number | string): boolean {
    return expandedAdminNotes.value[String(userId)] === true;
}

// === Columns ===
const columns = computed(() => [
    {
        accessorKey: 'id',
        header: '#ID',
        cell: ({ row }) => `#${row.original.id}`,
    },
    {
        accessorKey: 'username',
        header: ({column })=> getHeader(column, props.__.tables.users.header.username, tableHeaderComponent),
        meta: {
            class: {
                td: 'text-primary-light font-semibold min-w-20'
            },
        },
    },
    {
        accessorKey: 'email',
        header: ({ column })=> getHeader(column, props.__.tables.users.header.contact, tableHeaderComponent),
    },
    {
        accessorKey: 'active',
        header: props.__.tables.users.header.active,
    },
    ...(permissions.value.view_sensitive ? [{
        accessorKey: 'admin_notes',
        header: props.__.tables.users.header.admin_notes,
        enableSorting: false,
        meta: {
            class: {
                td: 'max-w-72 whitespace-normal',
            },
        },
    }] : []),
    {
        accessorKey: 'updated_at',
        header: ({ column })=> getHeader(column, props.__.tables.users.header.updated_at, tableHeaderComponent)
    },
    {
        id: 'actions',
        header: '',
        enableHiding: false
    },
]);
const invitationsColumns = [
    {
        accessorKey: 'id', 
        header: '#ID',
        cell: ({ row })=> `#${row.original.id}`,
    },
    {
        accessorKey: 'email',
        header: ({ column }) => getHeader(column, props.__.tables.invitations.header.email, tableHeaderComponent)
    },
    {
        accessorKey: 'username',
        header: ({ column }) => getHeader(column, props.__.tables.invitations.header.username, tableHeaderComponent),
        cell: ({ row })=> row.original.username ?? props.__.tables.invitations.cell.username.undefined,
    },
    {
        accessorKey: 'status_label',
        header: props.__.tables.invitations.header.status,
    },
    {
        accessorKey: 'expires_at',
        header: props.__.tables.invitations.header.expires_at,
        cell: ({ row }) => row.original.expires_at ?? props.__.tables.invitations.cell.expires_at.no_date,
    },
    {
        accessorKey: 'created_at',
        header: ({ column }) => getHeader(column, props.__.tables.invitations.header.created_at, tableHeaderComponent),
    },
    {
        accessorKey: 'invited_by',
        header: props.__.tables.invitations.header.invited_by,
        cell: ({ row }) => row.original.invited_by?.username ?? props.__.tables.invitations.cell.invited_by.unknown,
    },
    {
        id: 'actions',
        header: '',
        enableHiding: false
    }
]

const { copy } = useClipboard();


// === Users specifications ===
// --- Form validation ---
type UserModalTab = 'data' | 'security' | 'admin';

const activeUserModalTab = ref<UserModalTab>('data');

function isBlank(value: unknown) {
    return value === null || value === undefined || String(value).trim() === '';
}

const isDataTabValid = computed(() => {
    return !isBlank(userDataForm.username)
        && !isBlank(userDataForm.email);
});
const isSecurityTabValid = computed(() => {
    return score.value >= 5 && samePassword.value;
})
const isAdminTabValid = computed(() => {
    return true;
})

const isCurrentTabValid = computed(() => {
    if (activeUserModalTab.value === 'data') return isDataTabValid.value;
    if (activeUserModalTab.value === 'security') return isSecurityTabValid.value;
    if (activeUserModalTab.value === 'admin') return isAdminTabValid.value;
    return false;
})

// --- Views ---
const userTableViews = computed(() => [
    {
        label: props.__.views.active,
        value: 'active',
        icon: 'i-lucide-circle-check'
    },
    {
        label: props.__.views.trash,
        value: 'trash',
        icon: 'i-lucide-trash',
    },
    {
        label: props.__.views.all,
        value: 'all',
        icon: 'i-lucide-archive',
    }
])

// --- Actions ---
// Quick edit drawer
type QuickDrawerMode = 'create' | 'edit' | null;

const quickDrawerOpen = ref(false);
const quickDrawerMode = ref<QuickDrawerMode>(null);
const quickCreateFormRef = ref<InstanceType<typeof UserQuickCreateForm> | null>(null);
const quickEditFormRef = ref<InstanceType<typeof UserQuickEditForm> | null>(null);
const selectedQuickUser = ref(null);

const activeQuickForm = computed(() => {
    if (quickDrawerMode.value === 'create') return quickCreateFormRef.value;
    if (quickDrawerMode.value === 'edit') return quickEditFormRef.value;

    return null;
});

const quickDrawerTitle = computed(() => {
    if (quickDrawerMode.value === 'create') return props.__.user_actions.fast_create;
    if (quickDrawerMode.value === 'edit') return props.__.forms.users.edit.title;
    return '';
});

const quickDrawerDescription = computed(() => {
    if (quickDrawerMode.value === 'edit') {
        return `${props.__.forms.users.edit.description} "${selectedQuickUser.value?.username ?? ''}"`;
    }

    return '';
});
const quickDrawerProcessing = computed(() => activeQuickForm.value?.processing ?? false);
const quickDrawerDisabled = computed(() => activeQuickForm.value?.disabled ?? false);
const quickDrawerDirty = computed(() => activeQuickForm.value?.dirty ?? false);

function openQuickEdit(row) {
    selectedQuickUser.value = row.original;
    quickDrawerMode.value = 'edit';
    quickDrawerOpen.value = true;
    
    nextTick(() => {
        quickEditFormRef.value?.open(selectedQuickUser.value);
    });
}
function submitQuickDrawer() {
    activeQuickForm.value?.submit();
}
function closeQuickDrawer() {
    activeQuickForm.value?.close();
    quickDrawerOpen.value = false;
    quickDrawerMode.value = null;
    selectedQuickUser.value = null;
}
function handleQuickDrawerOpenChange(value: boolean) {
    if (value) {
        quickDrawerOpen.value = true;
        return;
    }
    
    if (quickDrawerDirty.value) {
        quickDrawerOpen.value = true;
        
        openDiscardModal({
            onKeep: () => {
                quickDrawerOpen.value = true;
            },
            onDiscard: ()=> {
                closeQuickDrawer();
            }
        });
        
        return;
    }
    
    closeQuickDrawer();
}
function handleQuickDrawerSaved() {
    if (quickDrawerMode.value === 'create') {
        toast.add({
            title: props.__.resources.users.messages.created.title,
            description: `${props.__.resources.users.messages.created.description}`.replace(':user', selectedQuickUser.value?.username ?? 'unknown'),
            color: 'success',
            icon: 'i-lucide-circle-check'
        });        
    } 
    else if (quickDrawerMode.value === 'edit') {
        toast.add({
            title: props.__.resources.users.messages.updated.title,
            description: `${props.__.resources.users.messages.updated.description}`.replace(':user', selectedQuickUser.value?.username ?? 'unknown'),
            color: 'success',
            icon: 'i-lucide-circle-check'
        })
    }
    
    closeQuickDrawer();
}

function openQuickCreate() {
    quickDrawerMode.value = 'create';
    selectedQuickUser.value = null;
    quickDrawerOpen.value = true;

    nextTick(() => {
        quickCreateFormRef.value?.open();
    });
}

function getRowEditActionsDropdownItems(row) {
    const user = row.original;

    if (user.id === currentUserId.value) {
        return [
            {
                label: props.__.user_actions.fast_edit,
                icon: 'i-lucide-panel-right-open',
                onSelect() {
                    openQuickEdit(row);
                },
            },
        ];
    }

    return [
        [
            {
                label: props.__.user_actions.fast_edit,
                icon: 'i-lucide-panel-right-open',
                onSelect() {
                    openQuickEdit(row);
                },
            },
        ],
        [
            {
                label: props.__.user_actions.reset_password,
                icon: 'i-lucide-rotate-ccw-key',
                color: 'warning',
            },
            {
                label: props.__.user_actions.revoke_sessions,
                icon: 'i-lucide-shield-off',
                color: 'warning',
            },
        ],
    ];
}

// End quick edit drawer

// Delete action
function onDeleteUser(user: any) {
    const identityModalConfig = {
        title: props.__.resources.users.confirmations.delete.title,
        description: `${props.__.resources.users.confirmations.delete.description}`.replace(':user', selectedQuickUser.value?.username ?? 'unknown'),
    }
    if (user.is_deleted) {
        openIdentityModal({
            ...identityModalConfig,
            onConfirmed: () => {
                router.delete(`/users/${user.id}/force-delete`, {
                    preserveState: true,
                    preserveScroll: true,
                    only: ['users', 'filters']
                });
            }
        });
        return;
    }
    
    requestIdentityConfirmation(() => {
        router.delete(`/users/${user.id}/delete`, {
            preserveState: true,
            preserveScroll: true,
            only: ['users', 'filters'],
            onSuccess: () => {
                toast.add({
                    title: props.__.resources.users.messages.deleted.title,
                    description: `${props.__.resources.users.messages.deleted.description}`.replace(':user', user.username ?? 'unknown').replace(':retention_days', page.props.app.config.retention_days),
                    color: 'success',
                })
            },
            onError: (errors: Object)=> {
                if (errors.toast) {
                    const error = JSON.parse(errors.toast);

                    toast.add({
                        title: error.title,
                        description: error.description,
                        color: 'error',
                    });
                }
            }
        });
        return;
    });
    
}
// End Delete action

// Todo: Move logic below in another page 'edit'
type UserModalMode = 'create' | 'edit' | null;

const userModalOpen = ref(false);
const userModalMode = ref<UserModalMode>(null);

// Forms
const userDataForm = useForm({
    username: '',
    email: '',
    first_name: '',
    last_name: '',
    job_title: '',
    phone: {
        extension: '',
        number: ''
    }
});
const userSecurityForm = useForm({
    new_password: '',
    confirm_password: '',
});
const userAdminForm = useForm({
    active: true,
    admin_notes: '',
    suspended_until: null,
    suspension_reason:  null,
    expire_at:  null,
    roles: [],
    forced_actions: [],
    repo_scope: [],
    host_scope: [],
    host_scope_mode: ''
});
const userSettingsForm = useForm({
    preferred_locale: '',
    preferred_timezone: '',
    preferred_start_page: '',
})
// Forms snapshots
const initialUserDataForm = ref({});
const initialUserSecurityForm = ref({});
const initialUserAdminForm = ref({});
const initialUserSettingsForm = ref({});

const isCreateModal = computed(() => userModalMode.value === 'create');
const isEditModal = computed(() => userModalMode.value === 'edit');

const userModalTitle = computed(() => {
    return isEditModal.value
        ? props.__.user_actions.edit ?? 'Edit User'
        : props.__.user_actions.create ?? 'Create User';
});

const currentTabProcessing = computed(() => {
    if (activeUserModalTab.value === 'data') return userDataForm.processing;
    if (activeUserModalTab.value === 'security') return userSecurityForm.processing;
    if (activeUserModalTab.value === 'admin') return userAdminForm.processing;
    
    return false;
})

const userFormLoading = ref(false);

function fillDataUserForm(user = null) {
    if (!user) {
        return {
            username: '',
            email: '',
            first_name: '',
            last_name: '',
            job_title: '',
            phone: {
                extension: '',
                number: '',
            },
        }
    }
    return {
        username: user.username,
        email: user.email,
        first_name: user.first_name ?? '',
        last_name: user.last_name ?? '',
        job_title: user.job_title ?? '',
        phone: {
            extension: user.phone.extension ?? '',
            number: user.phone.number ?? '',
        }
    }
}
function fillSecurityUserForm() {
    return {
        new_password: '',
        confirm_password: ''
    }
}
function fillAdminUserForm(user = null) {
    if (!user) {
        return {
            active: true,
            admin_notes: '',
            suspended_until: calendarDateFromTimestamp(null),
            suspended_reason: null,
            expire_at: calendarDateFromTimestamp(null),
            roles: [],
            forced_actions: [],
        }
    }
    return {
        active: user.active ?? true,
        admin_notes: user.admin_notes ?? '',
        suspended_until: calendarDateFromTimestamp(user.suspended_until) ?? calendarDateFromTimestamp(null),
        suspension_reason: user.suspension_reason ?? null,
        expire_at:  calendarDateFromTimestamp(user.expire_at) ?? calendarDateFromTimestamp(null),
        roles: user.roles ?? [],
        forced_actions: user.forced_actions ?? [],
    }
}
function fillSettingsUserForm(user = null) {
    if (!user) {
        return {
            preferred_locale: 'en_US',
            preferred_timezone: 'UTC',
            preferred_start_page: 'dashboard',
        }
    }
    return {
        preferred_locale: user.preferred_locale ?? 'en_US',
        preferred_timezone: user.preferred_timezone ?? 'UTC',
        preferred_start_page: user.preferred_start_page ?? 'dashboard',
    }
}

function openCreateUserModal() {
    userModalMode.value = 'create';
    userEditing.value = false;
    
    userDataForm.defaults(fillDataUserForm());
    userDataForm.reset();
    userDataForm.clearErrors();
    
    userSettingsForm.defaults(fillSettingsUserForm());
    userSettingsForm.reset();
    userSettingsForm.clearErrors();
    
    userSecurityForm.defaults(fillSecurityUserForm());
    userSecurityForm.reset();
    userSecurityForm.clearErrors();
    
    userAdminForm.defaults(fillAdminUserForm());
    userAdminForm.reset();
    userAdminForm.clearErrors();
    
    activeUserModalTab.value = 'data';
    snapshotForms();
    userModalOpen.value = true;
}
function openEditUserModal(user) {
    userModalMode.value = 'edit';
    userEditing.value = user.id;
    
    userDataForm.defaults(fillDataUserForm(user));
    userDataForm.reset();
    userDataForm.clearErrors();

    userSettingsForm.defaults(fillSettingsUserForm(user));
    userSettingsForm.reset();
    userSettingsForm.clearErrors();
    
    userSecurityForm.defaults(fillSecurityUserForm());
    userSecurityForm.reset();
    userSecurityForm.clearErrors();
    
    userAdminForm.defaults(fillAdminUserForm(user));
    userAdminForm.reset();
    userAdminForm.clearErrors();
    
    activeUserModalTab.value = 'data';
    snapshotForms();
    userModalOpen.value = true;
}
function closeUserModal() {
    userModalMode.value = null;
    
    userDataForm.defaults(fillDataUserForm());
    userSettingsForm.defaults(fillSettingsUserForm());
    userSecurityForm.defaults(fillSecurityUserForm());
    userAdminForm.defaults(fillAdminUserForm());
    
    userModalOpen.value = false;
}

function submitUserModal(userId: number | string | null = null) {
    if (!isCurrentTabValid.value) return;
    
    if (isCreateModal.value) {
        submitCreateUser();
        return;
    }
    
    if (!userEditing.value) return;
    
    if (activeUserModalTab.value === 'data') {
        userDataForm.patch(`/users/${userEditing.value}/data`, {
            only: ['users', 'filters', 'flash'],
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                userDataForm.defaults();
            }
        })
        
        return;
    }
    
    if (activeUserModalTab.value === 'security') {
        requestIdentityConfirmation(() => {
            userSecurityForm.patch(`users/${userEditing.value}/security`, {
                only: ['users', 'filters', 'flash'],
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    userSecurityForm.reset();
                    userSecurityForm.defaults();
                }
            });
        });
        
        return;
    }
    
    if (activeUserModalTab.value === 'admin') {
        requestIdentityConfirmation(() => {
            userAdminForm.patch(`users/${userEditing.value}/admin`, {
                only: ['users', 'filters', 'flash'],
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    userAdminForm.defaults();
                }
            })
        })
    }
}
function handleUserModalOpenChange(value: boolean) {
    if (value) {
        userModalOpen.value = true;
        return;
    }
    
    requestCloseUserModal();
}

function submitCreateUser() {
    const payload = {
        ...userDataForm.data(),
        ...userSecurityForm.data(),
        ...userAdminForm.data(),
    }
    
    router.post('/users', payload, {
        only: ['users', 'filters', 'flash'],
        preserveScroll: true,
        preserveState: true,
        onSuccess: closeUserModal
    })
}

function snapshotForms() {
    initialUserDataForm.value = clone({
        username: userDataForm.username,
        email: userDataForm.email,
        first_name: userDataForm.first_name,
        last_name: userDataForm.last_name,
        job_title: userDataForm.job_title,
        phone: userDataForm.phone,
    });

    initialUserSecurityForm.value = clone({
        new_password: userSecurityForm.new_password,
        confirm_password: userSecurityForm.confirm_password,
    });

    initialUserAdminForm.value = clone({
        active: userAdminForm.active,
        admin_notes: userAdminForm.admin_notes,
        suspended_until: userAdminForm.suspended_until,
        suspension_reason: userAdminForm.suspension_reason,
        expire_at: userAdminForm.expire_at,
        roles: userAdminForm.roles,
        forced_actions: userAdminForm.forced_actions,
        repo_scope: userAdminForm.repo_scope,
        host_scope: userAdminForm.host_scope,
        host_scope_mode: userAdminForm.host_scope_mode,
    });

    initialUserSettingsForm.value = clone({
        preferred_locale: userSettingsForm.preferred_locale,
        preferred_timezone: userSettingsForm.preferred_timezone,
        preferred_start_page: userSettingsForm.preferred_start_page,
    });
}

// --- Accordions ---
const userDataAccordionActive = ref('0');
const userDataAccordionItems = [
    {
        label: props.__.forms.users.sections.login,
        slot: 'login' as const,
        icon: 'i-lucide-log-in'
    },
    {
        label: props.__.forms.users.sections.data,
        slot: 'personal-data' as const,
        icon: 'i-lucide-clipboard-pen-line'
    },
    {
        label: props.__.forms.users.sections.settings,
        slot: 'settings' as const,
        icon: 'i-lucide-settings'
    }
];

const userAdminAccordionActive = ref('0');
const userAdminAccordionItems = [
    {
        label: props.__.forms.users.sections.access,
        slot: 'access' as const,
        icon: 'i-lucide-key-round'
    },
    {
        label: props.__.forms.users.sections.notes,
        slot: 'notes' as const,
        icon: 'i-lucide-notepad-text'
    },
    {
        label: props.__.forms.users.sections.constraints,
        slot: 'constraints' as const,
        icon: 'i-lucide-shield-alert',
    }
]


// --- Modal tabs ---
const tabsItems = computed(() => {
    const mode = userModalMode.value;
    const verb = props.__.user_actions.verbs[mode];
    
    return [
        {
            value: 'data',
            label: props.__.tabs.data.title,
            description: `${verb ?? ''} ${props.__.tabs.data.description}`,
            icon: 'i-lucide-user',
            slot: 'data' as const,
            dirty: userDataForm.isDirty
        },
        {
            value: 'security',
            label: props.__.tabs.security.title,
            description: `${verb ?? ''} ${props.__.tabs.security.description}`,
            icon:   'i-lucide-lock-keyhole',
            slot: 'security' as const,
            dirty: userSecurityForm.isDirty
        },
        {
            value: 'admin',
            label: props.__.tabs.admin.title,
            description: props.__.tabs.admin.description,
            icon: 'i-lucide-shield-check',
            slot: 'admin' as const,
            dirty: userAdminForm.isDirty
        },
    ]
});

// --- Modal confirmation unsaved ---
const confirmDiscardModalOpen = ref(false);
const initialUserModalSnapshot = ref('');

function createUserModalSnapshot() {
    return JSON.stringify({
        data: userDataForm.data(),
        security: userSecurityForm.data(),
        admin: userAdminForm.data(),
    })
}

const hasUnsavedModalChanges = computed(() => {
    return userDataForm.isDirty
        || userSecurityForm.isDirty
        || userAdminForm.isDirty
        || userSettingsForm.isDirty
})

function saveInitialUserModalSnapshot() {
    initialUserModalSnapshot.value = createUserModalSnapshot();
}
function requestCloseUserModal() {
    if (hasUnsavedModalChanges.value){
        confirmDiscardModalOpen.value = true;
        userModalOpen.value = true
        return;
    }
    
    closeUserModal();
}
function confirmCloseUserModal() {
    confirmDiscardModalOpen.value = false;
    closeUserModal();
}
function cancelCloseUserModal() {
    confirmDiscardModalOpen.value = false;
    userModalOpen.value = true;
}

// === Form fields ===
// --- Input password ---
const showPassword = ref(false);
const showConfirmation = ref(false);

function checkStrength(str: string) {
    const requirements = [
        { regex : /.{8,}/, text: props.__.resources.users.validation.password.length },
        { regex : /\d/, text: props.__.resources.users.validation.password.number },
        { regex : /[a-z]/, text: props.__.resources.users.validation.password.lowercase },
        { regex : /[A-Z]/, text: props.__.resources.users.validation.password.uppercase },
        { regex : /[!"#$%&'()*+,\-./:;<=>?@[\\\]^_`{|}~]/ , text: props.__.resources.users.validation.password.symbol }
    ]
    
    return requirements.map(req => ({met: req.regex.test(str), text: req.text}));
}
function checkSame(str1: string, str2: string): boolean {
    return str1 === str2 && str1 !== '' && str2 !== '';
} 
const strengthPassword = computed(() => checkStrength(userSecurityForm.new_password ?? ''));
const score = computed(() => strengthPassword.value.filter(req => req.met).length);
const samePassword = computed(() => checkSame(userSecurityForm.new_password, userSecurityForm.confirm_password ?? ''));

const colorPassword = computed(() => {
    if (score.value === 0) return 'neutral';
    if (score.value <= 1) return 'error';
    if (score.value <= 2) return 'warning';
    if (score.value <= 4) return 'warning';
    return 'success';
});
const colorConfirmation = computed(() => {
    if (!samePassword.value) return 'error';
    return 'success';
})
const confirmationText = computed(() => {
    if (!samePassword.value) return 'Password don\'t match';
    return 'Password match!';
})
const validatorText = computed(() => {
    if (score.value === 0) return 'Enter a password';
    if (score.value <= 2) return 'Weak password';
    if (score.value <= 4 ) return 'Medium password';
    return 'Strong password'
})

// --- Input active ---
const isAccountExpired = computed(() => {
    const expireAt = userAdminForm.expire_at;
    const suspended_until = userAdminForm.suspended_until;
    const suspension_reason = userAdminForm.suspension_reason;
    
    if (expireAt) return expireAt.compare(today(getLocalTimeZone())) < 0;
    if (suspended_until) return suspended_until.compare(today(getLocalTimeZone())) > 0;
    return !!suspension_reason;
})

watch(isAccountExpired, expired => {
    if (expired) userAdminForm.active = false;
})

// --- Input date ---

function calendarDateFromTimestamp(timestamp: number | null | undefined) {
    if (!timestamp) {
        return null
    }

    return toCalendarDate(
        fromDate(new Date(timestamp), getLocalTimeZone())
    )
}

// --- Input phone ---
const countryOpen = ref(false);
const countrySearch = ref('');

const selectedCountry = computed(() => {
    return countries.find(country => {
        return String(country.extension) === String(quickEditForm.phone.extension);
    }) ?? null;
});

const filteredCountries = computed(() => {
    const search = countrySearch.value.trim().toLowerCase();
    
    if (!search) return countries;
    
    return countries.filter(country => {
        return country.name.toLowerCase().includes(search)
            || country.extension.includes(search)
            || `+${country.extension}`.includes(search);
    })
})

function selectCountry(country) {
    quickEditForm.phone.extension = country.extension;
    countryOpen.value = false;
    countrySearch.value = '';
}
function updateNumber(value) {
    quickEditForm.phone.number = String(value) ?? '';
}

// --- Role select menu ---
const roles = ref([]);
const rolesLoading = ref(false);

async function fetchRoles() {
    rolesLoading.value = true;
    
    try {
        const response = await fetch('/roles/select-menu', {
            method: 'POST',
            headers: csrfHeaders()
        });
        
        
        const payload = await response.json();
        
        roles.value = (payload ?? []).map((role) => ({
            label: `${role.name}`,
            project: role.project?.name ? role.project.name : 'Global',
            value: role.id,
        }))
    } finally {
        rolesLoading.value = false;
    }
}

onMounted(async ()=> {
    await fetchRoles();
});

// --- Action dropdown ---
// Invite user
const inviteModalOpen = ref(false);
const inviteMode = ref('email');

const inviteForm = useForm({
    username: '',
    email: '',
    roles: [],
    forced_actions: [],
    admin_notes: ''
});

function openInviteModal(mode: string) {
    inviteModalOpen.value = true;
    inviteMode.value = mode;
    
    inviteForm.reset();
    inviteForm.clearErrors();
}
function closeInviteModal() {
    inviteModalOpen.value = false;
    
    inviteForm.reset();
    inviteForm.clearErrors();
}
function submitInviteForm() {
    router.post('/users/invite', {
        ...inviteForm.data(),
        mode: inviteMode.value,
    }, {
        only: ['users', 'invitations', 'filters', 'flash'],
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            closeInviteModal();
            toast.add({
                title: props.__.resources.invitations.messages.sent,
                color: 'success'
            })
        }
    })
}

const globalActionsDropdownItems = [
    {
        label: props.__.user_actions.invite,
        icon: 'i-lucide-send',
        children: [
            {
                label: props.__.user_actions.invite_by_email,
                icon: 'i-lucide-mail-plus',
                onSelect() {
                    openInviteModal('email');
                }
            },
            {
                label: props.__.user_actions.invite_by_link,
                icon: 'i-lucide-link',
                onSelect() {
                    openInviteModal('link');
                }
            }
        ]
    },
    {
        label: props.__.user_actions.create,
        icon: 'i-lucide-user-plus',
        onSelect() {
            router.get('/users/create');
        }
    },
]
// End invite user

// Context menu
function getRowContextMenuItems(row) {
    const user = row.original;

    const phone = user.phone?.extension && user.phone?.phone
        ? `+${user.phone.extension}${user.phone.phone}`
        : null;

    const roles = Array.isArray(user.roles) ? user.roles : [];
    const rolesLabel = roles.length > 0
        ? roles.map((role) => role.name).join(', ')
        : props.__.table.context_menu.roles.empty;

    return [
        {
            type: 'label',
            label: props.__.context_menu.sections.copy,
        },
        {
            label: props.__.context_menu.copy_email,
            icon: 'i-lucide-mail',
            ui: {
                item: 'cursor-pointer rounded-md',
            },
            onSelect() {
                copy(user.email);

                toast.add({
                    title: props.__.toasts.copy_email.title,
                    color: 'success',
                    icon: 'i-lucide-circle-check',
                });
            },
        },
        {
            label: props.__.context_menu.copy_identifier,
            icon: 'i-lucide-hash',
            ui: {
                item: 'cursor-pointer rounded-md',
            },
            onSelect() {
                copy(String(user.id));

                toast.add({
                    title: props.__.toasts.copy_identifier.title,
                    color: 'success',
                    icon: 'i-lucide-circle-check',
                });
            },
        },
        phone && {
            label: props.__.context_menu.copy_phone,
            icon: 'i-lucide-phone',
            ui: {
                item: 'cursor-pointer rounded-md',
            },
            onSelect() {
                copy(phone);

                toast.add({
                    title: props.__.toasts.copy_phone.title,
                    color: 'success',
                    icon: 'i-lucide-circle-check',
                });
            },
        },
        {
            type: 'separator',
        },
        {
            type: 'label',
            label: props.__.context_menu.sections.inspect,
        },
        user.permissions.view_sensitive && {
            label: props.__.context_menu.view_roles,
            icon: 'i-lucide-shield',
            ui: {
                item: 'cursor-pointer rounded-md',
            },
            onSelect() {
                openRolesModal(user);
            },
        },
        {
            label: props.__.context_menu.view_logs,
            icon: 'i-lucide-scroll-text',
            ui: {
                item: 'cursor-pointer rounded-md',
            },
            onSelect() {
                router.get('/logs', {
                    user_id: user.id,
                }, {
                    preserveScroll: true,
                });
            },
        },
    ].filter(Boolean);
}
// End Context menu

// Invitations actions
function getInvitationActionRowItems(row) {
    const arr = [];
    
    if (row.original.status === 'pending') {
        arr.push([
            {
                label: props.__.invitation_actions.resend_email,
                icon: 'i-lucide-send',
                color: 'info',
                onSelect() {
                    resendInvitationMail(row);
                }
            }
        ])
    }
    if (row.original.status === 'pending' || row.original.status === 'expired') {
        arr.push([
            {
                label: props.__.invitation_actions.renew_expiration,
                icon: 'i-lucide-rotate-ccw-key',
                color: 'warning',
                kbds: ['meta', 'd'],
                onSelect() {
                    openRenewModal(row);
                }
            },
            {
                label: props.__.invitation_actions.remove_expiration,
                icon: 'i-lucide-timer-off',
                color: 'error',
                onSelect() {
                    removeInvitationExpiration(row);
                }
            }
        ])
    }
    
    if (row.original.is_deleted) {
        arr.push([
            {
                label: props.__.invitation_actions.restore,
                icon: 'i-lucide-rotate-ccw',
                color: 'success',
                onSelect() {
                    restoreInvite(row)
                }
            }
        ])
    }
    
    arr.push([
        {
            label: row.original.is_deleted ? props.__.invitation_actions.force_delete : props.__.invitation_actions.delete,
            icon:   'i-lucide-trash',
            color: 'error',
            onSelect() {
                if (row.original.is_deleted) return forceDeleteInvite(row)
                else return deleteInvite(row);
            }
        }
    ])
    
    return arr;
}
function getInvitationContextMenuItems(row) {
    if (row.original.status === 'revoked') {
        return [];
    }
    return [
        {
            label: props.__.context_menu.copy_link,
            icon: 'i-lucide-copy',
            color: 'neutral',
            onSelect() {
                copyInvitationLink(row.original);
            } 
        }
    ]
}

// Shortcuts
// defineShortcuts(extractShortcuts(getInvitationContextMenuItems()))

async function copyInvitationLink(invitation: any) {
    const response = await fetch(`/users/invitations/${invitation.id}/link`, {
        headers: csrfHeaders(),
    });
    
    if (!response.ok) {
        toast.add({
            title: props.__.resources.invitations.messages.link_unavailable,
            color: 'error',
            icon: 'i-lucide-circle-x',
        });
        return;
    }
    
    const data = await response.json();
    
    await copy(data.link);
    
    toast.add({
        title: props.__.toasts.invite_link_copy.title,
        color: 'success',
        icon: 'i-lucide-copy-check',
    })
}

// Invitations resend invite
function resendInvitationMail(row) {
    const config = {
        ...discardModalConfig.value,
        title: props.__.global.modals.confirm.title,
        description: `${props.__.global.modals.confirm.description}`.replace(':action', `${props.__.invitation_actions.resend_email}`.toLowerCase()),
        discardLabel: props.__.invitation_actions.resend_email,
        keepLabel: props.__.actions.cancel,
        discardColor: 'warning'
    };
    
    requestDiscardModal(()=> {
        router.post(`/users/invitations/${row.original.id}/send-email`, {}, {
            only: ['flash'],
            preserveScroll: true,
            preserveState: true,
        })
    }, config);
}

// Invitations renew expiration
const expirationModalOpen = ref(false);
const invitationEditing = ref(null);
const renewInvitationExpirationForm = useForm({
    renew_amount: 7, // in days
    expiration_date: null
})

function openRenewModal(row: any) {
    expirationModalOpen.value = true;
    invitationEditing.value = row.original.id;
}
function closeRenewModal() {
    expirationModalOpen.value = false;
    invitationEditing.value = null;
}
function submitRenewExpirationForm() {
    const config = {
        ...discardModalConfig.value,
        title: props.__.global.modals.confirm.title,
        description: `${props.__.global.modals.confirm.description}`.replace(':action', `${props.__.invitation_actions.renew_expiration}`.toLowerCase()),
        discardLabel: props.__.invitation_actions.renew_expiration,
        keepLabel: props.__.actions.cancel,
        discardColor: 'warning'
    }
    
    requestDiscardModal(() => {
        renewInvitationExpirationForm.transform((data)=> ({
            ...data,
            expiration_date: serializeDate(data.expiration_date)
        })).post(`/users/invitations/${invitationEditing.value}/renew`, {
            only: ['invitations', 'flash'],
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                closeRenewModal();
            }
        })
    }, config)
}

// Invitations remove expiration
function removeInvitationExpiration(row) {
    const config = {
        ...discardModalConfig.value,
        title: props.__.global.modals.confirm.title,
        description: `${props.__.global.modals.confirm.description}`.replace(':action', `${props.__.invitation_actions.renew_expiration}`.toLowerCase()),
        discardLabel: props.__.invitation_actions.renew_expiration,
        keepLabel: props.__.actions.cancel,
        discardColor: 'warning'
    };
    
    requestDiscardModal(()=> {
        router.post(`/users/invitations/${row.original.id}/remove-expiration`, {}, {
            only: ['invitations', 'flash'],
            preserveState: true,
            preserveScroll: true,
        })
    }, config)
}

// Invitations reactive
function reactiveInvite(row) {
    requestIdentityConfirmation(()=> {
        router.post(`/users/invitations/${row.original.id}/reactive`, {}, {
            only: ['invitations', 'flash', 'filters'],
            preserveState: true,
            preserveScroll: true,
        });
    });
}

// Invitations revoke
function revokeInvite(row) {
    requestIdentityConfirmation(()=> {
        router.post(`/users/invitations/${row.original.id}/revoke`, {}, {
            only: ['invitations', 'filters', 'flash'],
            preserveScroll: true,
            preserveState: true,
        });
    });
}

// Invitations restore
function restoreInvite(row) {
    router.post(`/users/invitations/${row.original.id}/restore`, {}, {
        only: ['invitations', 'flash'],
        preserveScroll: true,
        preserveState: true,
    })
}

// Invitations delete
function deleteInvite(row) {
    requestIdentityConfirmation(()=> {
        router.delete(`/users/invitations/${row.original.id}/delete`, {
            only: ['invitations', 'flash'],
            preserveScroll: true,
            preserveState: true,
        })
    })
}

// Invitations force delete 
function forceDeleteInvite(row) {
    requestIdentityConfirmation(()=> {
        router.delete(`/users/invitations/${row.original.id}/force-delete`, {
            only: ['invitations', 'flash'],
            preserveScroll: true,
            preserveState: true,
        })
    })
}
</script>

<template>
    <!--  Invite modals  -->
    <UModal v-model:open="inviteModalOpen" class="max-w-9/10 w-9/10 md:w-7/10 lg:w-6/10">
        <template #header>
            <h2 class="text-lg font-semibold">
                {{ props.__.modals.invite.title }} {{ props.__.modals.invite.mode[inviteMode]}}
            </h2>
        </template>
        
        <template #body>
            <p class="text-muted">
                {{ props.__.modals.discard.description }}
            </p>
            
            <UForm :state="inviteForm" class="flex flex-col gap-4 mt-5"
                   @submit.prevent="submitInviteForm"
            >
                <div class="flex gap-4 justify-between">
                    <!-- Email input -->
                    <UFormField
                        :required="inviteMode === 'email'"
                        class="w-full"
                        :label="props.__.resources.users.fields.email"
                    >
                        <UInput v-model="inviteForm.email" placeholder=" " :ui="{ base: 'peer'}" required class="w-full"/>
                    </UFormField>

                    <!-- Username input -->
                    <UFormField
                        :help="props.__.forms.users.help.username_optional"
                        :hint="props.__.forms.users.hint.optional"
                        name="username"
                        class="w-full"
                        :label="props.__.resources.users.fields.username"
                    >

                        <UInput
                            v-model="inviteForm.username"
                            placeholder=" "
                            :ui="{ base: 'peer' }"
                            required
                            class="w-full"
                        />
                    </UFormField>
                </div>
                
                <div class="flex flex-col gap-4 justify-between">
                    <!-- Forced actions input -->
                    <UFormField
                        :label="props.__.resources.users.fields.forced_actions"
                        :hint="props.__.forms.users.hint.optional"
                    >
                        <UCheckboxGroup
                            indicator="start"
                            variant="table"
                            v-model="inviteForm.forced_actions"
                            :items="availableForcedActions"
                            value-key="value"
                        >
                            <template #label="{ item }">
                                {{ item.label.title }}
                            </template>
                            <template #description="{item}">
                                {{ item.label.description }}
                            </template>
                        </UCheckboxGroup>
                    </UFormField>

                    <!-- Roles input -->
                    <UFormField
                        required
                        label="Roles"
                        class="w-full"
                    >
                        <USelectMenu
                            v-model="inviteForm.roles"
                            :filter-fields="['label', 'project']"
                            :items="roles"
                            :loading="rolesLoading"
                            multiple
                            class="w-full"
                        >
                            <template #item-label="{ item }">
                                {{ item.label }}

                                <span class="text-muted">- {{ item.project }}</span>
                            </template>
                        </USelectMenu>
                    </UFormField>
                </div>
            </UForm>
        </template>
        
        <template #footer>
            <div class="flex justify-end gap-2 w-full">
                <UButton
                    color="neutral"
                    variant="ghost"
                    :label="props.__.actions.close"
                    type="button"
                    @click="closeInviteModal"
                    class="cursor-pointer"
                />

                <UButton
                    type="submit"
                    :loading="inviteForm.processing"
                    :label="props.__.actions.save"
                    @click="submitInviteForm"
                    class="cursor-pointer"
                />
            </div>
        </template>
    </UModal>
    <UModal v-model:open="expirationModalOpen" class="max-w-9/10 w-9/10 md:w-7/10 lg:w-6/10"
        :title="props.__.forms.invitations.renew_expiration.title"
        :description="props.__.forms.invitations.renew_expiration.description"
    >
        <template #body>
            <UForm :state="renewInvitationExpirationForm" class="flex gap-4 justify-between">
                <UFormField
                    name="renew_amount"
                    :label="props.__.forms.invitations.fields.labels.renew_amount"
                    :help="props.__.forms.invitations.fields.helps.renew_amount"
                    :error="renewInvitationExpirationForm.errors.renew_amount"
                    class="w-4/10 h-full"
                >
                    <UInputNumber 
                        v-model="renewInvitationExpirationForm.renew_amount" 
                        :min="1" 
                        :max="30" 
                        :format-options="{
                            style: 'unit',
                            unit: 'day',
                            unitDisplay: 'long'
                        }"/>
                </UFormField>
                
                <p class="text-muted text-sm w-1/10 justify-center flex items-center">OU</p>
                
                <UFormField
                    name="expiration_date"
                    :label="props.__.forms.invitations.fields.labels.expiration_date"
                    :help="props.__.forms.invitations.fields.helps.expiration_date"
                    :error="renewInvitationExpirationForm.errors.expiration_date"
                    class="w-4/10 flex flex-col justify-stretch items-end"
                >
                    <FgInputDatePicker 
                        :__="props.__.global.forms"
                        v-model="renewInvitationExpirationForm.expiration_date"
                    />
                </UFormField>
            </UForm>
        </template>
        
        <template #footer>
            <div class="flex justify-end gap-2 w-full">
                <UButton
                    color="neutral"
                    variant="ghost"
                    :label="props.__.actions.close"
                    type="button"
                    @click="closeRenewModal"
                    class="cursor-pointer"
                />

                <UButton
                    type="submit"
                    :loading="renewInvitationExpirationForm.processing"
                    :label="props.__.forms.invitations.renew_expiration.submit"
                    @click="submitRenewExpirationForm"
                    class="cursor-pointer"
                />
            </div>
        </template>
    </UModal>
    <!-- Roles modal -->
    <UModal v-model:open="rolesModalOpen">
        <template #header>
            <div>
                <h2 class="text-lg font-semibold">
                    Rôles de {{ rolesModalUser?.username }}
                </h2>
                <p class="text-sm text-muted">
                    {{ rolesModalUser?.roles?.length ?? 0 }} rôle(s) assigné(s)
                </p>
            </div>
        </template>

        <template #body>
            <div
                v-if="rolesModalUser?.roles?.length"
                class="flex flex-col gap-2"
            >
                <div
                    v-for="role in rolesModalUser.roles"
                    :key="role.id"
                    class="flex items-center justify-between rounded-md border border-default px-3 py-2"
                >
                    <div class="min-w-0">
                        <p class="font-medium truncate">
                            {{ role.name }}
                        </p>
                        <p v-if="role.project?.name" class="text-sm text-muted truncate">
                            {{ role.project.name }}
                        </p>
                    </div>

                    <UBadge color="neutral" variant="soft">
                        Rôle
                    </UBadge>
                </div>
            </div>

            <UAlert
                v-else
                color="neutral"
                variant="soft"
                icon="i-lucide-shield"
                title="Aucun rôle assigné"
            />
        </template>

        <template #footer>
            <div class="flex justify-end w-full">
                <UButton
                    color="neutral"
                    variant="ghost"
                    label="Fermer"
                    @click="rolesModalOpen = false"
                />
            </div>
        </template>
    </UModal>
    <!-- Bulk actions modal -->
    <FgConfirmBulkActionModal
        v-model:open="bulkActionModalOpen"
        :methods="page.props.auth?.user?.two_factor_methods ?? []"
        :selected-method="page.props.auth?.user?.primary_second_factor ?? null"
        :masked-email="page.props.auth?.user?.masked_email ?? ''"
        :identity-config="props.__.global.confirm_identity"
        :bulk-config="bulkActionConfig"
        @confirmed="confirmBulkAction"
    />
    <!-- User create/edit modal -->
    <UModal
        scrollable
        v-model:open="userModalOpen"
        @update:open="handleUserModalOpenChange"
        class="w-7/10 max-w-235 max-h-[calc(100vh-8rem)] overflow-y-scroll relative"
    >
        <template #header>
            <h2 class="text-lg font-semibold">
                {{ userModalTitle }}
            </h2>
        </template>
        <template #body>
            <UTabs v-model="activeUserModalTab" :items="tabsItems" :ui="{ trigger: 'cursor-pointer', label: 'overflow-visible' }">
                <template #default="{ item }">
                    <UChip color="warning" :show="item.dirty" :ui="{ base: 'ring-0 border-1'}">
                        <span class="px-1">{{ item.label }}</span>
                    </UChip>
                </template>
                
                <template #data="{item}">
                    <p class="text-muted">
                        {{ item.description }}
                    </p>
                    <hr class="text-white/30 mt-2">
                    <UForm :state="userDataForm" class="flex flex-col gap-4 mt-5" @submit="submitUserModal">
                        <UAccordion
                            v-model="userDataAccordionActive"
                            :items="userDataAccordionItems"
                            :ui="{ trigger: 'cursor-pointer rounded-md hover:bg-white/10 px-4 text-white data-[state=open]:text-primary-light data-[state=open]:bg-white/5', content: 'py-4' }"
                        >
                            <template #default="{item}">
                                <h3 class="text-lg">{{item.label}}</h3>
                            </template>
                            
                            <template #login="{item}">
                                <div class="flex gap-4 justify-between">
                                    <!-- Username input -->
                                    <UFormField
                                        required
                                        :help="props.__.forms.users.help.username"
                                        name="username"
                                        class="w-full"
                                    >
                                        <template #label>
                                            <span class="inline-flex items-center gap-1">
                                                <span>{{ props.__.resources.users.fields.username }}</span>
                                    
                                                <span
                                                    v-if="isFieldDirty(userDataForm, initialUserDataForm, 'username')"
                                                    class="inline-block size-1.5 shrink-0 rounded-full bg-warning"
                                                    title="Modification non sauvegardée"
                                                />
                                            </span>
                                        </template>

                                        <UInput
                                            v-model="userDataForm.username"
                                            placeholder=" "
                                            :ui="{ base: 'peer' }"
                                            required
                                            class="w-full"
                                            size="lg"
                                        />
                                    </UFormField>
                                    
                                    <!-- Email input -->
                                    <UFormField
                                        required
                                        class="w-full"
                                    >
                                        <template #label>
                                            <span class="inline-flex items-center gap-1">
                                                <span>{{ props.__.resources.users.fields.email }}</span>
                                    
                                                <span
                                                    v-if="isFieldDirty(userDataForm, initialUserDataForm, 'email')"
                                                    class="inline-block size-1.5 shrink-0 rounded-full bg-warning"
                                                    title="Modification non sauvegardée"
                                                />
                                            </span>
                                        </template>
                                        
                                        <UInput v-model="userDataForm.email" placeholder=" " :ui="{ base: 'peer'}" required class="w-full" size="lg" />
                                    </UFormField>
                                </div>
                            </template>
                            
                            <template #personal-data="{item}">
                                <!-- Name inputs -->
                                <div class="grid grid-cols-2 gap-4 py-2">
                                    <UFormField>
                                        <template #label>
                                            <span class="inline-flex items-center gap-1">
                                                <span>{{ props.__.resources.users.fields.last_name }}</span>
                                    
                                                <span
                                                    v-if="isFieldDirty(userDataForm, initialUserDataForm, 'last_name')"
                                                    class="inline-block size-1.5 shrink-0 rounded-full bg-warning"
                                                    title="Modification non sauvegardée"
                                                />
                                            </span>
                                        </template>
                                        
                                        <UInput v-model="userDataForm.last_name" placeholder=" " :ui="{ base: 'peer'}" class="w-full"/>
                                    </UFormField>
                                    
                                    <UFormField>
                                        <template #label>
                                            <span class="inline-flex items-center gap-1">
                                                <span>{{ props.__.resources.users.fields.first_name }}</span>
                                    
                                                <span
                                                    v-if="isFieldDirty(userDataForm, initialUserDataForm, 'first_name')"
                                                    class="inline-block size-1.5 shrink-0 rounded-full bg-warning"
                                                    title="Modification non sauvegardée"
                                                />
                                            </span>
                                        </template>
                                        
                                        <UInput v-model="userDataForm.first_name" placeholder=" " :ui="{ base: 'peer'}" class="w-full"/>
                                    </UFormField>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <!-- Job input -->
                                    <UFormField>
                                        <template #label>
                                            <span class="inline-flex items-center gap-1">
                                                <span>{{ props.__.resources.users.fields.job_title }}</span>
                                    
                                                <span
                                                    v-if="isFieldDirty(userDataForm, initialUserDataForm, 'job_title')"
                                                    class="inline-block size-1.5 shrink-0 rounded-full bg-warning"
                                                    title="Modification non sauvegardée"
                                                />
                                            </span>
                                        </template>
                                        
                                        <UInput v-model="userDataForm.job_title" label="Poste" placeholder=" " :ui="{ base: 'peer'}" class="w-full" />
                                    </UFormField>

                                    <!-- Phone Input -->
                                    <UFormField
                                        class="w-full"
                                    >
                                        <template #label>
                                            <span class="inline-flex items-center gap-1">
                                                <span>{{ props.__.resources.users.fields.phone.label }}</span>
                                    
                                                <span
                                                    v-if="isFieldDirty(userDataForm, initialUserDataForm, 'phone')"
                                                    class="inline-block size-1.5 shrink-0 rounded-full bg-warning"
                                                    title="Modification non sauvegardée"
                                                />
                                            </span>
                                        </template>
                                        
                                        <UFieldGroup class="w-full">
                                            <UPopover v-model:open="countryOpen">
                                                <UButton
                                                    type="button"
                                                    color="neutral"
                                                    variant="outline"
                                                    class="h-full w-1/4 justify-between rounded-none border-0 bg-transparent px-3 rounded-l-md"
                                                    trailing-icon="i-lucide-chevron-down"
                                                >
                                            <span class="flex items-center gap-2">
                                                <span v-if="selectedCountry">{{ selectedCountry.flag }}</span>
                                                <span>{{ userDataForm.phone.extension ? `+${userDataForm.phone.extension}` : '+...' }}</span>
                                            </span>
                                                </UButton>

                                                <template #content>
                                                    <div class="w-80 space-y-2 p-2">
                                                        <UInput
                                                            :size="size"
                                                            v-model="countrySearch"
                                                            placeholder="Rechercher un pays ou indicatif..."
                                                            icon="i-lucide-search"
                                                            autofocus
                                                            class="w-full"
                                                        />

                                                        <div class="max-h-72 overflow-y-auto">
                                                            <button
                                                                v-for="country in filteredCountries"
                                                                :key="`${country.name}-${country.extension}`"
                                                                type="button"
                                                                class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-left text-sm hover:bg-white/10"
                                                                @click="selectCountry(country)"
                                                            >
                                                                <span class="text-lg">{{ country.flag }}</span>

                                                                <span class="min-w-0 flex-1">
                                                            <span class="block truncate text-white">
                                                                {{ country.name }}
                                                            </span>
                                                            <span class="text-xs text-white/50">
                                                                +{{ country.extension }}
                                                            </span>
                                                        </span>
                                                            </button>

                                                            <p
                                                                v-if="filteredCountries.length === 0"
                                                                class="px-3 py-4 text-center text-sm text-white/50"
                                                            >
                                                                Aucun résultat
                                                            </p>
                                                        </div>
                                                    </div>
                                                </template>
                                            </UPopover>

                                            <div class="w-px bg-white/10" />

                                            <UInput
                                                :model-value="userDataForm.phone.number"
                                                variant="outline"
                                                v-maska="'##########'"
                                                type="tel"
                                                inputmode="tel"
                                                class="min-w-0 flex-1 h-full"
                                                :placeholder="props.__.resources.users.fields.phone.label"
                                                :ui="{
                                                    base: 'rounded-none border-0 bg-transparent h-full rounded-r-md',
                                                }"
                                                @update:model-value="updateNumber"
                                            />
                                        </UFieldGroup>
                                    </UFormField>
                                </div>
                            </template>
                            
                            <template #settings>
                                <div class="flex gap-4 items-center justify-center w-full">
                                    <!-- Locale input -->
                                    <UFormField class="w-full">
                                        <template #label>
                                            <span class="inline-flex items-center gap-1">
                                                <span>{{ props.__.resources.users.fields.preferred_locale }}</span>
                                    
                                                <span
                                                    v-if="isFieldDirty(userDataForm, initialUserSettingsForm, 'preferred_locale')"
                                                    class="inline-block size-1.5 shrink-0 rounded-full bg-warning"
                                                    title="Modification non sauvegardée"
                                                />
                                            </span>
                                        </template>
                                        
                                        <USelectMenu
                                            v-model="userSettingsForm.preferred_locale"
                                            :items="availableLocales"
                                            value-key="value"
                                            class="w-full"
                                        >
                                            <template #default="{modelValue}">
                                                {{ availableLocales.filter((locale)=> locale.value === modelValue)[0]?.icon || ''}}
                                                {{ availableLocales.filter((locale)=> locale.value === modelValue)[0]?.label || ''}}
                                            </template>
                                            <template #item="{item}">
                                                {{ item.icon }} {{ item.label }}
                                            </template>
                                        </USelectMenu>
                                    </UFormField>

                                    <!-- Timezone input -->
                                    <UFormField class="w-full">
                                        <template #label>
                                            <span class="inline-flex items-center gap-1">
                                                <span>{{ props.__.resources.users.fields.preferred_timezone }}</span>
                                    
                                                <span
                                                    v-if="isFieldDirty(userDataForm, initialUserSettingsForm, 'preferred_timezone')"
                                                    class="inline-block size-1.5 shrink-0 rounded-full bg-warning"
                                                    title="Modification non sauvegardée"
                                                />
                                            </span>
                                        </template>
                                        
                                        <USelectMenu
                                            v-model="userSettingsForm.preferred_timezone"
                                            :items="availableTimezones"
                                            value-key="value"
                                            class="w-full"
                                        />
                                    </UFormField>

                                    <!-- Preferred start page -->
                                    <UFormField class="w-full">
                                        <template #label>
                                            <span class="inline-flex items-center gap-1">
                                                <span>{{ props.__.resources.users.fields.preferred_start_page }}</span>
                                    
                                                <span
                                                    v-if="isFieldDirty(userDataForm, initialUserSettingsForm, 'preferred_start_page')"
                                                    class="inline-block size-1.5 shrink-0 rounded-full bg-warning"
                                                    title="Modification non sauvegardée"
                                                />
                                            </span>
                                        </template>
                                        
                                            <USelect
                                                v-model="userSettingsForm.preferred_start_page"
                                                :items="availableStartPages"
                                                class="w-full"
                                            />
                                        </UFormField>
                                </div>
                            </template>
                        </UAccordion>
                    </UForm>
                </template>
                
                <template #security="{item}">
                    <p class="text-muted">
                        {{ item.description }}
                    </p>
                    <hr class="text-white/30 mt-2">
                    
                    <UForm :state="userSecurityForm" class="flex flex-col gap-4 mt-5" @submit="submitUserModal">
                        <div>
                            <UFormField
                                :label="props.__.resources.users.fields.password"
                                required
                            >
                                <UInput
                                    v-model="userSecurityForm.new_password"
                                    :color="colorPassword"
                                    :type="showPassword ? 'text' : 'password'"
                                    :aria-invalid="score < 4"
                                    aria-describedby="password-strength"
                                    :ui="{ trailing: 'pe-1' }"
                                    class="w-full"
                                >
                                    <template #trailing>
                                        <UButton
                                            color="neutral"
                                            variant="link"
                                            size="sm"
                                            :icon="showPassword ? 'i-lucide-eye-off' : 'i-lucide-eye'"
                                            :aria-label="showPassword ? 'Hide password' : 'Show password'"
                                            :aria-pressed="showPassword"
                                            aria-controls="password"
                                            @click="showPassword = !showPassword"
                                        />
                                    </template>
                                </UInput>
                            </UFormField>
                            
                            <UProgress 
                                :color="colorPassword"
                                :indicator="validatorText"
                                :model-value="score"
                                :max="5"
                                size="sm"
                                class="mt-1"
                            />
                            
                            <p id="password-strength" class="text-sm font-medium">
                                {{ validatorText }}. <span v-if="score < 5">Must contain:</span>
                            </p>
                            
                            <ul class="space-y-1" aria-label="Password requirements">
                                <li
                                    v-for="(req, index) in strengthPassword"
                                    :key="index"
                                    class="flex items-center gap-0.5"
                                    :class="req.met ? 'text-success' : 'text-muted'"
                                >
                                    <UIcon :name="req.met ? 'i-lucide-circle-check' : 'i-lucide-circle-x' " class="size-4 shrink-0" />
                                    
                                    <span class="text-sm font-light">
                                        {{ req.text }}
                                        <span class="sr-only">
                                            {{ req.met ? ' - Requirement met : ' : ' - Requirement not met'}}
                                        </span>
                                    </span>
                                </li>
                            </ul>
                        </div>
                        
                        <div v-if="score >= 5">
                            <UFormField
                                :label="props.__.resources.users.fields.password_confirmation"
                            >
                                <UInput
                                    v-model="userSecurityForm.confirm_password"
                                    :color="colorConfirmation"
                                    :type="showConfirmation ? 'text' : 'password'"
                                    :aria-invalid="score < 4"
                                    aria-describedby="password-strength"
                                    :ui="{ trailing: 'pe-1' }"
                                    class="w-full"
                                >
                                    <template #trailing>
                                        <UButton
                                            color="neutral"
                                            variant="link"
                                            size="sm"
                                            :icon="showConfirmation ? 'i-lucide-eye-off' : 'i-lucide-eye'"
                                            :aria-label="showConfirmation ? 'Hide password' : 'Show password'"
                                            :aria-pressed="showConfirmation"
                                            aria-controls="password"
                                            @click="showConfirmation = !showConfirmation"
                                        />
                                    </template>
                                </UInput>

                                <p v-if="userSecurityForm.confirm_password !== ''" id="password-confirmation" class="text-sm font-medium" :class="samePassword ? 'text-success/80' : 'text-danger/80'">
                                    {{ confirmationText }}
                                </p>
                            </UFormField>
                        </div>
                    </UForm>
                </template>
                
                <template #admin="{item}">
                    <p class="text-muted">
                        {{ item.description }}
                    </p>
                    <hr class="text-white/30 mt-2">
                    
                    <UForm :state="userAdminForm" class="flex flex-col gap-4 mt-5" @submit="submitUserModal">

                        <UAccordion
                            v-model="userAdminAccordionActive"
                            :items="userAdminAccordionItems"
                            :ui="{ trigger: 'cursor-pointer rounded-md hover:bg-white/10 px-4 text-white data-[state=open]:text-primary-light data-[state=open]:bg-white/5', content: 'py-4' }"
                        >
                            <template #default="{item}">
                                <h3 class="text-lg">{{item.label}}</h3>
                            </template>
                            
                            <template #access="{item}">
                                <div class="flex gap-4">
                                    <!-- Active account -->
                                    <UFormField
                                        :label="props.__.resources.users.fields.suspended_until"
                                        :help="props.__.forms.users.help.suspended_until"
                                        class="w-1/2"
                                    >
                                        <FgInputDatePicker
                                            v-model="userAdminForm.suspended_until"
                                            :__="props.__.global.forms"
                                        />
                                    </UFormField>

                                    <!-- Expire at -->
                                    <UFormField
                                        :label="props.__.resources.users.fields.expire_at"
                                        :help="props.__.forms.users.help.expire_at"
                                        name="expire_at"
                                        class="flex flex-col items-end [&>*:nth-child(2)]:flex [&>*:nth-child(2)]:flex-col [&>*:nth-child(2)]:items-end w-3/5"
                                        :ui="{ help: 'text-right' }"
                                    >
                                        <FgInputDatePicker
                                            v-model="userAdminForm.expire_at"
                                            :ui="{ base: 'flex justify-end'}"
                                            :__="props.__.global.forms"
                                        />
                                    </UFormField>

                                </div>

                                <USeparator class="my-3"/>

                                <div class="flex gap-4 ">
                                    <UFormField
                                        :label="props.__.resources.users.fields.suspension_reason"
                                        :help="props.__.forms.users.help.suspension_reason"
                                        class="w-1/2"
                                    >
                                        <UTextarea v-model="userAdminForm.suspension_reason" placeholder=" " class="w-full" :ui="{ base: 'peer max-h-37.5 min-h-15'}"/>
                                    </UFormField>

                                    <UFormField
                                        :label='props.__.resources.users.fields.active'
                                        name="active"
                                        class="flex flex-col gap-2 w-2/5"
                                        :help="props.__.forms.users.help.active"
                                    >
                                        <USwitch v-model="userAdminForm.active" color="success" :disabled="isAccountExpired" />
                                    </UFormField>
                                </div>
                            </template>
                            
                            <template #notes="{item}">
                                <UFormField
                                    :label='props.__.resources.users.fields.admin_notes'
                                    :help="props.__.forms.users.help.admin_notes"
                                    name="admin_notes"
                                >
                                    <UTextarea v-model="userAdminForm.admin_notes" placeholder=" " :ui="{ base: 'peer max-h-37.5 min-h-15'}" class="w-full"/>
                                </UFormField>
                            </template>
                            
                            <template #constraints="{item}">
                                <div class="flex gap-4 justify-center">
                                    <!-- Forced actions input -->
                                    <UFormField
                                        :label="props.__.resources.users.fields.forced_actions"
                                    >
                                        <UCheckboxGroup
                                            indicator="start"
                                            variant="table"
                                            v-model="userAdminForm.forced_actions"
                                            :items="availableForcedActions"
                                            value-key="value"
                                        >
                                            <template #label="{ item }">
                                                {{ item.label.title }}
                                            </template>
                                            <template #description="{item}">
                                                {{ item.label.description }}
                                            </template>
                                        </UCheckboxGroup>
                                    </UFormField>

                                    <!-- Roles input -->
                                    <UFormField
                                        label="Roles"
                                        class="w-full"
                                    >
                                        <USelectMenu
                                            v-model="userAdminForm.roles"
                                            :filter-fields="['label', 'project']"
                                            :items="roles"
                                            :loading="rolesLoading"
                                            multiple
                                            class="w-full"
                                        >
                                            <template #item-label="{ item }">
                                                {{ item.label }}

                                                <span class="text-muted">
                                            - {{ item.project }}
                                        </span>
                                            </template>
                                        </USelectMenu>
                                    </UFormField>
                                </div>
                            </template>
                        </UAccordion>
                        
                    </UForm>
                        
                </template>
            </UTabs>
        </template>
        
        <template #footer>
            <div class="flex justify-end gap-2 w-full">
                <UButton
                    color="neutral"
                    variant="ghost"
                    :label="props.__.actions.close"
                    type="button"
                    @click="requestCloseUserModal"
                    class="cursor-pointer"
                />

                <UButton
                    type="submit"
                    :disabled="currentTabProcessing || !isCurrentTabValid"
                    :loading="currentTabProcessing"
                    :label="isEditModal ? 'Enregistrer' : props.__.actions.save"
                    @click="submitUserModal"
                    class="cursor-pointer"
                />
            </div>
        </template>
    </UModal>
    <!--  Confirm identity modal -->
    <FgConfirmIdentityModal
        v-model:open="identityModalOpen"
        :methods="page.props.auth?.user?.two_factor_methods ?? []"
        :selected-method="page.props.auth?.user?.primary_second_factor ?? null"
        :masked-email="page.props.auth?.user?.masked_email ?? ''"
        :config="identityModalConfig"
        @confirmed="runPendingSensitiveAction"
    />
    <!--  Quick edit drawer  -->
    <UserDrawerShell
        v-model:open="quickDrawerOpen"
        :dismissible="!quickDrawerDirty"
        :title="quickDrawerTitle"
        :description=" quickDrawerDescription"
        :close-label="props.__.actions.close"
        :submit-label="props.__.actions.save"
        :loading="quickDrawerProcessing"
        :disabled="quickDrawerDisabled"
        @update:open="handleQuickDrawerOpenChange"
        @close="handleQuickDrawerOpenChange(false)"
        @submit="submitQuickDrawer"
    >
        <UserQuickCreateForm
            v-show="quickDrawerMode === 'create'"
            ref="quickCreateFormRef"
            :__="props.__"
            :countries="phoneCountries"
            :roles="roles"
            :roles-loading="rolesLoading"
            @created="handleQuickDrawerSaved"
        />
        
        <UserQuickEditForm
            v-show="quickDrawerMode === 'edit'"
            ref="quickEditFormRef"
            :__="props.__"
            :user="selectedQuickUser"
            :countries="phoneCountries"
            @saved="handleQuickDrawerSaved"
        />
    </UserDrawerShell>
    <!--  Discard modal  -->
    <FgDiscardChangesModal
        v-model:open="discardModalOpen"
        :title="discardModalConfig.title"
        :description="discardModalConfig.description"
        :keep-label="discardModalConfig.keepLabel"
        :discard-label="discardModalConfig.discardLabel"
        :keep-color="discardModalConfig.keepColor"
        :discard-color="discardModalConfig.discardColor"
        @keep="handleDiscardKeep"
        @discard="handleDiscardDiscard"
    />
    
    <!-- Details drawer -->
    <UserDetailsDrawer
        v-if="detailsUserId !== null"
        v-model:open="detailsDrawerOpen"
        :user-id="detailsUserId"
        :locale="locale ?? 'fr-FR'"
        :labels="props.__.drawer"
        @closed="destroyDetailsDrawer"
    />
        
    <!-- Users table -->
    <PrimaryDataTable
        :title="props.__.title"
        :description="props.__.description"
        v-model:row-selection="rowSelection"
        selectable
        :data="users.data"
        :columns="columns"
        :meta="users.meta"
        :filters="filters"
        :views="userTableViews"
        view-filter-key="view"
        :loading="usersLoading"
        :initial-column-visibility="{ id: false }"
        :row-menu-items="getRowContextMenuItems"
        :labels="{
            search: props.__.actions.search,
            columns: props.__.tables.users.columns,
            selectedWord: props.__.messages.selected,
            connectingWord: props.__.messages.selected_connecting_word,
            unselect: props.__.actions.unselect,
        }"
        @reload="reloadUsers"
        @row-double-click="openUserDetails"
        @row-activate="openUserDetails"
        @bulk-delete="handleBulkDelete"
        @bulk-force-delete="handleBulkForceDelete"
        @bulk-restore="handleBulkRestore"
        @active-row-change="(row) => {
            activeTable = row ? 'users' : null;
            activeUserRow = row;
        }"
    >
        <template #global-actions>
            <div class="flex gap-4 justif-between w-full">
                
                <UFieldGroup>
                    <UButton 
                        v-if="permissions.create"
                        :label="props.__.user_actions.fast_create"
                        @click="openQuickCreate"
                        class="cursor-pointer"
                    />
                    
                    <UDropdownMenu :items="globalActionsDropdownItems" :ui="{ item: 'cursor-pointer' }">
                        <UButton 
                            color="primary"
                            icon="i-lucide-chevron-down"
                        />
                    </UDropdownMenu>
                        
                </UFieldGroup>
            </div>
        </template>
        
        <!-- Username slot -->
        <template #username-cell="{ row, getValue }">
            <div class="flex flex-col gap-1 items-start justify-center">
                <div class="flex gap-2">
                    <UBadge
                        v-if="currentUserId === row.original.id"
                        :label="props.__.tables.users.cell.username.badge"
                        color="success"
                        size="sm"
                        class="rounded-full opacity-80"
                    />
                    <p class="text-muted">{{ getValue() }}</p>
                </div>
                <p v-if="row.original.first_name || row.original.last_name" class="text-xs text-sandstone"><span class="text-muted font-bold">{{ props.__.resources.users.fields.last_name }}</span> : {{ row.original.last_name }} {{ row.original.first_name }}</p>
                <p v-if="row.original.job_title" class="text-xs text-sandstone"><span class="text-muted font-bold">{{ props.__.resources.users.fields.job_title}}</span> : {{ row.original.job_title }}</p>
            </div>
        </template>
        
        <!-- Contact slot -->
        <template #email-cell="{ row, getValue }">
            <div class="flex flex-col gap-2">
                <a
                    class="text-center text-white/50 hover:underline inline-flex gap-1 items-center w-fit"
                    :href="`mailto:${getValue()}`"
                >
                    <UIcon name="i-lucide-mail" class="size-5"/>
                    <p class="align-middle">{{ getValue() }}</p>
                </a>
                <a
                    v-if="row.original.phone?.extension && row.original.phone?.phone"
                    class="text-center text-white/50 hover:underline inline-flex gap-1 items-center w-fit"
                    :href="`tel:+${row.original.phone.extension}${row.original.phone.phone}`"
                >
                    <UIcon name="i-lucide-phone" class="size-5"/>
                    <p class="align-middle">+{{ row.original.phone.extension + row.original.phone.phone}}</p>
                </a>
            </div>
        </template>

        <!-- Active slot -->
        <template #active-cell="{ row, getValue }">    
            <UBadge
                :label="getValue() ? props.__.tables.users.cell.active.true : props.__.tables.users.cell.active.false"
                :color="getValue() ? 'success' : 'error'"
                size="sm"
                class="rounded-full"
            />
        </template>

        <!-- Administrative notes slot -->
        <template #admin_notes-cell="{ row, getValue }">
            <div
                v-if="row.original.permissions.view_sensitive && getValue()"
                class="flex max-w-md items-start justify-between gap-2"
            >
                <p class="whitespace-pre-wrap text-sm text-white/70">
                    {{
                        isAdminNoteExpanded(row.original.id)
                            ? getValue()
                            : truncate(getValue(), 20)
                    }}
                </p>

                <UButton
                    v-if="getValue().length > 20"
                    type="button"
                    :icon="isAdminNoteExpanded(row.original.id)
                        ? 'i-lucide-chevron-up'
                        : 'i-lucide-chevron-down'"
                    size="xs"
                    color="neutral"
                    variant="ghost"
                    class="shrink-0 cursor-pointer"
                    :aria-expanded="isAdminNoteExpanded(row.original.id)"
                    :aria-label="isAdminNoteExpanded(row.original.id)
                        ? props.__.tables.users.cell.admin_notes.collapse
                        : props.__.tables.users.cell.admin_notes.expand"
                    @click.stop="toggleAdminNote(row.original.id)"
                    @dblclick.stop
                />
            </div>
            <span v-else class="text-muted">—</span>
        </template>

        <!-- Actions slot -->
        <template #actions-cell="{ row }">
            <div class="flex flex-col justify-end gap-2">
                <UButton
                    v-if="row.original.permissions.view"
                    type="button"
                    icon="i-lucide-external-link"
                    size="xs"
                    color="neutral"
                    variant="outline"
                    :label="props.__.drawer.actions.view_details"
                    class="w-fit cursor-pointer"
                    @click.stop="router.get(`/users/${row.original.id}`)"
                />
                
                <UFieldGroup v-if="row.original.permissions.update">
                    <UButton
                        type="button"
                        icon="i-lucide-pencil"
                        size="xs"
                        color="neutral"
                        variant="outline"
                        class="cursor-pointer"
                        :label="props.__.user_actions.edit"
                        @click.stop="router.get(`/users/${row.original.id}/edit`)"
                    />
                    
                    <UDropdownMenu :items="getRowEditActionsDropdownItems(row)" :modal="true" size="xs" :ui="{ item: 'cursor-pointer', separator: 'h-px w-full bg-white/20'}">
                        <UButton 
                            color="neutral"
                            variant="outline"
                            size="xs"
                            icon="i-lucide-chevron-down"
                            class="cursor-pointer"
                        />
                    </UDropdownMenu>
                </UFieldGroup>
                
                <UButton 
                    v-if="
                        row.original.id !== currentUserId
                        && (
                            row.original.is_deleted
                                ? row.original.permissions.force_delete
                                : row.original.permissions.delete
                        )
                    "
                    icon="i-lucide-trash"
                    size="xs"
                    color="error"
                    variant="outline"
                    :label="row.original.is_deleted ? props.__.user_actions.force_delete : props.__.user_actions.delete"
                    class="w-fit cursor-pointer"
                    @click="onDeleteUser(row.original)"
                />
            </div>
        </template>
        
    </PrimaryDataTable>
    
    <USeparator class="mt-10"/>
    
    <!-- Invitation table -->
    <SecondaryDataTable
        table-key="invitations"
        :title="props.__.tables.invitations.name"
        v-model:row-selection="invitationsRowSelection"
        :initial-column-visibility="{ id: false }"
        :data="invitations.data"
        :columns="invitationsColumns"
        :meta="invitations.meta"
        :filters="filters"
        :loading="invitationsLoading"
        :row-menu-items="getInvitationContextMenuItems"
        :selectable="false"
        :pagination="false"
        :infinite-scroll="true"
        max-height="24rem"
        :views="userTableViews"
        view-filter-key="view"
        :labels="{
            search: props.__.actions.search,
            columns: props.__.tables.users.columns,
            selectedWord: props.__.messages.selected,
            connectingWord: props.__.messages.selected_connecting_word,
            unselect: props.__.actions.unselect,
            loadingMore: 'Chargement des invitations...',
            endReached: 'Toutes les invitations sont affichées',
        }"
        class="mb-10 mt-20"
        @reload="reloadInvitations"
    >
        <template #email-cell="{ row, getValue }">
            <div class="flex flex-col gap-2">
                <a
                    class="text-center text-white/50 hover:underline inline-flex gap-1 items-center w-fit"
                    :href="`mailto:${getValue()}`"
                >
                    <p class="align-middle">{{ getValue() }}</p>
                </a>
            </div>
        </template>
        
        <template #status_label-cell="{ row, getValue }">
            <UBadge
                :label="row.original.status_label"
                :color="row.original.status === 'accepted' ? 'success' : row.original.status === 'revoked' || row.original.status === 'expired' ? 'error' : 'warning'"
                size="sm"
                class="rounded-full"
            />
        </template>
        
        <template #actions-cell="{ row }">
            <div class="flex gap-2">
                <UDropdownMenu
                    :items="getInvitationActionRowItems(row)"
                    :modal="true" 
                    size="xs" 
                    :ui="{ item: 'cursor-pointer', separator: 'h-px w-full bg-white/20'}"
                >
                    <UButton
                        color="neutral"
                        variant="outline"
                        size="xs"
                        trailing-icon="i-lucide-chevron-down"
                        class="cursor-pointer"
                        label="Actions"
                    />
                </UDropdownMenu>
                
                <UButton
                    v-if="row.original.status  !== 'accepted' && row.original.status !== 'revoked'"
                    color="error"
                    variant="outline"
                    icon="i-lucide-link-2-off"
                    size="xs"
                    class="w-fit cursor-pointer"
                    :label="props.__.invitation_actions.revoke"
                    @click="revokeInvite(row)"
                />
                <UButton 
                    v-if="row.original.status === 'revoked'"
                    color="success"
                    variant="outline"
                    icon="i-lucide-link"
                    size="xs"
                    class="w-fit cursor-pointer"
                    :label="props.__.invitation_actions.reactive"
                    @click="reactiveInvite(row)"
                />
            </div>
        </template>
    </SecondaryDataTable>
</template>
