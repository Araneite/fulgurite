<script setup lang="ts">
import { computed, ref, resolveComponent, useTemplateRef, watch, h, toRef} from 'vue';
import { usePage } from '@inertiajs/vue3';
import { upperFirst } from 'scule';
import { useDataTableKeyboardNavigation } from '@/Composables/useDataTableKeyboardNavigation';

type PaginationMeta = {
    current_page?: number,
    per_page?: number,
    total?: number,
}

const props = withDefaults(defineProps<{
    title?: string;
    description?: string;
    data: any[]
    columns: any[]
    meta: PaginationMeta
    filters?: Record<string, any>
    loading?: boolean
    getRowId?: (row: any) => string | number
    initialColumnVisibility?: Record<string, boolean>
    perPageItems?: number[]
    searchable?: boolean
    selectable?: boolean
    stripedRows?: boolean
    views?: TableView[]
    viewFilterKey?: string
    labels?: {
        search?: string
        columns?: string
        selectedWord?: string
        connectingWord?: string
        unselect?: string
    }
    rowMenuItems?: (row: any) => any[]
    ui?: Record<string, any>
}>(), {
    filters: () => ({}),
    initialColumnVisibility: () => ({}),
    perPageItems: () => [10, 20, 50, 100],
    searchable: true,
    selectable: true,
    stripedRows: true,
    labels: () => ({
        search: 'Rechercher...',
        columns: 'Colonnes',
        selectedWord: 'sélectionné(s)',
        connectingWord: 'sur',
        unselect: 'Désélectionner',
    }),
    getRowId: (row: any) => row.id,
    rowMenuItems: undefined,
    views: ()=> [],
    viewFilterKey: 'view',
    ui: () => ({}),
});

const page = usePage();

const emit = defineEmits<{
    reload: [parms: Record<string, any>]
    create: []
    contextmenu: [event: Event, row: any],
    activeRowChange: [row: any | null],
    rowActivate: [row: any],
    rowDoubleClick: [row: any],
    bulkDelete: [rows: any[]],
    bulkForceDelete: [rows: any[]],
    bulkRestore: [rows: any[]],
}>();

const table = useTemplateRef('table');
const UCheckbox = resolveComponent('UCheckbox');

const tableKeyboardContainer = useTemplateRef<HTMLElement>('tableKeyboardContainer');

const {
    onKeydown: onTableKeydown,
    onFocusIn: onTableFocusIn,
    onFocusOut: onTableFocusOut,
    refreshKeyboardNavigation,
    isTableFocused,
    isKeyboardMode,
} = useDataTableKeyboardNavigation({
    table,
    container: tableKeyboardContainer,
    selectable: toRef(props, 'selectable'),
    onActiveRowChange: (row) => emit('activeRowChange', row),
    onRowActivate: (row) => emit('rowActivate', row),
});

watch(
    ()=> [props.data, props.columns, props.loading],
    ()=> refreshKeyboardNavigation(),
    { deep: true, immediate: true },
)

const rowSelection = defineModel<Record<string, boolean>>('rowSelection', { default: {} });
const columnVisibility = ref<Record<string, boolean>>({ ...props.initialColumnVisibility });

const globalFilter = ref(props.filters.search ?? '');
const perPageSelectValue = ref(props.filters.per_page ?? props.meta.per_page ?? 10);

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

const currentPage = computed(() => props.meta.current_page ?? 1);
const perPage = computed(() => props.meta.per_page ?? perPageSelectValue);
const total = computed(() => props.meta.total ?? 0);
const totalSelected = computed(() => Object.keys(rowSelection.value ?? {}).length);

const tableColumns = computed(() => {
    if (!props.selectable) return props.columns;
    
    return [
        {
            id: 'select',
            header: ({ table }) =>
                h(UCheckbox, {
                    modelValue: table.getIsSomePageRowsSelected()
                        ? 'indeterminate'
                        : table.getIsAllPageRowsSelected(),
                    'onUpdate:modelValue': (value: boolean | 'indeterminate') =>
                        table.toggleAllPageRowsSelected(!!value),
                    'aria-label': 'Select all'
                }),
            cell: ({ row }) =>
                h(UCheckbox, {
                    modelValue: row.getIsSelected(),
                    'onUpdate:modelValue': (value: boolean | 'indeterminate') => row.toggleSelected(!!value),
                    'aria-label': 'Select row'
                }),
            enableHiding: false,
            meta: {
                class: {
                    th: 'align-middle !p-4',
                    td: 'align-middle !p-4',
                },
            },
        },
        ...props.columns
    ]
});

const tableMeta = computed(() => ({
    class: {
        tr: (row: any) => [
            props.stripedRows
                ? row.index % 2 === 0
                    ? 'bg-white/2'
                    : 'bg-white/5'
                : '',
            'hover:bg-white/10 transition-colors',
        ].join(' '),
    },
}));

const defaultUi = {
    root: 'min-h-[300px]',
    base: 'min-w-[1200px] w-full table-auto relative',
    thead: 'sticky top-0 bg-darknight',
    tbody: 'align-top',
    tr: 'h-auto',
    td: 'w-fit',
};

