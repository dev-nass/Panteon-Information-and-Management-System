import L from "leaflet";
import { useClerkMapStates } from "@/stores/useCLerkMapStates";

const { map, googleLayer } = useClerkMapStates();

// Panteon Long and Lat
const LAT = 14.3052681;
const LONG = 120.9758;
const ZOOM_LVL = 18;

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
        // lotsUndergroundLayer.value = L.layerGroup();
        // lotsApartmentLayer.value = L.layerGroup();
    };

    return {
        initializeMap,
        cleanupMap,
    };
}
