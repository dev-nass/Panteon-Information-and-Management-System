import { debounce } from "lodash";
import { route } from "ziggy-js";

import { useMapSearchStates } from "@/stores/useMapSearchStates";
import { useSearchFeatureProcessing } from "@/composables/map/useSearchFeatureProcessing"; // adjust path as needed

export function useSearch() {
    const { search, suggestions, loading, isOnSearchMode, searchResultLayer } =
        useMapSearchStates();

    const { normalizeCoordinates, markClusterPolygon, markLotPoint } =
        useSearchFeatureProcessing();

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
                `${route("api.map.search")}?search=${encodeURIComponent(search.value)}`,
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
     * Description: Used within Clerk/BurialRecord/Show
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
                showSearchResult(data.data[0]);
            }
        } catch (err) {
            console.error(err);
        }
    };

    // ✅ Now uses the imported helpers directly
    const showSearchResult = (clusterData) => {
        searchResultLayer.value.clearLayers();
        const cluster = clusterData.cluster;
        const lots = clusterData.lots;
        if (!cluster?.geometry?.coordinates) {
            console.error("No cluster data available for this record");
            return;
        }
        const clusterPolygonCoords = normalizeCoordinates(
            cluster.geometry.coordinates,
        );
        markClusterPolygon(clusterData, clusterPolygonCoords);
        if (lots?.length > 0) {
            lots.forEach((lotResource) => {
                const lot = lotResource.lot;
                if (lot?.geometry?.coordinates) markLotPoint(lot);
            });
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
        showSearchResult,
        clearSearch,
    };
}
