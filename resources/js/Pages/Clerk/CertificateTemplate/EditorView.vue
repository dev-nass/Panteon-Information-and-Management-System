<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref } from "vue";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useToast } from "vue-toast-notification";
import { Canvas, Rect } from "fabric";

import Button from "@/Components/Form/Button.vue";
import Dashboard from "@/Layouts/Dashboard.vue";
import { COS_FIELDS } from "@/lib/cosFields";
import { renderPdfPages } from "@/lib/pdfRender";

defineOptions({
    layout: Dashboard,
});

const props = defineProps({
    template: Object,
});

const toast = useToast();
const containers = ref([]);
const pageCount = ref(0);
const selectedBox = ref(null);
const isAddingBox = ref(true);
const isSaving = ref(false);
const isLoading = ref(true);
const loadError = ref("");

const fabricCanvases = [];
const boxes = [];
const scale = 1.5;

onMounted(async () => {
    try {
        const response = await fetch(
            route("clerk.certificate_templates.file", props.template.id),
        );
        if (!response.ok) {
            throw new Error("Failed to load template file");
        }
        const bytes = await response.arrayBuffer();
        const pages = await renderPdfPages(bytes);

        pageCount.value = pages.length;
        isLoading.value = false;
        await nextTick();

        for (const page of pages) {
            const container = containers.value[page.pageIndex];
            if (!container) continue;

            container.style.width = page.canvas.width + "px";
            container.style.height = page.canvas.height + "px";

            const pdfCanvas = document.createElement("canvas");
            pdfCanvas.width = page.canvas.width;
            pdfCanvas.height = page.canvas.height;
            pdfCanvas.style.display = "block";
            pdfCanvas.getContext("2d").drawImage(page.canvas, 0, 0);
            container.appendChild(pdfCanvas);

            const fabricEl = document.createElement("canvas");
            fabricEl.width = page.canvas.width;
            fabricEl.height = page.canvas.height;
            container.appendChild(fabricEl);

            const canvas = new Canvas(fabricEl, {
                selection: false,
                preserveObjectStacking: true,
                width: page.canvas.width,
                height: page.canvas.height,
            });

            const fabricWrapper = fabricEl.parentElement;
            if (fabricWrapper && fabricWrapper !== container) {
                fabricWrapper.style.position = "absolute";
                fabricWrapper.style.top = "0";
                fabricWrapper.style.left = "0";
            }

            fabricCanvases.push(canvas);
            setupPageCanvas(canvas, page);
            loadSavedBoxes(canvas, page);
        }
    } catch (e) {
        console.error("Template editor load failed:", e);
        loadError.value = "Unable to render this PDF. Please try another file.";
        isLoading.value = false;
    }
});

const setupPageCanvas = (canvas, page) => {
    canvas.on("mouse:down", (opt) => {
        if (!isAddingBox.value) return;
        if (opt.target) return;

        const pointer = canvas.getScenePoint(opt.e);
        const rect = new Rect({
            left: pointer.x,
            top: pointer.y,
            width: 0.1,
            height: 0.1,
            fill: "rgba(16, 185, 129, 0.15)",
            stroke: "#10b981",
            strokeWidth: 2,
            field: null,
            page: page.pageIndex,
        });
        canvas.add(rect);

        const onMove = (moveOpt) => {
            const p = canvas.getScenePoint(moveOpt.e);
            rect.set({
                width: Math.abs(p.x - pointer.x),
                height: Math.abs(p.y - pointer.y),
                left: Math.min(p.x, pointer.x),
                top: Math.min(p.y, pointer.y),
            });
            canvas.requestRenderAll();
        };

        const onUp = () => {
            canvas.off("mouse:move", onMove);
            canvas.off("mouse:up", onUp);

            if (rect.width < 8 || rect.height < 8) {
                const pageWidth = page.pdfWidth * scale;
                const pageHeight = page.pdfHeight * scale;
                rect.set({
                    left: Math.min(Math.max(pointer.x, 0), pageWidth - 220),
                    top: Math.min(Math.max(pointer.y, 0), pageHeight - 26),
                    width: 220,
                    height: 26,
                });
            }

            const box = { canvas, rect, page: page.pageIndex, field: null };
            boxes.push(box);
            selectBox(box);
        };

        canvas.on("mouse:move", onMove);
        canvas.on("mouse:up", onUp);
    });

    canvas.on("selection:created", ({ selected }) => {
        const box = boxes.find((b) => b.rect === selected[0]);
        if (box) selectBox(box);
    });

    canvas.on("selection:updated", ({ selected }) => {
        const box = boxes.find((b) => b.rect === selected[0]);
        if (box) selectBox(box);
    });

    canvas.on("selection:cleared", () => {
        selectedBox.value = null;
    });
};