const contextMenuItems = ref<any[]>([]);

watch(
    () => props.filters.search,
    search => {
        globalFilter.value = search ?? ''
    }
);

watch(globalFilter, search => {
    if (search === (props.filters.search ?? '')) return;
    
    if (searchTimeout) clearTimeout(searchTimeout);
    
    searchTimeout = setTimeout(() => {
        rowSelection.value = {};
        emit('reload', { search, page: 1 });
    }, 300);
});

watch(perPageSelectValue, value => {
    if (Number(value) === Number(props.filters.per_page ?? props.meta.per_page)) return;
    
    rowSelection.value = {};
    
    emit('reload', { per_page: value, page: 1 });
})

function goToPage(page: number) {
    if (Number(page) === Number(currentPage.value)) return;
    
    emit('reload', { page });
}

function onSelect(event: Event, row: any) {
    
    if (event instanceof MouseEvent && event.detail === 2) {
        emit('rowDoubleClick', row);
        
        return;
    }
    
    row.toggleSelected(!row.getIsSelected());
}

function onUnselectAll() {
    rowSelection.value = {};
}

function onContextMenu(event: Event, row: any) {
    if (props.rowMenuItems) contextMenuItems.value = props.rowMenuItems(row);
    
    emit('contextmenu', event, row);
}

// --- Views ---
type TableView = {
    label: string;
    value: string;
    icon?: string
};

// === Actions ===
function isCurrentUser(row: any) {
    return Boolean(row?.id === page.props.auth.user.id);
}
// --- Views ---
const hasViews = computed(() => props.views.length > 0);
const currentTableView = computed(() => {
    return props.filters?.[props.viewFilterKey] ?? props.views[0]?.value;
});

function changeTableView(view: string) {
    if (view === currentTableView.value) return;
    
    rowSelection.value = {}
    
    emit('reload', {
        [props.viewFilterKey]: view,
        page: 1
    });
}
// --- End Views ---

// --- Bulk actions ---
const selected = computed(() => {
    return props.data.filter((row)=> rowSelection.value[row.id]);
})

const selectedActive = computed(() => selected.value.filter((row)=> !row.deleted_at));
const selectedTrashed = computed(() => selected.value.filter((row)=> row.deleted_at));

const softDeletable = computed(() => selectedActive.value.filter((row)=> row.permissions?.delete && !isCurrentUser(row)));
const forceDeletable = computed(() => selectedTrashed.value.filter((row)=> row.permissions?.force_delete && !isCurrentUser(row)));
const restorable = computed(() => selectedTrashed.value.filter((row)=> row.permissions?.restore && !isCurrentUser(row)));

const canBulkDelete = computed(() => softDeletable.value.length > 0);
const canBulkForceDelete = computed(() => forceDeletable.value.length > 0);
const canBulkRestore = computed(() => restorable.value.length > 0);
// End Delete
// --- End Bulk actions ---
</script>

