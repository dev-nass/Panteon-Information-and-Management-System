<script setup>
const props = defineProps({
    column_name: Object,
});

const emit = defineEmits(["sort"]);

const handleSort = (field) => {
    emit("sort", field);
};
</script>

<template>
    <th
        @click="handleSort(column_name)"
        class="cursor-pointer px-6 py-3 text-xs font-semibold uppercase text-gray-800 dark:text-neutral-200 text-left"
    >
        <slot />
    </th>
</template>
