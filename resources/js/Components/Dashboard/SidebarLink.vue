<script setup>
import { Link } from "@inertiajs/vue3";

const props = defineProps({
    isActive: {
        type: Boolean,
    },
});
</script>

<template>
    <Link
        :class="[
            'min-h-[36px] w-full flex items-center gap-x-3.5 py-2 px-2.5 text-sm rounded-lg transition-colors',
            props.isActive
                ? 'bg-green-500 dark:bg-green-900 text-gray-900 dark:text-green-200'
                : 'text-gray-800 dark:text-neutral-200 hover:bg-neutral-100 dark:hover:bg-neutral-700',
        ]"
    >
        <slot />
    </Link>
</template>
