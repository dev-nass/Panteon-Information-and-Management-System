<script setup>
import { computed } from "vue";

const props = defineProps({
    size: {
        type: String,
        default: "base",
    },
    // highlighted is used on 'Clerk/BurialRecords/Show' Edit button
    // Not highlihted is used on 'Clerk/Burial/Index' table buttons
    highlighted: {
        type: Boolean,
        default: false,
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

const variantClasses = computed(() => {
    if (props.highlighted) {
        return `
        bg-green-500/10 text-green-400
        border-transparent
        hover:bg-green-500/20
        hover:border-green-500/40
        hover:text-green-600
        dark:hover:text-green-300
        `;
    }

    return `
    bg-white dark:bg-neutral-900
    border-gray-300 dark:border-neutral-700
    focus-within:border-green-500
    focus-within:ring-2
    focus-within:ring-green-500
    focus:text-green-400
    `;
});
</script>

<template>
    <button
        type="button"
        class="flex items-center gap-2 max-w-md rounded-lg border transition duration-200"
        :class="[sizeClasses, variantClasses]"
    >
        <slot />
    </button>
</template>
