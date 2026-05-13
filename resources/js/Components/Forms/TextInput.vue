<script setup>
defineProps({
    id: {type: String, default: null},
    name: {type: String, required: true},
    type: {type: String, default: "text"},
    label: {type: String, required: true},
    autocomplete: {type: String, default: "off"},
    error: {type: String, default: null},
    autofocus: {type: Boolean, default: false},
    required: {type: Boolean, default: false},
    readonly: {type: Boolean, default: false},
    selectable: {type: Boolean, default: false},
    value: {type: String, default: null},
})

const model = defineModel({
    type: [String, Number],
    default: ""
});
</script>

<template>
    <div class="w-full">
        <div class="input-group text" :class="readonly
            ? (
                selectable
                    ? 'brightness-70 cursor-text [&_input]:cursor-text [&_label]:cursor-text'
                    : 'brightness-70 cursor-not-allowed select-none [&_input]:cursor-not-allowed [&_label]:cursor-not-allowed'
            )
            : ''
        ">
            <input 
                :id="id ?? name"
                v-model="model"
                :type="type"
                :name="name"
                :autocomplete="autocomplete"
                :autofocus="autofocus"
                placeholder=""
                :required="required"
                :readonly="readonly"
            >
    
            <label :for="id ?? name">{{ label }}{{ required ? "*" : "" }}</label>
        </div>
        <p v-if="error" class="mt-2 text-sm text-red-400">{{ error }}</p>
    </div>
</template>

<style scoped>

</style>
