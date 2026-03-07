<script setup>
defineProps({
    label: String,
    value: [String, Number],
});
</script>

<template>
    <div>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ label }}
        </p>
        <p
            class="text-base font-medium text-gray-800 dark:text-white transition duration-300 hover:text-green-600 hover:dark:text-green-400"
        >
            {{ value || "—" }}
        </p>
    </div>
</template>
