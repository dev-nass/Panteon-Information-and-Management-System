import L from "leaflet";
import { useMapStates } from "@/stores/useMapStates";
import { useMapSearchStates } from "@/stores/useMapSearchStates";
import { useDbGeoJson } from "./map/useDbGeoJson";

// Panteon Long and Lat
const LAT = 14.3052681;
const LONG = 120.9758;
const ZOOM_LVL = 18;
const MIN_RENDER_ZOOM = 20;
const RENDER_DEBOUNCE_MS = 2000;
let moveTimeout = null;

export function useMap() {
    const {
        map,
        googleLayer,

        phaseLayerGroup,
        phaseVisibility,

        clusterLayers,
        clusterVisibility,
        uniqueTypes,

        toggleMapFeaturesState,
    } = useMapStates();
    const { isOnSearchMode } = useMapSearchStates();
    const { loadAllPhases, loadVisibleClusters } = useDbGeoJson();

    /**
     * Description: Initalize the map
     * @param {*} mapContainerElem the instance of the map assigned to an HTML element
     */
    const initializeMap = async (mapContainerElem) => {
        map.value = L.map(mapContainerElem).setView([LAT, LONG], ZOOM_LVL);
        map.value.zoomControl.remove();
        L.control
            .zoom({
                position: "bottomleft",
            })
            .addTo(map.value);

        initializeLayers();

        // Registration of event listener when the map if moved
        map.value.on("moveend", () => {
            if (moveTimeout) clearTimeout(moveTimeout);
            moveTimeout = setTimeout(() => {
                loadVisibleClusters(); // ← no argument needed
            }, 300);
        });

        // Registration of event listener when the map if zoomed
        map.value.on("zoomend", () => {
            loadVisibleClusters(); // ← no argument needed
            updateVisibility();
        });

        // initial load
        loadAllPhases();
        loadVisibleClusters();
    };

    /**
     * Description: Initializes the map layers
     */
    const initializeLayers = () => {
        if (!googleLayer.value) {
            googleLayer.value = L.tileLayer(
                "https://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}",
                {
                    maxZoom: 30,
                    subdomains: ["mt0", "mt1", "mt2", "mt3"],
                },
            );

            googleLayer.value.addTo(map.value);
        }
    };

    /**
     * Description: Properly destroys the map each onBeforeMount;
     *              Used in Clerk/Map/index
     */
    const cleanupMap = () => {
        if (map.value) {
            map.value.remove(); // Properly destroys map and removes all listeners
            map.value = null;
        }

        isOnSearchMode.value = false;

        // Clear layer references
        googleLayer.value = null;
        phaseLayerGroup.value = null;
        clusterLayers.value.clear();
    };

    /**
     * Description: Used if the zoom level is too far
     */
    const cleanupLayers = () => {
        if (!map.value) return;

        uniqueTypes.value.forEach((type) => {
            const layer = clusterLayers.value.get(type);
            if (layer && map.value.hasLayer(layer)) {
                map.value.removeLayer(layer);
            }
        });
    };

    /**
     * Description: Toggle map 'clusters' features on/off for filtering
     * @param {string} type - Type of feature to toggle ("all" or specific type)
     * @enum {string} type - "all", "apartment", 'underground'
     */
    const toggleMapFeatures = (type = "all") => {
        console.log("Toggling feature type of: ", type);
        if (type === "all") {
            // Toggle all types
            const allVisible = Array.from(
                clusterVisibility.value.values(),
            ).every((v) => v);
            const newState = !allVisible;

            uniqueTypes.value.forEach((t) => {
                clusterVisibility.value.set(t, newState);
            });
        } else {
            // Toggle specific type
            if (!clusterVisibility.value.has(type)) return;

            const isVisible = clusterVisibility.value.get(type);
            clusterVisibility.value.set(type, !isVisible);
        }

        updateVisibility();

        // Update the state
        toggleMapFeaturesState.value = Array.from(
            clusterVisibility.value.values(),
        ).some((v) => v);
    };

    /**
     * Description: Toggle map 'phase' features on/off for filtering
     */
    const togglePhaseVisibility = (type = "off") => {
        if (type == "off") {
            map.value.removeLayer(phaseLayerGroup.value);
        } else if (type == "on") {
            phaseLayerGroup.value.addTo(map.value);
        }
    };

    /**
     * Description: Update the visibility of the map based on
     *              clusterVisibility state (true/false)
     *              and zoom level
     */
    const updateVisibility = () => {
        if (!map.value) return;

        // Hide all layers when in search mode
        if (isOnSearchMode.value) {
            cleanupLayers();
            return;
        }

        const zoom = map.value.getZoom();

        // loads the cluster
        if (zoom >= MIN_RENDER_ZOOM) {
            phaseVisibility.value = false;
            if (
                phaseLayerGroup.value &&
                map.value.hasLayer(phaseLayerGroup.value)
            ) {
                map.value.removeLayer(phaseLayerGroup.value);
            }

            uniqueTypes.value.forEach((type) => {
                const layer = clusterLayers.value.get(type);
                if (!layer) return;

                const shouldShow = clusterVisibility.value.get(type) === true;

                const isAdded = map.value.hasLayer(layer);

                if (shouldShow && !isAdded) {
                    layer.addTo(map.value);
                }

                if (!shouldShow && isAdded) {
                    map.value.removeLayer(layer);
                }
            });
        }
        // loads the phase
        else {
            phaseVisibility.value = true;
            if (
                phaseLayerGroup.value &&
                !map.value.hasLayer(phaseLayerGroup.value)
            ) {
                phaseLayerGroup.value.addTo(map.value);
                toggleMapFeatures("all");
                cleanupLayers();
            }
        }
    };

    return {
        initializeMap,
        cleanupMap,
        toggleMapFeatures,
        togglePhaseVisibility,
    };
}
