<script setup lang="ts">
import { computed, ref, h, resolveComponent, watch, useTemplateRef } from "vue";
import { upperFirst } from "scule";

type PaginationMeta = {
    current_page?: number;
    last_page?: number;
    per_page?: number;
    total?: number;
}

type TableView = {
    label: string;
    value: string;
    icon?: string;
}

const props = withDefaults(defineProps<{
    tableKey: string;
    title?: string;
    description?: string;
    data: any[];
    columns: any[];
    meta?: PaginationMeta;
    filters?: Record<string, any>;
    loading?: boolean;
    getRowId?: (row: any) => string | number;
    initialColumnVisibility?: Record<string, boolean>;
    perPageItems?: number[];
    searchable?: boolean;
    selectable?: boolean;
    stripedRows?: boolean;
    pagination?: boolean;
    infiniteScroll?: boolean;
    maxHeight?: string;
    serverSearchDelay?: number;
    rowMenuItems?: (row: any) => any[];
    views?: TableView[];
    viewFilterKey?: string;
    labels?: {
        search?: string;
        columns?: string;
        selectedWord?: string;
        connectingWord?: string;
        unselect?: string;
        loadingMore?: string;
        endReached?: string;
    };
    ui?: Record<string, any>;
}>(), {
    filters: () => ({}),
    meta: () => ({}),
    initialColumnVisibility: () => ({}),
    perPageItems: () => [10, 20, 50, 100],
    searchable: true,
    selectable: false,
    stripedRows: true,
    pagination: false,
    infiniteScroll: true,
    maxHeight: '28rem',
    serverSearchDelay: 300,
    views: () => [],
    viewFilterKey: 'view',
    labels: () => ({
        search: 'Search...',
        columns: 'Columns',
        selectedWord: 'selected',
        connectingWord: 'of',
        unselect: 'Unselect',
        loadingMore: 'Loading...',
        endReached: 'All elements are displayed.',
    }),
    getRowId: (row: any) => row.id,
    rowMenuItems: undefined,
    ui: () => ({}),
});

const emit = defineEmits<{
    reload: [params: Record<string, any>];
    contextMenu: [event: Event, row: any];
}>();

const table = useTemplateRef('table');
const UCheckbox = resolveComponent('UCheckbox');

const rowSelection = defineModel<Record<string, boolean>>('rowSelection', { default: {} });

const columnVisibility = ref<Record<string, boolean>>({ ...props.initialColumnVisibility });
const localRows = ref<any[]>([...props.data]);
const pendingAppend = ref(false);
const searchTimeout = ref<ReturnType<typeof setTimeout> | null>(null);
const currentContextMenuItems = ref<any[]>([]);

const prefix = computed(() => `${props.tableKey}_`);

function scopedParam(key: string) {
    return key.startsWith(prefix.value) ? key : `${prefix.value}${key}`;
}

const pageParam = computed(() => scopedParam('page'));
const perPageParam = computed(() => scopedParam('per_page'));
const searchParam = computed(() => scopedParam('search'));
const viewParam = computed(() => scopedParam(props.viewFilterKey));

const searchValue = ref(props.filters[searchParam.value] ?? '');
const perPageSelectValue = ref(props.filters[perPageParam.value] ?? props.meta.per_page ?? props.perPageItems[0]);

const currentPage = computed(() => Number(props.meta.current_page ?? props.filters[pageParam.value] ?? 1));
const perPage = computed(() => Number(props.meta.per_page ?? perPageSelectValue.value));
const total = computed(() => Number(props.meta.total ?? localRows.value.length));
const lastPage = computed(() => Number(props.meta.last_page ?? Math.ceil(total.value / perPage.value) ?? 1));
const hasMore = computed(() => currentPage.value < lastPage.value && localRows.value.length < total.value);
const allRowsLoaded = computed(() => total.value <= localRows.value.length);
const totalSelected = computed(() => Object.keys(rowSelection.value ?? {}).length);