const loadSavedBoxes = (canvas, page) => {
    for (const saved of props.template.fields ?? []) {
        if (saved.page !== page.pageIndex) continue;

        const rect = new Rect({
            left: saved.x * scale,
            top: saved.y * scale,
            width: saved.w * scale,
            height: saved.h * scale,
            fill: "rgba(16, 185, 129, 0.15)",
            stroke: "#10b981",
            strokeWidth: 2,
            field: saved.field,
            page: page.pageIndex,
        });
        canvas.add(rect);
        boxes.push({ canvas, rect, page: page.pageIndex, field: saved.field });
    }
};

const selectBox = (box) => {
    selectedBox.value = box;
    box.canvas.setActiveObject(box.rect);
    box.canvas.requestRenderAll();
};

const styleBox = (box) => {
    const assigned = box.field !== null;
    box.rect.set({
        fill: assigned
            ? "rgba(16, 185, 129, 0.15)"
            : "rgba(239, 68, 68, 0.10)",
        stroke: assigned ? "#10b981" : "#ef4444",
        strokeDashArray: assigned ? null : [5, 4],
    });
    box.canvas.requestRenderAll();
};

const assignField = (field) => {
    if (!selectedBox.value || !field) return;

    const existing = boxes.find(
        (b) => b !== selectedBox.value && b.field === field,
    );
    if (existing) {
        existing.field = null;
        existing.rect.set("field", null);
        styleBox(existing);
    }

    selectedBox.value.field = field;
    selectedBox.value.rect.set("field", field);
    styleBox(selectedBox.value);
};

const deleteSelectedBox = () => {
    if (!selectedBox.value) return;

    selectedBox.value.canvas.remove(selectedBox.value.rect);
    boxes.splice(boxes.indexOf(selectedBox.value), 1);
    selectedBox.value = null;
};

const unassignedCount = () => boxes.filter((b) => !b.field).length;

const boxCountForPage = (page) =>
    boxes.filter((b) => b.page === page).length;

const saveFields = () => {
    const unassigned = unassignedCount();
    if (unassigned > 0) {
        toast.warning(`${unassigned} box(es) have no field assigned yet.`, {
            position: "top-right",
        });
    }

    isSaving.value = true;

    const fields = boxes.map((b) => ({
        field: b.field,
        page: b.page,
        x: Number((b.rect.left / scale).toFixed(2)),
        y: Number((b.rect.top / scale).toFixed(2)),
        w: Number((b.rect.width / scale).toFixed(2)),
        h: Number((b.rect.height / scale).toFixed(2)),
    }));

    router.put(
        route("clerk.certificate_templates.fields", props.template.id),
        { fields },
        {
            onSuccess: () => {
                isSaving.value = false;
                toast.success("Template fields saved.", {
                    position: "top-right",
                    duration: 5000,
                });
            },
            onError: (errors) => {
                isSaving.value = false;
                toast.error(
                    errors.fields || "Failed to save template fields.",
                    {
                        position: "top-right",
                        duration: 5000,
                    },
                );
            },
        },
    );
};

onBeforeUnmount(() => {
    for (const canvas of fabricCanvases) {
        canvas.dispose();
    }
});
</script>

