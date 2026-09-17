<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { ref, computed, watch } from "vue";
import { route } from "ziggy-js";
import { useBurialRecordModal } from "@/composables/map/burialrecordmodal/useBurialRecordModal";

const page = usePage();
const user = computed(() => page.props.auth.user);
const userRole = computed(() => page.props.auth?.user?.role?.toLowerCase()?.trim());
const burialShowRoute = computed(() => {
    if (userRole.value === 'admin') return 'admin.burial_records.show';
    if (userRole.value === 'clerk') return 'clerk.burial_records.show';
    return null;
});

const props = defineProps({
    clusterId: { type: Number, default: null },
    feature: { type: Object, default: null },
});

const fetchedFeature = ref(null);
const isLoading = ref(false);
const searchTerm = ref("");
const selectedBurial = ref(null);
// Pagination state
const ITEMS_PER_PAGE = 25;
const currentPage = ref(1);

const emit = defineEmits(["viewPath"]);

const closeBurialModal = () => {
    try {
        const overlay = typeof HSOverlay !== "undefined" ? HSOverlay : window.HSOverlay;
        overlay?.close("#hs-scroll-inside-body-modal");
    } catch (_) {}
    // Fallback manual cleanup if HSOverlay unavailable or still open
    const el = document.getElementById("hs-scroll-inside-body-modal");
    if (el) {
        el.classList.add("hidden");
        el.classList.remove("open", "opened");
        el.setAttribute("aria-hidden", "true");
    }
    document.querySelectorAll(".hs-overlay-backdrop").forEach((b) => b.remove());
    document.body.classList.remove("overflow-hidden");
    document.body.style.removeProperty("overflow");
};

const handleViewPath = (burialId) => {
    // Close immediately as requested, then emit to draw path
    closeBurialModal();
    emit("viewPath", burialId);
};

// Use feature prop if provided (search mode), otherwise fetch by clusterId
const activeFeature = computed(() => props.feature || fetchedFeature.value);

// Lot image helpers - now embedded via composable (no separate overlay)
const lotClusterType = computed(
    () => activeFeature.value?.cluster?.properties?.type ?? "",
);

const lotPhaseName = computed(
    () => activeFeature.value?.cluster?.properties?.phase ?? "",
);

const {
    imageError,
    isShowingLotImage,
    showLotImage,
    hideLotImage,
    backToDetails,
    imageSrc,
    imageLabel,
    typeGuidance,
    canShowLotImage,
    openFullscreen,
    modalWrapperClass,
    modalContentClass,
} = useBurialRecordModal(lotClusterType, lotPhaseName, selectedBurial);

// Fetch cluster data when clusterId changes (only if feature is not provided)
watch(
    () => props.clusterId,
    async (newClusterId) => {
        // Don't fetch if feature is already provided (search mode)
        if (props.feature) {
            return;
        }

        if (!newClusterId) {
            fetchedFeature.value = null;
            return;
        }

        searchTerm.value = "";
        selectedBurial.value = null;
        isLoading.value = true;

        try {
            const response = await fetch(
                route("api.map.cluster.burials", { clusterId: newClusterId }),
                { credentials: "same-origin" },
            );
            const data = await response.json();
            fetchedFeature.value = data.data;
        } catch (error) {
            console.error("Error fetching cluster burial records:", error);
        } finally {
            isLoading.value = false;
        }
    },
    { immediate: true },
);

// Reset state when feature prop changes
watch(
    () => props.feature,
    () => {
        searchTerm.value = "";
        selectedBurial.value = null;
    },
);

const filteredBurials = computed(() => {
    if (!activeFeature.value?.lots) return [];

    const allBurials = activeFeature.value.lots.flatMap((lot) =>
        (lot.burial_records || []).map((burial) => ({
            ...burial,
            lot: lot.lot,
        })),
    );

    const term = searchTerm.value.toLowerCase().trim();
    if (!term) return allBurials;

    return allBurials.filter((burial) => {
        return (
            burial.deceased?.first_name?.toLowerCase().includes(term) ||
            burial.deceased?.last_name?.toLowerCase().includes(term) ||
            burial.burial?.date?.toLowerCase().includes(term)
        );
    });
});

// Pagination
// Reset page when search term or feature changes
watch([searchTerm, activeFeature], () => {
    currentPage.value = 1;
});

