<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import Dashboard from '@/Layouts/Dashboard.vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';

const props = defineProps({
    burialSchedules: Array
});

const calendarOptions = ref({
    plugins: [dayGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,dayGridYear,dayGridDay'
    },
    views: {
        dayGridYear: {
            type: 'dayGrid',
            duration: { years: 1 },
            buttonText: 'Year'
        },
        dayGridDay: {
            type: 'dayGrid',
            duration: { days: 1 },
            buttonText: 'Today'
        }
    },
    events: props.burialSchedules,
    eventClick: (info) => {
        alert(`Deceased: ${info.event.extendedProps.deceased_name}\nLot: ${info.event.extendedProps.lot_info}\nBurial Date: ${info.event.start.toLocaleDateString()}`);
    },
    height: 'auto',
    eventColor: '#16a34a'
});

defineOptions({
    layout: Dashboard
});
</script>

<template>
    <div class="max-w-340 px-4 py-10 sm:px-6 lg:px-8 lg:py-6 mx-auto">
        <div class="flex flex-col">
            <div class="-m-1.5 overflow-x-auto">
                <div class="p-1.5 min-w-full inline-block align-middle">
                    <div class="bg-white dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-xl shadow-2xs overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-neutral-700">
                            <h2 class="text-xl font-semibold text-gray-800 dark:text-neutral-200">
                                Burial Schedules Calendar
                            </h2>
                        </div>
                        
                        <div class="p-6">
                            <FullCalendar :options="calendarOptions" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
:root {
    --fc-border-color: #e5e7eb;
    --fc-button-bg-color: #16a34a;
    --fc-button-border-color: #16a34a;
    --fc-button-hover-bg-color: #15803d;
    --fc-button-hover-border-color: #15803d;
    --fc-button-active-bg-color: #166534;
    --fc-button-active-border-color: #166534;
}

.dark {
    --fc-border-color: #404040;
    --fc-page-bg-color: #262626;
}

.fc {
    font-family: inherit;
}

.fc .fc-button {
    text-transform: capitalize;
    font-weight: 500;
}

.fc .fc-toolbar-title {
    font-size: 1.5rem;
    font-weight: 600;
}

.dark .fc-theme-standard td,
.dark .fc-theme-standard th {
    border-color: var(--fc-border-color);
}

.dark .fc-col-header-cell {
    background-color: #404040;
}

.dark .fc-daygrid-day {
    background-color: #262626;
}

.dark .fc-toolbar-title,
.dark .fc-col-header-cell-cushion,
.dark .fc-daygrid-day-number {
    color: #e5e7eb;
}
</style>
