import { ref } from "vue";
import L from "leaflet";

const search = ref("");
const suggestions = ref([]);
const loading = ref(false);

const searchResultLayer = ref(L.layerGroup());

export function useMapSearchStates() {
    return {
        search,
        suggestions,
        loading,

        searchResultLayer,
    };
}
