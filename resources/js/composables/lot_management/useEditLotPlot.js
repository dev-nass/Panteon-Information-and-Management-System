import L from "leaflet";
import "leaflet-draw";
import { ref } from "vue";

const LAT = 14.3052681;
const LONG = 120.9758;
const ZOOM_LVL = 18;

export function useEditLotPlot() {
    const map = ref(null);
    const drawnItems = ref(null);
    const drawControl = ref(null);
    const coordinates = ref(null);

    const initializeMap = (mapContainerElem, existingCoordinates = null) => {
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

        map.value.on(L.Draw.Event.CREATED, function (e) {
            const layer = e.layer;

            drawnItems.value.clearLayers();
            drawnItems.value.addLayer(layer);

            const latlng = layer.getLatLng();

            coordinates.value = {
                type: "Point",
                coordinates: [latlng.lng, latlng.lat],
            };
        });

        map.value.on(L.Draw.Event.EDITED, function (e) {
            const layers = e.layers;
            layers.eachLayer(function (layer) {
                const latlng = layer.getLatLng();

                coordinates.value = {
                    type: "Point",
                    coordinates: [latlng.lng, latlng.lat],
                };
            });
        });

        map.value.on(L.Draw.Event.DELETED, function () {
            coordinates.value = null;
        });

        if (existingCoordinates) {
            loadExistingCoordinates(existingCoordinates);
        }
    };

    const loadCluster = (clusterId, phases) => {
        if (!map.value || !clusterId) return;

        let cluster = null;
        for (const phase of phases) {
            cluster = phase.clusters?.find((c) => c.id == clusterId);
            if (cluster) break;
        }

        if (cluster && cluster.coordinates) {
            try {
                let geojson = cluster.coordinates;

                if (typeof geojson === "string") {
                    geojson = JSON.parse(geojson);
                }

                const feature = {
                    type: "Feature",
                    geometry: geojson,
                    properties: {},
                };

                const clusterLayer = L.geoJSON(feature, {
                    style: {
                        color: "#9ca3af",
                        fillColor: "#9ca3af",
                        fillOpacity: 0.1,
                        weight: 2,
                        dashArray: "5, 5",
                    },
                }).addTo(map.value);

                const bounds = clusterLayer.getBounds();
                if (bounds.isValid()) {
                    map.value.fitBounds(bounds, { padding: [50, 50] });
                }
            } catch (error) {
                console.error("Error loading cluster:", error);
            }
        }
    };

    const loadExistingCoordinates = (coords) => {
        if (!map.value || !coords) return;

        try {
            let geojson = coords;

            if (typeof geojson === "string") {
                geojson = JSON.parse(geojson);
            }

            const latlng = L.latLng(geojson.coordinates[1], geojson.coordinates[0]);
            const marker = L.marker(latlng);

            drawnItems.value.addLayer(marker);
            coordinates.value = geojson;

            map.value.setView(latlng, ZOOM_LVL);
        } catch (error) {
            console.error("Error loading existing coordinates:", error);
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
