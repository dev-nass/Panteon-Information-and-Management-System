import { router, usePage } from "@inertiajs/vue3";
import { debounce } from "lodash";
import { ref, watch } from "vue";
import { route } from "ziggy-js";

export function useSearchBurialRecords(route_name) {
    const page = usePage();
    const search = ref(page.props.filters.search || "");

    watch(
        search,
        debounce(function (value) {
            router.get(
                route(route_name),
                { search: value },
                {
                    preserveState: true,
                    replace: true,
                    // 'replace' ensure that only one history is make every time
                    // 'user-vet' is visited, ensures that when previous is clicked
                    // on the browser it won't delete the letters
                },
            );
        }, 500),
    );

    return { search };
}
