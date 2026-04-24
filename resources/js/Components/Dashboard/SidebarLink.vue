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
            'group relative w-full flex items-center gap-1 py-1.5 px-2.5 text-sm rounded-lg before:absolute before:inset-y-0 before:-start-2 before:rounded-e-full before:w-1 before:h-full',
            isActive
                ? 'bg-green-400 dark:bg-green-900 text-gray-900 dark:text-green-200'
                : 'text-gray-600 dark:text-neutral-200 hover:bg-neutral-100 dark:hover:bg-neutral-700',
        ]"
    >
        <span
            class="-ms-[5px] flex shrink-0 justify-center items-center size-6"
        >
            <slot name="icon" />
        </span>
        <span
            class="truncate hs-overlay-minified:opacity-0 transition-opacity duration-300"
        >
            <slot name="text" />
        </span>
    </Link>
</template>
