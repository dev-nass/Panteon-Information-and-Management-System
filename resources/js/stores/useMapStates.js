import { computed, ref } from "vue";
import L from "leaflet";

export const MIN_RENDER_ZOOM_DESKTOP = 20;
export const MIN_RENDER_ZOOM_MOBILE = 19;

/**
 * Returns the minimum zoom at which clusters should render.
 * Mobile (L.Browser.mobile OR viewport <640px) uses 19 so pinch/double-tap
 * on small screens reaches clusters sooner; desktop keeps 20.
 */
export function getMinRenderZoom() {
    if (typeof window !== "undefined") {
        const isMobileViewport = window.innerWidth < 640;
        const isMobileBrowser = !!(L && L.Browser && L.Browser.mobile);
        if (isMobileViewport || isMobileBrowser) {
            return MIN_RENDER_ZOOM_MOBILE;
        }
    }
    return MIN_RENDER_ZOOM_DESKTOP;
}

const map = ref(null);
const googleLayer = ref(null);

const dbGeoJsonPhases = ref([]);
const phaseLayerGroup = ref(null);
const phaseVisibility = ref(true);

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

/**
 * Description: Is use to make the map more versatile. With this state the map can be use
 *              for Clerk/BurialRecord and Clerk/LotManagement
 */
const mode = ref("view"); // view | manage
const context = ref("burial"); // burial | phase | cluster | lot

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

        mode,
        context,
    };
}
