import L from "leaflet";
import { ref } from "vue";

const LAT = 14.3052681;
const LONG = 120.9758;
const ZOOM_LVL = 18;

export function useVisitorMap() {
    const map = ref(null);
    const googleLayer = ref(null);

    const initializeMap = (mapContainerElem) => {
        map.value = L.map(mapContainerElem).setView([LAT, LONG], ZOOM_LVL);
        map.value.zoomControl.remove();
        L.control.zoom({ position: "bottomleft" }).addTo(map.value);

        googleLayer.value = L.tileLayer(
            "https://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}",
            {
                maxZoom: 30,
                subdomains: ["mt0", "mt1", "mt2", "mt3"],
            }
        );
        googleLayer.value.addTo(map.value);
    };

    const cleanupMap = () => {
        if (map.value) {
            map.value.remove();
            map.value = null;
        }
        googleLayer.value = null;
    };

    return {
        initializeMap,
        cleanupMap,
    };
}
