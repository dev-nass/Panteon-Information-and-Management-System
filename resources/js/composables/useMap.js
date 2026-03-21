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
    const { map, googleLayer, lotLayers, lotVisibility, uniqueTypes } =
        useMapStates();
    const { isOnSearchMode } = useMapSearchStates();
    const { loadVisibleLots } = useDbGeoJson();

    const initializeMap = async (mapContainerElem) => {
        map.value = L.map(mapContainerElem).setView([LAT, LONG], ZOOM_LVL);
        map.value.zoomControl.remove();
        L.control
            .zoom({
                position: "bottomleft",
            })
            .addTo(map.value);

        initializeLayers();

        // ✅ Remove the map argument — useDbGeoJson reads from store directly
        map.value.on("moveend", () => {
            if (moveTimeout) clearTimeout(moveTimeout);
            moveTimeout = setTimeout(() => {
                loadVisibleLots(); // ← no argument needed
            }, 300);
        });

        map.value.on("zoomend", () => {
            loadVisibleLots(); // ← no argument needed
            updateVisibility();
        });

        loadVisibleLots(); // initial load
    };

    const initializeLayers = () => {
        if (!googleLayer.value) {
            googleLayer.value = L.tileLayer(
                "https://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}",
                {
                    maxZoom: 30,
                    subdomains: ["mt0", "mt1", "mt2", "mt3"],
                }
            );

            googleLayer.value.addTo(map.value);
        }
    };

    // properly destroys the map each render; used in View
    const cleanupMap = () => {
        if (map.value) {
            map.value.remove(); // Properly destroys map and removes all listeners
            map.value = null;
        }

        // Clear layer references
        googleLayer.value = null;
        // entranceLayer.value = L.layerGroup();
    };

    // used if the zoom is too far
    const cleanupLayers = () => {
        if (!map.value) return;

        uniqueTypes.value.forEach((type) => {
            const layer = lotLayers.value.get(type);
            if (layer && map.value.hasLayer(layer)) {
                map.value.removeLayer(layer);
            }
        });
    };

    const clearSearch = () => {
        search.value = "";
        suggestions.value = [];
        searchResultLayer.value.clearLayers();
        isOnSearchMode.value = false;
    };

    const updateVisibility = () => {
        if (!map.value) return;

        // Hide all layers when in search mode
        if (isOnSearchMode.value) {
            cleanupLayers();
        }

        const zoom = map.value.getZoom();

        uniqueTypes.value.forEach((type) => {
            const layer = lotLayers.value.get(type);
            if (!layer) return;

            const shouldShow =
                zoom >= MIN_RENDER_ZOOM &&
                lotVisibility.value.get(type) === true;

            const isAdded = map.value.hasLayer(layer);

            if (shouldShow && !isAdded) {
                layer.addTo(map.value);
            }

            if (!shouldShow && isAdded) {
                map.value.removeLayer(layer);
            }
        });
    };

    // continuously calls the updateVisibility
    // setInterval(() => {
    //     console.log("updating...");
    //     updateVisibility();
    // }, RENDER_DEBOUNCE_MS);

    return {
        initializeMap,
        cleanupMap,
    };
}
