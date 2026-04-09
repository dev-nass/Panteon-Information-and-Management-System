import { useMapSearchStates } from "@/stores/useMapSearchStates";
import { useMapStates } from "@/stores/useMapStates";
import { useDrawProcessedPath } from "@/composables/map/pathfinder/useDrawProcessedPath";

export function useSearchFeatureProcessing() {
    const { map } = useMapStates();
    const { search, suggestions, loading, isOnSearchMode, searchResultLayer } =
        useMapSearchStates();
    const { drawPathToLot } = useDrawProcessedPath();

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
                `Unable to mark cluster polygon, invalid polygon coordinates`
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
            window.openBurialRecordModal(feature, layer._leaflet_id);
        });
    };

    /**
     * @param lot expects a lot with Point geometry
     */
    const markBurialRecordLotPoint = (lot) => {
        console.log(lot);
        if (!lot.geometry?.coordinates) {
            console.error(`Unable to mark lot point, invalid coordinates`);
            return;
        }

        if (lot.geometry.type !== "Point") {
            console.error(
                `Expected Point geometry but got ${lot.geometry.type}`
            );
            return;
        }

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
        drawPathToLot([lng, lat]);
    };

    /**
     * Description: Mark phase polygon on map
     * @param phaseData expects a phase record from PhaseResource
     * @param polygonCoordinate expects GeoJSON coordinates
     */
    const markPhasePolygon = (phaseData, polygonCoordinate) => {
        if (!polygonCoordinate || !polygonCoordinate.length) {
            console.error(
                `Unable to mark phase polygon, invalid polygon coordinates`
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
                id: phaseData.id,
                ...phaseData.phase.properties,
            },
        };

        const geoJsonLayer = L.geoJSON(geoJsonFeature, {
            style: () => ({
                color: "#10b981",
                fillColor: "#10b981",
                fillOpacity: 0.2,
                weight: 3,
            }),
            onEachFeature: attachPhasePopup,
        });

        searchResultLayer.value.addLayer(geoJsonLayer);

        if (!map.value.hasLayer(searchResultLayer.value)) {
            searchResultLayer.value.addTo(map.value);
        }

        map.value.fitBounds(geoJsonLayer.getBounds(), {
            padding: [50, 50],
            maxZoom: 18,
        });
    };

    /**
     * Description: Attach popup to the searched result phase polygon
     * @param feature
     * @param layer
     */
    const attachPhasePopup = (feature, layer) => {
        console.log(feature);
        layer.on("click", function () {
            window.openPhaseModal(feature, layer._leaflet_id);
        });
    };

    /**
     * Description: Mark cluster polygon on map
     * @param clusterData expects a cluster record from ClusterResource
     * @param polygonCoordinate expects GeoJSON coordinates
     */
    const markClusterPolygon = (clusterData, polygonCoordinate) => {
        if (!polygonCoordinate || !polygonCoordinate.length) {
            console.error(
                `Unable to mark cluster polygon, invalid polygon coordinates`
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
            style: () => ({
                color: "#3b82f6",
                fillColor: "#3b82f6",
                fillOpacity: 0.2,
                weight: 3,
            }),
            onEachFeature: attachClusterPopup,
        });

        searchResultLayer.value.addLayer(geoJsonLayer);

        if (!map.value.hasLayer(searchResultLayer.value)) {
            searchResultLayer.value.addTo(map.value);
        }

        map.value.fitBounds(geoJsonLayer.getBounds(), {
            padding: [50, 50],
            maxZoom: 19,
        });
    };

    /**
     * Description: Attach popup to the searched result cluster polygon
     * @param feature
     * @param layer
     */
    const attachClusterPopup = (feature, layer) => {
        layer.on("click", function () {
            window.openClusterModal(feature, layer._leaflet_id);
        });
    };

    /**
     * Description: Mark lot point on map and attach a popup each
     * @param lot expects a lot with Point geometry
     */
    const markLotPoint = (lot) => {
        console.log("Mark Lot Point", lot);
        if (!lot.geometry || !lot.geometry.coordinates) {
            console.error(`Unable to mark lot point, invalid coordinates`);
            return;
        }

        const [lng, lat] = lot.geometry.coordinates;

        const marker = L.circleMarker([lat, lng], {
            radius: 10,
            fillColor: "#f59e0b",
            color: "#fff",
            weight: 2,
            opacity: 1,
            fillOpacity: 0.8,
        });

        const lotFeature = {
            type: "Feature",
            geometry: lot.geometry,
            properties: lot.properties || {},
        };

        marker.on("click", function () {
            window.openLotDetailsModal(lotFeature);
        });

        searchResultLayer.value.addLayer(marker);

        if (!map.value.hasLayer(searchResultLayer.value)) {
            searchResultLayer.value.addTo(map.value);
        }

        map.value.setView([lat, lng], 20);
    };

    // ✅ Export helpers so useSearch can consume them
    return {
        normalizeCoordinates,
        markBurialRecordClusterPolygon,
        markBurialRecordLotPoint,
        markPhasePolygon,
        markClusterPolygon,
        markLotPoint,
    };
}
