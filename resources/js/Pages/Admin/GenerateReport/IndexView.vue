<script setup>
import { ref, computed, watch, nextTick, onMounted } from "vue";
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

// Count-based PDF guard: soft 1,000-1,500 warns, >1,500 blocks — DomPDF OOMs at ~2k+ rows
const PDF_SOFT_LIMIT = 1000;
const PDF_HARD_LIMIT = 1500;

const recordCount = ref(null);
const isCounting = ref(false);
let countAbort = null;
let countDebounceTimer = null;

const isHardExceeded = computed(() => recordCount.value !== null && recordCount.value > PDF_HARD_LIMIT);
const isSoftRange = computed(() => recordCount.value !== null && recordCount.value >= PDF_SOFT_LIMIT && recordCount.value <= PDF_HARD_LIMIT);
const isPdfHardExceeded = computed(() => isHardExceeded.value && format.value === 'pdf' && showDateRange.value);
const isPdfSoftRange = computed(() => isSoftRange.value && format.value === 'pdf' && showDateRange.value);

const fetchRecordCount = async () => {
    if (!showDateRange.value || !startDate.value || !endDate.value) {
        recordCount.value = null;
        return;
    }
    // Validate dates locally before request
    const s = new Date(startDate.value);
    const e = new Date(endDate.value);
    if (isNaN(s.getTime()) || isNaN(e.getTime()) || s > e) {
        recordCount.value = null;
        return;
    }
    if (countAbort) countAbort.abort();
    countAbort = new AbortController();
    isCounting.value = true;
    try {
        const url = route('admin.generate_report.count', {
            reportType: reportType.value,
            startDate: startDate.value,
            endDate: endDate.value,
        });
        const res = await fetch(url, { signal: countAbort.signal, headers: { Accept: 'application/json' } });
        if (!res.ok) throw new Error('count failed');
        const json = await res.json();
        recordCount.value = json.count ?? 0;
    } catch (err) {
        if (err?.name !== 'AbortError') recordCount.value = null;
    } finally {
        isCounting.value = false;
    }
};

const scheduleCountFetch = () => {
    if (countDebounceTimer) clearTimeout(countDebounceTimer);
    // debounce 400ms to avoid spamming while typing dates
    countDebounceTimer = setTimeout(fetchRecordCount, 400);
};

// Reset date fields when report type changes
watch(reportType, () => {
    startDate.value = "";
    endDate.value = "";
    monthDate.value = "";
    yearDate.value = new Date().getFullYear();
    recordCount.value = null;
    isCounting.value = false;
});

