<script setup>
defineProps({
    title: String,
    value: String,
    change: String,
});
</script>

<template>
    <div
        class="p-5 rounded-xl bg-white dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 shadow-sm hover:shadow-md transition"
    >
        <div class="flex justify-between items-start">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ title }}
            </p>

            <span class="text-gray-400">•••</span>
        </div>

        <div class="mt-3 text-2xl font-bold text-gray-900 dark:text-white">
            {{ value }}
        </div>

        <p class="text-sm text-green-500 mt-1">
            {{ change }} from last quarter
        </p>
    </div>
</template>
