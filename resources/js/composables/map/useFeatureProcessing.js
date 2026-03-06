import { useMapStates } from "@/stores/useMapStates";
import { geoJson } from "leaflet";
import { join } from "lodash";

export function useFeatureProcessing() {
    const { map, lotLayers, lotVisibility, uniqueTypes } = useMapStates();

    /* traverse through DBGeoJson data and change 'multipolygon'
   to 'polygon' */
    const processFeatures = (lots) => {
        if (!Array.isArray(lots)) return [];

        return lots
            .filter((feature) => {
                const hasGeom =
                    feature?.geometry?.type &&
                    Array.isArray(feature.geometry.coordinates);
                if (!hasGeom) console.warn("Skipping invalid lot:", feature);
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
     * @param features receives the processed features
     */
    const separateLotsByType = (features) => {
        const types = [
            ...new Set(features.map((feature) => feature.properties.lot_type)),
        ];

        types.forEach((type) => {
            // Reuse existing layerGroup or create new one
            if (!lotLayers.value.has(type)) {
                lotLayers.value.set(type, L.layerGroup());
                lotVisibility.value.set(type, true);
            } else {
                // Clear old features but keep the layerGroup reference
                lotLayers.value.get(type).clearLayers();
            }
        });

        types.forEach((type) => {
            // holds bunch of same type lots
            const typeFeatures = features.filter(
                (f) => f.properties.lot_type === type,
            );

            // apply style to those same type lots
            const geoJsonLayer = L.geoJSON(
                { type: "FeatureCollection", features: typeFeatures },
                {
                    style: getLotStyle,
                    onEachFeature: onEachFeatureCustom,
                },
            );

            // since we set lotLayers hash map values to L.layerGroup() above, we are just accessing
            // that L.layerGroup here
            const layerGroup = lotLayers.value.get(type);
            // console.log(`layer group`, layerGroup);
            // console.log(`geoJson layer`, geoJsonLayer);
            geoJsonLayer.addTo(layerGroup);

            // ✅ Add directly to map if visible
            if (
                lotVisibility.value.get(type) &&
                !map.value.hasLayer(layerGroup)
            ) {
                layerGroup.addTo(map.value);
            }
        });

        // ✅ Update uniqueTypes AFTER layers are built
        uniqueTypes.value = types;
    };

    const clearLayers = () => {
        uniqueTypes.value.forEach((type) => {
            const layer = lotLayers.value.get(type);
            if (layer) {
                if (map.value?.hasLayer(layer)) {
                    map.value.removeLayer(layer);
                }
                layer.clearLayers();
            }
        });

        lotLayers.value.clear();
        uniqueTypes.value = []; // ✅ Only cleared here, not mid-rebuild
    };

    const onEachFeatureCustom = (feature, layer) => {
        attachLotPopup(feature, layer);
    };

    // lot styling
    const getLotStyle = (feature) => {
        const colors = {
            available: "#90EE90",
            occupied: "#FFB6C6",
            reserved: "#FFE66D",
        };

        return {
            fillColor: colors[feature.properties.status] || "#CCCCCC",
            weight: 1,
            color: "black",
            fillOpacity: 0.7,
        };
    };

    /**
     * @param feature is the actual lot rendered as polygon hence referred to as 'feature'
     * @param layer
     * Description: attach modal as popUp
     */
    const attachLotPopup = (feature, layer) => {
        console.log(feature);
        if (feature.properties.lot_type === "underground") {
            layer.on("click", function () {
                window.openUndergroundModal(feature, layer._leaflet_id);
            });
        } else {
            layer.on("click", function () {
                window.openApartmentModal(feature, layer._leaflet_id);
            });
        }
    };

    return {
        processFeatures,
        separateLotsByType,
        clearLayers,
    };
}
