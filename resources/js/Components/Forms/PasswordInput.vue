<script setup>
import { computed, reactive } from 'vue';

defineProps({
    id: {type: String, default: null},
    name: {type: String, required: true},
    label: {type: String, required: true},
    autocomplete: {type: String, default: "current-password"},
    error: {rtpe: String, default: null},
    required: {type: Boolean, default: false},
    readonly: {type: Boolean, default: false},
});

const model = defineModel({
    type: String,
    default: ""
});

const eye = reactive({
    visible: false,
    pupilX: 0,
    pupilY: 0,
});

const inputType = computed(()=> eye.visible ? "text" : "password");

function toggle() {
    eye.visible = !eye.visible;
}

function trackPoint(event) {
    if (eye.visible) return;
    
    const svg = event.currentTarget.querySelector(".password-eye");
    
    if (!svg) return;
    
    const rect = svg.getBoundingClientRect();
    const centerX = rect.left + rect.width / 2;
    const centerY = rect.top + rect.height / 2;
    
    const dx = event.clientX - centerX;
    const dy = event.clientY - centerY;
    
    eye.pupilX = Math.max(-4, Math.min(4, dx / 18));
    eye.pupilY = Math.max(-3, Math.min(3, dy / 18));
}

function reset() {
    eye.pupilX = 0;
    eye.pupilY = 0;
}

defineExpose({
    trackPoint,
    reset,
});
</script>

<template>
    <div class="w-full">
        <div class="input-group text">
            <input
                :id="id ?? name"
                v-model="model"
                :type="inputType"
                :name="name"
                :autocomplete="autocomplete"
                placeholder=""
                class="pr-12"
                :required="required"
                :disabled="readonly"
            >
        
            <label :for="id ?? name">{{ label }}{{ required ? "*" : "" }}</label>
        
            <button
                type="button"
                class="absolute right-3 top-1/2 grid size-8 -translate-y-1/2 cursor-pointer place-items-center rounded-md text-text-400 hover:text-text-300 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-primary"
                :aria-label="eye.visible ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
                @click="toggle"
            >
                <svg
                    class="password-eye"
                    :class="{ 'is-closed': eye.visible }"
                    viewBox="0 0 64 40"
                    width="28"
                    height="28"
                    aria-hidden="true"
                >
                    <path class="password-eye__upper" d="M4 20C10 9 20 4 32 4C44 4 54 9 60 20" />
                    <path class="password-eye__lower" d="M4 20C10 31 20 36 32 36C44 36 54 31 60 20" />
        
                    <g
                        class="password-eye__iris"
                        :style="`transform: translate(${eye.pupilX}px, ${eye.pupilY}px)`"
                    >
                        <circle cx="32" cy="20" r="8" class="password-eye__iris-fill" />
                        <circle cx="32" cy="20" r="3.5" class="password-eye__pupil" />
                    </g>
        
                    <path class="password-eye__closed-line" d="M6 22C14 16 22 14 32 14C42 14 50 16 58 22" />
                    <path class="password-eye__lash password-eye__lash-left" d="M18 24L14 31" />
                    <path class="password-eye__lash password-eye__lash-center" d="M32 26L32 34" />
                    <path class="password-eye__lash password-eye__lash-right" d="M46 24L50 31" />
                </svg>
            </button>
        </div>
        <p v-if="error" class="mt-2 text-sm text-red-400">
            {{error}}
        </p>
    </div>
</template>

<style scoped>

</style>
