<script setup>
import Modal from "@/Components/Modal.vue";
import { ref, watch } from "vue";

const props = defineProps({
    filterOptions: { type: Object, default: () => ({ barangays: [] }) },
    activeFilters: {
        type: Object,
        default: () => ({ age_range: null, barangay: null }),
    },
});

const emit = defineEmits(["apply", "reset"]);

const modalId = "dashboard-filters-modal";

const localAgeRange = ref(props.activeFilters?.age_range ?? null);
const localBarangay = ref(props.activeFilters?.barangay ?? null);

watch(
    () => props.activeFilters,
    (val) => {
        localAgeRange.value = val?.age_range ?? null;
        localBarangay.value = val?.barangay ?? null;
    },
    { deep: true },
);

const ageRanges = [
    { value: null, label: "All Ages" },
    { value: "0-12", label: "Child (0-12)" },
    { value: "13-19", label: "Teen (13-19)" },
    { value: "20-39", label: "Young Adult (20-39)" },
    { value: "40-59", label: "Adult (40-59)" },
    { value: "60-74", label: "Senior (60-74)" },
    { value: "75+", label: "Elderly (75+)" },
];

const applyFilters = () => {
    emit("apply", {
        age_range: localAgeRange.value,
        barangay: localBarangay.value,
    });
};

const resetFilters = () => {
    localAgeRange.value = null;
    localBarangay.value = null;
    emit("reset");
};

const hasActiveFilters = () => {
    return localAgeRange.value !== null || localBarangay.value !== null;
};
</script>

<template>
    <Modal :id="modalId" size="lg">
        <template #header>
            <svg
                class="size-7"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
            >
                <path
                    d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"
                />
            </svg>
        </template>

        <template #main>
            <h3
                :id="`${modalId}-label`"
                class="text-2xl font-bold text-green-600 dark:text-green-400"
            >
                Dashboard Filters
            </h3>
            <p class="text-gray-500 dark:text-gray-400 mt-1">
                Filter records by age range and barangay
            </p>

            <div class="w-full mt-6 space-y-5 text-left">
                <div class="space-y-2">
                    <label
                        for="age-range"
                        class="block text-sm font-semibold text-gray-700 dark:text-gray-300"
                    >
                        Age Range
                    </label>
                    <select
                        id="age-range"
                        v-model="localAgeRange"
                        class="w-full px-4 py-2.5 border bg-white dark:bg-neutral-800 border-gray-200 dark:border-neutral-700 rounded-xl text-sm text-gray-800 dark:text-neutral-200 focus:border-green-500 focus:ring-2 focus:ring-green-500 transition"
                    >
                        <option
                            v-for="range in ageRanges"
                            :key="range.value ?? 'all'"
                            :value="range.value"
                        >
                            {{ range.label }}
                        </option>
                    </select>
                </div>

                <div class="space-y-2">
                    <label
                        for="barangay"
                        class="block text-sm font-semibold text-gray-700 dark:text-gray-300"
                    >
                        Barangay
                    </label>
                    <select
                        id="barangay"
                        v-model="localBarangay"
                        class="w-full px-4 py-2.5 border bg-white dark:bg-neutral-800 border-gray-200 dark:border-neutral-700 rounded-xl text-sm text-gray-800 dark:text-neutral-200 focus:border-green-500 focus:ring-2 focus:ring-green-500 transition"
                    >
                        <option :value="null">All Barangays</option>
                        <option
                            v-for="barangay in filterOptions.barangays"
                            :key="barangay"
                            :value="barangay"
                        >
                            {{ barangay.charAt(0).toUpperCase() + barangay.slice(1) }}
                        </option>
                    </select>
                </div>
            </div>
        </template>

        <template #footer>
            <button
                type="button"
                class="w-full py-3 text-sm font-semibold text-gray-600 dark:text-gray-400 hover:bg-gray-500/10 transition"
                :data-hs-overlay="`#${modalId}`"
                @click="resetFilters"
            >
                Reset
            </button>
            <button
                type="button"
                class="w-full py-3 text-sm font-semibold text-green-600 dark:text-green-400 hover:bg-green-500/10 transition"
                :data-hs-overlay="`#${modalId}`"
                @click="applyFilters"
            >
                Apply Filters
            </button>
        </template>
    </Modal>
</template>
