import { watch } from "vue";
import { useMapStates } from "@/stores/useMapStates";
import { pathFinder } from "@/composables/map/pathfinder/usePathProcessor";

export function useDrawProcessedPath() {
    const { map, isOnSearchMode } = useMapStates();
    const {
        findRouteToPlot,
        findNearestJunction,
        getEntranceJunction,
        findShortestPath,
        fetchNavigationData,
        junctions,
        loading,
    } = pathFinder();

    const drawPathToLot = async (lotCoordinates) => {
        // lotCoordinates should be [lng, lat] from the lot's Point geometry
        const [lng, lat] = lotCoordinates;

        console.log("Drawing path to lot:", {
            lat: lat,
            lng: lng,
        });

        // Ensure navigation data is loaded
        if (junctions.value.length === 0 && !loading.value) {
            console.log("Loading navigation data...");
            await fetchNavigationData();
        }

        // Wait for loading to complete if in progress
        if (loading.value) {
            console.log("Waiting for navigation data to load...");
            // Wait for loading to finish
            await new Promise((resolve) => {
                const checkLoading = setInterval(() => {
                    if (!loading.value) {
                        clearInterval(checkLoading);
                        resolve();
                    }
                }, 100);
            });
        }

        if (junctions.value.length === 0) {
            console.error("Failed to load navigation data");
            return;
        }

        const targetPlot = {
            longitude: lng,
            latitude: lat,
            id: "target-lot",
        };

        const plotRoute = findRouteToPlot(targetPlot);
        console.log(plotRoute);

        if (plotRoute.success) {
            console.log("Route to lot found!", {
                path: plotRoute.path,
                distance: plotRoute.totalDistance,
                details: plotRoute.details,
            });

            drawPathOnMap(plotRoute.details);
        } else {
            console.log(
                "No direct route found to lot, finding path to nearest junction",
            );

            // Find nearest junction to the lot
            const nearestJunction = findNearestJunction(lat, lng);

            if (!nearestJunction) {
                console.error("No junctions available");
                return;
            }

            console.log("Nearest junction found:", nearestJunction);

            // Get entrance junction
            const entrance = getEntranceJunction();

            if (!entrance) {
                console.error("No entrance junction found");
                return;
            }

            // Find path from entrance to nearest junction
            const pathToNearestJunction = findShortestPath(
                entrance.id,
                nearestJunction.id,
            );

            if (pathToNearestJunction.success) {
                console.log(
                    "Path to nearest junction found!",
                    pathToNearestJunction,
                );
                drawPathOnMap(pathToNearestJunction.details);

                // Draw dashed line from nearest junction to lot
                drawDashedLineToLot(nearestJunction, lat, lng);
            } else {
                console.error("No path found to nearest junction");
            }
        }
    };

    // Draw dashed line from nearest junction to the lot
    const drawDashedLineToLot = (nearestJunction, targetLat, targetLng) => {
        if (!map.value) return;

        // Parse junction coordinates from GeoJSON
        const junctionCoords = JSON.parse(nearestJunction.coordinates);
        const [jLng, jLat] = junctionCoords.coordinates;

        const coordinates = [
            [jLat, jLng],
            [targetLat, targetLng],
        ];

        // Create dashed line layer
        if (window.dashedLineLayer) {
            map.value.removeLayer(window.dashedLineLayer);
        }

        window.dashedLineLayer = L.polyline(coordinates, {
            color: "orange",
            weight: 3,
            opacity: 0.7,
            dashArray: "10, 10",
        }).addTo(map.value);

        // Add marker for target lot and track it
        if (!window.junctionMarkers) {
            window.junctionMarkers = [];
        }
        
        const marker = L.marker([targetLat, targetLng])
            .bindPopup(
                `Target Lot<br>Nearest Junction: ${nearestJunction.junction_number}`,
            )
            .addTo(map.value);
        
        window.junctionMarkers.push(marker);
    };

    // Draw path on map (from Entrance junction/edge to end/destination junction)
    const drawPathOnMap = (routeDetails) => {
        if (!map.value || !routeDetails || routeDetails.length === 0) return;

        // Clear previous path layers
        if (window.testPathLayer) {
            map.value.removeLayer(window.testPathLayer);
        }

        const coordinates = routeDetails.map((detail) => [
            detail.latitude,
            detail.longitude,
        ]);

        // Create polyline for the path
        window.testPathLayer = L.polyline(coordinates, {
            color: "red",
            weight: 4,
            opacity: 0.7,
        }).addTo(map.value);

        // Clear previous junction markers
        if (window.junctionMarkers) {
            window.junctionMarkers.forEach(marker => map.value.removeLayer(marker));
        }
        window.junctionMarkers = [];

        // Add markers for junctions
        const markersToShow = [
            ...routeDetails.filter((detail) => detail.junctionId === 1),
            routeDetails.find(
                (detail) => detail.junctionId === 3 || detail.junctionId === 89,
            ),
        ].filter(Boolean); // Remove undefined if no 3 or 89 found

        markersToShow.forEach((detail) => {
            const marker = L.marker([
                detail.latitude,
                detail.longitude,
            ])
                .bindPopup(`Junction ${detail.junctionNumber} (${detail.type})`)
                .addTo(map.value);
            
            window.junctionMarkers.push(marker);
        });

        // Fit map to show the entire path
        if (coordinates.length > 0) {
            map.value.fitBounds(coordinates);
        }
    };

    // Clear all path layers
    const clearPathLayers = () => {
        if (!map.value) return;

        // Remove main path layer
        if (window.testPathLayer) {
            map.value.removeLayer(window.testPathLayer);
            window.testPathLayer = null;
        }

        // Remove dashed line layer
        if (window.dashedLineLayer) {
            map.value.removeLayer(window.dashedLineLayer);
            window.dashedLineLayer = null;
        }

        // Remove junction markers
        if (window.junctionMarkers) {
            window.junctionMarkers.forEach(marker => map.value.removeLayer(marker));
            window.junctionMarkers = [];
        }
    };

    return {
        drawPathToLot,
        drawPathOnMap,
        drawDashedLineToLot,
        clearPathLayers,
    };
}
