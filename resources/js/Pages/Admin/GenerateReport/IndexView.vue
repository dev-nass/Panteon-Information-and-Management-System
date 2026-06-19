<script setup>
import { ref, computed, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import Dashboard from "@/Layouts/Dashboard.vue";
import Button from "@/Components/Form/Button.vue";

defineOptions({
    layout: Dashboard,
});

const reportType = ref("");
const startDate = ref("");
const endDate = ref("");
const monthDate = ref("");
const format = ref("pdf");
const isGenerating = ref(false);

// Computed property to determine which date fields to show
const showDateRange = computed(() => {
    return reportType.value === "burial" || reportType.value === "deceased";
});

const showMonthPicker = computed(() => {
    return reportType.value === "summary";
});

const showNoDates = computed(() => {
    return reportType.value === "phase";
});

// Reset date fields when report type changes
watch(reportType, () => {
    startDate.value = "";
    endDate.value = "";
    monthDate.value = "";
});

const setDateRange = (type) => {
    const today = new Date();
    const formatDate = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };

    if (type === 'today') {
        startDate.value = formatDate(today);
        endDate.value = formatDate(today);
    } else if (type === 'week') {
        const dayOfWeek = today.getDay();
        const firstDay = new Date(today);
        firstDay.setDate(today.getDate() - dayOfWeek);
        const lastDay = new Date(today);
        lastDay.setDate(today.getDate() + (6 - dayOfWeek));
        startDate.value = formatDate(firstDay);
        endDate.value = formatDate(lastDay);
    } else if (type === 'month') {
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        startDate.value = formatDate(firstDay);
        endDate.value = formatDate(lastDay);
    } else if (type === 'year') {
        const firstDay = new Date(today.getFullYear(), 0, 1);
        const lastDay = new Date(today.getFullYear(), 11, 31);
        startDate.value = formatDate(firstDay);
        endDate.value = formatDate(lastDay);
    }
};

const resetForm = () => {
    reportType.value = "";
    startDate.value = "";
    endDate.value = "";
    monthDate.value = "";
    format.value = "pdf";
};

const generateReport = () => {
    // Validation based on report type
    if (!reportType.value) {
        alert("Please select a report type");
        return;
    }

    if (showDateRange.value && (!startDate.value || !endDate.value)) {
        alert("Please fill in start and end dates");
        return;
    }

    if (showMonthPicker.value && !monthDate.value) {
        alert("Please select a month");
        return;
    }

    isGenerating.value = true;

    // Build query parameters based on report type
    const params = {
        reportType: reportType.value,
        format: format.value,
    };

    if (showDateRange.value) {
        params.startDate = startDate.value;
        params.endDate = endDate.value;
    } else if (showMonthPicker.value) {
        params.monthDate = monthDate.value;
    }

    const queryString = new URLSearchParams(params).toString();
    const url = route("admin.generate_report.generate") + "?" + queryString;
    window.open(url, "_blank");

    setTimeout(() => {
        isGenerating.value = false;
    }, 1000);
};
</script>

