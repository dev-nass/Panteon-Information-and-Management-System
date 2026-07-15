import { ref } from "vue";
import { useMapStates } from "@/stores/useMapStates";
import { pathFinder } from "@/composables/map/pathfinder/usePathProcessor";

// Module-level refs — each layer remembers the map it was added to
const pathLayer = ref(null);
const dashedLayer = ref(null);
const junctionMarkers = ref([]);

export function useDrawProcessedPath() {
    const { map } = useMapStates();
    const {
        findRouteToPlot,
        findNearestJunction,
        getEntranceJunction,
        findShortestPath,
        fetchNavigationData,
        junctions,
        loading,
    } = pathFinder();

    const clearPathLayers = () => {
        if (pathLayer.value) {
            try {
                if (pathLayer.value._map) {
                    pathLayer.value._map.removeLayer(pathLayer.value);
                }
            } catch (_) {}
            pathLayer.value = null;
        }

        if (dashedLayer.value) {
            try {
                if (dashedLayer.value._map) {
                    dashedLayer.value._map.removeLayer(dashedLayer.value);
                }
            } catch (_) {}
            dashedLayer.value = null;
        }

        junctionMarkers.value.forEach((marker) => {
            try {
                if (marker._map) {
                    marker._map.removeLayer(marker);
                }
            } catch (_) {}
        });
        junctionMarkers.value = [];
    };

    const drawPathOnMap = (routeDetails) => {
        if (!map.value || !routeDetails || routeDetails.length === 0) return;

        clearPathLayers();

        const coordinates = routeDetails.map((detail) => [
            detail.latitude,
            detail.longitude,
        ]);

        pathLayer.value = L.polyline(coordinates, {
            color: "red",
            weight: 4,
            opacity: 0.7,
        }).addTo(map.value);

        const markersToShow = [
            ...routeDetails.filter((detail) => detail.junctionId === 1),
            routeDetails.find(
                (detail) => detail.junctionId === 3 || detail.junctionId === 89,
            ),
        ].filter(Boolean);

        markersToShow.forEach((detail) => {
            const marker = L.marker([detail.latitude, detail.longitude])
                .bindPopup(`Junction ${detail.junctionNumber} (${detail.type})`)
                .addTo(map.value);

            marker.on("click", () => {
                window.openJunctionLandMarkModal(
                    detail.junctionId,
                    detail.junctionNumber,
                    detail.type,
                );
            });

            junctionMarkers.value.push(marker);
        });
    };

    const drawDashedLineToLot = (nearestJunction, targetLat, targetLng) => {
        if (!map.value) return;

        const junctionCoords = JSON.parse(nearestJunction.coordinates);
        const [jLng, jLat] = junctionCoords.coordinates;

        dashedLayer.value = L.polyline(
            [[jLat, jLng], [targetLat, targetLng]],
            { color: "orange", weight: 3, opacity: 0.7, dashArray: "10, 10" },
        ).addTo(map.value);

        const marker = L.marker([targetLat, targetLng])
            .bindPopup(`Target Lot<br>Nearest Junction: ${nearestJunction.junction_number}`)
            .addTo(map.value);

        junctionMarkers.value.push(marker);
    };

    const drawPathToLot = async (lotCoordinates) => {
        const [lng, lat] = lotCoordinates;

        if (junctions.value.length === 0 && !loading.value) {
            await fetchNavigationData();
        }

        if (loading.value) {
            await new Promise((resolve) => {
                const check = setInterval(() => {
                    if (!loading.value) {
                        clearInterval(check);
                        resolve();
                    }
                }, 100);
            });
        }

        if (junctions.value.length === 0) {
            console.error("Failed to load navigation data");
            return;
        }

        const plotRoute = findRouteToPlot({ longitude: lng, latitude: lat, id: "target-lot" });

        if (plotRoute.success) {
            drawPathOnMap(plotRoute.details);
        } else {
            const nearestJunction = findNearestJunction(lat, lng);
            if (!nearestJunction) return;

            const entrance = getEntranceJunction();
            if (!entrance) return;

            const pathToNearest = findShortestPath(entrance.id, nearestJunction.id);
            if (pathToNearest.success) {
                drawPathOnMap(pathToNearest.details);
                drawDashedLineToLot(nearestJunction, lat, lng);
            }
        }
    };

    return {
        drawPathToLot,
        drawPathOnMap,
        drawDashedLineToLot,
        clearPathLayers,
    };
}
