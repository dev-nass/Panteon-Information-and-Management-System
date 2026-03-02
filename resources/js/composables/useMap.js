import L from "leaflet";
import { useMapStates } from "@/stores/useMapStates";

const { map, googleLayer, lotLayers, uniqueTypes } = useMapStates();

// Panteon Long and Lat
const LAT = 14.3052681;
const LONG = 120.9758;
const ZOOM_LVL = 18;
const MIN_RENDER_ZOOM = 20;
const RENDER_DEBOUNCE_MS = 2000;

export function useMap() {
    const initializeMap = (mapContainerElem) => {
        map.value = L.map(mapContainerElem).setView([LAT, LONG], ZOOM_LVL);
        map.value.zoomControl.remove();
        L.control
            .zoom({
                position: "bottomleft",
            })
            .addTo(map.value);

        initializeLayers();
    };

    const initializeLayers = () => {
        if (!googleLayer.value) {
            googleLayer.value = L.tileLayer(
                "https://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}",
                {
                    maxZoom: 30,
                    subdomains: ["mt0", "mt1", "mt2", "mt3"],
                },
            );

            googleLayer.value.addTo(map.value);
        }
    };

    // properly destroys the map each render; used in View
    const cleanupMap = () => {
        if (map.value) {
            map.value.remove(); // Properly destroys map and removes all listeners
            map.value = null;
        }

        // Clear layer references
        googleLayer.value = null;
        // entranceLayer.value = L.layerGroup();
    };

    // used if the zoom is too far
    const cleanupLayers = () => {
        // map.value.removeLayer(sectionLayer.value);
        uniqueTypes.value.forEach((type) => {
            map.value.removeLayer(lotLayers.value.get(type));
        });
    };

    const updateVisibility = () => {
        if (!map.value) return;

        const zoom = map.value.getZoom();

        // too far
        if (zoom < MIN_RENDER_ZOOM) {
            // map.value.removeLayer(lotsUndergroundLayer.value);
            // map.value.removeLayer(lotsApartmentLayer.value);
            cleanupLayers();

            console.log("Lots hidden (zoom too far)");
        }
        // right zoom
        else {
            uniqueTypes.value.forEach((type) => {
                if (lotVisibility.value.get(type) === true) {
                    lotLayers.value.get(type).addTo(map.value);
                } else {
                    map.value.removeLayer(lotLayers.value.get(type));
                }
            });
        }
    };

    // continuously calls the updateVisibility
    setInterval(() => {
        console.log("updating...");
        updateVisibility();
    }, RENDER_DEBOUNCE_MS);

    return {
        initializeMap,
        cleanupMap,
    };
}
