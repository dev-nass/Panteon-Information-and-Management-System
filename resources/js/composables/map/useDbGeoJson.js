import { route } from "ziggy-js";
import { useFeatureProcessing } from "./useFeatureProcessing";
import { useMapStates } from "@/stores/useMapStates";

const { dbGeoJsonLots } = useMapStates();
const { processFeatures, separateLotsByType } = useFeatureProcessing();

export function useDbGeoJson() {
    const fetchGeoJson = async (route_name) => {
        try {
            const response = await fetch(route(route_name));

            if (!response.ok) {
                throw new Error(`HTTP error! status ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error("Error loading GeoJSON:", error);
        }
    };

    const fetchLot = async () => {
        const data = await fetchGeoJson("api.map.burials");
        // extract all 'lot' objects from the burial records
        const lots = data.data.map((record) => record.lot).filter(Boolean);

        const processedFeatures = processFeatures(lots);
        dbGeoJsonLots.value = processedFeatures;

        console.log("Total features:", dbGeoJsonLots.value.length);

        separateLotsByType(dbGeoJsonLots.value);
    };

    return {
        fetchLot,
    };
}
