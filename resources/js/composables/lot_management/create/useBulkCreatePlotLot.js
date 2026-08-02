import L from "leaflet";
import "leaflet-draw";
import { ref, computed } from "vue";

const LAT = 14.3052681;
const LONG = 120.9758;
const ZOOM_LVL = 18;

export function useBulkCreatePlotLot({ row, startColumn, endColumn }) {
    const map = ref(null);
    const drawnItems = ref(null);
    const drawControl = ref(null);
    const clusterLayer = ref(null);
    const lots = ref([]);
    const currentIndex = ref(0);
    const markerLayers = new Map();

    const currentLot = computed(() => lots.value[currentIndex.value] || null);
    const isComplete = computed(
        () =>
            lots.value.length > 0 &&
            lots.value.every((lot) => lot.coordinates !== null),
    );
    const canSave = computed(() => isComplete.value);

    const generateLots = (row, startColumn, endColumn) => {
        const list = [];
        const start = parseInt(startColumn, 10);
        const end = parseInt(endColumn, 10);

        if (Number.isNaN(start) || Number.isNaN(end) || start > end) return [];

        for (let col = start; col <= end; col++) {
            list.push({
                column: String(col),
                row: String(row),
                coordinates: null,
            });
        }

        return list;
    };

    const setNextIndex = () => {
        const index = lots.value.findIndex((lot) => !lot.coordinates);
        currentIndex.value = index === -1 ? lots.value.length : index;
    };

    const addMarker = (lot, index) => {
        if (!map.value || !lot.coordinates) return;

        const [lng, lat] = lot.coordinates.coordinates;
        const marker = L.marker([lat, lng]).bindTooltip(
            `${lot.column}${lot.row}`,
        );

        drawnItems.value.addLayer(marker);
        markerLayers.set(index, marker);
    };

    const removeMarker = (index) => {
        const marker = markerLayers.get(index);

        if (marker && drawnItems.value) {
            drawnItems.value.removeLayer(marker);
        }

        markerLayers.delete(index);
    };

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
            edit: false,
        });

        map.value.addControl(drawControl.value);

        // Event: when user places a marker for the active lot
        map.value.on(L.Draw.Event.CREATED, (e) => {
            const layer = e.layer;
            const latlng = layer.getLatLng();

            plotCurrentLot(
                {
                    type: "Point",
                    coordinates: [latlng.lng, latlng.lat],
                },
                layer,
            );
        });
    };

    const loadCluster = (clusterId, phases) => {
        if (!map.value || !clusterId) return;

        // Remove previous cluster boundary to avoid duplicates
        if (clusterLayer.value) {
            map.value.removeLayer(clusterLayer.value);
            clusterLayer.value = null;
        }

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

                // Create GeoJSON feature
                const feature = {
                    type: "Feature",
                    geometry: geojson,
                    properties: {},
                };

                // Add cluster boundary
                clusterLayer.value = L.geoJSON(feature, {
                    style: {
                        color: "#3b82f6",
                        fillColor: "#3b82f6",
                        fillOpacity: 0.2,
                        weight: 2,
                    },
                }).addTo(map.value);

                // Fit map to cluster bounds
                const bounds = clusterLayer.value.getBounds();
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

    const plotCurrentLot = (coords, layer = null) => {
        if (!coords || isComplete.value) return;

        const lot = lots.value[currentIndex.value];
        if (!lot) return;

        lot.coordinates = coords;

        if (layer) {
            drawnItems.value.addLayer(layer);
            markerLayers.set(currentIndex.value, layer);
        } else {
            addMarker(lot, currentIndex.value);
        }

        setNextIndex();
    };

    const removeLot = (index) => {
        const lot = lots.value[index];
        if (!lot) return;

        lot.coordinates = null;
        removeMarker(index);
        setNextIndex();
    };

    const reset = (row, startColumn, endColumn) => {
        markerLayers.clear();

        if (drawnItems.value) {
            drawnItems.value.clearLayers();
        }

        lots.value = generateLots(row, startColumn, endColumn);
        currentIndex.value = 0;
    };

    const cleanupMap = () => {
        markerLayers.clear();

        if (map.value) {
            map.value.remove();
            map.value = null;
        }
    };

    // Generate the lot list on creation
    reset(row, startColumn, endColumn);

    return {
        lots,
        currentIndex,
        currentLot,
        isComplete,
        canSave,
        initializeMap,
        loadCluster,
        plotCurrentLot,
        removeLot,
        reset,
        cleanupMap,
    };
}
