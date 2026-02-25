<script setup>
const props = defineProps({
    placeholder: {
        type: String,
    },
    type: {
        type: String,
    },
});
</script>

<template>
    <div
        class="flex items-center gap-2 w-full max-w-md px-3 py-2.5 rounded-lg bg-white dark:bg-neutral-900 border border-gray-300 dark:border-neutral-700 focus-within:border-green-500 focus-within:ring-2 focus-within:ring-green-500 transition"
    >
        <!-- Search Icon -->
        <svg
            v-if="type === 'search'"
            xmlns="http://www.w3.org/2000/svg"
            width="19"
            height="19"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            class="text-gray-400 dark:text-gray-500"
        >
            <path d="m21 21-4.34-4.34" />
            <circle cx="11" cy="11" r="8" />
        </svg>

        <!-- Input -->
        <input
            type="text"
            :placeholder="placeholder"
            class="flex-1 bg-transparent text-black dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none"
        />
    </div>
</template>
