<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { AlertCircle, CheckCircle, X, XCircle } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const page = usePage();
const show = ref(false);

const flashMessages = computed(() => {
    const props = page.props as any;
    return {
        success: props.flash?.success,
        error: props.flash?.error,
        warning: props.flash?.warning,
        info: props.flash?.info,
    };
});

const hasMessages = computed(() => {
    return Object.values(flashMessages.value).some((message) => message);
});

watch(
    hasMessages,
    (newVal) => {
        if (newVal) {
            show.value = true;
            // Auto hide after 5 seconds
            setTimeout(() => {
                show.value = false;
            }, 5000);
        }
    },
    { immediate: true },
);

const close = () => {
    show.value = false;
};
</script>

<template>
    <div class="fixed top-4 left-1/2 -translate-x-1/2 sm:left-auto sm:right-4 sm:translate-x-0 z-50 w-[min(92vw,300px)]">
        <Transition
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show && hasMessages"
                role="alert"
                aria-live="assertive"
                class="pointer-events-auto w-full max-w-none overflow-hidden rounded-xl bg-gradient-to-br from-white to-gray-50 shadow-xl ring-1 ring-black/10 dark:from-gray-800 dark:to-gray-700 dark:ring-gray-600"
            >
                <!-- Success Message -->
                <div v-if="flashMessages.success" class="p-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <CheckCircle class="h-6 w-6 text-green-400" />
                        </div>
                        <div class="ml-3 w-0 flex-1 pt-0.5">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Success!</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-300">{{ flashMessages.success }}</p>
                        </div>
                        <div class="ml-4 flex flex-shrink-0">
                            <button
                                @click="close"
                                class="inline-flex rounded-md bg-white text-gray-400 hover:text-gray-500 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none dark:bg-gray-800 dark:text-gray-300 dark:hover:text-gray-200"
                            >
                                <X class="h-5 w-5" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Error Message -->
                <div v-if="flashMessages.error" class="p-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <XCircle class="h-6 w-6 text-red-400" />
                        </div>
                        <div class="ml-3 w-0 flex-1 pt-0.5">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Error!</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-300">{{ flashMessages.error }}</p>
                        </div>
                        <div class="ml-4 flex flex-shrink-0">
                            <button
                                @click="close"
                                class="inline-flex rounded-md bg-white text-gray-400 hover:text-gray-500 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none dark:bg-gray-800 dark:text-gray-300 dark:hover:text-gray-200"
                            >
                                <X class="h-5 w-5" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Warning Message -->
                <div v-if="flashMessages.warning" class="p-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <AlertCircle class="h-6 w-6 text-yellow-400" />
                        </div>
                        <div class="ml-3 w-0 flex-1 pt-0.5">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Warning!</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-300">{{ flashMessages.warning }}</p>
                        </div>
                        <div class="ml-4 flex flex-shrink-0">
                            <button
                                @click="close"
                                class="inline-flex rounded-md bg-white text-gray-400 hover:text-gray-500 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none dark:bg-gray-800 dark:text-gray-300 dark:hover:text-gray-200"
                            >
                                <X class="h-5 w-5" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Info Message -->
                <div v-if="flashMessages.info" class="p-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <AlertCircle class="h-6 w-6 text-blue-400" />
                        </div>
                        <div class="ml-3 w-0 flex-1 pt-0.5">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Info</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-300">{{ flashMessages.info }}</p>
                        </div>
                        <div class="ml-4 flex flex-shrink-0">
                            <button
                                @click="close"
                                class="inline-flex rounded-md bg-white text-gray-400 hover:text-gray-500 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none dark:bg-gray-800 dark:text-gray-300 dark:hover:text-gray-200"
                            >
                                <X class="h-5 w-5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>
