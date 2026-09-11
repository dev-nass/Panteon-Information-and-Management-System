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
const yearDate = ref(new Date().getFullYear());
const format = ref("pdf");
const isGenerating = ref(false);

const currentYear = new Date().getFullYear();
const yearOptions = Array.from(
    { length: currentYear - 2013 + 1 },
    (_, i) => 2013 + i,
).reverse();

// Computed property to determine which date fields to show
const showDateRange = computed(() => {
    return reportType.value === "burial" || reportType.value === "deceased";
});

const showMonthPicker = computed(() => {
    return reportType.value === "summary";
});

const showYearPicker = computed(() => {
    return reportType.value === "annual";
});

const showNoDates = computed(() => {
    return reportType.value === "phase";
});

// Large range detection for PDF performance (A+B)
const rangeDays = computed(() => {
    if (!startDate.value || !endDate.value) return 0;
    const start = new Date(startDate.value);
    const end = new Date(endDate.value);
    const diff = (end - start) / (1000 * 60 * 60 * 24);
    return Math.ceil(diff) + 1;
});
const isLargeRange = computed(() => rangeDays.value > 90);
const isPdfLargeRange = computed(() => isLargeRange.value && format.value === 'pdf' && showDateRange.value);

// Reset date fields when report type changes
watch(reportType, () => {
    startDate.value = "";
    endDate.value = "";
    monthDate.value = "";
    yearDate.value = new Date().getFullYear();
});

const setDateRange = (type) => {
    const today = new Date();
    const formatDate = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, "0");
        const day = String(date.getDate()).padStart(2, "0");
        return `${year}-${month}-${day}`;
    };

    if (type === "today") {
        startDate.value = formatDate(today);
        endDate.value = formatDate(today);
    } else if (type === "week") {
        const dayOfWeek = today.getDay();
        const firstDay = new Date(today);
        firstDay.setDate(today.getDate() - dayOfWeek);
        const lastDay = new Date(today);
        lastDay.setDate(today.getDate() + (6 - dayOfWeek));
        startDate.value = formatDate(firstDay);
        endDate.value = formatDate(lastDay);
    } else if (type === "month") {
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        startDate.value = formatDate(firstDay);
        endDate.value = formatDate(lastDay);
    } else if (type === "year") {
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

    if (showYearPicker.value && !yearDate.value) {
        alert("Please select a year");
        return;
    }

    // B: Warn for large PDF ranges (>90 days / 5,000 records max) – Dompdf 30s limit, Excel recommended
    if (isPdfLargeRange.value) {
        const proceed = confirm(
            `The selected range is ${rangeDays.value} days (max for PDF is 90 days / 5,000 records) and may contain thousands of records. PDF generation may time out (30s limit) and server will block PDF >5,000 records.\n\n` +
                `It is strongly recommended to use Excel format for large ranges.\n\n` +
                `Click OK to continue with PDF anyway, or Cancel to switch to Excel.`
        );
        if (!proceed) {
            format.value = 'excel';
            // Let user review warning then click Generate again
            return;
        }
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
    } else if (showYearPicker.value) {
        params.year = yearDate.value;
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
                <div class="p-1.5 w-full inline-block align-middle">
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
                                    <option value="annual">
                                        Annual Summary
                                    </option>
                                    <option value="phase">
                                        Phase Availability
                                    </option>
                                </select>
                            </div>

                            <!-- Date Range (for burial and deceased) -->
                            <template v-if="showDateRange">
                                <!-- Quick Date Buttons -->
                                <div
                                    class="grid grid-cols-2 sm:grid-cols-4 gap-2 col-span-full"
                                >
                                    <Button
                                        @click="setDateRange('today')"
                                        size="sm"
                                        highlighted
                                        class="w-full justify-center"
                                    >
                                        Today
                                    </Button>
                                    <Button
                                        @click="setDateRange('week')"
                                        size="sm"
                                        highlighted
                                        class="w-full justify-center"
                                    >
                                        This Week
                                    </Button>
                                    <Button
                                        @click="setDateRange('month')"
                                        size="sm"
                                        highlighted
                                        class="w-full justify-center"
                                    >
                                        This Month
                                    </Button>
                                    <Button
                                        @click="setDateRange('year')"
                                        size="sm"
                                        highlighted
                                        class="w-full justify-center"
                                    >
                                        This Year
                                    </Button>
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

                            <!-- Year Picker (for annual summary) -->
                            <div
                                v-if="showYearPicker"
                                class="flex flex-col col-span-full gap-1"
                            >
                                <label
                                    class="text-sm font-medium text-gray-600 dark:text-gray-300"
                                >
                                    Select Year
                                </label>

                                <select
                                    v-model="yearDate"
                                    class="py-2 px-4 w-full border bg-white dark:bg-neutral-800 border-gray-200 dark:border-neutral-700 rounded-lg text-sm text-gray-800 dark:text-neutral-200 focus:border-green-500 focus:ring-2 focus:ring-green-500"
                                >
                                    <option
                                        v-for="y in yearOptions"
                                        :key="y"
                                        :value="y"
                                    >
                                        {{ y }}
                                    </option>
                                </select>
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
                                <p
                                    v-if="format === 'pdf' && showDateRange"
                                    class="text-xs text-gray-500 dark:text-gray-400 mt-1"
                                >
                                    Tip: Max for PDF is <span class="font-semibold">90 days / 5,000 records</span> — for larger ranges, Excel is faster and avoids timeouts (PDF will be blocked by server).
                                </p>
                            </div>

                            <!-- Large range warning (B) -->
                            <div
                                v-if="isPdfLargeRange"
                                class="col-span-full flex gap-3 p-4 rounded-xl border border-amber-300 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-700 text-amber-800 dark:text-amber-200"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0 mt-0.5">
                                    <circle cx="12" cy="12" r="10" />
                                    <path d="M12 8v4" />
                                    <path d="M12 16h.01" />
                                </svg>
                                <div class="text-sm">
                                    <p class="font-semibold">Large date range ({{ rangeDays }} days) — exceeds max 90 days / 5,000 records for PDF</p>
                                    <p class="mt-1">PDF generation may exceed 30s and fail. Server enforces <span class="font-semibold">max 5,000 records for PDF</span> and will block the request. Please switch to <button type="button" @click="format='excel'" class="underline font-semibold">Excel</button> or narrow to ≤90 days.</p>
                                </div>
                            </div>
                            <div
                                v-else-if="isLargeRange && showDateRange"
                                class="col-span-full flex gap-3 p-3 rounded-xl border border-gray-200 dark:border-neutral-700 bg-gray-50 dark:bg-neutral-800 text-gray-600 dark:text-gray-300"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0 mt-0.5">
                                    <circle cx="12" cy="12" r="10" />
                                    <path d="M12 16v-4" />
                                    <path d="M12 8h.01" />
                                </svg>
                                <p class="text-sm">Range is {{ rangeDays }} days (max 90 days / 5,000 records for PDF). For best performance with large data, Excel is recommended.</p>
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
