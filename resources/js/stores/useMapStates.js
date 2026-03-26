import { computed, ref } from "vue";
import L from "leaflet";

const map = ref(null);
const googleLayer = ref(null);

const dbGeoJsonPhases = ref([]);
const phaseLayerGroup = ref(null);
const phaseVisibility = ref(false);

const dbGeoJsonClusters = ref([]); // holds the processes features data (before clusterLayers hashmap)
const clusterLayers = ref(new Map()); // each type is divided into their own HashMap and this variable holds them all
const clusterVisibility = ref(new Map());
const uniqueTypes = ref([]);

/**
 * Description: Is the computed state of clusterVisibility primarily used for the EYE SVG,
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

        dbGeoJsonPhases,
        phaseLayerGroup,
        phaseVisibility,

        dbGeoJsonClusters,
        clusterLayers,
        clusterVisibility,
        uniqueTypes,

        toggleMapFeaturesState,
    };
}
