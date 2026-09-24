import NProgress from "nprogress";
import { useDebounceFn } from "@vueuse/core";
import { useFeatureProcessing } from "./useFeatureProcessing";
import { getMinRenderZoom, useMapStates } from "@/stores/useMapStates";
import { useMapSearchStates } from "@/stores/useMapSearchStates";

let lastBounds = null;
let lastZoom = null;
let lastFeatureIds = new Set();

export function useDbGeoJson() {
    const {
        processFeatures,
        separateClustersByType,
        clearLayers,
        renderPhases,
    } = useFeatureProcessing();
    const { map, phaseVisibility, toggleMapFeaturesState } = useMapStates();
    const { isOnSearchMode } = useMapSearchStates();

    /**
     * Description: Fetch all Phases
     */
    const loadAllPhases = async () => {
        if (!map.value || isOnSearchMode.value) return;

        const currentZoom = map.value.getZoom();

        if (currentZoom >= getMinRenderZoom()) {
            return;
        }

        try {
            const response = await fetch(route("api.map.phases"), {
                credentials: "same-origin",
                headers: { Accept: "application/json" },
            });

            if (!response.ok) {
                const text = await response.text();
                throw new Error(`Failed to fetch phases: ${response.status} ${text.slice(0, 500)}`);
            }

            const json = await response.json();
            const processed = processFeatures(json.data, "phase");
            renderPhases(processed);
        } catch (error) {
            console.error("Error loading phases:", error);
        }
    };

    /**
     * Description: Fetch all the Clusters within the bounds
     */
    const loadVisibleClusters = useDebounceFn(async () => {
        // prevents if the map doesn't exist or is on search mode
        if (!map.value || isOnSearchMode.value || !toggleMapFeaturesState.value)
            return;

        const currentZoom = map.value.getZoom();
        const minZoom = getMinRenderZoom();

        if (currentZoom < minZoom) {
            // Only clear if we were previously rendering layers
            if (lastZoom !== null && lastZoom >= minZoom) {
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
                }),
                {
                    credentials: "same-origin",
                    headers: { Accept: "application/json" },
                }
            );

            if (!response.ok) {
                const text = await response.text();
                console.warn(`partial-burials failed: ${response.status}`, text.slice(0, 500));
                return;
            }

            const json = await response.json();
            // Backend returns array of clusters with nested lots and burial_records
            const clusters = json.data || [];

            if (clusters.length === 0) {
                clearLayers();
                lastFeatureIds = new Set();
                return;
            }

            const currentIds = new Set(
                clusters
                    .map((c) => c.cluster?.properties?.cluster_id)
                    .filter(Boolean)
            );
            const isSame =
                currentIds.size === lastFeatureIds.size &&
                [...currentIds].every((id) => lastFeatureIds.has(id));

            // ✅ Only clear + re-render if data actually changed
            if (isSame) return;

            lastFeatureIds = currentIds;

            // ✅ Clear AFTER confirming new data exists, not before
            clearLayers(); // this part still causes issue its automticaly removing the features on the map
            const processed = processFeatures(clusters);
            // console.log("processed", processed);
            separateClustersByType(processed);
        } catch (error) {
            console.error("Error loading visible clusters:", error);
        } finally {
            NProgress.done();
        }
    }, 400);

    return { loadAllPhases, loadVisibleClusters };
}
