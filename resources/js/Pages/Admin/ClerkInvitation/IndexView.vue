<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import Button from "@/Components/Form/Button.vue";
import Input from "@/Components/Form/Input.vue";
import Dashboard from "@/Layouts/Dashboard.vue";

defineOptions({
    layout: Dashboard,
});

const email = ref("");
const isSubmitting = ref(false);

const submitInvitation = () => {
    if (!email.value) return;

    isSubmitting.value = true;

    router.post(
        route("admin.clerk-invitation.store"),
        {
            email: email.value,
        },
        {
            onSuccess: () => {
                email.value = "";
                isSubmitting.value = false;
            },
            onError: () => {
                isSubmitting.value = false;
            },
        },
    );
};
</script>

<template>
    <div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-6 mx-auto">
        <div class="flex flex-col items-center" data-aos="zoom-out">
            <div class="-m-1.5 overflow-x-auto">
                <div class="p-1.5 min-w-[580px] inline-block align-middle">
                    <div
                        class="flex flex-col gap-y-6 bg-white/60 dark:bg-neutral-900/60 backdrop-blur-xl px-6 py-6 border border-white/20 dark:border-neutral-700 rounded-xl shadow-lg overflow-hidden"
                    >
                        <!-- Header -->
                        <div class="flex gap-x-4">
                            <div
                                class="flex items-center justify-center size-12 rounded-full bg-green-500/10 text-green-600 dark:text-green-400"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="24"
                                    height="24"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path
                                        d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"
                                    />
                                    <circle cx="9" cy="7" r="4" />
                                    <line x1="19" y1="8" x2="19" y2="14" />
                                    <line x1="22" y1="11" x2="16" y2="11" />
                                </svg>
                            </div>

                            <article>
                                <h1
                                    class="text-2xl font-bold text-green-600 dark:text-green-400"
                                >
                                    Invite Clerk
                                </h1>

                                <p
                                    class="text-sm text-gray-500 dark:text-gray-400"
                                >
                                    Send an invitation email to a new clerk to
                                    join the system.
                                </p>
                            </article>
                        </div>

                        <!-- Form -->
                        <form
                            @submit.prevent="submitInvitation"
                            class="flex flex-col gap-y-4"
                        >
                            <div class="flex flex-col w-full">
                                <label
                                    for="email"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                                >
                                    Email Address
                                </label>
                                <div class="w-full">
                                    <Input
                                        id="email"
                                        v-model="email"
                                        type="email"
                                        placeholder="clerk@example.com"
                                        required
                                    />
                                </div>
                            </div>

                            <div class="flex justify-end pt-2">
                                <Button
                                    type="submit"
                                    :highlighted="true"
                                    :disabled="!email || isSubmitting"
                                >
                                    <span v-if="isSubmitting">Sending...</span>
                                    <span v-else>Send Invitation</span>
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
