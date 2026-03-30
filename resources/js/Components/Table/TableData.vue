<script setup>
const props = defineProps({
    isHighlighted: {
        default: false,
        type: Boolean,
    },
    highlightColor: {
        default: 'green',
        type: String,
        validator: (value) => ['green', 'red'].includes(value),
    },
});

const highlightClasses = {
    green: 'bg-green-100 text-green-800 dark:bg-green-500/10 dark:text-green-400',
    red: 'bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-400',
};
</script>

<template>
    <td
        v-if="!isHighlighted"
        class="px-6 py-4 text-sm text-gray-800 dark:text-neutral-200"
    >
        <slot />
    </td>

    <td v-else class="px-6 py-4">
        <span
            class="px-2 py-1 text-xs rounded-full"
            :class="highlightClasses[highlightColor]"
        >
            <slot />
        </span>
    </td>
</template>
