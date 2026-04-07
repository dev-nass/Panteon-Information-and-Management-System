import L from "leaflet";
import "leaflet-draw";
import { ref } from "vue";

const LAT = 14.3052681;
const LONG = 120.9758;
const ZOOM_LVL = 18;

export function useCreatePlotCluster() {
    const map = ref(null);
    const drawnItems = ref(null);
    const drawControl = ref(null);
    const coordinates = ref(null);

    const initializeMap = (mapContainerElem) => {
        map.value = L.map(mapContainerElem).setView([LAT, LONG], ZOOM_LVL);

        L.tileLayer("http://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}", {
            maxZoom: 22,
            subdomains: ["mt0", "mt1", "mt2", "mt3"],
            attribution: "&copy; Google Maps",
        }).addTo(map.value);

        drawnItems.value = new L.FeatureGroup();
        map.value.addLayer(drawnItems.value);

        drawControl.value = new L.Control.Draw({
            draw: {
                polygon: true,
                rectangle: false,
                circle: false,
                marker: false,
                polyline: false,
                circlemarker: false,
            },
            edit: {
                featureGroup: drawnItems.value,
                remove: true,
            },
        });

        map.value.addControl(drawControl.value);

        map.value.on(L.Draw.Event.CREATED, function (e) {
            const layer = e.layer;

            drawnItems.value.clearLayers();
            drawnItems.value.addLayer(layer);

            const latlngs = layer.getLatLngs()[0];
            const coords = latlngs.map((latlng) => [latlng.lng, latlng.lat]);
            coords.push(coords[0]); // Close the polygon

            coordinates.value = {
                type: "Polygon",
                coordinates: [coords],
            };
        });
    };

    const loadPhase = (phaseId, phases) => {
        if (!map.value || !phaseId) return;

        const phase = phases.find((p) => p.id == phaseId);

        if (phase && phase.coordinates) {
            try {
                let geojson = phase.coordinates;

                if (typeof geojson === "string") {
                    geojson = JSON.parse(geojson);
                }

                const feature = {
                    type: "Feature",
                    geometry: geojson,
                    properties: {},
                };

                const phaseLayer = L.geoJSON(feature, {
                    style: {
                        color: "#3b82f6",
                        fillColor: "#3b82f6",
                        fillOpacity: 0.2,
                        weight: 2,
                    },
                }).addTo(map.value);

                const bounds = phaseLayer.getBounds();
                if (bounds.isValid()) {
                    map.value.fitBounds(bounds, { padding: [50, 50] });
                }
            } catch (error) {
                console.error("Error loading phase:", error);
            }
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
        loadPhase,
        cleanupMap,
        getCoordinates,
        clearCoordinates,
    };
}
