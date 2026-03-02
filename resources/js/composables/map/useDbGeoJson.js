import { route } from "ziggy-js";
import { useFeatureProcessing } from "./useFeatureProcessing";
import { useMapStates } from "@/stores/useMapStates";

const { dbGeoJsonLots } = useMapStates();
const { processesFeatures } = useFeatureProcessing();

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

    const fetchLot = () => {
        const data = fetchGeoJson("api.map.burial");
        const processedFeatures = useFeatureProcessing(data);
        dbGeoJsonLots.value = processedFeatures;

        console.log("Total features:", dbGeoJsonLots.value.length);

        separateLotsByType(dbGeoJsonLots.value);
    };
}
