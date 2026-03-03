// useMapLoader.js
import { useDebounceFn } from "@vueuse/core";
import { useFeatureProcessing } from "./useFeatureProcessing";
import { useMapStates } from "@/stores/useMapStates";

export function useDbGeoJson() {
    const { processFeatures, separateLotsByType, clearLayers } =
        useFeatureProcessing();
    const { map } = useMapStates();

    const loadVisibleLots = useDebounceFn(async () => {
        if (!map.value) return;
        const zoom = map.value.getZoom();
        if (zoom < 21) return;

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
        const features = json.data.map((record) => record.lot).filter(Boolean);

        if (features.length === 0) return;

        // ✅ clear old layers before re-rendering
        clearLayers();

        const processed = processFeatures(features);
        separateLotsByType(processed);
    }, 300);

    return { loadVisibleLots };
}
