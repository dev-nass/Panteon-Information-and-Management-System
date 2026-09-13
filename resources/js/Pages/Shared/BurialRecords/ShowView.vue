<script setup>
import { onMounted, onBeforeUnmount, ref, watch, computed } from "vue";
import { Link, usePage, router } from "@inertiajs/vue3";
import { has, isEqual } from "lodash";
import { useToast } from "vue-toast-notification";

import Display from "@/Components/Display.vue";
import Dashboard from "@/Layouts/Dashboard.vue";

import { useMap } from "@/composables/useMap";
import { useSearch } from "@/composables/map/search/useSearch";

const props = defineProps({
    burial_record: { type: Object, required: true },
    phases: { type: Array, required: true },
    current_selection: { type: Object, default: null },
});

const page = usePage();
// RBAC variables
const user = computed(() => page.props.auth.user);
const userRole = computed(() =>
    page.props.auth?.user?.role?.toLowerCase()?.trim(),
);
const roleRoutes = {
    admin: {
        route: "admin.map.index",
    },
    clerk: {
        route: "clerk.map.index",
    },
};
// response variables
const errors = computed(() => page.props.errors || {});
const $toast = useToast();

const isArchived = computed(() => !!props.burial_record.data.is_archived);

const archiveForm = ref({
    archived_reason: "",
    archived_notes: "",
});

const archiveClientErrors = ref({
    archived_reason: "",
    archived_notes: "",
});
const archiveServerErrors = ref({
    archived_reason: "",
    archived_notes: "",
});

const { initializeMap, cleanupMap, toggleMapFeatures, togglePhaseVisibility } =
    useMap();
const { fetchClusterByBurialId } = useSearch();

// console.log("Clerk Burial Show", props.burial_record);

const activeTab = ref("personal");
const tabs = [
    { key: "personal", label: "Personal Info" },
    { key: "death", label: "Death Info" },
    { key: "disposition", label: "Disposition" },
    { key: "family", label: "Family & Company" },
    { key: "applicant", label: "Applicant" },
    { key: "location", label: "Location" },
    { key: "imported", label: "Imported By" },
];

const back = () => {
    if (userRole.value === "clerk")
        router.visit(route("clerk.burial_records.index"));
    else if (userRole.value === "admin")
        router.visit(route("admin.burial_records.index"));
};

const editing = ref(false);
const hasChanges = ref(false);

// deep copy original data
const originalData = ref(JSON.parse(JSON.stringify(props.burial_record.data)));
const localData = ref(JSON.parse(JSON.stringify(originalData.value)));
// console.log(localData.value);

const selectedPhaseId = ref(props.current_selection?.phase_id || null);
const selectedClusterId = ref(props.current_selection?.cluster_id || null);
const selectedLotId = ref(props.current_selection?.lot_id || null);
const originalLotId = ref(props.current_selection?.lot_id || null);

watch(
    [localData, selectedLotId],
    ([newData, newLotId]) => {
        const dataChanged = !isEqual(newData, originalData.value);
        const lotChanged = newLotId !== originalLotId.value;
        hasChanges.value = dataChanged || lotChanged;
    },
    { deep: true },
);

watch(isArchived, (val) => {
    if (val) {
        editing.value = false;
    }
});

const discardChanges = () => {
    if (hasChanges.value) {
        HSOverlay.open("#hs-cookies");
        return;
    }

    editing.value = false;
};

const confirmDiscard = () => {
    localData.value = JSON.parse(JSON.stringify(originalData.value));
    selectedPhaseId.value = props.current_selection?.phase_id || null;
    selectedClusterId.value = props.current_selection?.cluster_id || null;
    selectedLotId.value = props.current_selection?.lot_id || null;
    originalLotId.value = props.current_selection?.lot_id || null;
    hasChanges.value = false;
    editing.value = false;

    HSOverlay.close("#hs-cookies");
};

/**
* Description: Get the burial ID of the record being showng and pass
               it on the fetchClusterByBurialId()
*/
const redirectToMap = () => {
    const burialId = props.burial_record.data.burial.id;
    router.visit(route(roleRoutes[userRole.value].route), {
        data: { burialId },
        onSuccess: () => {
            setTimeout(() => {
                fetchClusterByBurialId(burialId);
            }, 500);
        },
    });
};

const closeArchiveOverlay = (el) => {
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
    // remove Preline backdrop if present
    document
        .querySelectorAll(".hs-overlay-backdrop")
        .forEach((bd) => bd.remove());
    document.body.classList.remove("overflow-hidden");
};

