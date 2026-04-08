import { useMapStates } from "@/stores/useMapStates";
import { geoJson } from "leaflet";
import { join } from "lodash";
import axios from "axios";
import { route } from "ziggy-js";

export function useFeatureProcessing() {
    const {
        map,
        phaseLayerGroup,
        clusterLayers,
        clusterVisibility,
        uniqueTypes,
        toggleMapFeaturesState,
    } = useMapStates();

    /* traverse through DBGeoJson data and change 'multipolygon'
   to 'polygon' */
    const processFeatures = (data, type = "cluster") => {
        // console.log("Raw data:", data);

        if (!Array.isArray(data)) {
            console.warn(`${type}s is not an array:`, data);
            return [];
        }

        // Transform data to features
        const allFeatures = [];

        data.forEach((item) => {
            // console.log("Processing item:", item);

            let feature;

            if (type === "cluster") {
                const cluster = item.cluster;
                const lots = item.lots || [];

                // console.log("Cluster:", cluster, "Lots count:", lots.length);

                // Create a feature for the cluster with lots attached
                feature = {
                    // first three are for validation
                    type: cluster.type,
                    geometry: cluster.geometry,
                    properties: cluster.properties,
                    // these two are for the data for the UI and components
                    cluster: cluster,
                    lots: lots,
                };
            } else if (type === "phase") {
                const phase = item.phase;

                // Create a feature for the phase
                feature = {
                    // first three are for validation
                    type: phase.type,
                    geometry: phase.geometry,
                    // this is for the data for the UI and components
                    properties: phase.properties,
                    id: item.id,
                };
            }

            if (feature) {
                allFeatures.push(feature);
            }
        });

        // console.log("Total features extracted:", allFeatures.length);

        return allFeatures
            .filter((feature) => {
                const hasGeom =
                    feature?.geometry?.type &&
                    Array.isArray(feature.geometry.coordinates);
                if (!hasGeom)
                    console.warn(`Skipping invalid ${type}:`, feature);
                return hasGeom;
            })
            .map((feature) => {
                const { geometry } = feature;
                if (geometry.type === "MultiPolygon") {
                    const coords = geometry.coordinates?.[0];
                    if (coords)
                        feature.geometry = {
                            type: "Polygon",
                            coordinates: coords,
                        };
                }
                return feature;
            })
            .filter(validateFeature);
    };

    // Validates if the geometry coordinates are proper numbers
    const validateFeature = (feature) => {
        const coords = feature?.geometry?.coordinates;
        const type = feature?.geometry?.type;

        if (!coords || !type) return false;

        if (type === "Polygon") {
            if (!Array.isArray(coords) || !Array.isArray(coords[0]))
                return false;

            return coords[0].every((coord) => {
                if (!Array.isArray(coord) || coord.length < 2) return false;
                const [lng, lat] = coord;
                return (
                    typeof lng === "number" &&
                    typeof lat === "number" &&
                    !isNaN(lng) &&
                    !isNaN(lat) &&
                    Math.abs(lat) <= 90 &&
                    Math.abs(lng) <= 180
                );
            });
        }

        // Add more geometry types here if needed (MultiLineString, etc.)
        return true;
    };

    /**
     * @param features receives the processed features (lots with cluster and burial data)
     */
    const separateClustersByType = (features) => {
        const types = [
            ...new Set(
                features.map((feature) => feature.cluster.properties.type)
            ),
        ];

        // separate the types into their own L.layerGroup() and store it in the clusterLayers hashMap
        types.forEach((type) => {
            // Reuse existing layerGroup or create new one
            if (!clusterLayers.value.has(type)) {
                clusterLayers.value.set(type, L.layerGroup());
                // only set the visibility to true again if it doesn't exist yet
                if (!clusterVisibility.value.has(type)) {
                    clusterVisibility.value.set(type, true);
                }
            } else {
                // Clear old features but keep the layerGroup reference
                clusterLayers.value.get(type).clearLayers();
            }
        });

        types.forEach((type) => {
            // holds bunch of same type lots
            const typeFeatures = features.filter(
                (f) => f.cluster.properties.type === type
            );

            // apply style to those same type lots
            const geoJsonLayer = L.geoJSON(
                { type: "FeatureCollection", features: typeFeatures },
                {
                    style: getLotStyle,
                    onEachFeature: onEachFeatureCustom,
                }
            );

            // since we set clusterLayers hash map values to L.layerGroup() above, we are just accessing
            // that L.layerGroup here
            const layerGroup = clusterLayers.value.get(type);
            // console.log(`layer group`, layerGroup);
            // console.log(`geoJson layer`, geoJsonLayer);
            geoJsonLayer.addTo(layerGroup);

            // ✅ Add directly to map if visible
            if (
                clusterVisibility.value.get(type) &&
                !map.value.hasLayer(layerGroup)
            ) {
                layerGroup.addTo(map.value);
            }
        });

        // ✅ Update uniqueTypes AFTER layers are built
        uniqueTypes.value = types;

        // Updates the visibility
        toggleMapFeaturesState.value = Array.from(
            clusterVisibility.value.values()
        ).some((v) => v);
    };

    /**
     * @param features receives the processed phase features
     * Description: Simpler version for phases - just applies style and renders
     */
    const renderPhases = (features) => {
        // Create a single layer group for all phases
        phaseLayerGroup.value = L.layerGroup();

        // Apply style to phases
        const geoJsonLayer = L.geoJSON(
            { type: "FeatureCollection", features: features },
            {
                style: getPhaseStyle,
                onEachFeature: onEachPhaseFeature,
            }
        );

        geoJsonLayer.addTo(phaseLayerGroup.value);
        phaseLayerGroup.value.addTo(map.value);
    };

    /**
     * Description: Style for phase polygons
     */
    const getPhaseStyle = (feature) => {
        return {
            fillColor: "#3B82F6",
            weight: 2,
            color: "#1E40AF",
            fillOpacity: 0.3,
        };
    };

    /**
     * Description: Adds visual label for each phase polygon
     */
    const onEachPhaseFeature = (feature, layer) => {
        // Get the center of the polygon
        const center = layer.getBounds().getCenter();

        // Create a div icon with the phase number using Tailwind
        const phaseNumber = feature.properties.phase_name;

        const numberIcon = L.divIcon({
            className: "phase-number-label",
            html: `<div class="
            border-2 border-blue-900 
            rounded-full 
            w-12 h-12 
            flex items-center justify-center 
            font-bold 
            text-white 
            text-center
            text-base
            shadow-md
        ">${phaseNumber}</div>`,
            iconSize: [40, 40],
            iconAnchor: [20, 20],
        });

        // Add marker to the phase layer group
        const marker = L.marker(center, { icon: numberIcon });
        marker.addTo(phaseLayerGroup.value);

        // Click handler for the polygon
        layer.on("click", function () {
            // console.log("Phase clicked:", feature);
        });
    };

    const clearLayers = () => {
        uniqueTypes.value.forEach((type) => {
            const layer = clusterLayers.value.get(type);
            if (layer) {
                if (map.value?.hasLayer(layer)) {
                    map.value.removeLayer(layer);
                }
                layer.clearLayers();
            }
        });

        clusterLayers.value.clear();
        uniqueTypes.value = []; // ✅ Only cleared here, not mid-rebuild
    };

    const onEachFeatureCustom = (feature, layer) => {
        attachLotPopup(feature, layer);
    };

    /**
     * @param feature the actual rendered polygon (cluster)
     * Description: responsible for cluster styling
     */
    const getLotStyle = (feature) => {
        const colors = {
            available: "#90EE90",
            occupied: "#FFB6C6",
            partial: "#FFE66D",
        };

        return {
            fillColor: colors[feature.properties.status] || "#CCCCCC",
            weight: 1,
            color: "black",
            fillOpacity: 0.5,
        };
    };

    /**
     * @param feature is the actual cluster rendered as polygon
     * @param layer
     * Description: attach modal as popUp; the modal will fetch burial data based on the clicked cluster's cluster_id
     */
    const attachLotPopup = (feature, layer) => {
        layer.on("click", function () {
            const clusterId = feature.cluster.properties.cluster_id;
            window.openBurialRecordModal(clusterId, layer._leaflet_id);
        });
    };

    return {
        processFeatures,
        separateClustersByType,
        renderPhases,
        clearLayers,
    };
}
