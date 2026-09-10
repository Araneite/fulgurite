<script setup lang="ts">
import { computed, ref, watch } from 'vue';

type Requirement = {
    regex: RegExp;
    text: string;
};

const password = defineModel<string>('password', { default: ''});
const confirmation = defineModel<string>('confirmation', { default: ''});

const props = withDefaults(defineProps<{
    passwordLabel: string;
    confirmationLabel: string;
    passwordError?: string | null;
    confirmationError?: string | null;
    validation: {
        length: string;
        number: string;
        lowercase: string;
        uppercase: string;
        symbol: string;
        confirmed: string;
    };
    required?: boolean;
    minScore?: number;
}>(), {
    passwordError: null,
    confirmationError: null,
    required: true,
    minScore: 5,
});

const emit = defineEmits<{
    valid: [value: boolean];
    score: [value: number];
}>();

const showPassword = ref(false);
const showConfirmation = ref(false);

const requirements = computed<Requirement[]>(()=> [
    { regex: /.{8,}/, text: props.validation.length },
    { regex: /\d/, text: props.validation.number },
    { regex: /[a-z]/, text: props.validation.lowercase },
    { regex: /[A-Z]/, text: props.validation.uppercase },
    { regex: /[!"#$%&'()*+,\-./:;<=>?@[\\\]^_`{|}~]/, text: props.validation.symbol },
]);

const strength = computed(()=> {
    return requirements.value.map((requirements)=> ({
        met: requirements.regex.test(password.value ?? ''),
        text: requirements.text,
    }));
});

const score = computed(() => {
    return strength.value.filter((requirement)=> requirement.met).length;
});
const passwordsMatch = computed(() => {
    return password.value !== ''
        && confirmation.value !== ''
        && password.value === confirmation.value;
});

const isValid = computed(() => {
    return score.value >= props.minScore && passwordsMatch.value;
})
const passwordColor = computed(() => {
    if (score.value === 0) return 'neutral';
    if (score.value <= 1) return 'error';
    if (score.value <= 4) return 'warning';
    
    return 'success';
})
const confirmationColor = computed(() => {
    if (confirmation.value === '') return 'neutral';
    
     return passwordsMatch.value ? 'success' : 'error';
});

const validatorText = computed(() => {
    if (score.value === 0) return 'Saisissez un mot de passe';
    if (score.value <= 1) return 'Mot de passe faible';
    if (score.value <= 4) return 'Mot de passe moyen';
    
    return 'Mot de passe fort';
});
const confirmationText = computed(() => {
    return passwordsMatch.value
        ? 'Les mots de passe correspondent.'
        : props.validation.confirmed;
});

watch(isValid, (value)=> emit('valid', value), { immediate: true });
watch(score, (value)=> emit('score', value), { immediate: true });
</script>

<template>
    <div class="space-y-4">
        <div>
            <UFormField
                :label="passwordLabel"
                :error="passwordError"
                :required="required"
                name="password"
            >
                <UInput
                    v-model="password"
                    :color="passwordColor"
                    :type="showPassword ? 'text' : 'password'"
                    :aria-invalid="score < minScore"
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
                            :aria-label="showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
                            :aria-pressed="showPassword"
                            type="button"
                            @click="showPassword = !showPassword"
                        />
                    </template>
                </UInput>
            </UFormField>
            
            <UProgress
                :color="passwordColor"
                :indicator="validatorText"
                :model-value="score"
                :max="5"
                size="sm"
                class="mt-1"
            />
            
            <p id="password-strength" class="mt-2 text-sm font-medium">
                {{ validatorText }}<span v-if="score < 5">. Doit contenir :</span>
            </p>
            
            <ul class="mt-2 space-y-1" aria-label="Exigences du mot de passe">
                <li
                    v-for="(requirement, index) in strength"
                    :key="index"
                    class="flex items-center gap-1"
                    :class="requirement.met ? 'text-success' : 'text-muted'"
                >
                    <UIcon
                        :name="requirement.met ? 'i-lucide-circle-check' : 'i-lucide-circle-x'"
                        class="size-4 shrink-0"
                    />
                    
                    <span class="text-sm font-light">
                        {{ requirement.text }}
                    </span>
                </li>
            </ul>
        </div>
        
        <UFormField
            v-if="score >= minScore"
            :label="confirmationLabel"
            :error="confirmationError"
            :required="required"
            name="password_confirmation"
        >
            <UInput
                v-model="confirmation"
                :color="confirmationColor"
                :type="showConfirmation ? 'text' : 'password'"
                aria-describedby="password-confirmation"
                :ui="{ trailing: 'pe-1' }"
                class="w-full"
            >
                <template #trailing>
                    <UButton
                        color="neutral"
                        variant="link"
                        size="sm"
                        :icon="showConfirmation ? 'i-lucide-eye-off' : 'i-lucide-eye'"
                        :aria-label="showConfirmation ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
                        :aria-pressed="showConfirmation"
                        type="button"
                        @click="showConfirmation = !showConfirmation"
                    />
                </template>
            </UInput>
            
            <p
                v-if="confirmation !== ''"
                id="password-confirmation"
                class="mt-1 text-sm font-medium"
                :class="passwordsMatch ? 'text-success/80' : 'text-danger/80'"
            >
                {{ confirmationText }}
            </p>
        </UFormField>
    </div>
</template>

<style scoped>

</style>