watch([reportType, startDate, endDate], () => {
    if (showDateRange.value && reportType.value && startDate.value && endDate.value) {
        scheduleCountFetch();
    } else {
        recordCount.value = null;
    }
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
    recordCount.value = null;
    isCounting.value = false;
};

const openLargeRangeModal = () => {
    nextTick(() => {
        const el = document.getElementById("pdf-large-range-modal");
        try {
            if (el && window.HSOverlay) {
                window.HSOverlay.open(el);
            } else if (typeof HSOverlay !== "undefined") {
                HSOverlay.open("#pdf-large-range-modal");
            }
        } catch {
            if (typeof HSOverlay !== "undefined") HSOverlay.open("#pdf-large-range-modal");
        }
    });
};

const closeLargeRangeModal = () => {
    try {
        const el = document.getElementById("pdf-large-range-modal");
        if (el && window.HSOverlay) window.HSOverlay.close(el);
        else if (typeof HSOverlay !== "undefined") HSOverlay.close("#pdf-large-range-modal");
        else if (el) {
            el.classList.remove("open", "opened");
            el.classList.add("hidden");
        }
    } catch {}
};

const switchToExcelAndClose = () => {
    format.value = "excel";
    closeLargeRangeModal();
};

const proceedGenerate = () => {
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

onMounted(() => {
    nextTick(() => {
        try {
            if (window.HSOverlay && window.HSOverlay.autoInit) window.HSOverlay.autoInit();
            else if (typeof HSOverlay !== "undefined" && HSOverlay.autoInit) HSOverlay.autoInit();
        } catch {}
    });
});

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

    // Count-based guard: >1,500 blocks PDF (Excel only), 1,000-1,500 is soft warning
    if (isPdfHardExceeded.value) {
        openLargeRangeModal();
        return;
    }

    proceedGenerate();
};
</script>

<template>
    <div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-6 mx-auto">
        <div class="flex flex-col items-center">
            <div
                class="w-full sm:max-w-[700px] flex flex-col gap-y-6 bg-white/60 dark:bg-neutral-900/60 backdrop-blur-xl px-6 py-6 border border-white/20 dark:border-white/10 rounded-xl shadow-lg overflow-hidden"
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
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 w-full min-w-0">
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
                                    <span v-if="isCounting" class="italic">Checking records...</span>
                                    <span v-else-if="recordCount !== null">Found <span class="font-semibold">{{ recordCount.toLocaleString() }} record{{ recordCount === 1 ? '' : 's' }}</span> — PDF max is <span class="font-semibold">1,500</span><span v-if="isPdfHardExceeded" class="text-red-600 dark:text-red-400 font-semibold"> (exceeds limit — Excel required)</span><span v-else-if="isPdfSoftRange" class="text-amber-600 dark:text-amber-400"> (1,000-1,500: Excel recommended)</span>.</span>
                                    <span v-else>Tip: PDF max is <span class="font-semibold">1,500 records</span> — large results are faster as Excel (server will block PDF over limit).</span>
                                </p>
                            </div>

                            <!-- Count-based warnings: >1.5k hard block, 1k-1.5k soft advisory -->
                            <div
                                v-if="isPdfHardExceeded"
                                class="col-span-full flex items-start gap-3 p-4 rounded-xl border border-red-300 bg-red-50 dark:bg-red-900/20 dark:border-red-700 text-red-800 dark:text-red-200 w-full min-w-0"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0 mt-0.5">
                                    <circle cx="12" cy="12" r="10" />
                                    <path d="M12 8v4" />
                                    <path d="M12 16h.01" />
                                </svg>
                                <div class="text-sm flex-1 min-w-0 break-words">
                                    <p class="font-semibold break-words [overflow-wrap:break-word] whitespace-normal leading-snug">Large result set ({{ recordCount?.toLocaleString() }} records) — exceeds max 1,500 for PDF</p>
                                    <p class="mt-1 break-words [overflow-wrap:break-word] whitespace-normal leading-snug">PDF is not available for this count. Server enforces <span class="font-semibold">max 1,500 records for PDF</span>. Please switch to <button type="button" @click="format='excel'" class="underline font-semibold">Excel</button> or narrow the date range.</p>
                                </div>
                            </div>
                            <div
                                v-else-if="isPdfSoftRange"
                                class="col-span-full flex items-start gap-3 p-4 rounded-xl border border-amber-300 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-700 text-amber-800 dark:text-amber-200 w-full min-w-0"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0 mt-0.5">
                                    <circle cx="12" cy="12" r="10" />
                                    <path d="M12 8v4" />
                                    <path d="M12 16h.01" />
                                </svg>
                                <div class="text-sm flex-1 min-w-0 break-words">
                                    <p class="font-semibold break-words [overflow-wrap:break-word] whitespace-normal leading-snug">Large result set ({{ recordCount?.toLocaleString() }} records) — in advisory range 1,000-1,500</p>
                                    <p class="mt-1 break-words [overflow-wrap:break-word] whitespace-normal leading-snug">PDF is still available but may be slow (many pages). For best performance, <button type="button" @click="format='excel'" class="underline font-semibold">Excel is recommended</button>.</p>
                                </div>
                            </div>
                            <div
                                v-else-if="isCounting && showDateRange && startDate && endDate"
                                class="col-span-full flex items-start gap-3 p-3 rounded-xl border border-gray-200 dark:border-neutral-700 bg-gray-50 dark:bg-neutral-800 text-gray-600 dark:text-gray-300 w-full min-w-0"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0 mt-0.5 animate-spin">
                                    <path d="M21 12a9 9 0 1 1-6.219-8.56" />
                                </svg>
                                <p class="text-sm flex-1 min-w-0">Checking records for selected dates...</p>
                            </div>
                            <div
                                v-else-if="recordCount !== null && showDateRange && !isPdfHardExceeded && !isPdfSoftRange"
                                class="col-span-full flex items-start gap-3 p-3 rounded-xl border border-green-200 dark:border-green-700 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 w-full min-w-0"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0 mt-0.5">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                                    <path d="M22 4L12 14.01l-3-3" />
                                </svg>
                                <p class="text-sm flex-1 min-w-0">{{ recordCount.toLocaleString() }} record{{ recordCount === 1 ? '' : 's' }} found — PDF ready.</p>
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

    <Teleport to="body">
        <div
            id="pdf-large-range-modal"
            class="hs-overlay hidden size-full fixed top-0 start-0 z-2000 overflow-x-hidden overflow-y-auto bg-black/40 backdrop-blur-sm"
            role="dialog"
            tabindex="-1"
            aria-labelledby="pdf-large-range-modal-label"
        >
            <div
                class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-0 ease-out transition-all sm:max-w-lg sm:w-full m-3 sm:mx-auto"
            >
                <div
                    class="relative w-full max-h-full flex flex-col bg-white/70 dark:bg-neutral-900/70 backdrop-blur-xl border border-white/20 dark:border-white/10 rounded-2xl shadow-lg shadow-gray-200/50 dark:shadow-black/50"
                >
                    <div class="absolute top-3 end-3">
                        <button
                            type="button"
                            class="size-8 inline-flex justify-center items-center rounded-full bg-white/40 dark:bg-neutral-800/40 backdrop-blur-md border border-white/20 dark:border-white/10 text-gray-700 dark:text-neutral-200 hover:bg-white/60 dark:hover:bg-neutral-700/60 transition"
                            @click="closeLargeRangeModal"
                        >
                            <svg
                                class="size-4"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path d="M18 6 6 18" />
                                <path d="m6 6 12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="p-10 flex flex-col items-center gap-y-4 text-center">
                        <div
                            class="flex items-center justify-center size-14 rounded-full bg-red-500/10 text-red-600 dark:text-red-400"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="60"
                                height="60"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <circle cx="12" cy="12" r="10" />
                                <path d="M12 8v4" />
                                <path d="M12 16h.01" />
                            </svg>
                        </div>

                        <h3
                            id="pdf-large-range-modal-label"
                            class="-mt-2 text-2xl font-bold text-red-600 dark:text-red-400"
                        >
                            Too Many Records for PDF
                        </h3>

                        <div class="text-gray-600 dark:text-neutral-300 max-w-sm space-y-2">
                            <p>
                                The selected range contains
                                <span class="font-semibold text-gray-900 dark:text-white">{{ recordCount?.toLocaleString() ?? '—' }} records</span>
                                (PDF max is <span class="font-semibold">1,500</span>).
                            </p>
                            <p>
                                PDF generation would exceed limits and will be blocked by the server
                                (<span class="font-semibold">max 1,500 records</span>).
                            </p>
                            <p class="font-medium text-amber-700 dark:text-amber-300">
                                Please use Excel for this range or narrow the dates to reduce the count.
                            </p>
                        </div>
                    </div>

                    <div class="flex border-t border-white/20 dark:border-white/10">
                        <button
                            type="button"
                            class="w-full py-3 text-sm font-semibold text-gray-600 dark:text-gray-400 hover:bg-gray-500/10 transition"
                            @click="closeLargeRangeModal"
                        >
                            Cancel
                        </button>

                        <button
                            type="button"
                            class="w-full py-3 text-sm font-semibold text-green-600 dark:text-green-400 hover:bg-green-500/10 transition"
                            @click="switchToExcelAndClose"
                        >
                            Switch to Excel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
