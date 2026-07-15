import L from "leaflet";
import { useMapSearchStates } from "@/stores/useMapSearchStates";
import { useMapStates } from "@/stores/useMapStates";
import { useDrawProcessedPath } from "@/composables/map/pathfinder/useDrawProcessedPath";
import { useDbGeoJson } from "@/composables/map/useDbGeoJson";

const LAT = 14.3052681;
const LONG = 120.9758;
const ZOOM_LVL = 18;
const MIN_RENDER_ZOOM = 20;
let moveTimeout = null;

export function useVisitorMap() {
    const {
        map,
        googleLayer,
        phaseLayerGroup,
        phaseVisibility,
        clusterLayers,
        uniqueTypes,
    } = useMapStates();
    const { searchResultLayer, isOnSearchMode } = useMapSearchStates();
    const { clearPathLayers } = useDrawProcessedPath();
    const { loadAllPhases, loadVisibleClusters } = useDbGeoJson();

    const initializeMap = (mapContainerElem) => {
        map.value = L.map(mapContainerElem).setView([LAT, LONG], ZOOM_LVL);
        map.value.zoomControl.remove();
        L.control.zoom({ position: "bottomleft" }).addTo(map.value);

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

        searchResultLayer.value = L.layerGroup().addTo(map.value);

        map.value.on("moveend", () => {
            if (moveTimeout) clearTimeout(moveTimeout);
            moveTimeout = setTimeout(() => loadVisibleClusters(), 300);
        });

        map.value.on("zoomend", () => {
            loadVisibleClusters();
            updateVisibility();
        });

        loadAllPhases();
        loadVisibleClusters();
    };

    const updateVisibility = () => {
        if (!map.value) return;

        if (isOnSearchMode.value) {
            uniqueTypes.value.forEach((type) => {
                const layer = clusterLayers.value.get(type);
                if (layer && map.value.hasLayer(layer)) {
                    map.value.removeLayer(layer);
                }
            });
            return;
        }

        const zoom = map.value.getZoom();

        if (zoom >= MIN_RENDER_ZOOM) {
            phaseVisibility.value = false;
            if (phaseLayerGroup.value && map.value.hasLayer(phaseLayerGroup.value)) {
                map.value.removeLayer(phaseLayerGroup.value);
            }
        } else {
            phaseVisibility.value = true;
            if (phaseLayerGroup.value && !map.value.hasLayer(phaseLayerGroup.value)) {
                phaseLayerGroup.value.addTo(map.value);
            }
        }
    };

    const cleanupMap = () => {
        if (map.value) {
            map.value.remove();
            map.value = null;
        }
        isOnSearchMode.value = false;
        googleLayer.value = null;
        phaseLayerGroup.value = null;
        clusterLayers.value.clear();
        searchResultLayer.value = null;
    };

    const clearSearch = () => {
        if (searchResultLayer.value) {
            if (map.value?.hasLayer(searchResultLayer.value)) {
                map.value.removeLayer(searchResultLayer.value);
            }
            searchResultLayer.value = L.layerGroup().addTo(map.value);
        }
        clearPathLayers();
        isOnSearchMode.value = false;
    };

    return {
        initializeMap,
        cleanupMap,
        clearSearch,
    };
}
