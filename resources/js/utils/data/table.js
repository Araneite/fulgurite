import { h } from "vue";

function getHeader(column, label, components) {
    const { UDropdownMenu, UButton } = components;
    const isSorted = column.getIsSorted();

    return h(
        UDropdownMenu,
        {
            content: {
                align: 'start',
            },
            ui: {
                content: 'z-100',
            },
            'aria-label': 'Actions dropdown',
            items: [
                {
                    label: 'Asc',
                    type: 'checkbox',
                    icon: 'i-lucide-arrow-up-narrow-wide',
                    checked: isSorted === 'asc',
                    onSelect: () => {
                        if (isSorted === 'asc') {
                            column.clearSorting();
                        } else {
                            column.toggleSorting(false);
                        }
                    },
                },
                {
                    label: 'Desc',
                    icon: 'i-lucide-arrow-down-wide-narrow',
                    type: 'checkbox',
                    checked: isSorted === 'desc',
                    onSelect: () => {
                        if (isSorted === 'desc') {
                            column.clearSorting();
                        } else {
                            column.toggleSorting(true);
                        }
                    },
                },
            ],
        },
        () =>
            h(UButton, {
                color: 'neutral',
                variant: 'ghost',
                label,
                icon: isSorted
                    ? isSorted === 'asc'
                        ? 'i-lucide-arrow-up-narrow-wide'
                        : 'i-lucide-arrow-down-wide-narrow'
                    : 'i-lucide-arrow-up-down',
                class: '-mx-2.5 data-[state=open]:bg-elevated',
                'aria-label': `Sort by ${isSorted === 'asc' ? 'descending' : 'ascending'}`,
            })
    );
}

export { getHeader };
