import { ref } from "vue";
import L from "leaflet";

const search = ref(""); // search input either first or last name
const suggestions = ref([]);
const loading = ref(false);
const isOnSearchMode = ref(false); // use so that when searching only that polygon will appear

const searchResultLayer = ref(L.layerGroup());

export function useMapSearchStates() {
    return {
        search,
        suggestions,
        loading,
        isOnSearchMode,

        searchResultLayer,
    };
}
