<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import Dashboard from "@/Layouts/Dashboard.vue";
import FullCalendar from "@fullcalendar/vue3";
import dayGridPlugin from "@fullcalendar/daygrid";
import interactionPlugin from "@fullcalendar/interaction";

const props = defineProps({
    burialSchedules: Array,
});

const calendarOptions = ref({
    plugins: [dayGridPlugin, interactionPlugin],
    initialView: "dayGridMonth",
    headerToolbar: {
        left: "prev,next",
        center: "title",
        right: "dayGridDay,dayGridMonth,dayGridYear",
    },
    views: {
        dayGridYear: {
            type: "dayGrid",
            duration: { years: 1 },
            buttonText: "Yearly",
        },
        dayGridMonth: {
            buttonText: "Monthly",
        },
        dayGridDay: {
            type: "dayGrid",
            duration: { days: 1 },
            buttonText: "Today",
        },
    },
    events: props.burialSchedules,
    eventClick: (info) => {
        router.visit(route("clerk.burial_records.show", info.event.id));
    },
    height: "auto",
    eventDisplay: "block",
    eventBackgroundColor: "transparent",
    eventBorderColor: "#16a34a",
    eventTextColor: "#16a34a",
});

defineOptions({
    layout: Dashboard,
});
</script>

<template>
    <div class="max-w-340 px-4 py-10 sm:px-6 lg:px-8 lg:py-6 mx-auto">
        <div class="flex flex-col">
            <div class="-m-1.5 overflow-x-auto">
                <div class="p-1.5 min-w-full inline-block align-middle">
                    <div
                        class="bg-white dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-xl shadow-2xs overflow-hidden"
                    >
                        <div class="p-6 overflow-x-auto">
                            <div class="min-w-200">
                                <FullCalendar :options="calendarOptions" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
:root {
    --fc-border-color: #d1d5db;
    --fc-page-bg-color: #ffffff;
    --fc-neutral-bg-color: #f9fafb;
}

.dark {
    --fc-border-color: #404040;
    --fc-page-bg-color: #171717;
    --fc-neutral-bg-color: #262626;
}

.fc {
    font-family: inherit;
}

/* Custom button styling */
.fc .fc-button {
    text-transform: capitalize;
    font-weight: 500;
    background: rgba(34, 197, 94, 0.1) !important;
    color: #22c55e !important;
    border: 1px solid transparent !important;
    padding: 0.5rem 0.75rem;
    border-radius: 0.5rem;
    transition: all 0.2s;
}

.fc .fc-button:hover {
    background: rgba(34, 197, 94, 0.2) !important;
    border-color: rgba(34, 197, 94, 0.4) !important;
    color: #16a34a !important;
}

.fc .fc-button:focus {
    box-shadow: none !important;
}

.fc .fc-button-active {
    background: rgba(34, 197, 94, 0.25) !important;
    border-color: rgba(34, 197, 94, 0.5) !important;
    color: #15803d !important;
}

.fc .fc-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.dark .fc .fc-button {
    color: #4ade80 !important;
}

.dark .fc .fc-button:hover {
    color: #22c55e !important;
}

.dark .fc .fc-button-active {
    color: #16a34a !important;
}

/* Title styling */
.fc .fc-toolbar-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1f2937;
}

.dark .fc .fc-toolbar-title {
    color: #e5e7eb;
}

/* Calendar background */
.fc .fc-scrollgrid {
    border-color: var(--fc-border-color) !important;
    background-color: var(--fc-page-bg-color);
}

.dark .fc-theme-standard td,
.dark .fc-theme-standard th {
    border-color: var(--fc-border-color);
}

/* Header cells */
.fc .fc-col-header-cell {
    background-color: var(--fc-neutral-bg-color);
    font-weight: 600;
    padding: 0.75rem 0.5rem;
}

.fc .fc-col-header-cell-cushion {
    color: #6b7280;
}

.dark .fc .fc-col-header-cell-cushion {
    color: #9ca3af;
}

/* Day cells */
.fc .fc-daygrid-day {
    background-color: var(--fc-page-bg-color);
}

.fc .fc-daygrid-day:hover {
    background-color: var(--fc-neutral-bg-color);
}

.fc .fc-daygrid-day-number {
    color: #374151;
    padding: 0.5rem;
    font-weight: 500;
}

.dark .fc .fc-daygrid-day-number {
    color: #d1d5db;
}

/* Event styling - outlined with border */
.fc-event {
    background-color: rgba(34, 197, 94, 0.1) !important;
    border: 1.5px solid #22c55e !important;
    border-radius: 0.375rem;
    padding: 2px 4px;
    margin: 2px 0;
    cursor: pointer;
    transition: all 0.2s;
    overflow: hidden;
}

.fc-event:hover {
    background-color: rgba(34, 197, 94, 0.2) !important;
    border-color: #16a34a !important;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(34, 197, 94, 0.2);
}

.fc-event .fc-event-title {
    color: #16a34a;
    font-weight: 500;
    font-size: 0.875rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: block;
}

.fc-event .fc-event-main {
    overflow: hidden;
}

.fc-daygrid-event-harness {
    overflow: hidden;
}

.dark .fc-event {
    background-color: rgba(34, 197, 94, 0.15) !important;
    border-color: #4ade80 !important;
}

.dark .fc-event:hover {
    background-color: rgba(34, 197, 94, 0.25) !important;
    border-color: #22c55e !important;
}

.dark .fc-event .fc-event-title {
    color: #4ade80;
}

/* Today highlight */
.fc .fc-day-today {
    background-color: rgba(0, 0, 0, 0.05) !important;
}

.dark .fc .fc-day-today {
    background-color: rgba(255, 255, 255, 0.05) !important;
}
</style>
