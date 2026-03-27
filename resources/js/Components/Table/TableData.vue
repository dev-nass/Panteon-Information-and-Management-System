<script setup>
const props = defineProps({
    isHighlighted: {
        default: false,
        type: Boolean,
    },
});
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
            class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800 dark:bg-green-500/10 dark:text-green-400"
        >
            <slot />
        </span>
    </td>
</template>
