import NProgress from "nprogress";

import { useDebounceFn } from "@vueuse/core";
import { useFeatureProcessing } from "./useFeatureProcessing";
import { useMapStates } from "@/stores/useMapStates";

export function useDbGeoJson() {
    const { processFeatures, separateLotsByType, clearLayers } =
        useFeatureProcessing();
    const { map } = useMapStates();

    const loadVisibleLots = useDebounceFn(async () => {
        if (!map.value) return;
        if (map.value.getZoom() < 21) return;

        NProgress.start();

        try {
            const bounds = map.value.getBounds();
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

            if (features.length === 0) return;

            clearLayers();

            const processed = processFeatures(features);
            separateLotsByType(processed);
        } finally {
            NProgress.done();
        }
    }, 300);

    return { loadVisibleLots };
}
