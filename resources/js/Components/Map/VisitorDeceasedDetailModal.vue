<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import { route } from "ziggy-js";

const page = usePage();
const user = computed(() => page.props.auth.user);

const props = defineProps({
    feature: { type: Object, default: null },
    burialId: { type: [Number, String], default: null },
    isLoading: { type: Boolean, default: false },
});

const emit = defineEmits(["viewPath"]);

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
</script>

<template>
    <div
        id="hs-visitor-deceased-modal"
        class="hs-overlay hidden size-full fixed top-0 start-0 z-[2000] overflow-x-hidden overflow-y-auto bg-black/40 backdrop-blur-sm pointer-events-none"
        role="dialog"
        tabindex="-1"
        aria-labelledby="hs-visitor-deceased-modal-label"
    >
        <div
            class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-0 ease-out transition-all sm:max-w-3xl sm:w-full m-3 sm:mx-auto h-[calc(100%-3.5rem)] min-h-[calc(100%-3.5rem)] flex items-center"
        >
            <div
                class="max-h-full overflow-hidden flex flex-col bg-white/70 dark:bg-neutral-900/70 backdrop-blur-xl border border-white/20 dark:border-white/10 shadow-lg shadow-gray-200/50 dark:shadow-black/50 rounded-2xl pointer-events-auto w-full"
            >
                <!-- Header -->
                <div
                    class="flex justify-between items-center py-3 px-4 border-b border-white/20 dark:border-white/10 bg-white/40 dark:bg-neutral-800/40 backdrop-blur-md"
                >
                    <h3
                        id="hs-visitor-deceased-modal-label"
                        class="font-bold text-gray-800 dark:text-white"
                    >
                        Deceased Details
                    </h3>

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

                    <!-- DETAIL VIEW -->
                    <template v-else-if="activeBurial">
                        <div
                            class="p-5 rounded-xl border border-white/30 dark:border-white/10 bg-white/60 dark:bg-neutral-800/60 backdrop-blur-md"
                        >
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <div class="flex items-center gap-4 min-w-0">
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

                                <div class="flex items-center gap-2 shrink-0">
                                    <Link
                                        v-if="user !== null"
                                        :href="
                                            route(
                                                'clerk.burial_records.show',
                                                activeBurial.burial?.id,
                                            )
                                        "
                                        class="px-3 py-1.5 text-sm font-medium rounded-lg transition bg-green-500/10 text-green-400 border-transparent hover:bg-green-500/20 hover:border-green-500/40 hover:text-green-600 dark:hover:text-green-300"
                                    >
                                        View More
                                    </Link>

                                    <button
                                        @click="
                                            emit(
                                                'viewPath',
                                                activeBurial.burial?.id,
                                            )
                                        "
                                        class="px-3 py-1.5 text-sm font-medium rounded-lg text-green-600 dark:text-green-400 hover:underline transition"
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
