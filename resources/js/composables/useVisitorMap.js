import L from "leaflet";
import { ref } from "vue";
import { route } from "ziggy-js";
import { useMapSearchStates } from "@/stores/useMapSearchStates";
import { useMapStates } from "@/stores/useMapStates";
import { useDrawProcessedPath } from "@/composables/map/pathfinder/useDrawProcessedPath";

const LAT = 14.3052681;
const LONG = 120.9758;
const ZOOM_LVL = 18;

export function useVisitorMap() {
    const { map } = useMapStates();
    const googleLayer = ref(null);
    const { searchResultLayer, isOnSearchMode } = useMapSearchStates();
    const { clearPathLayers } = useDrawProcessedPath();

    const initializeMap = (mapContainerElem) => {
        map.value = L.map(mapContainerElem).setView([LAT, LONG], ZOOM_LVL);
        map.value.zoomControl.remove();
        L.control.zoom({ position: "bottomleft" }).addTo(map.value);

        googleLayer.value = L.tileLayer(
            "https://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}",
            {
                maxZoom: 30,
                subdomains: ["mt0", "mt1", "mt2", "mt3"],
            }
        );
        googleLayer.value.addTo(map.value);

        // Initialize search result layer
        if (!searchResultLayer.value) {
            searchResultLayer.value = L.layerGroup();
        }
        searchResultLayer.value.addTo(map.value);
    };

    const cleanupMap = () => {
        if (map.value) {
            map.value.remove();
            map.value = null;
        }
        googleLayer.value = null;
        if (searchResultLayer.value) {
            searchResultLayer.value.clearLayers();
        }
    };

    const searchBurialRecord = async (searchQuery, normalizeCoordinates, markBurialRecordClusterPolygon, markBurialRecordLotPoint) => {
        try {
            // Clear previous search results
            if (searchResultLayer.value) {
                searchResultLayer.value.clearLayers();
            }

            const response = await fetch(
                `${route("visitor.map.search")}?search=${encodeURIComponent(searchQuery)}`,
                {
                    headers: { Accept: "application/json" },
                    credentials: "same-origin",
                }
            );
            if (!response.ok) throw new Error("Failed to search burial records");
            const data = await response.json();
            if (data.data && data.data.length > 0) {
                data.data.forEach((cluster) => {
                    const clusterCoords = normalizeCoordinates(cluster.cluster.geometry.coordinates);
                    markBurialRecordClusterPolygon(cluster, clusterCoords);

                    // Mark lot points
                    if (cluster.lots && cluster.lots.length > 0) {
                        cluster.lots.forEach((lotResource) => {
                            const lot = lotResource.lot;
                            if (lot?.geometry?.coordinates) {
                                markBurialRecordLotPoint(lot);
                            }
                        });
                    }
                });
            }
        } catch (err) {
            console.error(err);
        }
    };

    const clearSearch = () => {
        if (searchResultLayer.value) {
            searchResultLayer.value.clearLayers();
        }
        clearPathLayers();
        isOnSearchMode.value = false;
    };

    return {
        initializeMap,
        cleanupMap,
        searchBurialRecord,
        clearSearch,
    };
}
