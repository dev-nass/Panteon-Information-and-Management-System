<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
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
    feature: { type: Object, default: null },
    burialId: { type: [Number, String], default: null },
    isLoading: { type: Boolean, default: false },
});

const emit = defineEmits(["viewPath"]);

const closeModal = () => {
    try {
        const overlay = typeof HSOverlay !== "undefined" ? HSOverlay : window.HSOverlay;
        overlay?.close("#hs-visitor-deceased-modal");
    } catch (_) {}
    const el = document.getElementById("hs-visitor-deceased-modal");
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
    closeModal();
    emit("viewPath", burialId);
};

/**
 * Find the matched burial record inside the cluster feature.
 * When searched via burial_id the cluster's lots are filtered to the single lot,
 * but that lot may still contain multiple burial_records, so we pinpoint the
 * exact one via burialId. Fallback to first record.
 */
const activeBurial = computed(() => {
    if (!props.feature?.lots) return null;

    const allBurials = props.feature.lots.flatMap((lot) =>
        (lot.burial_records || []).map((burial) => ({
            ...burial,
            lot: lot.lot,
        })),
    );

    if (allBurials.length === 0) return null;

    if (props.burialId !== null && props.burialId !== undefined) {
        const found = allBurials.find(
            (b) => String(b.burial?.id) === String(props.burialId),
        );
        if (found) return found;
    }

    return allBurials[0];
});

const clusterProps = computed(() => props.feature?.cluster?.properties || {});
const lotProps = computed(() => activeBurial.value?.lot?.properties || {});
const deceased = computed(() => activeBurial.value?.deceased || {});

const deceasedFullName = computed(() => {
    if (deceased.value?.full_name) return deceased.value.full_name;
    const first = deceased.value?.first_name || "";
    const last = deceased.value?.last_name || "";
    const combined = `${first} ${last}`.trim();
    return combined || "Unknown";
});

const formattedBurialDate = computed(() => {
    const raw = deceased.value?.burial?.date;
    if (!raw) return "N/A";
    // Prefer manual YYYY-MM-DD parsing to avoid UTC timezone shift
    const ymd = String(raw).match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (ymd) {
        const [, y, mo, d] = ymd;
        const date = new Date(Number(y), Number(mo) - 1, Number(d));
        if (!isNaN(date.getTime())) {
            return new Intl.DateTimeFormat("en-US", {
                year: "numeric",
                month: "long",
                day: "numeric",
            }).format(date);
        }
    }
    const parsed = new Date(raw);
    if (!isNaN(parsed.getTime())) {
        return new Intl.DateTimeFormat("en-US", {
            year: "numeric",
            month: "long",
            day: "numeric",
        }).format(parsed);
    }
    return raw;
});

// Lot image helpers - embedded via composable (retain design, no separate overlay)
const lotClusterType = computed(
    () => clusterProps.value?.type ?? lotProps.value?.type ?? "",
);

const lotPhaseName = computed(
    () => clusterProps.value?.phase ?? lotProps.value?.phase ?? "",
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
} = useBurialRecordModal(lotClusterType, lotPhaseName, activeBurial);
</script>

