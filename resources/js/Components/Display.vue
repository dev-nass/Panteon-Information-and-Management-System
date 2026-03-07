<script setup>
import { computed } from "vue";

const props = defineProps({
    label: String,
    modelValue: [String, Number],
    value: [String, Number],
    editing: Boolean,
    type: {
        type: String,
        default: "text",
    },
});

const emit = defineEmits(["update:modelValue"]);

const displayValue = computed(() => props.modelValue ?? props.value);

const updateValue = (event) => {
    emit("update:modelValue", event.target.value);
};
</script>

<template>
    <div>
        <label class="text-sm text-gray-500 dark:text-gray-400">
            {{ label }}
        </label>
        <input
            :type="type"
            :value="displayValue"
            @input="updateValue"
            :disabled="!editing"
            placeholder="—"
            class="mt-1 w-full rounded-lg border border-gray-300 dark:border-neutral-700 bg-gray-100 dark:bg-neutral-800 text-gray-800 dark:text-white px-3 py-2 text-sm transition disabled:border-none disabled:bg-white disabled:dark:bg-neutral-900 disabled:dark:text-gray-200 disabled:hover:text-green-600 disabled:hover:dark:text-green-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
        />
    </div>
</template>