const hasViews = computed(() => props.views.length > 0);
const currentTableView = computed(() => {
    return props.filters?.[viewParam.value] ?? props.views[0]?.value;
});

const searchableKeys = computed(() => {
    return props.columns
        .map((column) => column.accessorKey ?? column.id)
        .filter((key) => key && key !== 'actions');
});

const displayedRows = computed(() => {
    if (!props.searchable || !allRowsLoaded.value || !searchValue.value) {
        return localRows.value;
    }

    const needle = String(searchValue.value).toLowerCase();

    return localRows.value.filter((row) => {
        return searchableKeys.value.some((key) => {
            const value = row[key];

            return value !== null
                && value !== undefined
                && String(value).toLowerCase().includes(needle);
        });
    });
});

const selectedRows = computed(() => {
    return localRows.value.filter((row) => rowSelection.value[String(props.getRowId(row))]);
});

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
                    'onUpdate:modelValue': (value: boolean | 'indeterminate') => table.toggleAllRowsSelected(!!value),
                    'aria-label': 'Select all',
                }),
            cell: ({ row }) =>
                h(UCheckbox, {
                    modelValue: row.getIsSelected(),
                    'onUpdate:modelValue': (value: boolean | 'indeterminate') => row.toggleSelected(!!value),
                    'aria-label': 'Select row',
                }),
            enableHiding: false,
        },
        ...props.columns,
    ];
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
    root: 'min-h-0',
    base: 'min-w-[760px] w-full table-auto relative',
    thead: 'sticky top-0 bg-darknight',
    tbody: 'align-top',
    tr: 'h-auto',
};

watch(
    () => props.data,
    (rows) => {
        if (props.pagination || !pendingAppend.value || currentPage.value <= 1) {
            localRows.value = [...rows];
        } else {
            const existingIds = new Set(localRows.value.map((row) => String(props.getRowId(row))));
            const nextRows = rows.filter((row) => !existingIds.has(String(props.getRowId(row))));

            localRows.value = [...localRows.value, ...nextRows];
        }

        pendingAppend.value = false;
    }
);

watch(
    () => props.filters[searchParam.value],
    (search) => {
        searchValue.value = search ?? '';
    }
);

watch(searchValue, (search) => {
    if (!props.searchable) return;
    if (allRowsLoaded.value) return;

    if (searchTimeout.value) clearTimeout(searchTimeout.value);

    searchTimeout.value = setTimeout(() => {
        rowSelection.value = {};
        pendingAppend.value = false;

        emit('reload', {
            [searchParam.value]: search,
            [pageParam.value]: 1,
        });
    }, props.serverSearchDelay);
});

watch(perPageSelectValue, (value) => {
    if (Number(value) === Number(props.filters[perPageParam.value] ?? props.meta.per_page)) return;

    rowSelection.value = {};
    pendingAppend.value = false;

    emit('reload', {
        [perPageParam.value]: value,
        [pageParam.value]: 1,
    });
});

function changeTableView(view: string) {
    if (view === currentTableView.value) return;

    rowSelection.value = {};
    pendingAppend.value = false;
    localRows.value = [];

    emit('reload', {
        [viewParam.value]: view,
        [pageParam.value]: 1,
    });
}

function goToPage(page: number) {
    if (Number(page) === Number(currentPage.value)) return;

    rowSelection.value = {};
    pendingAppend.value = false;

    emit('reload', {
        [pageParam.value]: page,
    });
}

function loadNextPage() {
    if (props.pagination || !props.infiniteScroll || props.loading || !hasMore.value) return;

    pendingAppend.value = true;

    emit('reload', {
        [pageParam.value]: currentPage.value + 1,
    });
}

function onScroll(event: Event) {
    const target = event.target as HTMLElement;
    const remaining = target.scrollHeight - target.scrollTop - target.clientHeight;

    if (remaining <= 80) {
        loadNextPage();
    }
}