const openArchiveModal = () => {
    archiveClientErrors.value = { archived_reason: "", archived_notes: "" };
    archiveServerErrors.value = { archived_reason: "", archived_notes: "" };
    const el = document.getElementById("archive-modal");
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

const closeArchiveModal = () => {
    const el = document.getElementById("archive-modal");
    closeArchiveOverlay(el);
    archiveForm.value = { archived_reason: "", archived_notes: "" };
    archiveClientErrors.value = { archived_reason: "", archived_notes: "" };
    archiveServerErrors.value = { archived_reason: "", archived_notes: "" };
};

const archiveBurialRecord = () => {
    // reset client errors
    archiveClientErrors.value = { archived_reason: "", archived_notes: "" };
    let hasError = false;

    if (!archiveForm.value.archived_reason) {
        archiveClientErrors.value.archived_reason =
            "Please select an archive reason.";
        $toast.error("Please select an archive reason.");
        hasError = true;
    }
    if (
        archiveForm.value.archived_reason === "other" &&
        !archiveForm.value.archived_notes?.trim()
    ) {
        archiveClientErrors.value.archived_notes =
            "Please provide details for 'Other' reason.";
        $toast.error("Please provide details for 'Other' reason.");
        hasError = true;
    }

    if (hasError) return;

    router.post(
        route(
            "clerk.burial_records.archive",
            props.burial_record.data.burial.id,
        ),
        {
            archived_reason: archiveForm.value.archived_reason,
            archived_notes: archiveForm.value.archived_notes,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                $toast.success("Burial record archived successfully!");
                closeArchiveModal();
            },
            onError: (err) => {
                archiveServerErrors.value = {
                    archived_reason: err.archived_reason || "",
                    archived_notes: err.archived_notes || "",
                };
                const msg =
                    err.archived_reason ||
                    err.archived_notes ||
                    "Failed to archive burial record.";
                $toast.error(msg);
            },
        },
    );
};

const openRestoreModal = () => {
    const el = document.getElementById("restore-modal");
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

const closeRestoreModal = () => {
    const el = document.getElementById("restore-modal");
    closeArchiveOverlay(el);
};

const confirmRestore = () => {
    router.post(
        route(
            "clerk.burial_records.restore",
            props.burial_record.data.burial.id,
        ),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                $toast.success("Burial record recovered successfully!");
                closeRestoreModal();
            },
            onError: (err) => {
                const msg =
                    err.lot_id ||
                    err.message ||
                    "Failed to recover — lot may be occupied.";
                $toast.error(msg);
            },
        },
    );
};

const recoverBurialRecord = () => {
    openRestoreModal();
};

const deleteBurialRecord = () => {
    // Deprecated: kept for backwards compat, delegates to archive modal
    openArchiveModal();
};

const saveChanges = () => {
    router.post(
        route(
            "clerk.burial_records.update",
            props.burial_record.data.burial.id,
        ),
        {
            deceased: localData.value.deceased,
            lot_id: selectedLotId.value,
        },
        {
            onSuccess: () => {
                originalData.value = JSON.parse(
                    JSON.stringify(localData.value),
                );
                originalLotId.value = selectedLotId.value;
                hasChanges.value = false;
                editing.value = false;
                $toast.success("Burial record updated successfully!");
            },
            onError: (errors) => {
                const duplicateError =
                    errors["deceased.first_name"] || errors.first_name;
                if (duplicateError?.includes("already exists")) {
                    $toast.error(duplicateError, { duration: 8000 });
                } else {
                    $toast.error(
                        "Failed to update burial record. Please check the form for errors.",
                    );
                }
            },
            preserveScroll: true,
            preserveState: false, // updates the record on show after updating
        },
    );
};

// Initialize location selections based on current burial record
// const initializeLocationSelections = () => {
//     const currentLotId = props.burial_record.data.lot?.lot?.id;
//     const currentClusterId = props.burial_record.data.lot?.cluster?.id;
//
//     if (currentLotId && currentClusterId) {
//         // Find the phase that contains this cluster
//         for (const phase of props.phases) {
//             const cluster = phase.clusters.find(
//                 (c) => c.id == currentClusterId,
//             );
//             if (cluster) {
//                 selectedPhaseId.value = phase.id;
//                 selectedClusterId.value = currentClusterId;
//                 selectedLotId.value = currentLotId;
//                 break;
//             }
//         }
//     }
// };

const availableClusters = computed(() => {
    if (!selectedPhaseId.value) return [];
    const phase = props.phases.find((p) => p.id == selectedPhaseId.value);
    return phase?.clusters || [];
});

const availableLots = computed(() => {
    if (!selectedClusterId.value) return [];
    const cluster = availableClusters.value.find(
        (c) => c.id == selectedClusterId.value,
    );
    return (
        cluster?.lots.filter(
            (lot) => !lot.is_occupied || lot.id == selectedLotId.value,
        ) || []
    );
});

const selectedLotColumn = computed(() => {
    if (!selectedLotId.value) return null;
    const lot = availableLots.value.find((l) => l.id == selectedLotId.value);
    return lot?.column || null;
});

const selectedLotRow = computed(() => {
    if (!selectedLotId.value) return null;
    const lot = availableLots.value.find((l) => l.id == selectedLotId.value);
    return lot?.row || null;
});

const selectedClusterType = computed(() => {
    if (!selectedClusterId.value) return null;
    const cluster = availableClusters.value.find(
        (c) => c.id == selectedClusterId.value,
    );
    return cluster?.cluster_type || null;
});

defineOptions({
    layout: Dashboard,
});

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
    document
        .querySelectorAll(".hs-overlay-backdrop")
        .forEach((el) => el.remove());
    document.body.classList.remove("overflow-hidden");
    document.body.style.removeProperty("overflow");
};

