import { computed, ref } from "vue";
import L from "leaflet";

const map = ref(null);
const googleLayer = ref(null);

const dbGeoJsonLots = ref([]); // holds the processes features data (before lotLayers hashmap)
const lotLayers = ref(new Map()); // each type is divided into their own HashMap and this variable holds them all
const lotVisibility = ref(new Map());
const uniqueTypes = ref([]);

/**
 * Description: Is the computed state of lotVisibility primarily used for the EYE SVG,
 *              If all the lots are hidden, then it should be false, otherwise true,
 *              The assigning code can be seen on useMap and useFeatureProcessing.
 * @param ref(true) means that the feature/polygon should be visible.
 * @param ref(false) means that the feature/polygon should be hidden.
 */
const toggleMapFeaturesState = ref(true);

export function useMapStates() {
    return {
        map,
        googleLayer,

        dbGeoJsonLots,
        lotLayers,
        lotVisibility,
        uniqueTypes,

        toggleMapFeaturesState,
    };
}
