<script setup>
import { ref } from 'vue'
// import DataTable from '@/Components/DataTable/DataTable.vue'
import Button from '@/Components/Button/Button.vue';

const selectedUserIds = ref([])

const tableLabels = {
    empty: 'Aucun utilisateur trouvé',
    loading: 'Chargement des utilisateurs...',
    columnVisibility: 'Colonnes',
}

const ui = {
    root({ columns }) {
        
    }
}

const columns = [
    {
        key: 'name',
        label: 'Nom',
        sortable: true,
        hideable: false,
        visible: true,
    },
    {
        key: 'email',
        label: 'Adresse email',
        sortable: true,
        hideable: true,
        visible: true,
    },
    {
        key: 'status',
        label: 'Statut',
        hideable: true,
        visible: true,
    },
    {
        key: 'actions',
        label: '',
        align: 'right',
        hideable: false,
        visible: true,
    },
]

const users = [
    {
        id: 1,
        name: 'Alice Martin',
        email: 'alice@example.com',
        status: 'active',
    },
    {
        id: 2,
        name: 'Nadia Bernard',
        email: 'nadia@example.com',
        status: 'inactive',
    },
    {
        id: 3,
        name: 'John doe',
        email: 'john@example.com',
        status: 'active',
    },
    {
        id: 4,
        name: 'Louis Dupont',
        email: 'louis@example.com',
        status: 'active',
    },
    {
        id: 5,
        name: 'Didier Lasalle',
        email: 'didier@example.com',
        status: 'inactive',
    }
]

function editUser(user) {
    console.log('edit', user)
}

function loadUsers() {
    console.log('Refresh')
}

function deleteSelectedUsers(selectedKeys) {
    console.log('Suppression :', selectedKeys)
}

function exportSelectedUsers(selectedKeys) {
    console.log('Export :', selectedKeys)
}

const usersAction = [
    {
        label: 'Modifier',
        icon: 'pencil',
        command: ({item})=> {
            console.log('edit : ' + item)
        }
    },
    {
        label: 'Voir le profil',
        icon: 'person',
        href: '/profile'
    },
    {
        separator: true,
    },
    {
        label: 'Supprimer',
        icon: 'trash',
        danger: true,
        command: ({item})=> {
            console.log('delete : ' + item)
        }
    }
]
</script>

<template>
    
    <Button
        split
        icon="gear"
        :actions="usersAction"
        class="flex items-center justify-center"
        href="/test"
        @click="()=> console.log('test')"
    >
        <p class="font-bold">Options avancées</p>
    </Button>
    
</template>