<template>
    <section class="min-h-0">
        <div class="container flex flex-col gap-4 h-full justify-between min-h-0">
            <div class="table-header flex flex-col justify-between">
                <hgroup v-if="title || description" class="mb-6">
                    <h3 v-if="title" class="text-base">{{ title }}</h3>
                    <p v-if="description" class="text-sm text-muted">{{ description }}</p>
                </hgroup>
                <div class="flex justify-between item-center w-full flex-wrap gap-3">
                    <div class="flex items-center justify-between w-full md:justify-start gap-4 md:w-fit">
                        <UInput 
                            v-if="searchable"
                            v-model="globalFilter"
                            :placeholder="labels.search"
                            class="min-w-[150px]"
                            data-users-search
                        />
                        <div class="flex gap-2">
                            <UTooltip
                                v-for="view in views"
                                :key="view.value"
                                :text="view.label"
                                :delay-duration="0"
                            >
                                <UButton
                                    :icon="view.icon"
                                    size="sm"
                                    :color="currentTableView === view.value ? 'primary' : 'neutral'"
                                    :variant="currentTableView === view.value ? 'solid' : 'ghost'"
                                    @click="changeTableView(view.value)"
                                    class="cursor-pointer"
                                />
                            </UTooltip>
                        </div>
                    </div>
                    
                    <div class="flex gap-2 flex-wrap sm:flex-nowrap w-fit">
                        <slot name="global-actions" />
                        
                        <UDropdownMenu
                            :items="
                                table?.tableApi?.getAllColumns()
                                    .filter((column) => column.getCanHide())
                                    .map((column) => ({
                                        label: upperFirst(column.id),
                                        type: 'checkbox',
                                        checked: column.getIsVisible(),
                                        onUpdateChecked(checked) {
                                            table?.tableApi?.getColumn(column.id)?.toggleVisibility(!!checked)
                                        },
                                        onSelect(event) {
                                            event.preventDefault()
                                        },
                                    }))
                            "
                            :ui="{ content: 'z-99'}"
                        >
                            <UButton 
                                :label="labels.columns"
                                color="neutral"
                                variant="outline"
                                trailing-icon="i-lucide-chevron-down"
                                @click.stop
                            />
                        </UDropdownMenu>
                    </div>
                </div>
            </div>
            
            <UContextMenu :items="contextMenuItems">
                <div 
                    ref="tableKeyboardContainer"
                    class="min-h-0 flex-1 overflow-auto rounded-md data-table-keyboard"
                    tabindex="0"
                    :data-table-focused="isTableFocused ? 'true' : undefined"
                    :data-keyboard-mode="isKeyboardMode ? 'true' : undefined"
                    @pointerdown="onTablePointerDown"
                    @keydown="onTableKeydown"
                    @focusin="onTableFocusIn"
                    @focusout="onTableFocusOut"
                >
                    <UTable
                        ref="table"
                        v-model:row-selection="rowSelection"
                        v-model:column-visibility="columnVisibility"
                        :data="data"
                        :columns="tableColumns"
                        :get-row-id="getRowId"
                        :loading="loading"
                        :meta="tableMeta"
                        :ui="{...defaultUi, ...ui}"
                        @contextmenu="onContextMenu"
                        @select="onSelect"
                    >
                        <template
                            v-for="(_, slotName) in $slots"
                            #[slotName]="slotProps"
                        >
                            <slot :name="slotName" v-bind="slotProps"/>
                        </template>
                    </UTable>
                </div>
            </UContextMenu>
            
            <div class="table-footer m-2 flex gap-2">
                    <div class="flex gap-2" v-if="selectable && totalSelected > 0">
                        <UButton
                            v-if="selectedActive.length"
                            icon="i-lucide-trash"
                            color="error"
                            variant="outline"
                            :disabled="!canBulkDelete"
                            @click="emit('bulkDelete', softDeletable)"
                        >
                            Mettre à la corbeille
                        </UButton>

                        <UButton
                            v-if="selectedTrashed.length"
                            icon="i-lucide-rotate-ccw"
                            color="neutral"
                            variant="outline"
                            :disabled="!canBulkRestore"
                            @click="emit('bulkRestore', restorable)"
                        >
                            Restaurer
                        </UButton>

                        <UButton
                            v-if="selectedTrashed.length"
                            icon="i-lucide-trash-2"
                            color="error"
                            variant="solid"
                            :disabled="!canBulkForceDelete"
                            @click="emit('bulkForceDelete', forceDeletable)"
                        >
                            Supprimer définitivement
                        </UButton>
                        
<!--                        <UTooltip :text="bulkDeleteBlocked.length > 0 ? bulkDeleteBlocked.length > 1 ? `${bulkDeleteBlocked.length} élément(s) seront ignorés faute de permissions.` : `${bulkDeleteBlocked.length} élément sera ignoré faute de permissions.` : ''"
                            delay-duration="200"
                        >
                            <UButton 
                                :disabled="!canBulkDelete"
                                color="error"
                                icon="i-lucide-trash"
                                :label="`Supprimer ${bulkDeletable.length} élément(s)`"
                                class="w-fit"
                            />
                        </UTooltip>-->
                </div>
                <div class="flex items-center gap-4">
                    <UButton
                        v-if="totalSelected > 0"
                        variant="outline"
                        :label="labels.unselect"
                        class="cursor-pointer"
                        @click="onUnselectAll"
                    />
                    
                    <p class="text-lg">
                        {{ totalSelected }}
                        {{ labels.connectingWord }}
                        {{ total }}
                        {{ labels.selectedWord }}
                    </p>
                </div>
            </div>
            
            <div class="flex items-center justify-center w-full mt-2 gap-5">
                <USelect
                    v-model="perPageSelectValue"
                    :items="perPageItems"
                    :disabled="loading"
                />
                
                <UPagination
                    :page="currentPage"
                    :items-per-page="perPage"
                    :total="total"
                    @update:page="goToPage"
                />
            </div>
        </div>
    </section>
</template>

<style scoped>
.data-table-keyboard[data-keyboard-mode="true"] :deep(tr[data-keyboard-managed="true"]:focus) {
    outline: 1px solid var(--color-primary);
    outline-offset: 2px;
}

.data-table-keyboard[data-keyboard-mode="true"] :deep(td[data-keyboard-cell="true"]:focus),
.data-table-keyboard[data-keyboard-mode="true"] :deep(th[data-keyboard-cell="true"]:focus) {
    outline: 1px solid var(--color-primary);
    outline-offset: -2px;
}

.data-table-keyboard[data-keyboard-mode="true"] :deep(a:focus),
.data-table-keyboard[data-keyboard-mode="true"] :deep(button:focus),
.data-table-keyboard[data-keyboard-mode="true"] :deep([role="button"]:focus),
.data-table-keyboard[data-keyboard-mode="true"] :deep([role="menuitem"]:focus) {
    outline: 1px solid var(--color-primary);
    outline-offset: 2px;
}

.data-table-keyboard[data-table-focused="true"][data-keyboard-mode="true"] {
    outline: 1px solid var(--color-primary);
    outline-offset: 10px;
}
</style>
