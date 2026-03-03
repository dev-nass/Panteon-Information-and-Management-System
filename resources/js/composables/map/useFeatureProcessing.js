import { useMapStates } from "@/stores/useMapStates";

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

    const separateLotsByType = (features) => {
        uniqueTypes.value = [
            ...new Set(features.map((feature) => feature.properties.lot_type)),
        ];

        uniqueTypes.value.forEach((type) => {
            // console.log(type);
            lotLayers.value.set(type, L.layerGroup());
            lotVisibility.value.set(type, true);
        });

        // ✅ Batch all features of a type into ONE L.geoJSON call
        uniqueTypes.value.forEach((type) => {
            const typeFeatures = features.filter(
                (f) => f.properties.lot_type === type,
            );

            const layer = L.geoJSON(
                { type: "FeatureCollection", features: typeFeatures },
                {
                    style: getLotStyle,
                    // onEachFeature: onEachFeatureCustom,
                },
            );

            layer.addTo(lotLayers.value.get(type));
        });
    };

    const clearLayers = () => {
        uniqueTypes.value.forEach((type) => {
            const layer = lotLayers.value.get(type);
            if (layer) {
                map.value.removeLayer(layer);
                layer.clearLayers();
            }
        });
        lotLayers.value.clear();
        uniqueTypes.value = [];
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
        clearLayers,
    };
}
