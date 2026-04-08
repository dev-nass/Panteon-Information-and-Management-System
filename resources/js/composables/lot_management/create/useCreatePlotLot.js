import L from "leaflet";
import "leaflet-draw";
import { ref } from "vue";

const LAT = 14.3052681;
const LONG = 120.9758;
const ZOOM_LVL = 18;

export function useCreatePlotLot() {
    const map = ref(null);
    const drawnItems = ref(null);
    const drawControl = ref(null);
    const coordinates = ref(null);

    const initializeMap = (mapContainerElem) => {
        map.value = L.map(mapContainerElem).setView([LAT, LONG], ZOOM_LVL);

        // Google Maps tile layer
        L.tileLayer("http://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}", {
            maxZoom: 22,
            subdomains: ["mt0", "mt1", "mt2", "mt3"],
            attribution: "&copy; Google Maps",
        }).addTo(map.value);

        drawnItems.value = new L.FeatureGroup();
        map.value.addLayer(drawnItems.value);

        drawControl.value = new L.Control.Draw({
            draw: {
                polygon: false,
                rectangle: false,
                circle: false,
                marker: true,
                polyline: false,
                circlemarker: false,
            },
            edit: {
                featureGroup: drawnItems.value,
                remove: true,
            },
        });

        map.value.addControl(drawControl.value);

        // Event: when user draws
        map.value.on(L.Draw.Event.CREATED, function (e) {
            const layer = e.layer;

            // Clear previous drawings
            drawnItems.value.clearLayers();

            // Add new layer
            drawnItems.value.addLayer(layer);

            const latlng = layer.getLatLng();

            // Store coordinates in GeoJSON format
            coordinates.value = {
                type: "Point",
                coordinates: [latlng.lng, latlng.lat],
            };
        });
    };

    const loadCluster = (clusterId, phases) => {
        if (!map.value || !clusterId) return;

        // Find cluster from phases
        let cluster = null;
        for (const phase of phases) {
            cluster = phase.clusters?.find((c) => c.id == clusterId);
            if (cluster) break;
        }

        if (cluster && cluster.coordinates) {
            try {
                let geojson = cluster.coordinates;

                // Parse if string
                if (typeof geojson === "string") {
                    geojson = JSON.parse(geojson);
                }

                console.log("Cluster coordinates:", geojson);

                // Create GeoJSON feature
                const feature = {
                    type: "Feature",
                    geometry: geojson,
                    properties: {},
                };

                // Add cluster boundary
                const clusterLayer = L.geoJSON(feature, {
                    style: {
                        color: "#3b82f6",
                        fillColor: "#3b82f6",
                        fillOpacity: 0.2,
                        weight: 2,
                    },
                }).addTo(map.value);

                console.log("Cluster layer added:", clusterLayer);

                // Fit map to cluster bounds
                const bounds = clusterLayer.getBounds();
                if (bounds.isValid()) {
                    map.value.fitBounds(bounds, { padding: [50, 50] });
                }
            } catch (error) {
                console.error("Error loading cluster:", error);
            }
        } else {
            console.warn("Cluster not found or has no coordinates:", clusterId);
        }
    };

    const cleanupMap = () => {
        if (map.value) {
            map.value.remove();
            map.value = null;
        }
        coordinates.value = null;
    };

    const getCoordinates = () => {
        return coordinates.value;
    };

    const clearCoordinates = () => {
        if (drawnItems.value) {
            drawnItems.value.clearLayers();
        }
        coordinates.value = null;
    };

    return {
        map,
        coordinates,
        initializeMap,
        loadCluster,
        cleanupMap,
        getCoordinates,
        clearCoordinates,
    };
}
