import NProgress from "nprogress";
import { useDebounceFn } from "@vueuse/core";
import { useFeatureProcessing } from "./useFeatureProcessing";
import { useMapStates } from "@/stores/useMapStates";
import { useMapSearchStates } from "@/stores/useMapSearchStates";

const MIN_RENDER_ZOOM = 20;
let lastBounds = null;
let lastZoom = null;
let lastFeatureIds = new Set();

export function useDbGeoJson() {
    const { processFeatures, separateLotsByType, clearLayers } =
        useFeatureProcessing();
    const { map } = useMapStates();
    const { isOnSearchMode } = useMapSearchStates();

    const loadVisibleLots = useDebounceFn(async () => {
        // prevents if the map doesn't exist or is on search mode
        if (!map.value || isOnSearchMode.value) return;

        const currentZoom = map.value.getZoom();

        if (currentZoom < MIN_RENDER_ZOOM) {
            // Only clear if we were previously rendering layers
            if (lastZoom !== null && lastZoom >= MIN_RENDER_ZOOM) {
                // clearLayers();
                lastBounds = null;
                lastFeatureIds = new Set();
            }
            lastZoom = currentZoom;
            return;
        }

        const bounds = map.value.getBounds();

        // Zoom changed within valid range — invalidate bounds cache only
        if (lastZoom !== currentZoom) {
            lastBounds = null;
            lastZoom = currentZoom;
        }

        // Skip if current view is already covered by last fetched bounds
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
                    zoom: currentZoom,
                })
            );

            const json = await response.json();
            // TODO: understand this before and after
            //const features = json.data.map((r) => r.lot).filter(Boolean);
            const features = json.data
                .map((r) => {
                    if (!r.lot) return null;

                    return {
                        ...r.lot,
                        properties: {
                            ...r.lot.properties,
                            burials: r.burials ?? [],
                        },
                    };
                })
                .filter(Boolean);

            if (features.length === 0) {
                clearLayers();
                lastFeatureIds = new Set();
                return;
            }

            const currentIds = new Set(json.data.map((r) => r.id));
            const isSame =
                currentIds.size === lastFeatureIds.size &&
                [...currentIds].every((id) => lastFeatureIds.has(id));

            // ✅ Only clear + re-render if data actually changed
            if (isSame) return;

            lastFeatureIds = currentIds;

            // ✅ Clear AFTER confirming new data exists, not before
            clearLayers(); // this part still causes issue its automticaly removing the features on the map
            const processed = processFeatures(features);
            separateLotsByType(processed);
        } finally {
            NProgress.done();
        }
    }, 400);

    return { loadVisibleLots };
}
