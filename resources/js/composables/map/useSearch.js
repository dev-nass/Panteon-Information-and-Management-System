import { debounce } from "lodash";
import { route } from "ziggy-js";

import { useMapSearchStates } from "@/stores/useMapSearchStates";
import { useSearchFeatureProcessing } from "@/composables/map/useSearchFeatureProcessing"; // adjust path as needed

export function useSearch() {
    const { search, suggestions, loading, isOnSearchMode, searchResultLayer } =
        useMapSearchStates();

    const {
        normalizeCoordinates,
        markBurialRecordClusterPolygon,
        markBurialRecordLotPoint,
        markPhasePolygon,
        markClusterPolygon,
        markLotPoint,
    } = useSearchFeatureProcessing();

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
     * Description: Fetch phase data
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
     * Description: Fetch cluster data
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

    /**
     * Description: Process the data fetched from the db
     *              and apply useSearchFeatureProcessing
     * @param {*} data retrieved from the database
     * @param {string} type - 'burial_record', 'phase', 'cluster', or 'lot'
     */
    const showSearchResult = (data, type = "burial_record") => {
        searchResultLayer.value.clearLayers();

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
                    if (lot?.geometry?.coordinates)
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
        searchResultLayer.value.clearLayers();
        isOnSearchMode.value = false;
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
