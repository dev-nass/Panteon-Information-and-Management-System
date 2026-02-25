<script setup>
import { computed } from "vue";

const props = defineProps({
    size: {
        type: String,
        default: "base",
    },
});

const sizeClasses = computed(() => {
    switch (props.size) {
        case "sm":
            return "px-2 py-1.5 text-sm";
        case "lg":
            return "px-4 py-3 text-lg";
        default:
            return "px-3 py-2.5 text-base";
    }
});
</script>

<template>
    <button
        type="button"
        class="flex items-center gap-2 w-full max-w-md rounded-lg border transition bg-white dark:bg-neutral-900 border-gray-300 dark:border-neutral-700 focus-within:border-green-500 focus-within:ring-2 focus-within:ring-green-500 focus:text-green-400"
        :class="[sizeClasses]"
    >
        <slot />
    </button>
</template>
