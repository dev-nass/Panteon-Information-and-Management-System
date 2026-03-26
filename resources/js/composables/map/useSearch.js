import { debounce } from "lodash";
import { route } from "ziggy-js";

import { useMapSearchStates } from "@/stores/useMapSearchStates";
import { useMapStates } from "@/stores/useMapStates";

export function useSearch() {
    const { map } = useMapStates();
    const { search, suggestions, loading, isOnSearchMode, searchResultLayer } =
        useMapSearchStates();

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
                    search.value
                )}`,
                {
                    headers: {
                        Accept: "application/json",
                    },
                    credentials: "same-origin",
                }
            );

            if (!response.ok) {
                throw new Error("Failed to fetch suggestions");
            }

            const data = await response.json();
            // Flatten burial records from all clusters and lots
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
            console.log(burials);
            suggestions.value = burials;
            console.log(suggestions.value);
        } catch (err) {
            console.error(err);
            suggestions.value = [];
        } finally {
            loading.value = false;
        }
    }, 300);

    const fetchClusterByBurialId = async (burialId) => {
        try {
            const response = await fetch(
                `${route("api.map.search")}?burial_id=${burialId}`,
                {
                    headers: {
                        Accept: "application/json",
                    },
                    credentials: "same-origin",
                }
            );

            if (!response.ok) {
                throw new Error("Failed to fetch cluster data");
            }

            const data = await response.json();
            if (data.data && data.data.length > 0) {
                showSearchResult(data.data[0]);
            }
        } catch (err) {
            console.error(err);
        }
    };

    const showSearchResult = (clusterData) => {
        searchResultLayer.value.clearLayers();

        console.log("Picked Result: ", clusterData);

        const cluster = clusterData.cluster;
        const lots = clusterData.lots;

        if (!cluster || !cluster.geometry || !cluster.geometry.coordinates) {
            console.error("No cluster data available for this record");
            return;
        }

        // Extract cluster polygon coordinates
        const clusterPolygonCoords = normalizeCoordinates(
            cluster.geometry.coordinates
        );
        console.log("Cluster polygon coords", clusterPolygonCoords);
        markClusterPolygon(clusterData, clusterPolygonCoords);

        // Mark each lot as a point
        if (lots && lots.length > 0) {
            lots.forEach((lotResource) => {
                const lot = lotResource.lot;
                if (lot && lot.geometry && lot.geometry.coordinates) {
                    markLotPoint(lot);
                }
            });
        }
    };

    const normalizeCoordinates = (coords) => {
        if (!coords || !Array.isArray(coords)) return [];

        // Flatten until we get array of [lng, lat] pairs
        let result = coords;
        while (Array.isArray(result[0]) && !isCoordinatePair(result[0])) {
            result = result[0];
        }

        return result;
    };

    const isCoordinatePair = (item) => {
        return (
            Array.isArray(item) &&
            item.length >= 2 &&
            typeof item[0] === "number" &&
            typeof item[1] === "number"
        );
    };

    /**
     * @param clusterData expects a cluster record from ClusterResource
     * @param polygonCoordinate expects GeoJSON coordinates
     */
    const markClusterPolygon = (clusterData, polygonCoordinate) => {
        if (!polygonCoordinate || !polygonCoordinate.length) {
            console.error(
                `Unable to mark cluster polygon, invalid polygon coordinates`
            );
            return;
        }

        const geoJsonFeature = {
            type: "Feature",
            geometry: {
                type: "Polygon",
                coordinates: [polygonCoordinate],
            },
            properties: {
                ...clusterData.cluster.properties,
            },
        };

        const geoJsonLayer = L.geoJSON(geoJsonFeature, {
            style: getClusterSearchResultStyle,
            onEachFeature: attachSearchPopup,
        });

        searchResultLayer.value.addLayer(geoJsonLayer);

        if (!map.value.hasLayer(searchResultLayer.value)) {
            searchResultLayer.value.addTo(map.value);
        }

        map.value.fitBounds(geoJsonLayer.getBounds(), {
            padding: [50, 50],
            maxZoom: 20,
        });
    };

    /**
     * @param lot expects a lot with Point geometry
     */
    const markLotPoint = (lot) => {
        if (!lot.geometry || !lot.geometry.coordinates) {
            console.error(`Unable to mark lot point, invalid coordinates`);
            return;
        }

        // lot.geometry.coordinates = [lng, lat] for Point type
        const [lng, lat] = lot.geometry.coordinates;

        const marker = L.circleMarker([lat, lng], {
            radius: 8,
            fillColor: "#ef4444",
            color: "#fff",
            weight: 2,
            opacity: 1,
            fillOpacity: 0.8,
        });

        searchResultLayer.value.addLayer(marker);
    };

    const getClusterSearchResultStyle = () => {
        return {
            color: "#ef4444",
            fillColor: "#ef4444",
            fillOpacity: 0.2,
            weight: 3,
        };
    };

    // Attach popup to cluster polygon
    const attachSearchPopup = (feature, layer) => {
        layer.on("click", function () {
            window.openLotModal(feature, layer._leaflet_id);
        });
    };

    /**
     * Description: Clears the current search
     *              Used within the view
     */
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