const totalPages = computed(() =>
    Math.ceil(filteredBurials.value.length / ITEMS_PER_PAGE),
);

const paginatedBurials = computed(() => {
    const start = (currentPage.value - 1) * ITEMS_PER_PAGE;
    return filteredBurials.value.slice(start, start + ITEMS_PER_PAGE);
});

const formatDate = (dateStr) => {
    if (!dateStr) return "N/A";
    const ymd = String(dateStr).match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (ymd) {
        const [, y, mo, d] = ymd;
        const date = new Date(Number(y), Number(mo) - 1, Number(d));
        if (!isNaN(date.getTime())) {
            return new Intl.DateTimeFormat("en-US", {
                year: "numeric",
                month: "short",
                day: "numeric",
            }).format(date);
        }
    }
    const parsed = new Date(dateStr);
    if (!isNaN(parsed.getTime())) {
        return new Intl.DateTimeFormat("en-US", {
            year: "numeric",
            month: "short",
            day: "numeric",
        }).format(parsed);
    }
    return dateStr;
};
</script>

<template>
    <div
        id="hs-scroll-inside-body-modal"
        class="hs-overlay hidden size-full fixed top-0 start-0 z-[2000] overflow-x-hidden overflow-y-auto bg-black/40 backdrop-blur-sm pointer-events-none"
        role="dialog"
        tabindex="-1"
        aria-labelledby="hs-scroll-inside-body-modal-label"
    >
        <div :class="modalWrapperClass">
            <div :class="modalContentClass">
                <!-- Header -->
                <div
                    class="flex justify-between items-center py-3 px-4 border-b border-white/20 dark:border-white/10 bg-white/40 dark:bg-neutral-800/40 backdrop-blur-md shrink-0"
                >
                    <div>
                        <h3
                            id="hs-scroll-inside-body-modal-label"
                            class="font-bold text-gray-800 dark:text-white"
                        >
                            {{
                                isShowingLotImage && selectedBurial
                                    ? "Lot Image"
                                    : "Cluster Details"
                            }}
                        </h3>
                        <p
                            v-if="isShowingLotImage && selectedBurial && imageSrc"
                            class="text-xs text-gray-500 dark:text-gray-400"
                        >
                            {{ imageLabel }} · Phase
                            {{ lotPhaseName || "N/A" }} ·
                            {{ lotClusterType || "N/A" }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="size-8 inline-flex justify-center items-center rounded-full bg-white/40 dark:bg-neutral-800/40 backdrop-blur-md border border-white/20 dark:border-white/10 text-gray-700 dark:text-neutral-200 hover:bg-white/60 dark:hover:bg-neutral-700/60 transition"
                        data-hs-overlay="#hs-scroll-inside-body-modal"
                    >
                        <svg
                            class="shrink-0 size-4"
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

                <!-- BODY -->
                <div
                    class="p-4 overflow-y-auto space-y-4 text-gray-700 dark:text-gray-300"
                >
                    <!-- LOADING SKELETON -->
                    <template v-if="isLoading">
                        <div class="animate-pulse space-y-4">
                            <!-- Skeleton Search -->
                            <div
                                class="h-10 bg-gray-300/50 dark:bg-gray-700/50 rounded-lg"
                            ></div>

                            <!-- Skeleton Info -->
                            <div class="space-y-2">
                                <div
                                    class="h-4 bg-gray-300/50 dark:bg-gray-700/50 rounded w-3/4"
                                ></div>
                                <div
                                    class="h-4 bg-gray-300/50 dark:bg-gray-700/50 rounded w-1/2"
                                ></div>
                                <div
                                    class="h-4 bg-gray-300/50 dark:bg-gray-700/50 rounded w-2/3"
                                ></div>
                                <div
                                    class="h-4 bg-gray-300/50 dark:bg-gray-700/50 rounded w-1/2"
                                ></div>
                            </div>

                            <!-- Skeleton Grid -->
                            <div
                                class="grid grid-cols-2 md:grid-cols-3 gap-3 mt-3"
                            >
                                <div
                                    v-for="i in 6"
                                    :key="i"
                                    class="h-20 bg-gray-300/50 dark:bg-gray-700/50 rounded-xl"
                                ></div>
                            </div>
                        </div>
                    </template>

                    <!-- LIST VIEW -->
                    <template v-else-if="activeFeature && !selectedBurial">
                        <!-- Search -->
                        <input
                            v-model="searchTerm"
                            type="search"
                            placeholder="Search by name or burial date..."
                            class="w-full px-3 py-2 text-sm rounded-lg border border-white/30 dark:border-white/10 bg-white/60 dark:bg-neutral-900/60 backdrop-blur-md focus:outline-none focus:ring-2 focus:ring-green-500"
                        />

                        <div class="text-sm space-y-1">
                            <p>
                                <strong>Cluster:</strong>
                                {{ activeFeature.cluster?.properties?.name }}
                            </p>
                            <p>
                                <strong>Type:</strong>
                                {{ activeFeature.cluster?.properties?.type }}
                            </p>
                            <p>
                                <strong>Occupants:</strong>
                                {{
                                    activeFeature.cluster?.properties
                                        ?.occupied_lots
                                }}
                                /
                                {{
                                    activeFeature.cluster?.properties
                                        ?.total_lots
                                }}
                            </p>
                            <p>
                                <strong>Status:</strong>
                                {{ activeFeature.cluster?.properties?.status }}
                            </p>
                        </div>

                        <!-- BURIAL GRID -->
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mt-3">
                            <button
                                v-for="burial in paginatedBurials"
                                :key="burial.id"
                                @click.stop="selectedBurial = burial"
                                class="flex items-center gap-3 text-left p-3 rounded-xl border border-white/30 dark:border-white/10 bg-white/60 dark:bg-neutral-800/60 backdrop-blur-md shadow-sm hover:border-green-500 hover:shadow-md transition w-full cursor-pointer"
                            >
                                <div
                                    class="flex items-center justify-center size-10 rounded-full bg-green-500/10 text-green-600 dark:text-green-400"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="20"
                                        height="20"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <circle cx="12" cy="8" r="5" />
                                        <path d="M20 21a8 8 0 0 0-16 0" />
                                    </svg>
                                </div>

                                <div>
                                    <div
                                        class="font-semibold text-green-600 dark:text-green-400 text-sm"
                                    >
                                        {{
                                            burial.deceased?.first_name +
                                                " " +
                                                burial.deceased?.last_name ??
                                            "Unknown"
                                        }}
                                    </div>

                                    <div
                                        class="text-xs text-gray-500 dark:text-gray-400"
                                    >
                                        Lot: {{ burial.lot?.properties?.column
                                        }}{{ burial.lot?.properties?.row }}
                                    </div>
                                </div>
                            </button>
                        </div>

                        <!-- PAGINATION -->
                        <div
                            v-if="totalPages > 1"
                            class="flex items-center justify-between pt-2 border-t border-white/20 dark:border-white/10"
                        >
                            <span
                                class="text-xs text-gray-500 dark:text-gray-400"
                            >
                                Page {{ currentPage }} of {{ totalPages }}
                            </span>

                            <div class="flex gap-2">
                                <button
                                    @click="currentPage--"
                                    :disabled="currentPage === 1"
                                    class="px-3 py-1 text-sm rounded-lg border border-white/30 dark:border-white/10 bg-white/40 dark:bg-neutral-800/40 backdrop-blur-md disabled:opacity-40 disabled:cursor-not-allowed hover:border-green-500 transition"
                                >
                                    Prev
                                </button>

                                <button
                                    @click="currentPage++"
                                    :disabled="currentPage === totalPages"
                                    class="px-3 py-1 text-sm rounded-lg border border-white/30 dark:border-white/10 bg-white/40 dark:bg-neutral-800/40 backdrop-blur-md disabled:opacity-40 disabled:cursor-not-allowed hover:border-green-500 transition"
                                >
                                    Next
                                </button>
                            </div>
                        </div>

                        <p
                            v-if="filteredBurials.length === 0"
                            class="text-sm text-gray-400 text-center py-4"
                        >
                            No burials found.
                        </p>
                    </template>

                    <!-- DETAIL VIEW / LOT IMAGE VIEW -->
                    <template v-if="selectedBurial">
                        <!-- Lot image embedded view -->
                        <template v-if="isShowingLotImage">
                            <button
                                @click="backToDetails"
                                class="flex items-center gap-1 text-sm text-green-600 dark:text-green-400 hover:underline"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="size-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M15 19l-7-7 7-7"
                                    />
                                </svg>
                                Back to details
                            </button>

                            <div class="space-y-3">
                                <template v-if="imageSrc && !imageError">
                                    <div
                                        class="rounded-xl overflow-hidden border border-white/30 dark:border-white/10 bg-white dark:bg-neutral-800 flex items-center justify-center overflow-auto"
                                    >
                                        <img
                                            :src="imageSrc"
                                            :alt="imageLabel"
                                            class="w-full h-auto max-h-[80vh] xl:max-h-[84vh] object-contain scale-[1.06] origin-center cursor-zoom-in"
                                            loading="lazy"
                                            title="Click to open fullscreen"
                                            @click="openFullscreen"
                                            @error="imageError = true"
                                        />
                                    </div>
                                    <p
                                        class="text-xs text-center text-gray-500 dark:text-gray-400"
                                    >
                                        Labeled lots – {{ imageLabel }}
                                    </p>
                                    <div
                                        v-if="typeGuidance"
                                        class="rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50/80 dark:bg-amber-900/20 px-3 py-2.5 flex gap-2.5"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="size-4 shrink-0 text-amber-600 dark:text-amber-400 mt-0.5"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <circle cx="12" cy="12" r="10" />
                                            <path d="M12 16v-4" />
                                            <path d="M12 8h.01" />
                                        </svg>
                                        <p
                                            class="text-xs leading-relaxed text-amber-800 dark:text-amber-200"
                                        >
                                            {{ typeGuidance }}
                                        </p>
                                    </div>
                                </template>

                                <template v-else-if="imageSrc && imageError">
                                    <div
                                        class="text-center py-8 space-y-2 text-gray-500 dark:text-gray-400"
                                    >
                                        <p class="text-sm font-medium">
                                            Failed to load lot image
                                        </p>
                                        <p class="text-xs">Tried: {{ imageSrc }}</p>
                                    </div>
                                </template>

                                <template v-else>
                                    <div
                                        class="text-center py-8 space-y-2 text-gray-500 dark:text-gray-400"
                                    >
                                        <p class="text-sm">
                                            No labeled lot image available for this lot
                                            type.
                                        </p>
                                        <p class="text-xs">
                                            Supported types: underground, apartment
                                            <span v-if="lotClusterType">
                                                · current: {{ lotClusterType }}</span
                                            >
                                        </p>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <template v-else>
                            <button
                                @click="selectedBurial = null"
                                class="flex items-center gap-1 text-sm text-green-600 dark:text-green-400 hover:underline"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="size-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M15 19l-7-7 7-7"
                                    />
                                </svg>
                                Back to occupants
                            </button>

                        <div
                            class="p-5 rounded-xl border border-white/30 dark:border-white/10 bg-white/60 dark:bg-neutral-800/60 backdrop-blur-md"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-4 min-w-0 flex-1">
                                    <div
                                        class="flex items-center justify-center size-14 rounded-full bg-green-500/10 text-green-600 dark:text-green-400 shrink-0"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="22"
                                            height="22"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <circle cx="12" cy="8" r="5" />
                                            <path d="M20 21a8 8 0 0 0-16 0" />
                                        </svg>
                                    </div>

                                    <div class="min-w-0">
                                        <h3
                                            class="text-lg font-semibold text-green-600 dark:text-green-400 truncate"
                                        >
                                            {{
                                                selectedBurial.deceased
                                                    ?.first_name +
                                                    " " +
                                                    selectedBurial.deceased
                                                        ?.last_name ?? "Unknown"
                                            }}
                                        </h3>

                                        <p
                                            class="text-xs text-gray-500 dark:text-gray-400"
                                        >
                                            Burial Record #{{
                                                selectedBurial.burial?.id
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <!-- View More stays at top for clerk/admin; visitor sees inline actions -->
                                <div
                                    class="flex items-center gap-2 shrink-0 flex-wrap justify-end"
                                >
                                    <Link
                                        v-if="burialShowRoute"
                                        :href="
                                            route(
                                                burialShowRoute,
                                                selectedBurial.burial?.id,
                                            )
                                        "
                                        @click="closeBurialModal"
                                        class="px-3 py-1.5 text-sm font-medium rounded-lg transition bg-green-500/10 text-green-400 border-transparent hover:bg-green-500/20 hover:border-green-500/40 hover:text-green-600 dark:hover:text-green-300"
                                    >
                                        View More
                                    </Link>

                                    <!-- Visitor: inline See Lot Image + View Path (2 buttons, looks balanced) -->
                                    <template v-if="user === null">
                                        <button
                                            @click="showLotImage"
                                            class="px-3 py-1.5 text-sm font-medium rounded-lg text-green-600 dark:text-green-400 hover:underline transition"
                                        >
                                            See Lot Image
                                        </button>
                                        <button
                                            @click="
                                                handleViewPath(
                                                    selectedBurial.burial?.id,
                                                )
                                            "
                                            data-hs-overlay="#hs-scroll-inside-body-modal"
                                            class="px-3 py-1.5 text-sm font-medium rounded-lg border border-green-500/30 bg-green-500/10 text-green-700 dark:text-green-300 hover:bg-green-500/20 hover:border-green-500/40 transition"
                                        >
                                            View Path
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <div class="mt-5 grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span
                                        class="text-gray-500 dark:text-gray-400"
                                    >
                                        Phase
                                    </span>
                                    <div class="font-medium">
                                        {{
                                            activeFeature?.cluster?.properties
                                                ?.phase ?? "N/A"
                                        }}
                                    </div>
                                </div>

                                <div>
                                    <span
                                        class="text-gray-500 dark:text-gray-400"
                                    >
                                        Cluster
                                    </span>
                                    <div class="font-medium">
                                        {{
                                            activeFeature?.cluster?.properties
                                                ?.name ?? "N/A"
                                        }}
                                    </div>
                                </div>

                                <div>
                                    <span
                                        class="text-gray-500 dark:text-gray-400"
                                    >
                                        Lot Location
                                    </span>
                                    <div class="font-medium">
                                        <template
                                            v-if="
                                                selectedBurial.lot?.properties
                                                    ?.column ||
                                                selectedBurial.lot?.properties
                                                    ?.row
                                            "
                                        >
                                            {{
                                                selectedBurial.lot?.properties
                                                    ?.column ?? ""
                                            }}{{
                                                selectedBurial.lot?.properties
                                                    ?.row ?? ""
                                            }}
                                            <span
                                                v-if="lotClusterType"
                                                class="text-xs text-gray-500 dark:text-gray-400"
                                                >({{ lotClusterType }})</span
                                            >
                                        </template>
                                        <template v-else>N/A</template>
                                    </div>
                                </div>

                                <div>
                                    <span
                                        class="text-gray-500 dark:text-gray-400"
                                    >
                                        Burial Date
                                    </span>
                                    <div class="font-medium">
                                        {{
                                            formatDate(
                                                selectedBurial.deceased?.burial
                                                    ?.date,
                                            )
                                        }}
                                    </div>
                                </div>

                                <div>
                                    <span
                                        class="text-gray-500 dark:text-gray-400"
                                    >
                                        Imported By
                                    </span>
                                    <div class="font-medium">
                                        {{
                                            selectedBurial.imported_by
                                                ?.full_name ?? "N/A"
                                        }}
                                    </div>
                                </div>

                                <div>
                                    <span
                                        class="text-gray-500 dark:text-gray-400"
                                    >
                                        Burial ID
                                    </span>
                                    <div class="font-medium">
                                        {{ selectedBurial.burial?.id }}
                                    </div>
                                </div>
                            </div>

                            <!-- Clerk/Admin: map actions below grid so 3 buttons are not cramped at top -->
                            <div
                                v-if="user !== null"
                                class="mt-4 pt-4 border-t border-white/20 dark:border-white/10 flex flex-wrap items-center justify-end gap-2"
                            >
                                <button
                                    @click="showLotImage"
                                    class="px-3 py-1.5 text-sm font-medium rounded-lg text-green-600 dark:text-green-400 hover:underline transition"
                                >
                                    See Lot Image
                                </button>
                                <button
                                    @click="
                                        handleViewPath(selectedBurial.burial?.id)
                                    "
                                    data-hs-overlay="#hs-scroll-inside-body-modal"
                                    class="px-3 py-1.5 text-sm font-medium rounded-lg border border-green-500/30 bg-green-500/10 text-green-700 dark:text-green-300 hover:bg-green-500/20 hover:border-green-500/40 transition"
                                >
                                    View Path
                                </button>
                            </div>
                        </div>
                        </template>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
