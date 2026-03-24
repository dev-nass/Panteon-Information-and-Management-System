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
            suggestions.value = data.data;
            console.log(suggestions.value);
        } catch (err) {
            console.error(err);
            suggestions.value = [];
        } finally {
            loading.value = false;
        }
    }, 300);

    const showSearchResult = (lotData) => {
        searchResultLayer.value.clearLayers();

        console.log("Picked Result: ", lotData);

        const lot = lotData.lot;

        if (!lot || !lot.geometry || !lot.geometry.coordinates) {
            console.error("No lot data available for this record");
            return;
        }

        // Extract coordinates from Polygon GeoJSON
        // LotResource returns: lot.geometry.coordinates = [[lng, lat], [lng, lat], ...]
        const polygonCoords = normalizeCoordinates(lot.geometry.coordinates);
        console.log("Polygon coords", polygonCoords);
        markPolygon(lotData, polygonCoords);
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
     * @param lotData expects a lot record from LotResource
     * @param polygonCoordinate expects GeoJSON coordinates
     */
    const markPolygon = (lotData, polygonCoordinate) => {
        if (!polygonCoordinate || !polygonCoordinate.length) {
            console.error(
                `Unable to mark polygon, invalid polygon coordinates`
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
                burials: lotData.burials ?? [],
                ...lotData.lot.properties,
            },
        };

        const geoJsonLayer = L.geoJSON(geoJsonFeature, {
            style: getSearchResultStyle,
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

    const getSearchResultStyle = () => {
        return {
            color: "#ef4444",
            fillColor: "#ef4444",
            fillOpacity: 0.3,
            weight: 3,
        };
    };

    // duplicate function
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
        showSearchResult,
        clearSearch,
    };
}
