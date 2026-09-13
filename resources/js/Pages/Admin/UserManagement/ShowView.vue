<script setup>
import { ref, watch, computed, onMounted, onBeforeUnmount } from "vue";
import { Link, usePage, router } from "@inertiajs/vue3";
import { isEqual, debounce } from "lodash";
import { useToast } from "vue-toast-notification";

import Display from "@/Components/Display.vue";
import Dashboard from "@/Layouts/Dashboard.vue";
import TableHeader from "@/Components/Table/TableHeader.vue";
import TableData from "@/Components/Table/TableData.vue";

const props = defineProps({
    user_data: { type: Object, required: true },
    burial_records: { type: Object, default: () => ({ data: [], meta: {} }) },
    filters: { type: Object, default: () => ({}) },
});

const page = usePage();
const $toast = useToast();
const errors = computed(() => page.props.errors || {});

const currentUser = computed(() => page.props.auth.user);

const activeTab = ref(new URLSearchParams(window.location.search).get("tab") || "profile");

watch(
    () => page.url,
    (newUrl) => {
        const tab = new URLSearchParams(newUrl.split("?")[1] || "").get("tab");
        if (tab) activeTab.value = tab;
    },
);
const tabs = [
    { key: "profile", label: "Profile" },
    { key: "account", label: "Account" },
    { key: "records", label: "Records" },
];

const switchTab = (tabKey) => {
    activeTab.value = tabKey;
    const url = new URL(window.location);
    url.searchParams.set("tab", tabKey);
    window.history.replaceState({}, "", url);
};

const back = () => {
    router.visit(route("admin.user_management.index"));
};

const editing = ref(false);
const hasChanges = ref(false);

const originalData = ref(JSON.parse(JSON.stringify(props.user_data)));
const localData = ref(JSON.parse(JSON.stringify(originalData.value)));

watch(
    localData,
    (newData) => {
        hasChanges.value = !isEqual(newData, originalData.value);
    },
    { deep: true },
);

const isTerminated = computed(() => !!props.user_data.is_terminated);
const terminated = computed(() => props.user_data.terminated);
const terminateForm = ref({ terminated_reason: "", terminated_notes: "" });
const terminateClientErrors = ref({ terminated_reason: "", terminated_notes: "" });
const terminateServerErrors = ref({ terminated_reason: "", terminated_notes: "" });

watch(isTerminated, (val) => {
    if (val) editing.value = false;
});

const mapReason = (reason) => {
    const map = {
        resigned: "Resigned",
        retired: "Retired",
        terminated: "Terminated",
        end_of_contract: "End of Contract",
        transferred: "Transferred",
        other: "Other",
    };
    return map[reason] || reason;
};

const cleanupAllOverlays = () => {
    document.querySelectorAll(".hs-overlay").forEach((el) => {
        if (typeof HSOverlay !== "undefined") {
            try {
                HSOverlay.close(el);
            } catch (e) {}
        }
        el.classList.add("hidden");
        el.classList.remove("open", "opened");
        el.setAttribute("aria-hidden", "true");
    });
    document.querySelectorAll(".hs-overlay-backdrop").forEach((el) => el.remove());
    document.body.classList.remove("overflow-hidden");
    document.body.style.removeProperty("overflow");
    document.documentElement.classList.remove("overflow-hidden");
};

onMounted(() => {
    cleanupAllOverlays();
});

onBeforeUnmount(() => {
    cleanupAllOverlays();
});

const closeTerminateOverlay = (el) => {
    if (typeof HSOverlay !== "undefined" && el) {
        try {
            HSOverlay.close(el);
            return;
        } catch (e) {}
    }
    if (!el) return;
    el.classList.remove("open", "opened");
    el.classList.add("hidden");
    el.setAttribute("aria-hidden", "true");
    document.querySelectorAll(".hs-overlay-backdrop").forEach((bd) => bd.remove());
    document.body.classList.remove("overflow-hidden");
};

const openTerminateModal = () => {
    terminateClientErrors.value = { terminated_reason: "", terminated_notes: "" };
    terminateServerErrors.value = { terminated_reason: "", terminated_notes: "" };
    const el = document.getElementById("terminate-modal");
    if (typeof HSOverlay !== "undefined" && el) {
        try {
            HSOverlay.open(el);
            return;
        } catch (e) {}
    }
    if (el) {
        el.classList.remove("hidden");
        el.classList.add("open");
        el.removeAttribute("aria-hidden");
    }
};

const closeTerminateModal = () => {
    const el = document.getElementById("terminate-modal");
    closeTerminateOverlay(el);
    terminateForm.value = { terminated_reason: "", terminated_notes: "" };
    terminateClientErrors.value = { terminated_reason: "", terminated_notes: "" };
    terminateServerErrors.value = { terminated_reason: "", terminated_notes: "" };
};

