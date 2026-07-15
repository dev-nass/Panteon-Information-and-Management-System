import { ref } from "vue";
import L from "leaflet";

const search = ref("");
const suggestions = ref([]);
const loading = ref(false);
const isOnSearchMode = ref(false);

// null by default — created fresh each time initializeMap runs
const searchResultLayer = ref(null);

export function useMapSearchStates() {
    return {
        search,
        suggestions,
        loading,
        isOnSearchMode,
        searchResultLayer,
    };
}
