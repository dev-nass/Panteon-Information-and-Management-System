<script setup>
import { ref } from "vue";
import { useForm, Link } from "@inertiajs/vue3";
import Input from "@/Components/Form/Input.vue";
import Button from "@/Components/Form/Button.vue";

const props = defineProps({
    token: String,
    email: String,
});

const showAgreementModal = ref(false);

const form = useForm({
    first_name: "",
    middle_name: "",
    last_name: "",
    contact_number: "",
    email: props.email,
    password: "",
    password_confirmation: "",
    token: props.token,
    agreement: false,
});

const handleSubmit = () => {
    form.post(route("clerk.register.store"));
};
</script>

<template>
    <div class="min-h-screen flex">
        <!-- Left Side - Image with Gradient Overlay -->
        <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden">
            <!-- Background Image -->
            <div
                class="absolute inset-0 bg-cover bg-center bg-[url('/images/entrance.jpg')]"
            ></div>

            <!-- Gradient Overlay -->
            <div
                class="absolute inset-0 bg-gradient-to-br from-green-800/95 via-green-700/90 to-green-500/85 dark:from-green-900/95 dark:via-green-800/90 dark:to-green-600/85"
            ></div>

            <!-- Content -->
            <div
                class="w-full relative z-10 flex flex-col justify-center items-center text-white p-12"
            >
                <h1
                    class="bona-nova-heading text-8xl font-thin text-yellow-400 text-center leading-none"
                >
                    Panteon De<br />Dasmariñas
                </h1>
            </div>
        </div>

        <!-- Right Side - Registration Form -->
        <div
            class="w-full lg:w-1/2 flex items-center justify-center p-8 bg-white dark:bg-neutral-900"
        >
            <div class="w-full max-w-md space-y-8">
                <!-- Header -->
                <div class="text-center">
                    <h2
                        class="text-3xl font-bold text-gray-900 dark:text-white mb-2"
                    >
                        Create Account
                    </h2>
                    <p class="text-gray-600 dark:text-neutral-400">
                        Clerk Registration Page
                    </p>
                </div>

                <!-- Form -->
                <form @submit.prevent="handleSubmit" class="space-y-6">
                    <!-- First Name -->
                    <div>
                        <label
                            for="first_name"
                            class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-2"
                        >
                            First Name
                        </label>
                        <Input
                            id="first_name"
                            v-model="form.first_name"
                            type="text"
                            placeholder="Enter your first name"
                            required
                        />
                        <p
                            v-if="form.errors.first_name"
                            class="mt-1 text-sm text-red-600 dark:text-red-400"
                        >
                            {{ form.errors.first_name }}
                        </p>
                    </div>

                    <!-- Middle Name -->
                    <div>
                        <label
                            for="middle_name"
                            class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-2"
                        >
                            Middle Name
                        </label>
                        <Input
                            id="middle_name"
                            v-model="form.middle_name"
                            type="text"
                            placeholder="Enter your middle name (optional)"
                        />
                        <p
                            v-if="form.errors.middle_name"
                            class="mt-1 text-sm text-red-600 dark:text-red-400"
                        >
                            {{ form.errors.middle_name }}
                        </p>
                    </div>

                    <!-- Last Name -->
                    <div>
                        <label
                            for="last_name"
                            class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-2"
                        >
                            Last Name
                        </label>
                        <Input
                            id="last_name"
                            v-model="form.last_name"
                            type="text"
                            placeholder="Enter your last name"
                            required
                        />
                        <p
                            v-if="form.errors.last_name"
                            class="mt-1 text-sm text-red-600 dark:text-red-400"
                        >
                            {{ form.errors.last_name }}
                        </p>
                    </div>

                    <!-- Contact Number -->
                    <div>
                        <label
                            for="contact_number"
                            class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-2"
                        >
                            Contact Number
                        </label>
                        <Input
                            id="contact_number"
                            v-model="form.contact_number"
                            type="text"
                            placeholder="Enter your contact number"
                            required
                        />
                        <p
                            v-if="form.errors.contact_number"
                            class="mt-1 text-sm text-red-600 dark:text-red-400"
                        >
                            {{ form.errors.contact_number }}
                        </p>
                    </div>

                    <!-- Email -->
                    <div>
                        <label
                            for="email"
                            class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-2"
                        >
                            Email Address
                        </label>
                        <Input
                            id="email"
                            v-model="form.email"
                            type="email"
                            placeholder="Enter your email"
                            disabled
                            readonly
                        />
                        <p
                            v-if="form.errors.email"
                            class="mt-1 text-sm text-red-600 dark:text-red-400"
                        >
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <!-- Password -->
                    <div>
                        <label
                            for="password"
                            class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-2"
                        >
                            Password
                        </label>
                        <Input
                            id="password"
                            v-model="form.password"
                            type="password"
                            placeholder="Create a password"
                            required
                        />
                        <p
                            v-if="form.errors.password"
                            class="mt-1 text-sm text-red-600 dark:text-red-400"
                        >
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label
                            for="password_confirmation"
                            class="block text-sm font-medium text-gray-700 dark:text-neutral-300 mb-2"
                        >
                            Confirm Password
                        </label>
                        <Input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            placeholder="Confirm your password"
                            required
                        />
                        <p
                            v-if="form.errors.password_confirmation"
                            class="mt-1 text-sm text-red-600 dark:text-red-400"
                        >
                            {{ form.errors.password_confirmation }}
                        </p>
                    </div>

                    <!-- User Agreement -->
                    <div>
                        <label
                            for="agreement"
                            class="flex items-start gap-3 cursor-pointer group"
                        >
                            <input
                                id="agreement"
                                v-model="form.agreement"
                                type="checkbox"
                                class="mt-0.5 size-4 shrink-0 rounded border-gray-300 dark:border-neutral-600 bg-white dark:bg-neutral-900 text-green-600 focus:ring-green-500 focus:ring-2 transition"
                                :class="{
                                    'border-red-500 dark:border-red-500':
                                        form.errors.agreement,
                                }"
                            />
                            <span
                                class="text-sm leading-5 text-gray-700 dark:text-neutral-300"
                            >
                                I have read and agree to the terms above.
                                <button
                                    type="button"
                                    class="inline font-medium text-green-600 dark:text-green-500 hover:text-green-700 dark:hover:text-green-400 hover:underline underline-offset-2 transition"
                                    @click.prevent="showAgreementModal = true"
                                >
                                    View User Agreement
                                </button>
                            </span>
                        </label>
                        <p
                            v-if="form.errors.agreement"
                            class="mt-1.5 text-sm text-red-600 dark:text-red-400"
                        >
                            {{ form.errors.agreement }}
                        </p>
                    </div>

                    <!-- Submit Button -->
                    <div>
                        <Button
                            type="submit"
                            :highlighted="true"
                            class="w-full justify-center"
                            :disabled="form.processing || !form.agreement"
                        >
                            {{
                                form.processing ? "Registering..." : "Register"
                            }}
                        </Button>
                        <p
                            v-if="!form.agreement && !form.processing"
                            class="mt-2 text-xs text-center text-gray-500 dark:text-neutral-500"
                        >
                            You must accept the User Agreement to continue.
                        </p>
                    </div>
                </form>

                <!-- Footer -->
                <div
                    class="text-center text-sm text-gray-600 dark:text-neutral-400"
                >
                    Already have an account?
                    <Link
                        :href="route('login')"
                        class="text-green-600 dark:text-green-500 hover:underline font-medium"
                    >
                        Sign in
                    </Link>
                </div>
            </div>
        </div>
    </div>

    <!-- User Agreement Modal -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showAgreementModal"
                class="fixed inset-0 z-[2000] flex items-center justify-center p-4"
                role="dialog"
                aria-modal="true"
                aria-labelledby="user-agreement-title"
                @keydown.esc="showAgreementModal = false"
            >
                <!-- Backdrop -->
                <div
                    class="absolute inset-0 bg-black/50 backdrop-blur-sm"
                    @click="showAgreementModal = false"
                ></div>

                <!-- Modal Panel -->
                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                >
                    <div
                        v-if="showAgreementModal"
                        class="relative w-full max-w-2xl max-h-[85vh] flex flex-col bg-white dark:bg-neutral-900 rounded-2xl shadow-xl border border-gray-200 dark:border-neutral-800 overflow-hidden"
                    >
                        <!-- Header -->
                        <div
                            class="flex items-start justify-between gap-4 px-6 py-5 border-b border-gray-200 dark:border-neutral-800"
                        >
                            <div>
                                <h3
                                    id="user-agreement-title"
                                    class="text-lg font-semibold text-gray-900 dark:text-white leading-6"
                                >
                                    User Agreement — Panteon De Dasmariñas
                                    System
                                </h3>
                                <p
                                    class="mt-1 text-sm text-gray-500 dark:text-neutral-400"
                                >
                                    Please read carefully before creating your
                                    account.
                                </p>
                            </div>
                            <button
                                type="button"
                                class="shrink-0 size-8 inline-flex justify-center items-center rounded-full bg-gray-100 dark:bg-neutral-800 text-gray-500 dark:text-neutral-400 hover:bg-gray-200 dark:hover:bg-neutral-700 hover:text-gray-700 dark:hover:text-white transition"
                                aria-label="Close modal"
                                @click="showAgreementModal = false"
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

                        <!-- Body -->
                        <div
                            class="px-6 py-5 overflow-y-auto overscroll-contain space-y-4 text-sm leading-6 text-gray-700 dark:text-neutral-300"
                        >
                            <p class="font-medium text-gray-900 dark:text-white">
                                By creating your account, you agree to the
                                following:
                            </p>

                            <ol class="list-decimal pl-5 space-y-4 marker:font-semibold marker:text-gray-900 dark:marker:text-white">
                                <li>
                                    <span
                                        class="font-semibold text-gray-900 dark:text-white"
                                        >Confidentiality of Records</span
                                    >
                                    <span class="block mt-1">
                                        You will access client, deceased, and
                                        burial plot records solely to perform
                                        your official duties. You will not
                                        copy, share, print, or disclose any
                                        personal information from this system to
                                        anyone outside authorized cemetery
                                        operations, including family members,
                                        friends, or other unauthorized parties.
                                    </span>
                                </li>
                                <li>
                                    <span
                                        class="font-semibold text-gray-900 dark:text-white"
                                        >Data Privacy</span
                                    >
                                    <span class="block mt-1">
                                        This system contains personal data
                                        protected under the Data Privacy Act of
                                        2012 (RA 10173). You are responsible
                                        for handling all records — names,
                                        addresses, and burial details — with
                                        care and only using them for legitimate
                                        cemetery management purposes.
                                    </span>
                                </li>
                                <li>
                                    <span
                                        class="font-semibold text-gray-900 dark:text-white"
                                        >Account Security</span
                                    >
                                    <span class="block mt-1">
                                        You are responsible for keeping your
                                        login credentials private. Do not share
                                        your password or let others use your
                                        account. Report any suspicious activity
                                        or suspected unauthorized access
                                        immediately.
                                    </span>
                                </li>
                                <li>
                                    <span
                                        class="font-semibold text-gray-900 dark:text-white"
                                        >Accuracy of Records</span
                                    >
                                    <span class="block mt-1">
                                        You agree to encode and update records
                                        truthfully and accurately, since this
                                        data is used to generate official
                                        documents (e.g., Certificates of
                                        Service).
                                    </span>
                                </li>
                                <li>
                                    <span
                                        class="font-semibold text-gray-900 dark:text-white"
                                        >Acceptable Use</span
                                    >
                                    <span class="block mt-1">
                                        This system is for official use only. Do
                                        not use it to access, alter, or delete
                                        records outside your assigned tasks.
                                    </span>
                                </li>
                                <li>
                                    <span
                                        class="font-semibold text-gray-900 dark:text-white"
                                        >Acknowledgment</span
                                    >
                                    <span class="block mt-1">
                                        By checking the box below, you confirm
                                        that you have read, understood, and
                                        agree to follow this agreement.
                                    </span>
                                </li>
                            </ol>
                        </div>

                        <!-- Footer -->
                        <div
                            class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-neutral-800 bg-gray-50 dark:bg-neutral-900/50"
                        >
                            <button
                                type="button"
                                class="inline-flex justify-center items-center px-5 py-2.5 text-sm font-medium rounded-lg border border-gray-300 dark:border-neutral-700 bg-white dark:bg-neutral-800 text-gray-700 dark:text-neutral-200 hover:bg-gray-50 dark:hover:bg-neutral-700 transition"
                                @click="showAgreementModal = false"
                            >
                                Close
                            </button>
                            <button
                                type="button"
                                class="inline-flex justify-center items-center px-5 py-2.5 text-sm font-medium rounded-lg bg-green-600 hover:bg-green-700 text-white shadow-sm transition"
                                @click="
                                    form.agreement = true;
                                    showAgreementModal = false;
                                "
                            >
                                I have read and agree
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.bona-nova-heading {
    font-family: "Bona Nova", serif;
}
</style>
