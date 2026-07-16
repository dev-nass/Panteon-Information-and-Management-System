<script setup>
import { useForm, Link } from "@inertiajs/vue3";
import Input from "@/Components/Form/Input.vue";
import Button from "@/Components/Form/Button.vue";

const form = useForm({
    email: "",
});

const handleSubmit = () => {
    form.post(route("password.email"));
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

        <!-- Right Side - Forgot Password Form -->
        <div
            class="w-full lg:w-1/2 flex items-center justify-center p-8 bg-white dark:bg-neutral-900"
        >
            <div class="w-full max-w-md space-y-8">
                <!-- Header -->
                <div class="text-center">
                    <h2
                        class="text-3xl font-bold text-gray-900 dark:text-white mb-2"
                    >
                        Forgot Password?
                    </h2>
                    <p class="text-gray-600 dark:text-neutral-400">
                        Enter your email and we'll send you a link to reset your
                        password.
                    </p>
                </div>

                <!-- Success State -->
                <div
                    v-if="form.recentlySuccessful"
                    class="rounded-md bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4 text-sm text-green-700 dark:text-green-400"
                >
                    A password reset link has been sent to your email address.
                </div>

                <!-- Form -->
                <form @submit.prevent="handleSubmit" class="space-y-6">
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
                            required
                            autofocus
                        />
                        <p
                            v-if="form.errors.email"
                            class="mt-1 text-sm text-red-600 dark:text-red-400"
                        >
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <!-- Submit Button -->
                    <div>
                        <Button
                            type="submit"
                            :highlighted="true"
                            class="w-full justify-center"
                            :disabled="form.processing"
                        >
                            {{
                                form.processing
                                    ? "Sending..."
                                    : "Send Reset Link"
                            }}
                        </Button>
                    </div>

                    <!-- Back to Login -->
                    <div
                        class="text-center text-sm text-gray-600 dark:text-neutral-400"
                    >
                        Remember your password?
                        <Link
                            :href="route('login')"
                            class="text-green-600 dark:text-green-500 hover:underline font-medium"
                        >
                            Sign In
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
.bona-nova-heading {
    font-family: "Bona Nova", serif;
}
</style>
