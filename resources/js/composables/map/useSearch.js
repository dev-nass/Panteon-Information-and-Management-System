import { debounce } from "lodash";
import { route } from "ziggy-js";

import { useMapSearchStates } from "@/stores/useMapSearchStates";
import { useMapStates } from "@/stores/useMapStates";

export function useSearch() {
    const { map } = useMapStates();
    const { search, suggestions, loading, searchResultLayer } =
        useMapSearchStates();

    const fetchSuggestions = debounce(async () => {
        if (!search.value) {
            suggestions.value = [];
            return;
        }

        loading.value = true;

        try {
            const response = await fetch(
                `${route("api.map.search")}?search=${encodeURIComponent(
                    search.value,
                )}`,
                {
                    headers: {
                        Accept: "application/json",
                    },
                    credentials: "same-origin",
                },
            );

            if (!response.ok) {
                throw new Error("Failed to fetch suggestions");
            }

            const data = await response.json();
            suggestions.value = data.results;
            console.log(suggestions.value);
        } catch (err) {
            console.error(err);
            suggestions.value = [];
        } finally {
            loading.value = false;
        }
    }, 300);

    return {
        fetchSuggestions,
    };
}
