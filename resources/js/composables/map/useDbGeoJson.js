import NProgress from "nprogress";
import { useDebounceFn } from "@vueuse/core";
import { useFeatureProcessing } from "./useFeatureProcessing";
import { useMapStates } from "@/stores/useMapStates";
import { useMapSearchStates } from "@/stores/useMapSearchStates";

const MIN_RENDER_ZOOM = 20;
let lastBounds = null;
let lastZoom = null;
let lastFeatureIds = new Set();
let isPhasesLoaded = false; // emsure that the loadAllPhases function is called once

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
        if (
            !map.value ||
            isOnSearchMode.value ||
            !phaseVisibility.value ||
            !toggleMapFeaturesState.value ||
            isPhasesLoaded
        )
            return;

        const currentZoom = map.value.getZoom();

        if (currentZoom >= 20) {
            console.log(currentZoom);
            return;
        }

        try {
            const response = await fetch(route("api.map.phases"));

            if (!response.ok) {
                throw new Error("Failed to fetch phases");
            }

            const json = await response.json();
            const processed = processFeatures(json.data, "phase");
            // console.log(processed);
            renderPhases(processed);
            isPhasesLoaded = true;
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
        } finally {
            NProgress.done();
        }
    }, 400);

    return { loadAllPhases, loadVisibleClusters };
}
