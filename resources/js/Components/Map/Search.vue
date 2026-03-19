<script setup>
import { ref } from "vue";

const showSuggestions = ref(false);

const props = defineProps({
    modelValue: {
        type: String,
        required: true,
    },
    suggestions: {
        type: Array,
        default: () => [],
    },
    placeholder: {
        type: String,
        default: "Search...",
    },
});

const emit = defineEmits(["update:modelValue", "select-suggestion"]);

const onInput = (e) => {
    emit("update:modelValue", e.target.value);
};

const onFocus = () => {
    showSuggestions.value = true;
};

const onBlur = () => {
    // delay so click still works
    setTimeout(() => {
        showSuggestions.value = false;
    }, 100);
};
</script>

<template>
    <div class="relative w-full max-w-md">
        <!-- Input Container (MATCHED DESIGN) -->
        <div
            class="flex items-center gap-2 w-full px-3 py-2.5 rounded-lg bg-white dark:bg-neutral-900 border border-gray-300 dark:border-neutral-700 focus-within:border-green-500 focus-within:ring-2 focus-within:ring-green-500 transition"
        >
            <!-- Search Icon -->
            <svg
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
                :value="modelValue"
                :placeholder="placeholder"
                autocomplete="off"
                @input="onInput"
                @focus="onFocus"
                @blur="onBlur"
                class="flex-1 bg-transparent text-black dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none"
            />
        </div>

        <!-- Suggestions Dropdown -->
        <div
            v-if="showSuggestions"
            class="absolute z-50 w-full mt-1 rounded-lg bg-white dark:bg-neutral-900 border border-gray-300 dark:border-neutral-700 shadow-lg overflow-hidden"
        >
            <!-- Suggestions -->
            <template v-if="suggestions.length">
                <p
                    v-for="suggestion in suggestions"
                    :key="suggestion.id"
                    @mousedown.prevent="emit('select-suggestion', suggestion)"
                    class="px-3 py-2 text-sm text-black dark:text-white hover:bg-gray-100 dark:hover:bg-neutral-800 cursor-pointer transition"
                >
                    {{ suggestion.first_name + " " + suggestion.last_name }}
                </p>
            </template>

            <!-- Empty State -->
            <p
                v-else
                class="px-3 py-2 text-sm text-gray-400 dark:text-gray-500"
            >
                No results found
            </p>
        </div>
    </div>
</template>
