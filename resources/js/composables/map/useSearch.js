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

    const showSearchResult = (burialData) => {
        searchResultLayer.value.clearLayers();
        isOnSearchMode.value = true;
        console.log(isOnSearchMode.value);

        console.log("Picked Result: ", burialData);

        const lot = burialData.lot.geometry;

        if (!lot || !lot.coordinates) {
            console.error("No lot data available for this record");
            return;
        }

        // Extract coordinates from MultiPolygon GeoJSON
        // coordinates structure: [[[[[lng, lat], [lng, lat], ...]]]
        const polygonCoords = lot.coordinates[0][0];
        console.log("Polygon coords", polygonCoords);
        markPolygon(polygonCoords);
    };

    const markPolygon = (polygonCoordinate) => {
        if (!polygonCoordinate || !polygonCoordinate.length) {
            console.error(
                `Unable to mark polygon, invalid polygon coordinates`
            );
            return;
        }

        const latLngs = polygonCoordinate
            .slice(0, -1)
            .map((coord) => [coord[1], coord[0]]);
        const polygon = L.polygon(latLngs, {
            color: "#ef4444",
            fillColor: "#ef4444",
            fillOpacity: 0.3,
            weight: 3,
        });

        searchResultLayer.value.addLayer(polygon);

        // Ensure the search result layer is added to the map
        if (!map.value.hasLayer(searchResultLayer.value)) {
            searchResultLayer.value.addTo(map.value);
        }

        // Fit map bounds to show the polygon
        map.value.fitBounds(polygon.getBounds(), {
            padding: [50, 50],
            maxZoom: 20,
        });
    };

    return {
        fetchSuggestions,
        showSearchResult,
    };
}