const terminateUser = () => {
    terminateClientErrors.value = { terminated_reason: "", terminated_notes: "" };
    let hasError = false;

    if (!terminateForm.value.terminated_reason) {
        terminateClientErrors.value.terminated_reason = "Please select a termination reason.";
        $toast.error("Please select a termination reason.");
        hasError = true;
    }
    if (terminateForm.value.terminated_reason === "other" && !terminateForm.value.terminated_notes?.trim()) {
        terminateClientErrors.value.terminated_notes = "Please provide details for 'Other' reason.";
        $toast.error("Please provide details for 'Other' reason.");
        hasError = true;
    }

    if (hasError) return;

    router.post(
        route("admin.user_management.terminate", props.user_data.id),
        {
            terminated_reason: terminateForm.value.terminated_reason,
            terminated_notes: terminateForm.value.terminated_notes,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                $toast.success("User terminated successfully!");
                const el = document.getElementById("terminate-modal");
                closeTerminateOverlay(el);
                terminateForm.value = { terminated_reason: "", terminated_notes: "" };
            },
            onError: (err) => {
                terminateServerErrors.value = {
                    terminated_reason: err.terminated_reason || "",
                    terminated_notes: err.terminated_notes || "",
                };
                $toast.error(err.terminated_reason || err.terminated_notes || "Failed to terminate user.");
            },
        },
    );
};

const openReinstateModal = () => {
    const el = document.getElementById("reinstate-modal");
    if (typeof HSOverlay !== "undefined" && el) {
        try {
            HSOverlay.open(el);
            return;
        } catch (e) {}
    }
    if (el) {
        el.classList.remove("hidden");
        el.classList.add("open");
        el.removeAttribute("aria-hidden");
    }
};

const closeReinstateModal = () => {
    const el = document.getElementById("reinstate-modal");
    closeTerminateOverlay(el);
};

const reinstateUser = () => {
    router.post(
        route("admin.user_management.reinstate", props.user_data.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                $toast.success("User reinstated successfully!");
                const el = document.getElementById("reinstate-modal");
                closeTerminateOverlay(el);
            },
            onError: (err) => {
                $toast.error(err.email || err.terminated_reason || err.terminated_notes || "Failed to reinstate user.");
            },
        },
    );
};

const discardChanges = () => {
    if (
        hasChanges.value &&
        !confirm(
            "Are you sure you want to discard your changes? This action cannot be undone.",
        )
    ) {
        return;
    }

    localData.value = JSON.parse(JSON.stringify(originalData.value));
    hasChanges.value = false;
    editing.value = false;
};

const saveChanges = () => {
    router.post(
        route("admin.user_management.update", localData.value.id),
        {
            first_name: localData.value.first_name,
            middle_name: localData.value.middle_name,
            last_name: localData.value.last_name,
            email: localData.value.email,
            contact_number: localData.value.contact_number,
            role: localData.value.role,
        },
        {
            onSuccess: () => {
                originalData.value = JSON.parse(
                    JSON.stringify(localData.value),
                );
                hasChanges.value = false;
                editing.value = false;
                $toast.success("User updated successfully!");
            },
            onError: () => {
                $toast.error(
                    "Failed to update user. Please check the form for errors.",
                );
            },
            preserveScroll: true,
            preserveState: false,
        },
    );
};

const formatDate = (date) => {
    if (!date) return "Not verified";
    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
    });
};

const formatTerminatedDate = (date) => {
    if (!date) return "";
    return new Date(date).toLocaleString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "numeric",
        minute: "2-digit",
        hour12: true,
    });
};

const canEdit = computed(() => localData.value.id !== currentUser.value.id);

const canDelete = computed(
    () =>
        localData.value.id !== currentUser.value.id &&
        localData.value.role !== "admin",
);

const search = ref(props.filters.search || "");

const currentFilters = computed(() => ({
    search: search.value,
    filter: props.filters.filter || "all",
    sort_field: props.filters.sort_field || "id",
    sort_direction: props.filters.sort_direction || "desc",
    disposal: props.filters.disposal || "",
}));

const reloadRecords = (overrides = {}) => {
    const url = new URL(window.location.href);
    const data = Object.fromEntries(url.searchParams.entries());
    Object.assign(data, currentFilters.value, overrides, { tab: activeTab.value });
    if (!data.disposal) delete data.disposal;
    if (!("page" in overrides)) delete data.page;
    router.get(route("admin.user_management.show", props.user_data.id), data, {
        preserveState: true,
        replace: true,
        only: ["burial_records", "filters"],
    });
};

