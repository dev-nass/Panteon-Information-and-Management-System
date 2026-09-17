import { ref, computed, watch } from "vue";

/**
 * Manages lot-image view inside BurialRecordModal.
 * Keeps modal auto-sizing logic:
 * - details view: normal sm:max-w-3xl
 * - lot image view: expanded max-w-[95vw] / 6xl / 7xl with 94vh height
 * Provides same image derivation as LotImageModal (underground/apartment + Phase 1A reversal).
 *
 * @param {import('vue').Ref<string>} clusterTypeRef - reactive cluster type
 * @param {import('vue').Ref<string>} phaseNameRef - reactive phase name
 * @param {import('vue').Ref<any>} selectedBurialRef - reactive selected burial to auto-reset view
 */
export function useBurialRecordModal(clusterTypeRef, phaseNameRef, selectedBurialRef = null) {
    const imageError = ref(false);
    const isShowingLotImage = ref(false);

    const normalizedType = computed(() =>
        (clusterTypeRef?.value ?? "").toString().trim().toLowerCase(),
    );

    const normalizedPhase = computed(() =>
        (phaseNameRef?.value ?? "").toString().trim().toUpperCase(),
    );

    const isPhase1A = computed(() => {
        const p = normalizedPhase.value;
        if (p === "1A") return true;
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
        const base = normalizedType.value === "underground" ? "Underground" : "Apartment";
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

    const canShowLotImage = computed(() => {
        const t = normalizedType.value;
        return t === "underground" || t === "apartment";
    });

    const showLotImage = () => {
        if (canShowLotImage.value && imageSrc.value) {
            isShowingLotImage.value = true;
        }
    };

    const hideLotImage = () => {
        isShowingLotImage.value = false;
    };

    const backToDetails = () => {
        hideLotImage();
    };

    const openFullscreen = () => {
        if (imageSrc.value) window.open(imageSrc.value, "_blank");
    };

    // Auto-adjust modal container sizing (retain hs-overlay transition)
    const modalWrapperClass = computed(() =>
        isShowingLotImage.value
            ? "hs-overlay-open:mt-4 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-0 ease-out transition-all w-full max-w-[95vw] sm:max-w-[92vw] lg:max-w-6xl xl:max-w-7xl m-3 sm:mx-auto min-h-[calc(100%-2rem)] flex items-center"
            : "hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-0 ease-out transition-all sm:max-w-3xl sm:w-full m-3 sm:mx-auto h-[calc(100%-3.5rem)] min-h-[calc(100%-3.5rem)] flex items-center",
    );

    const modalContentClass = computed(() =>
        isShowingLotImage.value
            ? "max-h-[94vh] h-[94vh] overflow-hidden flex flex-col bg-white/80 dark:bg-neutral-900/80 backdrop-blur-xl border border-white/20 dark:border-white/10 shadow-lg shadow-gray-200/50 dark:shadow-black/50 rounded-2xl pointer-events-auto w-full"
            : "max-h-full overflow-hidden flex flex-col bg-white/70 dark:bg-neutral-900/70 backdrop-blur-xl border border-white/20 dark:border-white/10 shadow-lg shadow-gray-200/50 dark:shadow-black/50 rounded-2xl pointer-events-auto w-full",
    );

    watch(imageSrc, () => {
        imageError.value = false;
    });

    // Reset to details when burial selection changes or cleared
    if (selectedBurialRef) {
        watch(
            () => selectedBurialRef.value,
            () => {
                hideLotImage();
                imageError.value = false;
            },
        );
    }

    return {
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
        normalizedType,
        normalizedPhase,
        isPhase1A,
    };
}
