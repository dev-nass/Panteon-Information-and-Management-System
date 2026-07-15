import { debounce } from "lodash";
import { route } from "ziggy-js";
import L from "leaflet";

import { useMapSearchStates } from "@/stores/useMapSearchStates";
import { useMapStates } from "@/stores/useMapStates";
import { useSearchFeatureProcessing } from "@/composables/map/search/useSearchFeatureProcessing";
import { useDrawProcessedPath } from "@/composables/map/pathfinder/useDrawProcessedPath";
import { useDbGeoJson } from "@/composables/map/useDbGeoJson";

export function useSearch() {
    const { search, suggestions, loading, isOnSearchMode, searchResultLayer } =
        useMapSearchStates();
    const { map, phaseLayerGroup, phaseVisibility, clusterLayers, uniqueTypes } =
        useMapStates();

    const {
        normalizeCoordinates,
        markBurialRecordClusterPolygon,
        markBurialRecordLotPoint,
        markPhasePolygon,
        markClusterPolygon,
        markLotPoint,
    } = useSearchFeatureProcessing();

    const { clearPathLayers } = useDrawProcessedPath();
    const { loadAllPhases, loadVisibleClusters } = useDbGeoJson();

    const hideMapLayers = () => {
        if (phaseVisibility.value && phaseLayerGroup.value && map.value?.hasLayer(phaseLayerGroup.value)) {
            phaseVisibility.value = false;
            map.value.removeLayer(phaseLayerGroup.value);
        }
        uniqueTypes.value.forEach((type) => {
            const layer = clusterLayers.value.get(type);
            if (layer && map.value?.hasLayer(layer)) {
                map.value.removeLayer(layer);
            }
        });
    };

    /**
     * Description: Fetch Burial Records as the user types
     */
    const fetchSuggestions = debounce(async () => {
        if (!search.value) {
            suggestions.value = [];
            return;
        }
        isOnSearchMode.value = true;
        loading.value = true;
        try {
            const response = await fetch(
                `${route("api.map.search")}?search=${encodeURIComponent(
                    search.value,
                )}`,
                {
                    headers: { Accept: "application/json" },
                    credentials: "same-origin",
                },
            );
            if (!response.ok) throw new Error("Failed to fetch suggestions");

            const data = await response.json();
            const burials = [];

            data.data.forEach((cluster) => {
                cluster.lots.forEach((lotResource) => {
                    const lot = lotResource.lot;
                    const burialRecords = lotResource.burial_records || [];
                    burialRecords.forEach((burial) => {
                        burials.push({
                            burial_id: burial.burial.id,
                            deceased_name: burial.deceased.full_name,
                            phase_name: cluster.cluster.properties.phase,
                            lot_location: `${lot.properties.column}${lot.properties.row}`,
                            cluster_name: cluster.cluster.properties.name,
                        });
                    });
                });
            });
            suggestions.value = burials;
        } catch (err) {
            console.error(err);
            suggestions.value = [];
        } finally {
            loading.value = false;
        }
    }, 300);

    /**
     * Description: Used within the Clerk/Map/Index and Clerk/BurialRecord/Show
     */
    const fetchClusterByBurialId = async (burialId) => {
        if (!isOnSearchMode.value) isOnSearchMode.value = true;
        try {
            const response = await fetch(
                `${route("api.map.search")}?burial_id=${burialId}`,
                {
                    headers: { Accept: "application/json" },
                    credentials: "same-origin",
                },
            );
            if (!response.ok) throw new Error("Failed to fetch cluster data");
            const data = await response.json();
            if (data.data && data.data.length > 0) {
                showSearchResult(data.data[0], "burial_record");
            }
        } catch (err) {
            console.error(err);
        }
    };

    /**
     * Description: Fetch phase data; Used on LotManagement "View on Map"
     */
    const fetchPhase = async (phaseId) => {
        if (!isOnSearchMode.value) isOnSearchMode.value = true;
        try {
            const response = await fetch(
                `${route("api.lot.management.phase")}?phase_id=${phaseId}`,
                {
                    headers: { Accept: "application/json" },
                    credentials: "same-origin",
                },
            );
            if (!response.ok) throw new Error("Failed to fetch phase data");
            const data = await response.json();
            if (data.data && data.data.length > 0) {
                showSearchResult(data.data[0], "phase");
            }
        } catch (err) {
            console.error(err);
        }
    };

    /**
     * Description: Fetch cluster data; Used on LotManagement "View on Map"
     */
    const fetchCluster = async (clusterId) => {
        if (!isOnSearchMode.value) isOnSearchMode.value = true;
        try {
            const response = await fetch(
                `${route(
                    "api.lot.management.cluster",
                )}?cluster_id=${clusterId}`,
                {
                    headers: { Accept: "application/json" },
                    credentials: "same-origin",
                },
            );
            if (!response.ok) throw new Error("Failed to fetch cluster data");
            const data = await response.json();
            if (data.data && data.data.length > 0) {
                showSearchResult(data.data[0], "cluster");
            }
        } catch (err) {
            console.error(err);
        }
    };

    /**
     * Description: Fetch lot data; Used on LotManagement "View on Map"
     */
    const fetchLot = async (lotId) => {
        if (!isOnSearchMode.value) isOnSearchMode.value = true;
        try {
            const response = await fetch(
                `${route("api.lot.management.lot")}?lot_id=${lotId}`,
                {
                    headers: { Accept: "application/json" },
                    credentials: "same-origin",
                },
            );
            if (!response.ok) throw new Error("Failed to fetch lot data");
            const data = await response.json();
            if (data.data && data.data.length > 0) {
                showSearchResult(data.data[0], "lot");
            }
        } catch (err) {
            console.error(err);
        }
    };

    const showSearchResult = (data, type = "burial_record") => {
        // Destroy and recreate the layer group to fully detach all zoom listeners
        if (searchResultLayer.value) {
            if (map.value?.hasLayer(searchResultLayer.value)) {
                map.value.removeLayer(searchResultLayer.value);
            }
        }
        searchResultLayer.value = L.layerGroup().addTo(map.value);
        hideMapLayers();

        if (type === "burial_record") {
            // Current process for burial records
            const cluster = data.cluster;
            const lots = data.lots;
            if (!cluster?.geometry?.coordinates) {
                console.error("No cluster data available for this record");
                return;
            }
            const clusterPolygonCoords = normalizeCoordinates(
                cluster.geometry.coordinates,
            );

            markBurialRecordClusterPolygon(data, clusterPolygonCoords);
            if (lots?.length > 0) {
                lots.forEach((lotResource) => {
                    const lot = lotResource.lot;
                    if (lot?.geometry?.coordinates);
                    markBurialRecordLotPoint(lot);
                });
            }
        } else if (type === "phase") {
            const phase = data.phase;
            if (!phase?.geometry?.coordinates) {
                console.error("No phase data available");
                return;
            }
            const phasePolygonCoords = normalizeCoordinates(
                phase.geometry.coordinates,
            );
            markPhasePolygon(data, phasePolygonCoords);
        } else if (type === "cluster") {
            const cluster = data.cluster;
            if (!cluster?.geometry?.coordinates) {
                console.error("No cluster data available");
                return;
            }
            const clusterPolygonCoords = normalizeCoordinates(
                cluster.geometry.coordinates,
            );
            markClusterPolygon(data, clusterPolygonCoords);
        } else if (type === "lot") {
            const lot = data.lot;
            if (!lot?.geometry?.coordinates) {
                console.error("No lot data available");
                return;
            }
            console.log("use search ", lot);
            markLotPoint(lot);
        }
    };

    const clearSearch = () => {
        suggestions.value = [];
        search.value = "";

        // Remove the old layer group from the map entirely and recreate it
        // so no stale zoom-animation listeners survive into the next search
        if (searchResultLayer.value) {
            if (map.value?.hasLayer(searchResultLayer.value)) {
                map.value.removeLayer(searchResultLayer.value);
            }
            searchResultLayer.value = L.layerGroup().addTo(map.value);
        }

        clearPathLayers();
        isOnSearchMode.value = false;
        loadAllPhases();
        loadVisibleClusters();
    };

    return {
        fetchSuggestions,
        fetchClusterByBurialId,
        fetchPhase,
        fetchCluster,
        fetchLot,
        showSearchResult,
        clearSearch,
    };
}