<template>
    <div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-6 mx-auto">
        <div class="flex flex-col items-center">
            <div class="-m-1.5 overflow-x-auto">
                <div class="p-1.5 min-w-[600px] inline-block align-middle">
                    <div
                        class="flex flex-col gap-y-6 bg-white/60 dark:bg-neutral-900/60 backdrop-blur-xl px-6 py-6 border border-white/20 dark:border-white/10 rounded-xl shadow-lg overflow-hidden"
                    >
                        <!-- Header -->
                        <div class="flex gap-x-4 items-center">
                            <div
                                class="flex items-center justify-center size-12 rounded-full bg-green-500/10 text-green-600 dark:text-green-400"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="22"
                                    height="22"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="lucide lucide-chart-pie-icon lucide-chart-pie"
                                >
                                    <path
                                        d="M21 12c.552 0 1.005-.449.95-.998a10 10 0 0 0-8.953-8.951c-.55-.055-.998.398-.998.95v8a1 1 0 0 0 1 1z"
                                    />
                                    <path d="M21.21 15.89A10 10 0 1 1 8 2.83" />
                                </svg>
                            </div>

                            <article>
                                <h1
                                    class="text-2xl font-bold text-green-600 dark:text-green-400"
                                >
                                    Generate Report
                                </h1>

                                <p
                                    class="text-sm text-gray-500 dark:text-gray-400"
                                >
                                    Access and generate reports from stored
                                    burial records.
                                </p>
                            </article>
                        </div>

                        <!-- Form -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <!-- Report Type -->
                            <div class="flex flex-col col-span-full gap-1">
                                <label
                                    class="text-sm font-medium text-gray-600 dark:text-gray-300"
                                >
                                    Type of Report
                                </label>

                                <select
                                    v-model="reportType"
                                    class="py-2 px-4 pe-12 w-full border bg-white dark:bg-neutral-800 border-gray-200 dark:border-neutral-700 rounded-lg text-sm text-gray-800 dark:text-neutral-200 focus:border-green-500 focus:ring-2 focus:ring-green-500"
                                >
                                    <option disabled value="">
                                        Select report type
                                    </option>
                                    <option value="burial">
                                        Burial Records
                                    </option>
                                    <option value="deceased">
                                        Deceased Records
                                    </option>
                                    <option value="summary">
                                        Monthly Summary
                                    </option>
                                    <option value="phase">
                                        Phase Availability
                                    </option>
                                </select>
                            </div>

                            <!-- Date Range (for burial and deceased) -->
                            <template v-if="showDateRange">
                                <!-- Quick Date Buttons -->
                                <div class="flex gap-2 col-span-full">
                                    <button
                                        @click="setDateRange('today')"
                                        type="button"
                                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-neutral-700 border border-gray-200 dark:border-neutral-600 rounded-lg hover:bg-gray-200 dark:hover:bg-neutral-600 transition"
                                    >
                                        Today
                                    </button>
                                    <button
                                        @click="setDateRange('week')"
                                        type="button"
                                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-neutral-700 border border-gray-200 dark:border-neutral-600 rounded-lg hover:bg-gray-200 dark:hover:bg-neutral-600 transition"
                                    >
                                        This Week
                                    </button>
                                    <button
                                        @click="setDateRange('month')"
                                        type="button"
                                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-neutral-700 border border-gray-200 dark:border-neutral-600 rounded-lg hover:bg-gray-200 dark:hover:bg-neutral-600 transition"
                                    >
                                        This Month
                                    </button>
                                    <button
                                        @click="setDateRange('year')"
                                        type="button"
                                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-neutral-700 border border-gray-200 dark:border-neutral-600 rounded-lg hover:bg-gray-200 dark:hover:bg-neutral-600 transition"
                                    >
                                        This Year
                                    </button>
                                </div>

                                <!-- Start Date -->
                                <div class="flex flex-col gap-1">
                                    <label
                                        class="text-sm font-medium text-gray-600 dark:text-gray-300"
                                    >
                                        Start Date
                                    </label>

                                    <input
                                        type="date"
                                        v-model="startDate"
                                        class="py-2 px-4 w-full border bg-white dark:bg-neutral-800 border-gray-200 dark:border-neutral-700 rounded-lg text-sm text-gray-800 dark:text-neutral-200 focus:border-green-500 focus:ring-2 focus:ring-green-500"
                                    />
                                </div>

                                <!-- End Date -->
                                <div class="flex flex-col gap-1">
                                    <label
                                        class="text-sm font-medium text-gray-600 dark:text-gray-300"
                                    >
                                        End Date
                                    </label>

                                    <input
                                        type="date"
                                        v-model="endDate"
                                        class="py-2 px-4 w-full border bg-white dark:bg-neutral-800 border-gray-200 dark:border-neutral-700 rounded-lg text-sm text-gray-800 dark:text-neutral-200 focus:border-green-500 focus:ring-2 focus:ring-green-500"
                                    />
                                </div>
                            </template>

                            <!-- Month Picker (for monthly summary) -->
                            <div
                                v-if="showMonthPicker"
                                class="flex flex-col col-span-full gap-1"
                            >
                                <label
                                    class="text-sm font-medium text-gray-600 dark:text-gray-300"
                                >
                                    Select Month
                                </label>

                                <input
                                    type="month"
                                    v-model="monthDate"
                                    class="py-2 px-4 w-full border bg-white dark:bg-neutral-800 border-gray-200 dark:border-neutral-700 rounded-lg text-sm text-gray-800 dark:text-neutral-200 focus:border-green-500 focus:ring-2 focus:ring-green-500"
                                />
                            </div>

                            <!-- No Date Fields (for phase availability) -->
                            <div v-if="showNoDates" class="col-span-full">
                                <p
                                    class="text-sm text-gray-500 dark:text-gray-400 italic"
                                >
                                    This report shows current phase availability
                                    data.
                                </p>
                            </div>

                            <!-- Export Format -->
                            <div class="flex flex-col col-span-full gap-1">
                                <label
                                    class="text-sm font-medium text-gray-600 dark:text-gray-300"
                                >
                                    Export Format
                                </label>

                                <div class="flex gap-4">
                                    <label
                                        class="flex items-center gap-2 cursor-pointer"
                                    >
                                        <input
                                            type="radio"
                                            v-model="format"
                                            value="pdf"
                                            class="w-4 h-4 text-green-600 focus:ring-green-500"
                                        />
                                        <span
                                            class="text-sm text-gray-700 dark:text-gray-300"
                                        >
                                            PDF (Recommended)
                                        </span>
                                    </label>
                                    <label
                                        class="flex items-center gap-2 cursor-pointer"
                                    >
                                        <input
                                            type="radio"
                                            v-model="format"
                                            value="excel"
                                            class="w-4 h-4 text-green-600 focus:ring-green-500"
                                        />
                                        <span
                                            class="text-sm text-gray-700 dark:text-gray-300"
                                        >
                                            Excel
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="flex justify-end gap-3 pt-2">
                            <Button @click="resetForm">
                                <span class="dark:text-white"> Reset </span>
                            </Button>
                            <Button
                                @click="generateReport"
                                :highlighted="true"
                                :disabled="isGenerating"
                            >
                                <span v-if="isGenerating">Generating...</span>
                                <span v-else>Generate Report</span>
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
