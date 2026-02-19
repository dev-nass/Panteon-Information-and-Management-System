<script setup>
import { Link } from "@inertiajs/vue3";

const props = defineProps({
    isActive: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <Link
        href="#"
        :class="[
            'min-h-[36px] w-full flex items-center gap-x-3.5 py-2 px-2.5 text-sm rounded-lg transition-colors',
            props.isActive
                ? 'bg-gray-100 dark:bg-neutral-700 text-gray-900 dark:text-white'
                : 'text-gray-800 dark:text-neutral-200 hover:bg-gray-100 dark:hover:bg-neutral-700',
        ]"
    >
        <slot />
    </Link>
</template>