const debounceSearch = debounce(() => {
    reloadRecords({ search: search.value });
}, 500);

const applyFilter = (filterValue) => {
    reloadRecords({ filter: filterValue });
};

const applyDisposalFilter = (disposalValue) => {
    reloadRecords({
        disposal: currentFilters.value.disposal === disposalValue ? "" : disposalValue,
    });
};

const sort = (field) => {
    let direction = "asc";
    if (currentFilters.value.sort_field === field && currentFilters.value.sort_direction === "asc") {
        direction = "desc";
    }
    reloadRecords({ sort_field: field, sort_direction: direction });
};

const viewRecord = (record) => {
    router.visit(route("admin.burial_records.show", record.burial.id));
};

const paginationLinks = computed(() => {
    return (props.burial_records?.meta?.links || []).map((link) => {
        if (!link.url) return link;
        const url = new URL(link.url);
        url.searchParams.set("tab", activeTab.value);
        return { ...link, url: url.toString() };
    });
});

const disposalTypes = [
    { value: "burial", label: "Burial" },
    { value: "muslim", label: "Muslim" },
    { value: "cremation", label: "Cremation" },
];

defineOptions({
    layout: Dashboard,
});
</script>

<template>
    <!-- Terminate Modal -->
    <Teleport to="body">
        <div
            id="terminate-modal"
            class="hs-overlay hidden size-full fixed top-0 start-0 z-2000 overflow-x-hidden overflow-y-auto bg-black/40 backdrop-blur-sm"
            role="dialog"
            tabindex="-1"
            aria-labelledby="terminate-modal-label"
            @click.self="closeTerminateModal"
        >
            <div
                class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-0 ease-out transition-all sm:max-w-lg sm:w-full m-3 sm:mx-auto"
            >
                <div
                    class="relative w-full max-h-full flex flex-col bg-white/70 dark:bg-neutral-900/70 backdrop-blur-xl border border-white/20 dark:border-white/10 rounded-2xl shadow-lg shadow-gray-200/50 dark:shadow-black/50"
                >
                    <div class="absolute top-3 end-3">
                        <button
                            type="button"
                            class="size-8 inline-flex justify-center items-center rounded-full bg-white/40 dark:bg-neutral-800/40 backdrop-blur-md border border-white/20 dark:border-white/10 text-gray-700 dark:text-neutral-200 hover:bg-white/60 dark:hover:bg-neutral-700/60 transition"
                            @click="closeTerminateModal"
                        >
                            <svg
                                class="size-4"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path d="M18 6 6 18" />
                                <path d="m6 6 12 12" />
                            </svg>
                        </button>
                    </div>

                    <div
                        class="p-10 flex flex-col items-center gap-y-4 text-center"
                    >
                        <div
                            class="flex items-center justify-center size-14 rounded-full bg-red-500/10 text-red-600 dark:text-red-400"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="28"
                                height="28"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M3 6h18" />
                                <path
                                    d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"
                                />
                                <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
                            </svg>
                        </div>

                        <h3
                            id="terminate-modal-label"
                            class="-mt-2 text-2xl font-bold text-red-600 dark:text-red-400"
                        >
                            Terminate User
                        </h3>

                        <p
                            class="text-gray-600 dark:text-neutral-300 max-w-sm text-sm"
                        >
                            This will hide
                            <span class="font-semibold text-gray-900 dark:text-white"
                                >{{ localData.first_name }} {{ localData.last_name }}</span
                            >
                            from the main user list. The account will only appear under the
                            <span class="font-semibold">Terminated</span> filter and can be reinstated later. Burial records created by this user will remain attributed.
                        </p>
                    </div>

                    <div class="px-6 pb-6 flex flex-col gap-4">
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                            >
                                Reason <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="terminateForm.terminated_reason"
                                @change="
                                    terminateClientErrors.terminated_reason = '';
                                    terminateClientErrors.terminated_notes = '';
                                    terminateServerErrors.terminated_reason = '';
                                    terminateServerErrors.terminated_notes = '';
                                "
                                :class="{
                                    'border-red-500 focus:ring-red-500 focus:border-red-500':
                                        terminateClientErrors.terminated_reason ||
                                        terminateServerErrors.terminated_reason ||
                                        errors.terminated_reason,
                                }"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-neutral-600 bg-white dark:bg-neutral-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-red-500"
                            >
                                <option value="">Select reason</option>
                                <option value="resigned">Resigned</option>
                                <option value="retired">Retired</option>
                                <option value="terminated">Terminated</option>
                                <option value="end_of_contract">End of Contract</option>
                                <option value="transferred">Transferred</option>
                                <option value="other">Other</option>
                            </select>
                            <p
                                v-if="
                                    terminateClientErrors.terminated_reason ||
                                    terminateServerErrors.terminated_reason ||
                                    errors.terminated_reason
                                "
                                class="mt-1 text-sm text-red-500"
                            >
                                {{
                                    terminateClientErrors.terminated_reason ||
                                    terminateServerErrors.terminated_reason ||
                                    errors.terminated_reason
                                }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                            >
                                Notes
                                <span
                                    v-if="
                                        terminateForm.terminated_reason === 'other'
                                    "
                                    class="text-red-500"
                                    >*</span
                                >
                                <span
                                    v-else
                                    class="text-gray-400 font-normal"
                                    >(optional)</span
                                >
                            </label>
                            <textarea
                                v-model="terminateForm.terminated_notes"
                                @input="
                                    terminateClientErrors.terminated_notes = '';
                                    terminateServerErrors.terminated_notes = '';
                                "
                                rows="3"
                                placeholder="Provide details (required if Other)..."
                                :class="{
                                    'border-red-500 focus:ring-red-500 focus:border-red-500':
                                        terminateClientErrors.terminated_notes ||
                                        terminateServerErrors.terminated_notes ||
                                        errors.terminated_notes,
                                }"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-neutral-600 bg-white dark:bg-neutral-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-red-500 resize-none"
                            ></textarea>
                            <p
                                v-if="
                                    terminateClientErrors.terminated_notes ||
                                    terminateServerErrors.terminated_notes ||
                                    errors.terminated_notes
                                "
                                class="mt-1 text-sm text-red-500"
                            >
                                {{
                                    terminateClientErrors.terminated_notes ||
                                    terminateServerErrors.terminated_notes ||
                                    errors.terminated_notes
                                }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="flex border-t border-white/20 dark:border-white/10"
                    >
                        <button
                            type="button"
                            class="w-full py-3 text-sm font-semibold text-gray-600 dark:text-neutral-300 hover:bg-gray-500/10 transition"
                            @click="closeTerminateModal"
                        >
                            Cancel
                        </button>

                        <button
                            type="button"
                            class="w-full py-3 text-sm font-semibold text-red-500 hover:bg-red-500/10 transition"
                            @click="terminateUser"
                        >
                            Terminate
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Reinstate Modal -->
    <Teleport to="body">
        <div
            id="reinstate-modal"
            class="hs-overlay hidden size-full fixed top-0 start-0 z-2000 overflow-x-hidden overflow-y-auto bg-black/40 backdrop-blur-sm"
            role="dialog"
            tabindex="-1"
            aria-labelledby="reinstate-modal-label"
            @click.self="closeReinstateModal"
        >
            <div
                class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-0 ease-out transition-all sm:max-w-lg sm:w-full m-3 sm:mx-auto"
            >
                <div
                    class="relative w-full max-h-full flex flex-col bg-white/70 dark:bg-neutral-900/70 backdrop-blur-xl border border-white/20 dark:border-white/10 rounded-2xl shadow-lg shadow-gray-200/50 dark:shadow-black/50"
                >
                    <div class="absolute top-3 end-3">
                        <button
                            type="button"
                            class="size-8 inline-flex justify-center items-center rounded-full bg-white/40 dark:bg-neutral-800/40 backdrop-blur-md border border-white/20 dark:border-white/10 text-gray-700 dark:text-neutral-200 hover:bg-white/60 dark:hover:bg-neutral-700/60 transition"
                            @click="closeReinstateModal"
                        >
                            <svg
                                class="size-4"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path d="M18 6 6 18" />
                                <path d="m6 6 12 12" />
                            </svg>
                        </button>
                    </div>

                    <div
                        class="p-10 flex flex-col items-center gap-y-4 text-center"
                    >
                        <div
                            class="flex items-center justify-center size-14 rounded-full bg-green-500/10 text-green-600 dark:text-green-400"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="28"
                                height="28"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="lucide lucide-rotate-ccw-icon lucide-rotate-ccw"
                            >
                                <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
                                <path d="M3 3v5h5" />
                            </svg>
                        </div>

                        <h3
                            id="reinstate-modal-label"
                            class="-mt-2 text-2xl font-bold text-green-600 dark:text-green-400"
                        >
                            Reinstate Account
                        </h3>

                        <p
                            class="text-gray-600 dark:text-neutral-300 max-w-sm text-sm"
                        >
                            This will make the account active again and allow login.
                        </p>
                    </div>

                    <div class="px-6 pb-4 flex flex-col gap-3">
                        <div
                            class="w-full bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4 text-left space-y-2"
                        >
                            <p
                                class="text-sm text-gray-600 dark:text-neutral-300"
                            >
                                You are about to reinstate
                                <span
                                    class="font-semibold text-green-700 dark:text-green-300"
                                    >{{ localData.first_name }} {{ localData.last_name }}</span
                                >
                                . The account will be moved from
                                <span class="font-semibold">Terminated</span> back to the main list.
                            </p>

                            <div
                                class="grid grid-cols-1 gap-1.5 pt-2 border-t border-green-200 dark:border-green-800"
                            >
                                <div class="flex justify-between">
                                    <span
                                        class="text-sm font-medium text-gray-500 dark:text-neutral-400"
                                        >Full Name:</span
                                    >
                                    <span
                                        class="text-sm font-semibold text-gray-900 dark:text-white"
                                        >{{ localData.first_name }} {{ localData.last_name }}</span
                                    >
                                </div>
                                <div class="flex justify-between">
                                    <span
                                        class="text-sm font-medium text-gray-500 dark:text-neutral-400"
                                        >Email:</span
                                    >
                                    <span
                                        class="text-sm font-semibold text-gray-900 dark:text-white"
                                        >{{ localData.email }}</span
                                    >
                                </div>
                                <div class="flex justify-between">
                                    <span
                                        class="text-sm font-medium text-gray-500 dark:text-neutral-400"
                                        >Role:</span
                                    >
                                    <span
                                        class="text-sm font-semibold text-gray-900 dark:text-white capitalize"
                                        >{{ localData.role }}</span
                                    >
                                </div>
                                <div v-if="terminated?.at" class="flex justify-between">
                                    <span
                                        class="text-sm font-medium text-gray-500 dark:text-neutral-400"
                                        >Terminated At:</span
                                    >
                                    <span
                                        class="text-sm font-semibold text-gray-900 dark:text-white"
                                        >{{ formatTerminatedDate(terminated.at) }}</span
                                    >
                                </div>
                                <div v-if="terminated?.reason" class="flex justify-between">
                                    <span
                                        class="text-sm font-medium text-gray-500 dark:text-neutral-400"
                                        >Reason:</span
                                    >
                                    <span
                                        class="text-sm font-semibold text-gray-900 dark:text-white"
                                        >{{ mapReason(terminated.reason) }}</span
                                    >
                                </div>
                                <div v-if="terminated?.by?.full_name" class="flex justify-between">
                                    <span
                                        class="text-sm font-medium text-gray-500 dark:text-neutral-400"
                                        >By:</span
                                    >
                                    <span
                                        class="text-sm font-semibold text-gray-900 dark:text-white"
                                        >{{ terminated.by.full_name }}</span
                                    >
                                </div>
                            </div>

                            <p
                                class="text-xs text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg px-3 py-2"
                            >
                                If email is already taken by an active user, reinstate will fail. Free email first or use different email.
                            </p>
                        </div>
                        <p
                            v-if="errors.email"
                            class="text-sm text-red-500 text-center"
                        >
                            {{ errors.email }}
                        </p>
                    </div>

                    <div
                        class="flex border-t border-white/20 dark:border-white/10"
                    >
                        <button
                            type="button"
                            class="w-full py-3 text-sm font-semibold text-green-600 dark:text-green-400 hover:bg-green-500/10 transition"
                            @click="reinstateUser"
                        >
                            Reinstate
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>

    <div class="max-w-6xl mx-auto p-6">
        <!-- Back button -->
        <button
            @click="back"
            class="flex items-center gap-1 mb-6 text-sm text-green-600 dark:text-green-400 hover:underline"
        >
            ← Back
        </button>

        <!-- Header -->
        <div class="mb-6 flex items-center justify-between">
            <div class="flex gap-x-3">
                <div
                    class="flex items-center justify-center size-13 rounded-full bg-green-500/10 text-green-600 dark:text-green-400"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="22"
                        height="22"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <circle cx="12" cy="8" r="5" />
                        <path d="M20 21a8 8 0 0 0-16 0" />
                    </svg>
                </div>
                <article>
                    <h1
                        class="text-2xl font-bold text-green-600 dark:text-green-400"
                    >
                        {{ localData.first_name }} {{ localData.last_name }}
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        User Account Details
                    </p>
                </article>
            </div>

            <div class="flex gap-x-3">
                <template v-if="isTerminated">
                    <button
                        @click="openReinstateModal"
                        class="flex items-center gap-x-2 px-4 py-2 rounded-xl border border-transparent bg-green-500/10 text-green-600 dark:text-green-400 hover:bg-green-500/20 hover:border-green-500/40 hover:text-green-700 dark:hover:text-green-300 transition-all duration-200"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="20"
                            height="20"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="lucide lucide-rotate-ccw-icon lucide-rotate-ccw"
                        >
                            <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
                            <path d="M3 3v5h5" />
                        </svg>
                        Reinstate
                    </button>
                </template>
                <template v-else-if="!editing">
                    <button
                        v-if="canEdit"
                        @click="editing = true"
                        class="flex items-center justify-center gap-x-2 px-4 py-2 bg-green-500/10 text-green-400 rounded-xl border border-transparent hover:bg-green-500/20 hover:border-green-500/40 hover:text-green-600 dark:hover:text-green-300 transition-all duration-200"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="20"
                            height="20"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path
                                d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"
                            />
                            <path d="m15 5 4 4" />
                        </svg>
                        Edit
                    </button>

                    <button
                        v-if="canDelete"
                        @click="openTerminateModal"
                        class="flex items-center justify-center gap-x-2 px-4 py-2 rounded-xl border border-transparent bg-red-500/10 text-red-500 hover:bg-red-500/20 hover:border-red-500/40 transition-all duration-200"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="20"
                            height="20"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="M3 6h18" />
                            <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" />
                            <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
                        </svg>
                        Terminate
                    </button>
                </template>

                <template v-else>
                    <button
                        :disabled="!hasChanges"
                        @click="saveChanges"
                        class="flex items-center justify-center gap-x-2 px-4 py-2 bg-green-500/10 text-green-400 rounded-xl border border-transparent hover:bg-green-500/20 hover:border-green-500/40 hover:text-green-600 dark:hover:text-green-300 transition-all duration-200"
                        :class="{
                            'opacity-50 cursor-not-allowed': !hasChanges,
                        }"
                    >
                        Save Changes
                    </button>
                    <button
                        @click="discardChanges"
                        class="flex items-center justify-center gap-x-2 px-4 py-2 rounded-xl border border-transparent dark:bg-neutral-800 hover:dark:bg-neutral-600 transition-all duration-200"
                    >
                        Discard
                    </button>
                </template>
            </div>
        </div>

        <!-- Tabs -->
        <div class="flex border-b border-gray-200 dark:border-neutral-700 mb-6">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                @click="switchTab(tab.key)"
                class="px-4 py-2 text-sm font-medium transition"
                :class="
                    activeTab === tab.key
                        ? 'border-b-2 border-green-500 text-green-600 dark:text-green-400'
                        : 'text-gray-500 dark:text-gray-400 hover:text-green-500'
                "
            >
                {{ tab.label }}
            </button>
        </div>

        <!-- Terminated banner -->
        <div
            v-if="isTerminated"
            class="mb-6 flex flex-col gap-2 rounded-xl border border-amber-200 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-800 px-4 py-3"
        >
            <div class="flex items-center gap-2 text-amber-700 dark:text-amber-400">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="18"
                    height="18"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3" />
                    <path d="M12 9v4" />
                    <path d="M12 17h.01" />
                </svg>
                <span class="text-sm font-semibold">Terminated Account</span>
            </div>
            <p class="text-sm text-amber-800 dark:text-amber-300">
                <span v-if="terminated?.at">Terminated on {{ formatTerminatedDate(terminated.at) }}</span>
                <span v-if="terminated?.reason"> — Reason: {{ mapReason(terminated.reason) }}</span>
                <span v-if="terminated?.by?.full_name"> — By {{ terminated.by.full_name }}</span>
            </p>
            <p
                v-if="terminated?.notes"
                class="text-sm bg-white/60 dark:bg-neutral-800/50 rounded-lg px-3 py-2 text-amber-900 dark:text-amber-200"
            >
                {{ terminated.notes }}
            </p>
        </div>

        <!-- Card Container -->
        <div
            class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-700 rounded-xl shadow-md p-6 transition"
        >
            <!-- Profile Tab -->
            <div
                v-if="activeTab === 'profile'"
                class="grid grid-cols-1 md:grid-cols-2 gap-6"
            >
                <Display
                    label="First Name"
                    :modelValue="localData.first_name"
                    :editing="editing"
                    :error="errors.first_name"
                    @update:modelValue="(val) => (localData.first_name = val)"
                />

                <Display
                    label="Middle Name"
                    :modelValue="localData.middle_name"
                    :editing="editing"
                    :error="errors.middle_name"
                    @update:modelValue="(val) => (localData.middle_name = val)"
                />

                <Display
                    label="Last Name"
                    :modelValue="localData.last_name"
                    :editing="editing"
                    :error="errors.last_name"
                    @update:modelValue="(val) => (localData.last_name = val)"
                />

                <div>
                    <label class="text-sm text-gray-500 dark:text-gray-400">
                        Email
                    </label>
                    <input
                        type="email"
                        :value="localData.email"
                        @input="(e) => (localData.email = e.target.value)"
                        :disabled="!editing"
                        placeholder="—"
                        :class="{
                            'border-red-500 focus:ring-red-500':
                                editing && errors.email,
                        }"
                        class="mt-1 w-full rounded-lg border border-gray-300 dark:border-neutral-700 bg-gray-100 dark:bg-neutral-800 text-gray-800 dark:text-white px-3 py-2 text-sm transition disabled:border-none disabled:bg-white disabled:dark:bg-neutral-900 disabled:dark:text-gray-200 disabled:hover:text-green-600 disabled:hover:dark:text-green-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                    />
                    <p
                        v-if="editing && errors.email"
                        class="mt-1 text-sm text-red-500"
                    >
                        {{ errors.email }}
                    </p>
                </div>

                <Display
                    label="Contact Number"
                    :modelValue="localData.contact_number"
                    :editing="editing"
                    :error="errors.contact_number"
                    @update:modelValue="
                        (val) => (localData.contact_number = val)
                    "
                />

                <div>
                    <label class="text-sm text-gray-500 dark:text-gray-400">
                        Email Verified At
                    </label>
                    <div class="mt-1.5 flex items-center gap-x-2">
                        <span
                            class="inline-flex items-center gap-x-1.5 py-1 px-2.5 rounded-full text-xs font-medium"
                            :class="
                                localData.email_verified_at
                                    ? 'bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-500'
                                    : 'bg-amber-100 text-amber-800 dark:bg-amber-800/30 dark:text-amber-500'
                            "
                        >
                            {{
                                localData.email_verified_at
                                    ? "Verified"
                                    : "Pending"
                            }}
                        </span>
                        <p
                            v-if="localData.email_verified_at"
                            class="text-sm text-gray-800 dark:text-gray-200"
                        >
                            {{ formatDate(localData.email_verified_at) }}
                        </p>
                    </div>
                </div>

                <div>
                    <label class="text-sm text-gray-500 dark:text-gray-400">
                        Member Since
                    </label>
                    <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">
                        {{ formatDate(localData.created_at) }}
                    </p>
                </div>
            </div>

            <!-- Account Tab -->
            <div
                v-if="activeTab === 'account'"
                class="grid grid-cols-1 md:grid-cols-2 gap-6"
            >
                <div>
                    <label class="text-sm text-gray-500 dark:text-gray-400">
                        Role
                    </label>
                    <select
                        v-if="editing"
                        v-model="localData.role"
                        class="mt-1 w-full rounded-lg border border-gray-300 dark:border-neutral-700 bg-gray-100 dark:bg-neutral-800 text-gray-800 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                    >
                        <option value="clerk">Clerk</option>
                        <option value="head">Head</option>
                        <option value="admin">Admin</option>
                    </select>
                    <div v-else class="mt-1.5">
                        <span
                            class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-medium"
                            :class="{
                                'bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-500':
                                    localData.role === 'admin',
                                'bg-blue-100 text-blue-800 dark:bg-blue-800/30 dark:text-blue-500':
                                    localData.role === 'head',
                                'bg-yellow-100 text-yellow-800 dark:bg-yellow-800/30 dark:text-yellow-500':
                                    localData.role === 'clerk',
                            }"
                        >
                            {{ localData.role }}
                        </span>
                    </div>
                </div>

                <div>
                    <label class="text-sm text-gray-500 dark:text-gray-400">
                        Burial Records Created
                    </label>
                    <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">
                        {{ localData.burial_records_count }}
                    </p>
                </div>
            </div>

            <!-- Records Tab -->
            <div v-if="activeTab === 'records'" class="p-0">
                <div
                    class="bg-white dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-xl shadow-2xs overflow-hidden"
                >
                    <!-- Header -->
                    <div
                        class="px-6 py-4 grid gap-3 lg:flex lg:justify-between lg:items-center border-b border-gray-200 dark:border-neutral-700"
                    >
                        <input
                            v-model="search"
                            @input="debounceSearch"
                            type="text"
                            placeholder="Search by name..."
                            class="w-full lg:max-w-md rounded-lg border border-gray-300 dark:border-neutral-700 bg-white dark:bg-neutral-900 text-gray-800 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                        />

                        <div class="flex flex-wrap gap-x-2">
                            <!-- Disposal filter -->
                            <div
                                v-for="disposal in disposalTypes"
                                :key="disposal.value"
                                @click="applyDisposalFilter(disposal.value)"
                                class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-medium cursor-pointer transition"
                                :class="
                                    filters.disposal === disposal.value
                                        ? 'bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-500'
                                        : 'bg-gray-100 text-gray-600 dark:bg-neutral-700 dark:text-neutral-400 hover:bg-gray-200 dark:hover:bg-neutral-600'
                                "
                            >
                                {{ disposal.label }}
                            </div>

                            <!-- Status filter -->
                            <div class="hs-dropdown relative inline-block">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-neutral-700 dark:text-neutral-400 hover:bg-gray-200 dark:hover:bg-neutral-600 transition"
                                >
                                    {{
                                        filters.filter === "buried"
                                            ? "Buried"
                                            : filters.filter === "pending"
                                              ? "Pending"
                                              : filters.filter === "assigned"
                                                ? "Assigned"
                                                : filters.filter === "unassigned"
                                                  ? "Unassigned"
                                                  : "Status"
                                    }}
                                </button>
                                <div
                                    class="hs-dropdown-menu transition-[opacity,margin] duration hs-dropdown-open:opacity-100 opacity-0 hidden min-w-32 z-10 bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-700 divide-y divide-gray-200 dark:divide-neutral-800 shadow-md rounded-lg mt-2"
                                >
                                    <div
                                        v-for="opt in [
                                            { value: 'all', label: 'All' },
                                            { value: 'buried', label: 'Buried' },
                                            { value: 'pending', label: 'Pending' },
                                            { value: 'assigned', label: 'Assigned' },
                                            { value: 'unassigned', label: 'Unassigned' },
                                        ]"
                                        :key="opt.value"
                                        @click="applyFilter(opt.value)"
                                        class="px-3 py-2 text-sm text-gray-800 dark:text-neutral-200 hover:bg-gray-50 dark:hover:bg-neutral-800 cursor-pointer"
                                    >
                                        {{ opt.label }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table
                            class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700"
                        >
                            <thead class="bg-gray-50 dark:bg-neutral-800">
                                <tr>
                                    <TableHeader @click="sort('id')">
                                        ID
                                    </TableHeader>
                                    <TableHeader
                                        @click="sort('deceased_first_name')"
                                    >
                                        Full Name
                                    </TableHeader>
                                    <TableHeader
                                        @click="sort('deceased_date_of_depository')"
                                    >
                                        Burial Date
                                    </TableHeader>
                                    <TableHeader>Phase</TableHeader>
                                    <TableHeader>Cluster</TableHeader>
                                    <TableHeader>Lot</TableHeader>
                                    <TableHeader>Lot Status</TableHeader>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-gray-200 dark:divide-neutral-700"
                            >
                                <tr
                                    v-if="burial_records.data?.length > 0"
                                    v-for="record in burial_records.data"
                                    :key="record.burial.id"
                                    @click="viewRecord(record)"
                                    class="bg-white dark:bg-neutral-800 hover:bg-gray-50 dark:hover:bg-neutral-700 cursor-pointer"
                                >
                                    <TableData>{{ record.burial.id }}</TableData>
                                    <TableData>
                                        {{ record.deceased.first_name }}
                                        {{ record.deceased.last_name }}
                                    </TableData>
                                    <TableData>
                                        {{ record.deceased.burial.date ?? "N/A" }}
                                    </TableData>
                                    <TableData>
                                        {{ record.cluster?.cluster?.properties?.phase ?? "N/A" }}
                                    </TableData>
                                    <TableData>
                                        {{ record.cluster?.cluster?.properties?.name ?? "N/A" }}
                                    </TableData>
                                    <TableData>
                                        {{
                                            record.lot?.lot?.properties?.column
                                                ? `${record.lot.lot.properties.column}${record.lot.lot.properties.row}`
                                                : "N/A"
                                        }}
                                    </TableData>
                                    <TableData>
                                        <span
                                            v-if="record.lot?.lot?.properties?.lot_id"
                                            class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-500"
                                        >
                                            Assigned
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800/30 dark:text-yellow-500"
                                        >
                                            Unassigned
                                        </span>
                                    </TableData>
                                </tr>
                                <tr v-else>
                                    <td colspan="7" class="px-6 py-8 text-center">
                                        <span
                                            class="text-sm text-gray-500 dark:text-neutral-400"
                                            >No records found</span
                                        >
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Footer / Pagination -->
                    <div
                        class="px-6 py-4 border-t border-gray-200 dark:border-neutral-700"
                    >
                        <div class="flex flex-wrap gap-2 max-w-md">
                                <template v-if="paginationLinks.length">
                                <component
                                    v-for="(link, index) in paginationLinks"
                                    :key="`${link.label}-${index}`"
                                    :is="link.url ? Link : 'span'"
                                    :href="link.url"
                                    preserve-scroll
                                    :only="['burial_records', 'filters']"
                                    v-html="link.label"
                                    :class="[
                                        'py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-neutral-400',
                                        link.active
                                            ? 'text-green-500'
                                            : 'text-gray-800 dark:text-neutral-400',
                                    ]"
                                />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