<template>
    <div
        id="hs-visitor-deceased-modal"
        class="hs-overlay hidden size-full fixed top-0 start-0 z-[2000] overflow-x-hidden overflow-y-auto bg-black/40 backdrop-blur-sm pointer-events-none"
        role="dialog"
        tabindex="-1"
        aria-labelledby="hs-visitor-deceased-modal-label"
    >
        <div :class="modalWrapperClass">
            <div :class="modalContentClass">
                <!-- Header -->
                <div
                    class="flex justify-between items-center py-3 px-4 border-b border-white/20 dark:border-white/10 bg-white/40 dark:bg-neutral-800/40 backdrop-blur-md shrink-0"
                >
                    <div>
                        <h3
                            id="hs-visitor-deceased-modal-label"
                            class="font-bold text-gray-800 dark:text-white"
                        >
                            {{
                                isShowingLotImage && activeBurial
                                    ? "Lot Image"
                                    : "Deceased Details"
                            }}
                        </h3>
                        <p
                            v-if="isShowingLotImage && activeBurial && imageSrc"
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
                        data-hs-overlay="#hs-visitor-deceased-modal"
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
                            <div
                                class="h-20 bg-gray-300/50 dark:bg-gray-700/50 rounded-xl"
                            ></div>
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
                            </div>
                            <div class="grid grid-cols-2 gap-3 mt-3">
                                <div
                                    v-for="i in 4"
                                    :key="i"
                                    class="h-16 bg-gray-300/50 dark:bg-gray-700/50 rounded-xl"
                                ></div>
                            </div>
                        </div>
                    </template>

                    <!-- LOT IMAGE EMBEDDED VIEW -->
                    <template v-else-if="isShowingLotImage && activeBurial">
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
                                        No labeled lot image available for this lot type.
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

                    <!-- DETAIL VIEW -->
                    <template v-else-if="activeBurial">
                        <div
                            class="p-5 rounded-xl border border-white/30 dark:border-white/10 bg-white/60 dark:bg-neutral-800/60 backdrop-blur-md"
                        >
                            <div
                                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
                            >
                                <div class="flex items-center gap-4 min-w-0 w-full">
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

                                    <div class="min-w-0 flex-1">
                                        <h3
                                            class="text-lg font-semibold text-green-600 dark:text-green-400 break-words sm:truncate"
                                        >
                                            {{ deceasedFullName }}
                                        </h3>

                                        <p
                                            class="text-xs text-gray-500 dark:text-gray-400"
                                        >
                                            Burial Record #{{
                                                activeBurial.burial?.id ?? "N/A"
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 flex-wrap justify-start sm:justify-end w-full sm:w-auto sm:shrink-0 border-t sm:border-0 border-white/20 dark:border-white/10 pt-3 sm:pt-0">
                                    <Link
                                        v-if="burialShowRoute"
                                        :href="
                                            route(
                                                burialShowRoute,
                                                activeBurial.burial?.id,
                                            )
                                        "
                                        @click="closeModal"
                                        class="px-3 py-1.5 text-sm font-medium rounded-lg transition bg-green-500/10 text-green-400 border-transparent hover:bg-green-500/20 hover:border-green-500/40 hover:text-green-600 dark:hover:text-green-300"
                                    >
                                        View More
                                    </Link>

                                    <!-- See Lot Image (plain green text, underline on hover) -->
                                    <button
                                        @click="showLotImage"
                                        class="px-3 py-1.5 text-sm font-medium rounded-lg text-green-600 dark:text-green-400 hover:underline transition"
                                    >
                                        See Lot Image
                                    </button>

                                    <!-- View Path (highlighted green as normal) -->
                                    <button
                                        @click="handleViewPath(activeBurial.burial?.id)"
                                        data-hs-overlay="#hs-visitor-deceased-modal"
                                        class="px-3 py-1.5 text-sm font-medium rounded-lg border border-green-500/30 bg-green-500/10 text-green-700 dark:text-green-300 hover:bg-green-500/20 hover:border-green-500/40 transition"
                                    >
                                        View Path
                                    </button>
                                </div>
                            </div>

                            <!-- Info grid - tailored for visitor with Phase & Cluster -->
                            <div class="mt-5 grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span
                                        class="text-gray-500 dark:text-gray-400"
                                    >
                                        Phase
                                    </span>
                                    <div class="font-medium">
                                        {{
                                            clusterProps.phase ||
                                            lotProps.phase ||
                                            "N/A"
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
                                            clusterProps.name ||
                                            lotProps.cluster ||
                                            "N/A"
                                        }}
                                        <span
                                            v-if="clusterProps.type"
                                            class="text-xs text-gray-500 dark:text-gray-400"
                                            >({{ clusterProps.type }})</span
                                        >
                                    </div>
                                </div>

                                <div>
                                    <span
                                        class="text-gray-500 dark:text-gray-400"
                                    >
                                        Lot Location
                                    </span>
                                    <div class="font-medium">
                                        {{ lotProps.column
                                        }}{{ lotProps.row || "" }}
                                        <span
                                            v-if="
                                                !lotProps.column &&
                                                !lotProps.row
                                            "
                                            >N/A</span
                                        >
                                    </div>
                                </div>

                                <div>
                                    <span
                                        class="text-gray-500 dark:text-gray-400"
                                    >
                                        Burial Date
                                    </span>
                                    <div class="font-medium">
                                        {{ formattedBurialDate }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <p
                            class="text-xs text-center text-gray-400 dark:text-gray-500 pt-1"
                        >
                            Tap the highlighted area on the map to view this
                            details again
                        </p>
                    </template>

                    <!-- Empty state -->
                    <template v-else>
                        <div class="text-center py-8 space-y-2">
                            <div class="flex justify-center">
                                <div
                                    class="size-12 rounded-full bg-gray-200 dark:bg-neutral-700 flex items-center justify-center text-gray-400"
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
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                No burial information available for this
                                selection.
                            </p>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

</template>
