<script setup>
import { computed, ref, watch } from "vue";

const props = defineProps({
    modalId: { type: String, required: true },
    clusterType: { type: String, default: "" },
    phaseName: { type: String, default: "" },
});

const imageError = ref(false);

const normalizedType = computed(() =>
    (props.clusterType ?? "").toString().trim().toLowerCase(),
);

const normalizedPhase = computed(() =>
    (props.phaseName ?? "").toString().trim().toUpperCase(),
);

const isPhase1A = computed(() => {
    const p = normalizedPhase.value;
    // Strict "1A" or token "1A" inside "PHASE 1A" / "PHASE-1A" etc.
    if (p === "1A") return true;
    // Split by space/hyphen/underscore to catch "Phase 1A"
    const tokens = p.split(/[\s\-_]+/);
    return tokens.includes("1A");
});

const imageSrc = computed(() => {
    if (normalizedType.value === "underground") {
        return isPhase1A.value
            ? "/images/labeled-lots/underground-reversed.png"
            : "/images/labeled-lots/underground.png";
    }
    if (normalizedType.value === "apartment") {
        return isPhase1A.value
            ? "/images/labeled-lots/apartment-reversed.png"
            : "/images/labeled-lots/apartment.png";
    }
    return null;
});

const imageLabel = computed(() => {
    if (!imageSrc.value) return "No image";
    const base =
        normalizedType.value === "underground" ? "Underground" : "Apartment";
    return isPhase1A.value ? `${base} (Phase 1A – Reversed)` : base;
});

const typeGuidance = computed(() => {
    if (normalizedType.value === "apartment") {
        return "Please ensure you are facing the cluster's fronton (main facade/entrance) — apartment numbers are labeled from this viewpoint. Orient yourself to the fronton before locating the lot.";
    }
    if (normalizedType.value === "underground") {
        return "The interactive map follows true cardinal directions (N, S, E, W). Use the map's north orientation to align this underground lot image correctly.";
    }
    return null;
});

const openFullscreen = () => {
    if (imageSrc.value) window.open(imageSrc.value, "_blank");
};

watch(imageSrc, () => {
    imageError.value = false;
});
</script>

<template>
    <div
        :id="modalId"
        class="hs-overlay hidden size-full fixed top-0 start-0 z-[3000] overflow-x-hidden overflow-y-auto bg-black/50 backdrop-blur-sm pointer-events-none"
        role="dialog"
        tabindex="-1"
        :aria-labelledby="`${modalId}-label`"
    >
        <div
            class="hs-overlay-open:mt-4 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-0 ease-out transition-all w-full max-w-[95vw] sm:max-w-[92vw] lg:max-w-6xl xl:max-w-7xl m-3 sm:mx-auto min-h-[calc(100%-2rem)] flex items-center"
        >
            <div
                class="max-h-[94vh] h-[94vh] overflow-hidden flex flex-col bg-white/80 dark:bg-neutral-900/80 backdrop-blur-xl border border-white/20 dark:border-white/10 shadow-lg shadow-gray-200/50 dark:shadow-black/50 rounded-2xl pointer-events-auto w-full"
            >
                <!-- Header -->
                <div
                    class="flex justify-between items-center py-3 px-4 border-b border-white/20 dark:border-white/10 bg-white/40 dark:bg-neutral-800/40 backdrop-blur-md shrink-0"
                >
                    <div>
                        <h3
                            :id="`${modalId}-label`"
                            class="font-bold text-gray-800 dark:text-white"
                        >
                            Lot Image
                        </h3>
                        <p
                            v-if="imageSrc"
                            class="text-xs text-gray-500 dark:text-gray-400"
                        >
                            {{ imageLabel }} · Phase {{ phaseName || "N/A" }} ·
                            {{ clusterType || "N/A" }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="size-8 inline-flex justify-center items-center rounded-full bg-white/40 dark:bg-neutral-800/40 backdrop-blur-md border border-white/20 dark:border-white/10 text-gray-700 dark:text-neutral-200 hover:bg-white/60 dark:hover:bg-neutral-700/60 transition"
                        :data-hs-overlay="`#${modalId}`"
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

                <!-- Body -->
                <div class="p-3 sm:p-4 overflow-y-auto space-y-3 flex-1 min-h-0">
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
                        <!-- Type-specific guidance -->
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
                                <span v-if="clusterType">
                                    · current: {{ clusterType }}</span
                                >
                            </p>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
