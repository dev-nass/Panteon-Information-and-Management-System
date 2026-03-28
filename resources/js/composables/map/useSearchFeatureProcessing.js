import { useMapSearchStates } from "@/stores/useMapSearchStates";
import { useMapStates } from "@/stores/useMapStates";

export function useSearchFeatureProcessing() {
    const { map } = useMapStates();
    const { search, suggestions, loading, isOnSearchMode, searchResultLayer } =
        useMapSearchStates();

    const normalizeCoordinates = (coords) => {
        if (!coords || !Array.isArray(coords)) return [];

        // Flatten until we get array of [lng, lat] pairs
        let result = coords;
        while (Array.isArray(result[0]) && !isCoordinatePair(result[0])) {
            result = result[0];
        }

        return result;
    };

    const isCoordinatePair = (item) => {
        return (
            Array.isArray(item) &&
            item.length >= 2 &&
            typeof item[0] === "number" &&
            typeof item[1] === "number"
        );
    };

    /**
     * Description: Use for BurialRecord Search
     * @param clusterData expects a cluster record from ClusterResource
     * @param polygonCoordinate expects GeoJSON coordinates
     */
    const markBurialRecordClusterPolygon = (clusterData, polygonCoordinate) => {
        if (!polygonCoordinate || !polygonCoordinate.length) {
            console.error(
                `Unable to mark cluster polygon, invalid polygon coordinates`,
            );
            return;
        }

        const geoJsonFeature = {
            type: "Feature",
            geometry: {
                type: "Polygon",
                coordinates: [polygonCoordinate],
            },
            properties: {
                ...clusterData.cluster.properties,
            },
            cluster: clusterData.cluster,
            lots: clusterData.lots,
        };

        const geoJsonLayer = L.geoJSON(geoJsonFeature, {
            style: getBurialRecordSearchClusterStyle,
            onEachFeature: attachBurialRecordClusterPopup,
        });

        searchResultLayer.value.addLayer(geoJsonLayer);

        if (!map.value.hasLayer(searchResultLayer.value)) {
            searchResultLayer.value.addTo(map.value);
        }

        map.value.fitBounds(geoJsonLayer.getBounds(), {
            padding: [50, 50],
            maxZoom: 20,
        });
    };

    /**
     * Description: also used for BurialRecord Search
     */
    const getBurialRecordSearchClusterStyle = () => {
        return {
            color: "#ef4444",
            fillColor: "#ef4444",
            fillOpacity: 0.2,
            weight: 3,
        };
    };

    /**
     * Description: Attach popup to the searched result cluster polygon
     * @param feature
     * @param layer
     */
    const attachBurialRecordClusterPopup = (feature, layer) => {
        // console.log(feature);
        layer.on("click", function () {
            window.openLotModal(feature, layer._leaflet_id);
        });
    };

    /**
     * @param lot expects a lot with Point geometry
     */
    const markBurialRecordLotPoint = (lot) => {
        if (!lot.geometry || !lot.geometry.coordinates) {
            console.error(`Unable to mark lot point, invalid coordinates`);
            return;
        }

        // lot.geometry.coordinates = [lng, lat] for Point type
        const [lng, lat] = lot.geometry.coordinates;

        const marker = L.circleMarker([lat, lng], {
            radius: 8,
            fillColor: "#ef4444",
            color: "#fff",
            weight: 2,
            opacity: 1,
            fillOpacity: 0.8,
        });

        searchResultLayer.value.addLayer(marker);
    };

    // ✅ Export helpers so useSearch can consume them
    return {
        normalizeCoordinates,
        markBurialRecordClusterPolygon,
        markBurialRecordLotPoint,
    };
}
