import { nextTick, onBeforeUnmount, onMounted, ref, type Ref } from 'vue';

type KeyboardNavigationOptions = {
    table: Ref<any>;
    container: Ref<HTMLElement | null>;
    selectable: Ref<boolean>;
    onActiveRowChange?: (row: any | null) => void;
    onRowActivate?: (row: any) => void;
};

type FocusMode = 'row' | 'cell' | 'action';

const interactiveSelector = [
    'a[href]',
    'button:not([disabled])',
    '[role="button"]',
    '[role="menuitem"]',
    '[role="checkbox"]',
    '[aria-haspopup]',
    'input:not([disabled])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(',');

function isTextInput(target: EventTarget | null) {
    const element = target as HTMLElement | null;
    
    if (!element) return false;
    
    return element.tagName === 'INPUT'
        || element.tagName === 'TEXTAREA'
        || element.isContentEditable;
}

function isVisible(element: HTMLElement) {
    return element.offsetParent !== null;
}

function isNavigableTableRow(row: HTMLTableRowElement) {
    if (!isVisible(row)) return false;

    if (row.getAttribute('aria-hidden') === 'true') return false;
    if (row.getAttribute('role') === 'separator') return false;
    if (row.dataset.divider === 'true') return false;
    if (row.dataset.separator === 'true') return false;
    if (row.classList.contains('divider')) return false;
    if (row.classList.contains('separator')) return false;

    const cells = Array.from(row.querySelectorAll<HTMLElement>('th, td'))
        .filter(isVisible);

    if (cells.length === 0) return false;

    const hasRealCell = cells.some((cell) => {
        if (cell.getAttribute('aria-hidden') === 'true') return false;
        if (cell.getAttribute('role') === 'separator') return false;

        const text = cell.textContent?.trim() ?? '';
        const hasInteractive = Boolean(cell.querySelector(interactiveSelector));

        return text !== '' || hasInteractive;
    });

    return hasRealCell;
}

function getFocusablePageElements() {
    return Array.from(document.querySelectorAll<HTMLElement>([
        'a[href]',
        'button:not([disabled])',
        'input:not([disabled])',
        'select:not([disabled])',
        'textarea:not([disabled])',
        '[tabindex]:not([tabindex="-1"])',
    ].join(','))).filter(isVisible);
}

export function useDataTableKeyboardNavigation(options: KeyboardNavigationOptions) {
    const activeRowIndex = ref(0);
    const activeColumnIndex = ref(0);
    const activeActionIndex = ref(0);
    const focusMode = ref<FocusMode>('row');
    const isTableFocused = ref(false);
    const isKeyboardMode = ref(false);


    let refreshFrame: number | null = null;

    function emitActiveRowChange() {
        options.onActiveRowChange?.(getCurrentRowApi());
    }
    
    function getHeaderRows() {
        return Array.from(
            options.container.value?.querySelectorAll<HTMLTableRowElement>('thead tr') ?? []
        ).filter(isNavigableTableRow);
    }

    function getBodyRows() {
        return Array.from(
            options.container.value?.querySelectorAll<HTMLTableRowElement>('tbody tr') ?? []
        ).filter(isNavigableTableRow);
    }
    
    function getRows() {
        return [...getHeaderRows(), ...getBodyRows()];
    }
    
    function getBodyRowStartIndex() {
        return getHeaderRows().length;
    }
    
    function getCells(row: HTMLTableRowElement | undefined) {
        return Array.from(row?.querySelectorAll<HTMLElement>('th, td') ?? []);
    }
    
    function getCurrentDomRow() {
        return getRows()[activeRowIndex.value] ?? null;
    }
    
    function getCurrentCell() {
        return getCells(getCurrentDomRow())[activeColumnIndex.value] ?? null;
    }
    
    function getCurrentRowApi() {
        const bodyIndex = activeRowIndex.value - getBodyRowStartIndex();
        
        if (bodyIndex < 0) return null;
        
        return options.table.value?.tableApi?.getRowModel?.().rows?.[bodyIndex] ?? null;
    }
    
    function getCellActions(cell = getCurrentCell()) {
        if (!cell) return [];
        
        return Array.from(cell.querySelectorAll<HTMLElement>(interactiveSelector))
            .filter((element)=> isVisible(element) && !element.closest('[aria-hidden="true"]'));
    }
    
    function isSelectCell(cell = getCurrentCell()) {
        if (!cell) return false;
        
        return Boolean(
            cell.querySelector('[role="checkbox"], input[type="checkbox"]')
            || activeColumnIndex.value === 0
        );
    }
    
    function clearManagedTabIndexes() {
        const container = options.container.value;
        
        if (!container) return;
        
        container.querySelectorAll<HTMLElement>(interactiveSelector).forEach((element) => {
            element.tabIndex = -1;
        });
    }
    
    function syncTabIndexes() {
        clearManagedTabIndexes();
        
        const row = getCurrentDomRow();
        const cell = getCurrentCell();
        
        getRows().forEach((tableRow)=> {
            tableRow.dataset.keyboardManaged = 'true';
            tableRow.tabIndex = -1;
            
            getCells(tableRow).forEach((tableCell)=> {
                tableCell.dataset.keyboardManaged = 'true';
                tableCell.dataset.keyboardCell = 'true';
                tableCell.tabIndex = -1;
            });
        });
        
        if (focusMode.value === 'row' && row) {
            row.tabIndex = 0;
            return;
        }
        
        if (focusMode.value === 'cell' && cell) {
            cell.tabIndex = 0;
            return;
        }
        
        if (focusMode.value === 'action') {
            const actions = getCellActions(cell);
            const action = actions[activeActionIndex.value];
            
            if (action) {
                action.tabIndex = 0;
            }
        }
    }
    
    function scheduleRefresh() {
        if (refreshFrame !== null) {
            cancelAnimationFrame(refreshFrame);
        }
        
        refreshFrame = requestAnimationFrame(()=> {
            refreshFrame = null;
            syncTabIndexes();
        })
    }
    
    function focusRow(rowIndex: number) {
        const rows = getRows();
        
        if (!rows.length) return;
        
        activeRowIndex.value = Math.max(0, Math.min(rowIndex, rows.length - 1));
        
        const currentCells = getCells(rows[activeRowIndex.value]);
        activeColumnIndex.value = Math.max(
            0,
            Math.min(activeColumnIndex.value, currentCells.length - 1)
        );
        
        focusMode.value = 'row';
        activeActionIndex.value = 0;
        
        syncTabIndexes();
        emitActiveRowChange();
        rows[activeRowIndex.value]?.focus({ preventScroll: false });
    }
    
    function focusCell(rowIndex: number, columnIndex: number) {
        const rows = getRows();
        
        if (!rows.length) return;
        
        activeRowIndex.value = Math.max(0, Math.min(rowIndex, rows.length - 1));
        
        const cells = getCells(rows[activeRowIndex.value]);
        
        if (!cells.length) return;
        
        activeColumnIndex.value = Math.max(0, Math.min(columnIndex, cells.length - 1));
        focusMode.value = 'cell';
        activeActionIndex.value = 0;
        
        syncTabIndexes();
        emitActiveRowChange()
        cells[activeColumnIndex.value]?.focus({ preventScroll: false });
    }
    
    function focusAction(index: number) {
        const actions = getCellActions();
        
        if (!actions.length) return;
        
        activeActionIndex.value = ((index % actions.length) + actions.length) % actions.length;
        focusMode.value = 'action';
        
        syncTabIndexes();
        actions[activeActionIndex.value]?.focus({ preventScroll: false });
    }
    
    function focusNextOutsideContainer(backward: boolean) {
        const container = options.container.value;
        
        if (!container) return;
        
        const focusables = getFocusablePageElements();
        const outside = focusables.filter((element)=> !container.contains(element));
        const currentIndex = focusables.findIndex((element)=> element === document.activeElement);
        
        const candidates = backward
            ? outside.filter((element)=> focusables.indexOf(element) < currentIndex).reverse()
            : outside.filter((element)=> focusables.indexOf(element) > currentIndex);
        
        candidates[0]?.focus();
    }
    
    function toggleCurrentRowSelection() {
        if (!options.selectable.value) return;
        
        const row = getCurrentRowApi();
        
        if (!row) return;
        
        row.toggleSelected(!row.getIsSelected());
    }
    
    function activateCurrentCell() {
        const cell = getCurrentCell();
        
        if (!cell) return;
        
        if (options.selectable.value && isSelectCell(cell)) {
            toggleCurrentRowSelection();
            return;
        }
        
        const actions = getCellActions();
        
        if (actions.length === 0) return;
        
        if (actions.length === 1) {
            actions[0].click();
            return;
        }
        
        focusAction(0);
    }
    
    function activateCurrentAction() {
        const actions = getCellActions();
        const action = actions[activeActionIndex.value];
        
        action?.click();
    }
    
    function openCurrentRowContextMenu() {
        const row = getCurrentDomRow();
        
        if (!row) return;
        
        const rect = row.getBoundingClientRect();
        
        row.dispatchEvent(new MouseEvent('contextmenu', { 
            bubbles: true,
            cancelable: true,
            button: 2,
            buttons: 2,
            clientX: rect.left + rect.width / 2,
            clientY: rect.top + rect.height / 2,
        }));
    }

    const keyboardNavigationKeys = new Set([
        'Tab',
        'ArrowUp',
        'ArrowDown',
        'ArrowLeft',
        'ArrowRight',
        'Home',
        'End',
        'Enter',
        ' ',
        'Escape',
        'ContextMenu',
        'F10',
    ]);

    function enableKeyboardMode() {
        isKeyboardMode.value = true;
    }

    function disableKeyboardMode() {
        isKeyboardMode.value = false;
    }

    function onGlobalPointerDown() {
        disableKeyboardMode();
    }

    function onGlobalKeyDown(event: KeyboardEvent) {
        if (isTextInput(event.target)) return;

        if (keyboardNavigationKeys.has(event.key)) {
            enableKeyboardMode();
        }
    }
    
    function onFocusIn(event: FocusEvent) {
        const target = event.target as HTMLElement | null;
        
        if (!target || !options.container.value?.contains(target)) return;

        isTableFocused.value = true;

        if (target === options.container.value) {
            syncTabIndexes();
            return;
        }
        
        const row = target.closest<HTMLTableRowElement>('tr');
        const cell = target.closest<HTMLElement>('th, td');
        
        if (!row) return;
        
        const rows = getRows();
        const cells = getCells(row);
        
        activeRowIndex.value = Math.max(0, rows.indexOf(row));
        
        if (cell) {
            activeColumnIndex.value = Math.max(0, cells.indexOf(cell));
            
            if (target.matches(interactiveSelector) &&  target !== cell) {
                const actions = getCellActions(cell);
                activeActionIndex.value = Math.max(0, actions.indexOf(target));
                focusMode.value = 'action';
            } else {
                focusMode.value = 'cell';
            }
        } else {
            focusMode.value = 'row'
        }
        
        syncTabIndexes();
        emitActiveRowChange();
    }

    function onFocusOut(event: FocusEvent) {
        const nextTarget = event.relatedTarget as HTMLElement | null;

        if (!nextTarget || !options.container.value?.contains(nextTarget)) {
            isTableFocused.value = false;
        }
    }
    
    function onKeydown(event: KeyboardEvent) {
        if (isTextInput(event.target)) return;
        
        const key = event.key;
        
        if (key === 'Tab') {
            event.preventDefault();
            focusNextOutsideContainer(event.shiftKey);
            return;
        }

        if (event.target === options.container.value) {
            if (key === 'ArrowDown' || key === 'ArrowRight' || key === 'Enter') {
                event.preventDefault();
                focusRow(0);
                return;
            }

            if (key === 'ArrowUp' || key === 'ArrowLeft') {
                event.preventDefault();
                focusRow(getRows().length - 1);
                return;
            }
        }
        
        if (key.toLowerCase() === 'x') {
            event.preventDefault();
            toggleCurrentRowSelection();
            return;
        }
        
        if (key === 'ContextMenu' || key === 'F10') {
            event.preventDefault();
            openCurrentRowContextMenu();
            return;
        }
        
        if (focusMode.value === 'action') {
            if (key === 'ArrowRight' || key === 'ArrowDown') {
                event.preventDefault();
                focusAction(activeActionIndex.value + 1);
                return;
            }
            
            if (key === 'ArrowLeft' || key === 'ArrowUp') {
                event.preventDefault();
                focusAction(activeActionIndex.value - 1);
                return;
            }
            
            if (key === 'Enter' || key === ' ') {
                event.preventDefault();
                activateCurrentAction();
                return;
            }
            
            if (key === 'Escape') {
                event.preventDefault();
                focusCell(activeRowIndex.value, activeColumnIndex.value);
                return;
            }
            
            return;
        }
        
        if (key === 'ArrowDown') {
            event.preventDefault();
            
            if (focusMode.value === 'row') {
                focusRow(activeRowIndex.value + 1);
            } else {
                focusCell(activeRowIndex.value + 1, activeColumnIndex.value);
            }
            
            return;
        }
        
        if (key === 'ArrowUp') {
            event.preventDefault();
            
            if (focusMode.value === 'row') {
                focusRow(activeRowIndex.value - 1);
            } else {
                focusCell(activeRowIndex.value - 1, activeColumnIndex.value);
            }
            
            return;
        }
        
        if (key === 'ArrowRight') {
            event.preventDefault();
            
            if (focusMode.value === 'row') {
                focusCell(activeRowIndex.value, 0);
            } else {
                focusCell(activeRowIndex.value, activeColumnIndex.value + 1);
            }
            
            return;
        }
        
        if (key === 'ArrowLeft') {
            event.preventDefault();
            
            if (focusMode.value === 'cell' && activeColumnIndex.value === 0) {
                focusRow(activeRowIndex.value);
            } else if (focusMode.value === 'cell') {
                focusCell(activeRowIndex.value, activeColumnIndex.value - 1);
            }
            
            return;
        }
        
        if (key === 'Home') {
            event.preventDefault();
            
            if (focusMode.value === 'row') {
                focusRow(0);
            } else {
                focusCell(activeRowIndex.value, 0);
            }
            
            return;
        }
        
        if (key === 'End') {
            event.preventDefault();
            
            if (focusMode.value === 'row') {
                focusRow(getRows().length - 1);
            } else {
                focusCell(activeRowIndex.value, getCells(getCurrentDomRow()).length - 1);
            }
            
            return;
        }
        
        if (key === 'Escape' && focusMode.value === 'cell') {
            event.preventDefault();
            focusRow(activeRowIndex.value);
            return;
        }
        
        if (key === 'Enter' || key === ' ') {
            event.preventDefault();
            
            if (focusMode.value === 'row') {
                if (key === 'Enter') {
                    const row = getCurrentRowApi();

                    if (row) options.onRowActivate?.(row);

                    return;
                }

                toggleCurrentRowSelection();
                return;
            }
            
            activateCurrentCell();
        }
    }
    
    async function refreshKeyboardNavigation() {
        await nextTick();
        
        const rows = getRows();

        activeRowIndex.value = Math.min(activeRowIndex.value, Math.max(rows.length - 1, 0));
        activeColumnIndex.value = Math.min(
            activeColumnIndex.value,
            Math.max(getCells(getCurrentDomRow()).length - 1, 0)
        );

        scheduleRefresh();
        emitActiveRowChange();
    }

    onMounted(() => {
        window.addEventListener('pointerdown', onGlobalPointerDown, true);
        window.addEventListener('mousedown', onGlobalPointerDown, true);
        window.addEventListener('keydown', onGlobalKeyDown, true);
    });

    onBeforeUnmount(() => {
        if (refreshFrame !== null) {
            cancelAnimationFrame(refreshFrame);
        }

        window.removeEventListener('pointerdown', onGlobalPointerDown, true);
        window.removeEventListener('mousedown', onGlobalPointerDown, true);
        window.removeEventListener('keydown', onGlobalKeyDown, true);
    });

    return {
        onKeydown,
        onFocusIn,
        onFocusOut,
        refreshKeyboardNavigation,
        isTableFocused,
        isKeyboardMode,
    };
}
