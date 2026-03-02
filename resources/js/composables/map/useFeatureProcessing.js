import { useMapStates } from "@/stores/useMapStates";

const { lotLayers, lotVisibility, uniqueTypes } = useMapStates();

export function useFeatureProcessing() {
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

    const separateLotsByType = (features) => {
        uniqueTypes.value = [
            ...new Set(features.map((feature) => feature.properties.lot_type)),
        ];

        uniqueTypes.value.forEach((type) => {
            // console.log(type);
            lotLayers.value.set(type, L.layerGroup());
            lotVisibility.value.set(type, true);
        });

        features.forEach((feature) => {
            if (!feature.properties?.lot_type) {
                console.warn("Feature missing lot type:", feature);
                return;
            }

            const type = feature.properties.lot_type;

            const lot = L.geoJSON(feature, {
                style: getLotStyle,
                // onEachFeature: onEachFeatureCustom,
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
        separateLotsByType,
    };
}
