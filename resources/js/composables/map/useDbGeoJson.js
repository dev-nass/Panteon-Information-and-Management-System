import NProgress from "nprogress";
import { useDebounceFn } from "@vueuse/core";
import { useFeatureProcessing } from "./useFeatureProcessing";
import { useMapStates } from "@/stores/useMapStates";

const MIN_RENDER_ZOOM = 20;

let lastBounds = null;
let lastFeatureIds = new Set();

export function useDbGeoJson() {
    const { processFeatures, separateLotsByType, clearLayers } =
        useFeatureProcessing();
    const { map } = useMapStates();

    const loadVisibleLots = useDebounceFn(async () => {
        if (!map.value) return;
        if (map.value.getZoom() >= MIN_RENDER_ZOOM) return;

        const bounds = map.value.getBounds();

        if (lastBounds && lastBounds.contains(bounds)) {
            return;
        }

        lastBounds = bounds;

        NProgress.start();

        try {
            const response = await fetch(
                route("api.map.partial.burials", {
                    minLat: bounds.getSouth(),
                    maxLat: bounds.getNorth(),
                    minLng: bounds.getWest(),
                    maxLng: bounds.getEast(),
                    zoom: map.value.getZoom(),
                }),
            );

            const json = await response.json();
            const features = json.data.map((r) => r.lot).filter(Boolean);

            if (features.length === 0) {
                clearLayers();
                return;
            }

            const currentIds = new Set(json.data.map((r) => r.id));

            const isSame =
                currentIds.size === lastFeatureIds.size &&
                [...currentIds].every((id) => lastFeatureIds.has(id));

            if (isSame) return;

            lastFeatureIds = currentIds;

            clearLayers();

            const processed = processFeatures(features);
            separateLotsByType(processed);
        } finally {
            NProgress.done();
        }
    }, 400);

    return { loadVisibleLots };
}
