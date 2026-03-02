import { useMapStates } from "@/stores/useMapStates";

const { lotLayers, uniqueTypes } = useMapStates();

export function useFeatureProcessing() {
    /* traverse through DBGeoJson data and change 'multipolygon'
   to 'polygon' */
    const processFeatures = (data) => {
        if (!data || !Array.isArray(data.features)) {
            console.warn("Invalid GeoJSON data structure");
            return [];
        }

        return (
            data.features
                // keep only valid geometries
                .filter((feature) => {
                    const isValid =
                        feature && feature.geometry && feature.geometry.type;

                    if (!isValid) {
                        console.warn(
                            "Skipping feature with null/missing geometry",
                            feature,
                        );
                    }

                    return isValid; // ✅ KEEP valid ones
                })
                // normalize MultiPolygon → Polygon
                .map((feature) => {
                    if (feature.geometry.type === "MultiPolygon") {
                        const coords = feature.geometry.coordinates?.[0];
                        if (coords) {
                            feature.geometry = {
                                type: "Polygon",
                                coordinates: coords,
                            };
                        }
                    }
                    return feature;
                })
                .filter(validateFeature)
        );
    };

    // validate if the coordinates is valid
    const validateFeature = (feature) => {
        if (!feature.geometry?.coordinates) return false;

        const coords = feature.geometry.coordinates;

        if (feature.geometry.type === "Polygon") {
            if (!Array.isArray(coords) || !coords[0]) return false;

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

        return true;
    };

    const separateLotsByType = (features) => {
        uniqueTypes.value = [
            ...new Set(features.map((feature) => feature.properties.lot_type)),
        ];

        uniqueTypes.value.forEach((type) => {
            // console.log(type);
            lotLayers.value.set(type, L.layerGroup());
            lotVisibility.value.set(type, false);
        });

        features.forEach((feature) => {
            if (!feature.properties?.lot_type) {
                console.warn("Feature missing lot type:", feature);
                return;
            }

            const type = feature.properties.lot_type;

            const lot = L.geoJSON(feature, {
                style: getLotStyle,
                onEachFeature: onEachFeatureCustom,
            });

            // add the lot to its respective type group on the hash map
            lot.addTo(lotLayers.value.get(type));
        });
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

    return {
        processFeatures,
        validateFeature,
    };
}
