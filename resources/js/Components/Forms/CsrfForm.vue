<script setup>
import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
    action: {
        type: String,
        required: true,
    },
    method: {
        type: String,
        default: "POST",
    }
})

const page = usePage();

const normalizedMethod = computed(()=> props.method.toLowerCase());
const formMethod = computed(()=> normalizedMethod.value === "get" ? "get": "post");
const csrfToken = computed(()=> page.props.csrf_token)
</script>

<template>

    <form :action="action" :method="formMethod">
        <input
            v-if="!formMethod === 'get'"
            type="hidden" name="_token" :value="csrfToken" />

        <input
            v-if="!['get', 'post'].includes(normalizedMethod)"
            type="hidden"
            name="_method"
            :value="normalizedMethod.toUpperCase()"
        >
        
        <slot />
    </form>
</template>

<style scoped>

</style>