function onSelect(_: Event, row: any) {
    if (!props.selectable) return;

    row.toggleSelected(!row.getIsSelected());
}

function onUnselectAll() {
    rowSelection.value = {};
}

function onContextMenu(event: MouseEvent, row: any) {
    const items = row
        ? props.rowMenuItems?.(row) ?? []
        : [];

    currentContextMenuItems.value = items;

    if (items.length === 0) {
        event.preventDefault();
        event.stopPropagation();
    }

    emit('contextMenu', event, row);
}
</script>

<template>
    <section class="min-h-0">
        <div class="flex min-h-0 flex-col gap-3">
            <hgroup v-if="title || description" class="min-w-0">
                <h3 v-if="title" class="text-base font-semibold">{{ title }}</h3>
                <p v-if="description" class="text-xs text-muted">{{ description }}</p>
            </hgroup>

            <div class="flex flex-col justify-end gap-3 md:flex-row md:items-end">
                <div class="flex flex-wrap items-center gap-2">
                    <UInput
                        v-if="searchable"
                        v-model="searchValue"
                        size="sm"
                        :placeholder="labels.search"
                    />

                    <div v-if="hasViews" class="flex gap-2">
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
                                class="cursor-pointer"
                                @click="changeTableView(view.value)"
                            />
                        </UTooltip>
                    </div>

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
                        :ui="{ content: 'z-99' }"
                    >
                        <UButton
                            size="sm"
                            :label="labels.columns"
                            color="neutral"
                            variant="outline"
                            trailing-icon="i-lucide-chevron-down"
                            @click.stop
                        />
                    </UDropdownMenu>
                </div>
            </div>

            <div
                class="min-h-0 overflow-auto rounded-md border border-white/10"
                :style="{ maxHeight }"
                @scroll="onScroll"
            >
                <UContextMenu :items="currentContextMenuItems">
                    <UTable
                        ref="table"
                        v-model:row-selection="rowSelection"
                        v-model:column-visibility="columnVisibility"
                        :data="displayedRows"
                        :columns="tableColumns"
                        :get-row-id="getRowId"
                        :loading="loading"
                        :meta="tableMeta"
                        :ui="{ ...defaultUi, ...ui }"
                        @contextmenu="onContextMenu"
                        @select="onSelect"
                    >
                        <template
                            v-for="(_, slotName) in $slots"
                            #[slotName]="slotProps"
                        >
                            <slot :name="slotName" v-bind="slotProps" />
                        </template>
                    </UTable>
                </UContextMenu>

                <div
                    v-if="!pagination && infiniteScroll"
                    class="flex items-center justify-center px-3 py-2 text-xs text-muted"
                >
                    <span v-if="loading">{{ labels.loadingMore }}</span>
                    <span v-else-if="!hasMore">{{ labels.endReached }}</span>
                </div>
            </div>

            <div
                v-if="selectable && totalSelected > 0"
                class="flex flex-wrap items-center justify-between gap-2"
            >
                <div class="flex flex-wrap items-center gap-2">
                    <slot
                        name="bulk-actions"
                        :selected-rows="selectedRows"
                        :row-selection="rowSelection"
                        :unselect-all="onUnselectAll"
                    />
                </div>

                <div class="flex items-center gap-3 text-sm">
                    <UButton
                        size="sm"
                        variant="outline"
                        :label="labels.unselect"
                        class="cursor-pointer"
                        @click="onUnselectAll"
                    />

                    <p>
                        {{ totalSelected }}
                        {{ labels.connectingWord }}
                        {{ total }}
                        {{ labels.selectedWord }}
                    </p>
                </div>
            </div>

            <div
                v-if="pagination"
                class="flex flex-wrap items-center justify-center gap-4"
            >
                <USelect
                    v-model="perPageSelectValue"
                    size="sm"
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