<template>
    <div class="max-w-[75rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-6 mx-auto">
        <div class="flex flex-col items-center" data-aos="zoom-out">
            <div
                class="w-full flex flex-col gap-y-6 bg-white/60 dark:bg-neutral-900/60 backdrop-blur-xl px-6 py-6 border border-white/20 dark:border-neutral-700 rounded-xl shadow-lg"
            >
                <!-- Header -->
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1
                            class="text-2xl font-bold text-green-600 dark:text-green-400"
                        >
                            {{ template.name }}
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Drag a box over each spot where a value should be
                            printed, then assign its field.
                        </p>
                    </div>
                    <Button
                        type="button"
                        @click="router.visit(route('clerk.certificate_templates.index'))"
                    >
                        Back to Templates
                    </Button>
                </div>

                <!-- Toolbar -->
                <div
                    class="flex flex-wrap items-center gap-3 p-4 rounded-xl border border-gray-200 dark:border-neutral-700 bg-white/50 dark:bg-neutral-900/40"
                >
                    <button
                        type="button"
                        @click="isAddingBox = !isAddingBox"
                        :class="[
                            'px-3 py-2 text-sm font-medium rounded-lg border transition',
                            isAddingBox
                                ? 'bg-green-500/10 text-green-600 dark:text-green-400 border-green-500/40'
                                : 'text-gray-600 dark:text-gray-300 border-gray-300 dark:border-neutral-700 hover:border-green-500',
                        ]"
                    >
                        {{ isAddingBox ? "Add Box: ON" : "Add Box: OFF" }}
                    </button>

                    <template v-if="selectedBox">
                        <span
                            class="text-sm text-gray-500 dark:text-gray-400"
                        >
                            Selected box:
                        </span>
                        <select
                            :value="selectedBox.field ?? ''"
                            @change="assignField($event.target.value)"
                            class="px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-neutral-700 bg-white dark:bg-neutral-900 text-gray-800 dark:text-gray-200 focus:border-green-500 focus:ring-2 focus:ring-green-500 outline-none"
                        >
                            <option value="" disabled>
                                -- choose a field --
                            </option>
                            <option
                                v-for="f in COS_FIELDS"
                                :key="f.key"
                                :value="f.key"
                            >
                                {{ f.label }}
                            </option>
                        </select>
                        <button
                            type="button"
                            @click="deleteSelectedBox"
                            class="px-3 py-2 text-sm font-medium rounded-lg text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition"
                        >
                            Delete Box
                        </button>
                    </template>

                    <span
                        v-else
                        class="text-sm text-gray-500 dark:text-gray-400"
                    >
                        Click on the page to place a box, or drag to size
                        it.
                    </span>

                    <div class="ms-auto flex items-center gap-3">
                        <span
                            v-if="unassignedCount() > 0"
                            class="text-xs font-medium px-2 py-1 rounded-full bg-red-500/10 text-red-600 dark:text-red-400"
                        >
                            {{ unassignedCount() }} unassigned
                        </span>
                        <Button
                            type="button"
                            :highlighted="true"
                            :disabled="isSaving"
                            @click="saveFields"
                        >
                            <span v-if="isSaving">Saving...</span>
                            <span v-else>Save Fields</span>
                        </Button>
                    </div>
                </div>

                <!-- Error -->
                <p
                    v-if="loadError"
                    class="text-sm text-red-600 dark:text-red-400"
                >
                    {{ loadError }}
                </p>

                <!-- Loading -->
                <div
                    v-if="isLoading"
                    class="animate-pulse space-y-4"
                    aria-label="Loading template"
                >
                    <div
                        class="h-10 bg-gray-200 dark:bg-neutral-700 rounded-md"
                    ></div>
                    <div
                        class="h-10 bg-gray-200 dark:bg-neutral-700 rounded-md"
                    ></div>
                </div>

                <!-- Pages -->
                <div v-if="!isLoading && !loadError" class="space-y-6">
                    <div
                        v-for="pageIndex in pageCount"
                        :key="pageIndex"
                        class="relative border border-gray-200 dark:border-neutral-700 rounded-lg overflow-hidden bg-white dark:bg-neutral-800"
                    >
                        <div
                            class="flex items-center justify-between px-3 py-2 bg-gray-50 dark:bg-neutral-800 border-b border-gray-200 dark:border-neutral-700"
                        >
                            <span
                                class="text-xs font-medium text-gray-500 dark:text-gray-400"
                            >
                                Page {{ pageIndex }}
                            </span>
                            <span
                                class="text-xs text-gray-400 dark:text-gray-500"
                            >
                                {{ boxCountForPage(pageIndex - 1) }} boxes
                            </span>
                        </div>
                        <div class="overflow-x-auto">
                            <div
                                :ref="(el) => (containers[pageIndex - 1] = el)"
                                class="relative w-fit mx-auto"
                            ></div>
                        </div>
                    </div>
                </div>

                <!-- Legend -->
                <div
                    class="flex flex-wrap items-center gap-4 text-xs text-gray-500 dark:text-gray-400 px-1"
                >
                    <span class="flex items-center gap-1.5">
                        <span
                            class="inline-block size-3 rounded-sm bg-green-500/20 border border-green-500"
                        ></span>
                        Assigned field
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span
                            class="inline-block size-3 rounded-sm bg-red-500/10 border border-dashed border-red-500"
                        ></span>
                        No field yet
                    </span>
                    <span>{{ COS_FIELDS.length }} fields available</span>
                </div>
            </div>
        </div>
    </div>
</template>