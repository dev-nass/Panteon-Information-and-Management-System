import { ref } from "vue";
import L from "leaflet";

const map = ref(null);
const googleLayer = ref(null);

const dbGeoJsonLots = ref([]); // holds the processes features data (before lotLayers hashmap)
const lotLayers = ref(new Map());
const lotVisibility = ref(new Map());
const uniqueTypes = ref([]);

export function useMapStates() {
    return {
        map,
        googleLayer,

        dbGeoJsonLots,
        lotLayers,
        lotVisibility,
        uniqueTypes,
    };
}
