<script setup>
import { computed } from "vue";

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false,
    },
    label: {
        type: String,
        default: "",
    },
    size: {
        type: String,
        default: "md", // xs, sm, md, lg
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    id: {
        type: String,
        default: () => `switch-${Math.random().toString(36).slice(2)}`,
    },
});

const emit = defineEmits(["update:modelValue"]);

const toggle = () => {
    if (props.disabled) return;
    emit("update:modelValue", !props.modelValue);
};

/**
 * Size system
 */
const sizes = {
    xs: {
        container: "w-9 h-5",
        knob: "size-4",
    },
    sm: {
        container: "w-11 h-6",
        knob: "size-5",
    },
    md: {
        container: "w-13 h-7",
        knob: "size-6",
    },
    lg: {
        container: "w-15 h-8",
        knob: "size-7",
    },
};

const sizeClasses = computed(() => sizes[props.size]);
</script>

<template>
    <div class="flex items-center gap-x-3">
        <!-- Switch -->
        <label
            :for="id"
            class="relative inline-block cursor-pointer"
            :class="sizeClasses.container"
        >
            <input
                type="checkbox"
                :id="id"
                class="peer sr-only"
                :checked="modelValue"
                :disabled="disabled"
                @change="toggle"
            />

            <!-- Track -->
            <span
                class="absolute inset-0 rounded-full transition-colors duration-200 ease-in-out bg-gray-200 dark:bg-neutral-600 peer-checked:bg-green-500 dark:peer-checked:bg-green-400 peer-disabled:opacity-50 peer-disabled:pointer-events-none"
            ></span>

            <!-- Knob -->
            <span
                class="absolute top-1/2 start-0.5 -translate-y-1/2 rounded-full bg-white shadow-sm transition-transform duration-200 ease-in-out"
                :class="[sizeClasses.knob, 'peer-checked:translate-x-full']"
            ></span>
        </label>

        <!-- Label -->
        <label
            :for="id"
            class="text-sm text-gray-500 dark:text-neutral-400 cursor-pointer"
        >
            {{ label }}
        </label>
    </div>
</template>
