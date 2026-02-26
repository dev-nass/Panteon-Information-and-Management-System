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
        <p class="text-base font-medium text-gray-800 dark:text-white">
            {{ value || "—" }}
        </p>
    </div>
</template>