// added to close the modal from Clerk/Map/IndexView
onMounted(() => {
    cleanupAllOverlays();
});

onBeforeUnmount(() => {
    cleanupMap();
    cleanupAllOverlays();
});
</script>

<template>
    <Teleport to="body">
        <div
            id="hs-cookies"
            class="hs-overlay hidden size-full fixed top-0 start-0 z-2000 overflow-x-hidden overflow-y-auto bg-black/40 backdrop-blur-sm"
            role="dialog"
            tabindex="-1"
            aria-labelledby="hs-cookies-label"
        >
            <div
                class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-0 ease-out transition-all sm:max-w-lg sm:w-full m-3 sm:mx-auto"
            >
                <div
                    class="relative w-full max-h-full flex flex-col bg-white/70 dark:bg-neutral-900/70 backdrop-blur-xl border border-white/20 dark:border-white/10 rounded-2xl shadow-lg shadow-gray-200/50 dark:shadow-black/50"
                >
                    <!-- Close button -->
                    <div class="absolute top-3 end-3">
                        <button
                            type="button"
                            class="size-8 inline-flex justify-center items-center rounded-full bg-white/40 dark:bg-neutral-800/40 backdrop-blur-md border border-white/20 dark:border-white/10 text-gray-700 dark:text-neutral-200 hover:bg-white/60 dark:hover:bg-neutral-700/60 transition"
                            data-hs-overlay="#hs-cookies"
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

                    <!-- Content -->
                    <div
                        class="p-10 flex flex-col items-center gap-y-4 text-center"
                    >
                        <div
                            class="flex items-center justify-center size-14 rounded-full bg-green-500/10 text-green-600 dark:text-green-400"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="60"
                                height="60"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="lucide lucide-circle-question-mark-icon lucide-circle-question-mark"
                            >
                                <circle cx="12" cy="12" r="10" />
                                <path
                                    d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"
                                />
                                <path d="M12 17h.01" />
                            </svg>
                        </div>

                        <h3
                            id="hs-cookies-label"
                            class="-mt-2 text-2xl font-bold text-green-600 dark:text-green-400"
                        >
                            Unsaved Changes
                        </h3>

                        <p class="text-gray-600 dark:text-neutral-300 max-w-sm">
                            Are you sure you want to discard your changes? This
                            action cannot be undone.
                        </p>
                    </div>

                    <!-- Buttons -->
                    <div
                        class="flex border-t border-white/20 dark:border-white/10"
                    >
                        <button
                            type="button"
                            class="w-full py-3 text-sm font-semibold text-green-600 dark:text-green-400 hover:bg-green-500/10 transition"
                            data-hs-overlay="#hs-cookies"
                        >
                            Cancel
                        </button>

                        <button
                            type="button"
                            class="w-full py-3 text-sm font-semibold text-red-500 hover:bg-red-500/10 transition"
                            @click="confirmDiscard"
                        >
                            Discard Changes
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>

    <div class="max-w-6xl mx-auto p-6">
        <!-- Header -->

        <button
            @click="back"
            class="flex items-center gap-1 mb-6 text-sm text-green-600 dark:text-green-400 hover:underline"
        >
            ← Back
        </button>
        <div
            class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
        >
            <div class="flex gap-x-3 w-full lg:w-auto">
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
                        class="lucide lucide-user-round-icon lucide-user-round"
                    >
                        <circle cx="12" cy="8" r="5" />
                        <path d="M20 21a8 8 0 0 0-16 0" />
                    </svg>
                </div>
                <article>
                    <h1
                        class="text-2xl font-bold text-green-600 dark:text-green-400"
                    >
                        {{ localData.deceased.full_name }}
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Deceased Record Details
                    </p>
                </article>
            </div>

            <div class="w-full lg:w-auto">
                <!-- Archived: single green Recover button -->
                <template v-if="isArchived">
                    <div
                        class="flex flex-wrap gap-x-3 gap-y-2 items-center justify-end"
                    >
                        <button
                            v-if="userRole === 'clerk'"
                            @click="recoverBurialRecord"
                            class="flex items-center justify-center gap-x-2 px-4 py-2 rounded-xl border border-transparent bg-green-500/10 text-green-600 dark:text-green-400 hover:bg-green-500/20 hover:border-green-500/40 hover:text-green-700 dark:hover:text-green-300 transition-all duration-200"
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
                            Recover
                        </button>
                    </div>
                </template>

                <!-- Active: normal action set -->
                <template v-else>
                    <article
                        v-if="!editing"
                        class="flex flex-wrap gap-x-3 gap-y-2 items-center"
                    >
                        <button
                            @click="redirectToMap"
                            :disabled="
                                !burial_record.data.lot?.lot?.geometry?.coordinates
                                    ?.length
                            "
                            :class="{
                                'opacity-50 cursor-not-allowed disabled:hover:dark:bg-neutral-800':
                                    !burial_record.data.lot?.lot?.geometry
                                        ?.coordinates?.length,
                            }"
                            class="flex items-center justify-center gap-x-2 mt-4 px-4 py-2 rounded-xl border border-transparent dark:text-white dark:bg-neutral-800 hover:dark:bg-neutral-600 transition-all duration-200"
                        >
                            View on Map
                        </button>
                        <button
                            v-if="userRole === 'clerk'"
                            @click="editing = !editing"
                            class="flex items-center justify-center gap-x-2 px-4 mt-4 py-2 bg-green-500/10 text-green-400 rounded-xl border border-transparent hover:bg-green-500/20 hover:border-green-500/40 hover:text-green-600 dark:hover:text-green-300 transition-all duration-200"
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
                                class="lucide lucide-pencil-icon lucide-pencil"
                            >
                                <path
                                    d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"
                                />
                                <path d="m15 5 4 4" />
                            </svg>
                            Edit
                        </button>

                        <button
                            v-if="userRole === 'clerk'"
                            @click="openArchiveModal"
                            class="flex items-center justify-center gap-x-2 mt-4 px-4 py-2 rounded-xl border border-transparent bg-red-500/10 text-red-500 hover:bg-red-500/20 hover:border-red-500/40 transition-all duration-200"
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
                            Archive
                        </button>

                        <Link
                            v-if="userRole === 'clerk'"
                            :href="
                                route(
                                    'clerk.certificate_of_service.show',
                                    burial_record.data.burial.id,
                                )
                            "
                            class="flex items-center justify-center gap-x-2 mt-4 px-4 py-2 rounded-xl border border-transparent dark:text-white dark:bg-neutral-800 hover:dark:bg-neutral-600 transition-all duration-200"
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
                                class="lucide lucide-file-text-icon lucide-file-text"
                            >
                                <path
                                    d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"
                                />
                                <path d="M14 2v4a2 2 0 0 0 2 2h4" />
                                <path d="M10 9H8" />
                                <path d="M16 13H8" />
                                <path d="M16 17H8" />
                            </svg>
                            COS
                        </Link>
                    </article>
                    <div class="flex flex-wrap gap-x-3 gap-y-2">
                        <button
                            v-if="editing"
                            :class="{
                                'opacity-50 cursor-not-allowed': !hasChanges,
                            }"
                            @click="saveChanges"
                            class="flex items-center justify-center gap-x-2 mt-4 px-4 py-2 bg-green-500/10 text-green-400 rounded-xl border border-transparent hover:bg-green-500/20 hover:border-green-500/40 hover:text-green-600 dark:hover:text-green-300 transition-all duration-200"
                        >
                            Save Changes
                        </button>
                        <button
                            v-if="editing"
                            @click="discardChanges"
                            class="flex items-center justify-center gap-x-2 mt-4 px-4 py-2 rounded-xl border border-transparent dark:bg-neutral-800 hover:dark:bg-neutral-600 transition-all duration-200"
                        >
                            Discard
                        </button>
                    </div>
                </template>
            </div>
        </div>

        <!-- Archived Banner -->
        <div
            v-if="isArchived"
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
                    <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566z" />
                </svg>
                <span class="text-sm font-semibold">Archived Record</span>
            </div>
            <p class="text-sm text-amber-800 dark:text-amber-300">
                <span v-if="burial_record.data.archived?.at">
                    Archived on
                    {{ new Date(burial_record.data.archived.at).toLocaleString() }}
                </span>
                <span v-if="burial_record.data.archived?.reason">
                    — Reason:
                    {{
                        burial_record.data.archived.reason === "pull_out"
                            ? "Pull Out"
                            : burial_record.data.archived.reason === "transfer"
                              ? "Transfer"
                              : burial_record.data.archived.reason === "expired"
                                ? "Expired"
                                : burial_record.data.archived.reason === "other"
                                  ? "Other"
                                  : burial_record.data.archived.reason
                    }}
                </span>
                <span
                    v-if="burial_record.data.archived?.by?.full_name"
                >
                    — By {{ burial_record.data.archived.by.full_name }}
                </span>
            </p>
            <p
                v-if="burial_record.data.archived?.notes"
                class="text-sm text-gray-700 dark:text-neutral-300 bg-white/60 dark:bg-neutral-800/50 rounded-lg px-3 py-2"
            >
                {{ burial_record.data.archived.notes }}
            </p>
        </div>

        <!-- Tabs -->
        <div
            class="mb-6 overflow-x-auto border-b border-gray-200 dark:border-neutral-700"
        >
            <div class="flex min-w-max">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    @click="activeTab = tab.key"
                    class="px-4 py-2 text-sm font-medium whitespace-nowrap transition"
                    :class="
                        activeTab === tab.key
                            ? 'border-b-2 border-green-500 text-green-600 dark:text-green-400'
                            : 'text-gray-500 dark:text-gray-400 hover:text-green-500'
                    "
                >
                    {{ tab.label }}
                </button>
            </div>
        </div>

        <!-- Card Container -->
        <div
            class="bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-700 rounded-xl shadow-md p-6 transition"
        >
            <!-- PERSONAL -->
            <div
                v-if="activeTab === 'personal'"
                class="grid grid-cols-1 md:grid-cols-2 gap-6"
            >
                <Display
                    label="First Name"
                    :modelValue="localData.deceased?.first_name"
                    :editing="editing"
                    :error="errors['deceased.first_name']"
                    @update:modelValue="
                        (val) => (localData.deceased.first_name = val)
                    "
                />

                <Display
                    label="Middle Name"
                    :modelValue="localData.deceased?.middle_name"
                    :editing="editing"
                    :error="errors['deceased.middle_name']"
                    @update:modelValue="
                        (val) => (localData.deceased.middle_name = val)
                    "
                />
                <Display
                    label="Last Name"
                    :modelValue="localData.deceased?.last_name"
                    :editing="editing"
                    :error="errors['deceased.last_name']"
                    @update:modelValue="
                        (val) => (localData.deceased.last_name = val)
                    "
                />
                <Display
                    label="Age"
                    :modelValue="localData.deceased?.age"
                    :editing="editing"
                    :error="errors['deceased.age']"
                    @update:modelValue="(val) => (localData.deceased.age = val)"
                />
                <Display
                    label="Date of Birth"
                    type="date"
                    :modelValue="localData.deceased?.birth?.date"
                    :editing="editing"
                    :error="errors['deceased.birth.date']"
                    @update:modelValue="
                        (val) => (localData.deceased.birth.date = val)
                    "
                />
                <Display
                    label="Civil Status"
                    :modelValue="localData.deceased?.civil_status"
                    :editing="editing"
                    @update:modelValue="
                        (val) => (localData.deceased.civil_status = val)
                    "
                />
                <Display
                    label="Religion"
                    :modelValue="localData.deceased?.religion"
                    :editing="editing"
                    @update:modelValue="
                        (val) => (localData.deceased.religion = val)
                    "
                />
                <Display
                    label="Nationality"
                    :modelValue="localData.deceased?.nationality"
                    :editing="editing"
                    @update:modelValue="
                        (val) => (localData.deceased.nationality = val)
                    "
                />
                <Display
                    label="Occupation"
                    :modelValue="localData.deceased?.occupation?.name"
                    :editing="editing"
                    @update:modelValue="
                        (val) => (localData.deceased.occupation.name = val)
                    "
                />
                <Display
                    label="Address"
                    :modelValue="localData.deceased?.address"
                    :editing="editing"
                    @update:modelValue="
                        (val) => (localData.deceased.address = val)
                    "
                />
                <Display
                    label="Part of LGBTQ"
                    :modelValue="localData.deceased?.lgbtq"
                    :editing="editing"
                    @update:modelValue="
                        (val) => (localData.deceased.lgbtq = val)
                    "
                />
                <Display
                    label="Precinct Number"
                    :modelValue="localData.deceased?.precinct_num"
                    :editing="editing"
                    :error="errors['deceased.precinct_num']"
                    @update:modelValue="
                        (val) => (localData.deceased.precinct_num = val)
                    "
                />
            </div>

            <!-- DEATH -->
            <div
                v-if="activeTab === 'death'"
                class="grid grid-cols-1 md:grid-cols-2 gap-6"
            >
                <Display
                    label="Date of Death"
                    type="date"
                    :modelValue="localData.deceased?.death?.date"
                    :editing="editing"
                    @update:modelValue="
                        (val) => (localData.deceased.death.date = val)
                    "
                />
                <Display
                    label="Cause of Death"
                    :modelValue="localData.deceased?.death?.cause"
                    :editing="editing"
                    @update:modelValue="
                        (val) => (localData.deceased.death.cause = val)
                    "
                />
                <Display
                    label="Place of Death"
                    :modelValue="localData.deceased?.death?.place"
                    :editing="editing"
                    @update:modelValue="
                        (val) => (localData.deceased.death.place = val)
                    "
                />
            </div>

            <!-- DISPOSITION -->
            <div
                v-if="activeTab === 'disposition'"
                class="grid grid-cols-1 md:grid-cols-2 gap-6"
            >
                <Display
                    label="Corpse Disposal"
                    :modelValue="localData.deceased?.corpse_disposal"
                    :editing="editing"
                    @update:modelValue="
                        (val) => (localData.deceased.corpse_disposal = val)
                    "
                />
                <Display
                    label="Cremation Place"
                    :modelValue="localData.deceased?.cremation?.place"
                    :editing="editing"
                    @update:modelValue="
                        (val) => (localData.deceased.cremation.place = val)
                    "
                />
                <Display
                    label="Cremation Date"
                    type="date"
                    :modelValue="localData.deceased?.cremation?.date"
                    :editing="editing"
                    @update:modelValue="
                        (val) => (localData.deceased.cremation.date = val)
                    "
                />
                <Display
                    label="Burial Place"
                    :modelValue="localData.deceased?.burial_place"
                    :editing="editing"
                    @update:modelValue="
                        (val) => (localData.deceased.burial_place = val)
                    "
                />

                <Display
                    label="Date of Depository"
                    placeholder="YYYY-MM-DD"
                    type="date"
                    :modelValue="localData.deceased?.burial?.date"
                    :editing="editing"
                    :error="errors['deceased.burial.date']"
                    @update:modelValue="
                        (val) => (localData.deceased.burial.date = val)
                    "
                />

                <Display
                    label="Time of Depository"
                    placeholder="HH:MM"
                    type="time"
                    :modelValue="localData.deceased?.burial?.time"
                    :editing="editing"
                    :error="errors['deceased.burial.time']"
                    @update:modelValue="
                        (val) => (localData.deceased.burial.time = val)
                    "
                />
            </div>

            <!-- FAMILY -->
            <div
                v-if="activeTab === 'family'"
                class="grid grid-cols-1 md:grid-cols-2 gap-6"
            >
                <Display
                    label="Father's Name"
                    :modelValue="localData.deceased?.family?.father"
                    :editing="editing"
                    @update:modelValue="
                        (val) => (localData.deceased.family.father = val)
                    "
                />
                <Display
                    label="Mother's Maiden Name"
                    :modelValue="localData.deceased?.family?.mother_maiden"
                    :editing="editing"
                    @update:modelValue="
                        (val) => (localData.deceased.family.mother_maiden = val)
                    "
                />
                <Display
                    label="Company Address"
                    :modelValue="localData.deceased?.occupation?.address"
                    :editing="editing"
                    @update:modelValue="
                        (val) => (localData.deceased.occupation.address = val)
                    "
                />
                <Display
                    label="Company Supervisor Name"
                    :modelValue="localData.deceased?.occupation?.supervisor"
                    :editing="editing"
                    @update:modelValue="
                        (val) =>
                            (localData.deceased.occupation.supervisor = val)
                    "
                />
            </div>

            <!-- APPLICANT -->
            <div
                v-if="activeTab === 'applicant'"
                class="grid grid-cols-1 md:grid-cols-2 gap-6"
            >
                <Display
                    label="Applicant First Name"
                    :modelValue="localData.deceased?.applicant?.first_name"
                    :editing="editing"
                    :error="errors['deceased.applicant.first_name']"
                    @update:modelValue="
                        (val) => (localData.deceased.applicant.first_name = val)
                    "
                />
                <Display
                    label="Applicant Middle Name"
                    :modelValue="localData.deceased?.applicant?.middle_name"
                    :editing="editing"
                    :error="errors['deceased.applicant.middle_name']"
                    @update:modelValue="
                        (val) =>
                            (localData.deceased.applicant.middle_name = val)
                    "
                />
                <Display
                    label="Applicant Last Name"
                    :modelValue="localData.deceased?.applicant?.last_name"
                    :editing="editing"
                    :error="errors['deceased.applicant.last_name']"
                    @update:modelValue="
                        (val) => (localData.deceased.applicant.last_name = val)
                    "
                />
                <Display
                    label="Contact Number"
                    :modelValue="localData.deceased?.applicant?.contact_number"
                    :editing="editing"
                    :error="errors['deceased.applicant.contact_number']"
                    @update:modelValue="
                        (val) =>
                            (localData.deceased.applicant.contact_number = val)
                    "
                />
                <Display
                    label="Relationship to Deceased"
                    :modelValue="localData.deceased?.applicant?.relationship"
                    :editing="editing"
                    :error="errors['deceased.applicant.relationship']"
                    @update:modelValue="
                        (val) =>
                            (localData.deceased.applicant.relationship = val)
                    "
                />
            </div>

            <!-- LOCATION -->
            <div
                v-if="activeTab === 'location'"
                class="grid grid-cols-1 md:grid-cols-2 gap-6"
            >
                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                    >
                        Phase
                    </label>
                    <select
                        v-if="editing"
                        v-model="selectedPhaseId"
                        :class="{
                            'border-red-500 focus:ring-red-500':
                                errors['lot_id'],
                        }"
                        class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-neutral-600 bg-white dark:bg-neutral-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-green-500"
                    >
                        <option value="">Select a phase</option>
                        <option
                            v-for="phase in phases"
                            :key="phase.id"
                            :value="phase.id"
                        >
                            {{ phase.name }}
                        </option>
                    </select>
                    <p v-else class="text-gray-900 dark:text-gray-100">
                        {{
                            localData.cluster?.cluster?.properties?.phase ||
                            "N/A"
                        }}
                    </p>
                </div>

                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                    >
                        Cluster
                    </label>
                    <select
                        v-if="editing"
                        v-model="selectedClusterId"
                        :disabled="!selectedPhaseId"
                        :class="{
                            'border-red-500 focus:ring-red-500':
                                errors['lot_id'],
                        }"
                        class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-neutral-600 bg-white dark:bg-neutral-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-green-500 disabled:opacity-50"
                    >
                        <option value="">Select a cluster</option>
                        <option
                            v-for="cluster in availableClusters"
                            :key="cluster.id"
                            :value="cluster.id"
                        >
                            {{ cluster.name }}
                        </option>
                    </select>
                    <p v-else class="text-gray-900 dark:text-gray-100">
                        {{
                            localData.cluster?.cluster?.properties?.name ||
                            "N/A"
                        }}
                    </p>
                </div>

                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                    >
                        Lot
                    </label>
                    <select
                        v-if="editing"
                        v-model="selectedLotId"
                        :disabled="!selectedClusterId"
                        :class="{
                            'border-red-500 focus:ring-red-500':
                                errors['lot_id'],
                        }"
                        class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-neutral-600 bg-white dark:bg-neutral-900 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-green-500 disabled:opacity-50"
                    >
                        <option value="">Select a lot</option>
                        <option
                            v-for="lot in availableLots"
                            :key="lot.id"
                            :value="lot.id"
                        >
                            {{ lot.column }} - {{ lot.row }}
                        </option>
                    </select>
                    <p v-else class="text-gray-900 dark:text-gray-100">
                        {{ localData.lot?.lot?.properties?.column }} -
                        {{ localData.lot?.lot?.properties?.row || "N/A" }}
                    </p>
                    <p
                        v-if="editing && errors['lot_id']"
                        class="mt-1 text-sm text-red-500"
                    >
                        {{ errors["lot_id"] }}
                    </p>
                </div>

                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                    >
                        Cluster Type
                    </label>
                    <p class="text-gray-900 dark:text-gray-100">
                        {{
                            editing
                                ? selectedClusterType ||
                                  "Select a cluster first"
                                : localData.cluster?.cluster?.properties
                                      ?.type || "N/A"
                        }}
                    </p>
                </div>

                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                    >
                        Column
                    </label>
                    <p class="text-gray-900 dark:text-gray-100">
                        {{
                            editing
                                ? selectedLotColumn || "Select a lot first"
                                : localData.lot?.lot?.properties?.column ||
                                  "N/A"
                        }}
                    </p>
                </div>

                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                    >
                        Row
                    </label>
                    <p class="text-gray-900 dark:text-gray-100">
                        {{
                            editing
                                ? selectedLotRow || "Select a lot first"
                                : localData.lot?.lot?.properties?.row || "N/A"
                        }}
                    </p>
                </div>
            </div>

            <!-- IMPORTED BY -->
            <div
                v-if="activeTab === 'imported'"
                class="grid grid-cols-1 md:grid-cols-2 gap-6"
            >
                <Display
                    label="Imported/Created By"
                    :modelValue="
                        burial_record.data.imported_by
                            ? burial_record.data.imported_by.full_name
                            : 'N/A'
                    "
                    :editing="false"
                />

                <Display
                    label="Role"
                    :modelValue="
                        burial_record.data.imported_by
                            ? burial_record.data.imported_by.role
                            : 'N/A'
                    "
                    :editing="false"
                />
            </div>
        </div>
    </div>

    <!-- Archive Modal -->
    <Teleport to="body">
        <div
            id="archive-modal"
            class="hs-overlay hidden size-full fixed top-0 start-0 z-2000 overflow-x-hidden overflow-y-auto bg-black/40 backdrop-blur-sm"
            role="dialog"
            tabindex="-1"
            aria-labelledby="archive-modal-label"
            @click.self="closeArchiveModal"
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
                            @click="closeArchiveModal"
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
                            class="flex items-center justify-center size-14 rounded-full bg-amber-500/10 text-amber-600 dark:text-amber-400"
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
                                <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8" />
                                <polyline points="16 6 12 2 8 6" />
                                <line x1="12" x2="12" y1="2" y2="15" />
                            </svg>
                        </div>

                        <h3
                            id="archive-modal-label"
                            class="-mt-2 text-2xl font-bold text-amber-600 dark:text-amber-400"
                        >
                            Archive Burial Record
                        </h3>

                        <p
                            class="text-gray-600 dark:text-neutral-300 max-w-sm text-sm"
                        >
                            This will hide the record from the main list. It
                            will only appear under the
                            <span class="font-semibold">Archived</span> filter
                            and can be recovered later.
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
                                v-model="archiveForm.archived_reason"
                                @change="
                                    archiveClientErrors.archived_reason = '';
                                    archiveClientErrors.archived_notes = '';
                                    archiveServerErrors.archived_reason = '';
                                    archiveServerErrors.archived_notes = '';
                                "
                                :class="{
                                    'border-red-500 focus:ring-red-500 focus:border-red-500':
                                        archiveClientErrors.archived_reason ||
                                        archiveServerErrors.archived_reason ||
                                        errors.archived_reason,
                                }"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-neutral-600 bg-white dark:bg-neutral-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-amber-500"
                            >
                                <option value="">Select reason</option>
                                <option value="pull_out">Pull Out</option>
                                <option value="transfer">Transfer</option>
                                <option value="expired">Expired</option>
                                <option value="other">Other</option>
                            </select>
                            <p
                                v-if="
                                    archiveClientErrors.archived_reason ||
                                    archiveServerErrors.archived_reason ||
                                    errors.archived_reason
                                "
                                class="mt-1 text-sm text-red-500"
                            >
                                {{
                                    archiveClientErrors.archived_reason ||
                                    archiveServerErrors.archived_reason ||
                                    errors.archived_reason
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
                                        archiveForm.archived_reason === 'other'
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
                                v-model="archiveForm.archived_notes"
                                @input="
                                    archiveClientErrors.archived_notes = '';
                                    archiveServerErrors.archived_notes = '';
                                "
                                rows="3"
                                placeholder="Provide details..."
                                :class="{
                                    'border-red-500 focus:ring-red-500 focus:border-red-500':
                                        archiveClientErrors.archived_notes ||
                                        archiveServerErrors.archived_notes ||
                                        errors.archived_notes,
                                }"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-neutral-600 bg-white dark:bg-neutral-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-amber-500 resize-none"
                            ></textarea>
                            <p
                                v-if="
                                    archiveClientErrors.archived_notes ||
                                    archiveServerErrors.archived_notes ||
                                    errors.archived_notes
                                "
                                class="mt-1 text-sm text-red-500"
                            >
                                {{
                                    archiveClientErrors.archived_notes ||
                                    archiveServerErrors.archived_notes ||
                                    errors.archived_notes
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
                            @click="closeArchiveModal"
                        >
                            Cancel
                        </button>

                        <button
                            type="button"
                            class="w-full py-3 text-sm font-semibold text-amber-600 dark:text-amber-400 hover:bg-amber-500/10 transition"
                            @click="archiveBurialRecord"
                        >
                            Archive
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Restore Modal -->
    <Teleport to="body">
        <div
            id="restore-modal"
            class="hs-overlay hidden size-full fixed top-0 start-0 z-2000 overflow-x-hidden overflow-y-auto bg-black/40 backdrop-blur-sm"
            role="dialog"
            tabindex="-1"
            aria-labelledby="restore-modal-label"
            @click.self="closeRestoreModal"
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
                            @click="closeRestoreModal"
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
                            id="restore-modal-label"
                            class="-mt-2 text-2xl font-bold text-green-600 dark:text-green-400"
                        >
                            Restore Burial Record
                        </h3>

                        <p
                            class="text-gray-600 dark:text-neutral-300 max-w-sm text-sm"
                        >
                            This will restore the archived record to the main
                            list. It will no longer appear under the
                            <span class="font-semibold">Archived</span> filter.
                        </p>
                    </div>

                    <div class="px-6 pb-4 flex flex-col gap-3">
                        <div
                            class="w-full bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4 text-left space-y-2"
                        >
                            <p
                                class="text-sm text-gray-600 dark:text-neutral-300"
                            >
                                You are about to restore
                                <span
                                    class="font-semibold text-green-700 dark:text-green-300"
                                    >{{
                                        burial_record.data.deceased?.full_name ||
                                        localData.deceased?.full_name ||
                                        "this burial record"
                                    }}</span
                                >
                                . The record will be moved from
                                <span class="font-semibold">Archived</span> back
                                to the main list.
                            </p>

                            <div
                                class="grid grid-cols-1 gap-1.5 pt-2 border-t border-green-200 dark:border-green-800"
                            >
                                <div class="flex justify-between">
                                    <span
                                        class="text-sm font-medium text-gray-500 dark:text-neutral-400"
                                        >Phase:</span
                                    >
                                    <span
                                        class="text-sm font-semibold text-gray-900 dark:text-white"
                                        >{{
                                            burial_record.data.cluster?.cluster
                                                ?.properties?.phase ||
                                            localData.cluster?.cluster
                                                ?.properties?.phase ||
                                            "N/A"
                                        }}</span
                                    >
                                </div>
                                <div class="flex justify-between">
                                    <span
                                        class="text-sm font-medium text-gray-500 dark:text-neutral-400"
                                        >Cluster:</span
                                    >
                                    <span
                                        class="text-sm font-semibold text-gray-900 dark:text-white"
                                        >{{
                                            burial_record.data.cluster?.cluster
                                                ?.properties?.name ||
                                            localData.cluster?.cluster
                                                ?.properties?.name ||
                                            "N/A"
                                        }}</span
                                    >
                                </div>
                                <div class="flex justify-between">
                                    <span
                                        class="text-sm font-medium text-gray-500 dark:text-neutral-400"
                                        >Lot:</span
                                    >
                                    <span
                                        class="text-sm font-semibold text-gray-900 dark:text-white"
                                        >{{
                                            burial_record.data.lot?.lot
                                                ?.properties?.column &&
                                            burial_record.data.lot?.lot
                                                ?.properties?.row
                                                ? burial_record.data.lot.lot
                                                      .properties.column +
                                                  "-" +
                                                  burial_record.data.lot.lot
                                                      .properties.row
                                                : "Unassigned"
                                        }}</span
                                    >
                                </div>
                            </div>

                            <p
                                class="text-xs text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg px-3 py-2"
                            >
                                If this lot is currently occupied, the record
                                will be restored as
                                <span class="font-semibold">Unassigned</span>
                                (no lot). You can assign a new lot afterwards.
                                To keep the original lot, cancel (X) and free
                                up the space first.
                            </p>
                        </div>
                    </div>

                    <div
                        class="flex border-t border-white/20 dark:border-white/10"
                    >
                        <button
                            type="button"
                            class="w-full py-3 text-sm font-semibold text-green-600 dark:text-green-400 hover:bg-green-500/10 transition"
                            @click="confirmRestore"
                        >
                            Restore
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
