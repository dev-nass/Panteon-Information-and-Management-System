import { ref } from "vue";

const map = ref(null);
const googleLayer = ref(null);

export function useClerkMapStates() {
    return {
        map,
        googleLayer,
    };
}
