<script setup lang="ts">
import { dashboard, login, register } from '@/routes';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Briefcase, Building2, ChevronLeft, ChevronRight, Menu, TrendingUp, Users, X } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useSiteSettings } from '@/composables/useSiteSettings';
import { getInitials } from '@/composables/useInitials';

interface Brand {
    id: number;
    nama: string;
    logo: string | null;
    logo_url: string | null;
}

interface Props {
    brands: Brand[];
}

const props = defineProps<Props>();

// Mobile menu state
const isMobileMenuOpen = ref(false);

// Site settings
const { siteDescription, siteLogo, siteTitle } = useSiteSettings();

// User info for badge
const page = usePage();
const auth = computed(() => page.props.auth);
const userName = computed(() => auth.value?.user?.username || auth.value?.user?.name || 'User');
const initials = computed(() => getInitials(userName.value));

// Brand slider state
const currentSlide = ref(0);
const isAutoPlay = ref(true);
let autoPlayInterval: number | null = null;

// Mobile menu toggle
const toggleMobileMenu = () => {
    isMobileMenuOpen.value = !isMobileMenuOpen.value;
};

// Close mobile menu when clicking outside
const closeMobileMenu = () => {
    isMobileMenuOpen.value = false;
};

// Slider functionality
const nextSlide = () => {
    if (props.brands.length > 0) {
        currentSlide.value = (currentSlide.value + 1) % Math.ceil(props.brands.length / getSlidesPerView());
    }
};

const prevSlide = () => {
    if (props.brands.length > 0) {
        currentSlide.value = currentSlide.value === 0 ? Math.ceil(props.brands.length / getSlidesPerView()) - 1 : currentSlide.value - 1;
    }
};

const getSlidesPerView = () => {
    if (typeof window !== 'undefined') {
        return window.innerWidth >= 768 ? 4 : 2; // Desktop: 4 kolom, Mobile: 2 kolom
    }
    return 4;
};

// Computed property to chunk brands into groups
const chunkedBrands = computed(() => {
    const slidesPerView = getSlidesPerView();
    const chunks = [];
    for (let i = 0; i < props.brands.length; i += slidesPerView) {
        chunks.push(props.brands.slice(i, i + slidesPerView));
    }
    return chunks;
});

const startAutoPlay = () => {
    if (autoPlayInterval) clearInterval(autoPlayInterval);
    autoPlayInterval = setInterval(() => {
        if (isAutoPlay.value) {
            nextSlide();
        }
    }, 3000);
};

const stopAutoPlay = () => {
    if (autoPlayInterval) {
        clearInterval(autoPlayInterval);
        autoPlayInterval = null;
    }
};

// Handle escape key
const handleEscapeKey = (event: KeyboardEvent) => {
    if (event.key === 'Escape' && isMobileMenuOpen.value) {
        closeMobileMenu();
    }
};

onMounted(() => {
    startAutoPlay();
    document.addEventListener('keydown', handleEscapeKey);
});

onUnmounted(() => {
    stopAutoPlay();
    document.removeEventListener('keydown', handleEscapeKey);
});
</script>

<template>
    <Head title="Partner Bisnismu - Sistem Pencatatan Database">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <!-- Main Container with Soft Blue-Orange Gradient Background -->
    <div class="relative min-h-screen overflow-hidden bg-gradient-to-br from-blue-300 via-blue-200 to-orange-300">
        
        <!-- Decorative Elements -->
        <div class="absolute top-0 left-0 h-full w-full">
            <!-- Floating circles for decoration with more transparency -->
            <div class="absolute top-20 left-10 h-32 w-32 rounded-full bg-white/5 blur-xl"></div>
            <div class="absolute top-40 right-20 h-24 w-24 rounded-full bg-white/8 blur-lg"></div>
            <div class="absolute bottom-32 left-1/4 h-40 w-40 rounded-full bg-white/3 blur-2xl"></div>
            <div class="absolute right-1/3 bottom-20 h-28 w-28 rounded-full bg-white/5 blur-xl"></div>
        </div>

        <!-- Header Navigation -->
        <header class="relative z-20 border-b border-white/10 bg-black/5 backdrop-blur-sm">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16 items-center">
                    <!-- Kolom Kiri - Logo (standard spacing) -->
                    <div class="flex items-center space-x-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg border border-white/20 bg-white/15 p-1 backdrop-blur-sm">
                            <img v-if="siteLogo" :src="siteLogo" :alt="siteTitle" class="h-full w-full object-contain" />
                            <img v-else src="/images/partner-bisnismu-logo.png" alt="Partner Bisnismu" class="h-full w-full object-contain" />
                        </div>
                        <!-- Hide text on mobile, show on desktop -->
                        <div class="mobile-logo-text">
                            <h1 class="text-xl font-bold text-gray-700">{{ siteTitle }}</h1>
                        </div>
                    </div>

                    <!-- Kolom Kanan - Menu (standard spacing) -->
                    <div class="flex items-center">
                        <!-- Desktop Auth Buttons - Hide on mobile -->
                        <div class="desktop-buttons items-center space-x-4">
                            <Link
                                v-if="$page.props.auth.user"
                                :href="dashboard()"
                                class="rounded-full border border-white/20 bg-white/15 px-6 py-2 font-medium text-gray-700 backdrop-blur-sm transition-all duration-300 hover:bg-white/25"
                            >
                                Dashboard
                            </Link>
                            <template v-else>
                                <Link :href="login()" class="font-medium text-gray-600 transition-colors hover:text-gray-800">
                                    Log In
                                </Link>
                                <Link
                                    :href="register()"
                                    class="rounded-full border border-white/20 bg-white/15 px-6 py-2 font-medium text-gray-700 backdrop-blur-sm transition-all duration-300 hover:bg-white/25"
                                >
                                    Join Now
                                </Link>
                            </template>
                        </div>

                        <!-- Mobile Hamburger Button - Show only on mobile -->
                        <button
                            @click="toggleMobileMenu"
                            class="mobile-hamburger flex items-center justify-center h-10 w-10 rounded-lg border border-white/20 bg-white/15 backdrop-blur-sm transition-all duration-300 hover:bg-white/25"
                            :class="{ 'bg-white/25': isMobileMenuOpen }"
                            type="button"
                            aria-label="Toggle mobile menu"
                        >
                            <Menu v-if="!isMobileMenuOpen" class="h-5 w-5 text-gray-700" />
                            <X v-else class="h-5 w-5 text-gray-700" />
                        </button>
                    </div>
                </div>

                <!-- Mobile Menu Overlay - Only show on mobile when menu is open -->
                <div 
                    v-if="isMobileMenuOpen"
                    @click="closeMobileMenu"
                    class="sm:hidden fixed inset-0 top-16 bg-black/20 backdrop-blur-sm z-10"
                ></div>

                <!-- Mobile Menu Dropdown - Only show on mobile when menu is open -->
                <div 
                    v-if="isMobileMenuOpen"
                    class="sm:hidden absolute left-0 right-0 top-full bg-white/90 backdrop-blur-md border-b border-white/20 shadow-lg z-20"
                >
                    <div class="px-4 py-4 space-y-3">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="dashboard()"
                            @click="isMobileMenuOpen = false"
                            class="block w-full text-center rounded-lg border border-blue-200 bg-blue-50 px-6 py-3 font-medium text-blue-700 transition-all duration-300 hover:bg-blue-100"
                        >
                            Dashboard
                        </Link>
                        <template v-else>
                            <Link 
                                :href="login()" 
                                @click="isMobileMenuOpen = false"
                                class="block w-full text-center rounded-lg border border-gray-200 bg-gray-50 px-6 py-3 font-medium text-gray-700 transition-all duration-300 hover:bg-gray-100"
                            >
                                Log In
                            </Link>
                            <Link
                                :href="register()"
                                @click="isMobileMenuOpen = false"
                                class="block w-full text-center rounded-lg border border-orange-200 bg-gradient-to-r from-orange-50 to-orange-100 px-6 py-3 font-medium text-orange-700 transition-all duration-300 hover:from-orange-100 hover:to-orange-200"
                            >
                                Join Now
                            </Link>
                        </template>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Hero Section -->
        <main class="relative z-10 mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-20">
            <div class="grid min-h-[70vh] items-center gap-12 lg:grid-cols-2">
                <!-- Left Content -->
                <div class="space-y-8 text-center text-left md:text-left">
                    <!-- David Badge -->
                    <div class="inline-flex items-center space-x-2 rounded-full border border-blue-300/30 bg-blue-400/20 px-4 py-2 backdrop-blur-sm">
                        <div class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-400">
                            <span class="text-xs font-medium text-white">{{ initials }}</span>
                        </div>
                        <span class="text-sm font-medium text-gray-700">{{ userName }}</span>
                    </div>

                    <div class="space-y-6">
                        <h1 class="text-4xl leading-tight font-bold text-gray-800 sm:text-5xl lg:text-6xl">
                            {{ siteDescription }}
                        </h1>

                        
                    </div>
                </div>

                <!-- Right Content - Animated Circle with Avatars -->
                <div class="relative flex items-center justify-center">
                    <!-- Central Circle -->
                    <div class="relative">
                        <!-- Outer rotating ring -->
                        <div class="animate-spin-slow relative h-80 w-80 rounded-full border-2 border-white/20 sm:h-96 sm:w-96">
                            <!-- Avatar positions around the circle -->
                            <div class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 transform">
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-full border-2 border-white/30 bg-white/15 backdrop-blur-sm"
                                >
                                    <Users class="h-6 w-6 text-gray-600" />
                                </div>
                            </div>
                            <div class="absolute top-1/4 right-0 translate-x-1/2 -translate-y-1/2 transform">
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-full border-2 border-white/30 bg-white/15 backdrop-blur-sm"
                                >
                                    <TrendingUp class="h-6 w-6 text-gray-600" />
                                </div>
                            </div>
                            <div class="absolute right-0 bottom-1/4 translate-x-1/2 translate-y-1/2 transform">
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-full border-2 border-white/30 bg-white/15 backdrop-blur-sm"
                                >
                                    <Briefcase class="h-6 w-6 text-gray-600" />
                                </div>
                            </div>
                            <div class="absolute bottom-0 left-1/2 -translate-x-1/2 translate-y-1/2 transform">
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-full border-2 border-white/30 bg-white/15 backdrop-blur-sm"
                                >
                                    <Building2 class="h-6 w-6 text-gray-600" />
                                </div>
                            </div>
                            <div class="absolute bottom-1/4 left-0 -translate-x-1/2 translate-y-1/2 transform">
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-full border-2 border-white/30 bg-white/15 backdrop-blur-sm"
                                >
                                    <Users class="h-6 w-6 text-gray-600" />
                                </div>
                            </div>
                            <div class="absolute top-1/4 left-0 -translate-x-1/2 -translate-y-1/2 transform">
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-full border-2 border-white/30 bg-white/15 backdrop-blur-sm"
                                >
                                    <TrendingUp class="h-6 w-6 text-gray-600" />
                                </div>
                            </div>
                        </div>

                        <!-- Center content -->
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="text-center">
                                <div class="mb-2 text-6xl font-bold text-gray-700">20k+</div>
                                <div class="text-lg text-gray-600">Database Mitra</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Brand Partners Section -->
        <section class="relative z-10 border-t border-white/10 bg-black/5 py-12 backdrop-blur-sm">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-8 text-center">
                    <h2 class="mb-2 text-2xl font-bold text-gray-700">Brand Kami</h2>
                    <p class="text-gray-600">Mitra yang mempercayai solusi pemasaran kami</p>
                </div>

                <!-- Brand Slider -->
                <div
                    class="relative"
                    @mouseenter="
                        isAutoPlay = false;
                        stopAutoPlay();
                    "
                    @mouseleave="
                        isAutoPlay = true;
                        startAutoPlay();
                    "
                >
                    <!-- Slider Container -->
                    <div class="overflow-hidden rounded-2xl">
                        <div class="flex transition-transform duration-500 ease-in-out" :style="`transform: translateX(-${currentSlide * 100}%)`">
                            <div
                                v-for="(brandGroup, groupIndex) in chunkedBrands"
                                :key="groupIndex"
                                class="grid w-full flex-shrink-0 grid-cols-2 gap-6 px-4 md:grid-cols-4"
                            >
                                <div
                                    v-for="brand in brandGroup"
                                    :key="brand.id"
                                    class="flex flex-col items-center justify-center space-y-3 rounded-xl border border-white/15 bg-white/15 p-4 backdrop-blur-sm transition-all duration-300 hover:bg-white/25"
                                >
                                    <!-- Logo Section -->
                                    <div class="flex h-12 w-full items-center justify-center">
                                        <img
                                            v-if="brand.logo_url"
                                            :src="brand.logo_url"
                                            :alt="brand.nama"
                                            class="max-h-full max-w-full object-contain transition-transform duration-300 hover:scale-110"
                                        />
                                        <div v-else class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-400/50">
                                            <span class="text-sm font-bold text-gray-600">{{ brand.nama.charAt(0).toUpperCase() }}</span>
                                        </div>
                                    </div>

                                    <!-- Brand Name -->
                                    <div class="w-full text-center">
                                        <span class="line-clamp-2 text-center text-sm font-medium text-gray-600">{{ brand.nama }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Arrows -->
                    <button
                        @click="prevSlide"
                        class="absolute top-1/2 left-0 -translate-x-4 -translate-y-1/2 transform rounded-full border border-white/20 bg-white/15 p-3 backdrop-blur-sm transition-all duration-300 hover:bg-white/25"
                    >
                        <ChevronLeft class="h-6 w-6 text-gray-600" />
                    </button>

                    <button
                        @click="nextSlide"
                        class="absolute top-1/2 right-0 translate-x-4 -translate-y-1/2 transform rounded-full border border-white/20 bg-white/15 p-3 backdrop-blur-sm transition-all duration-300 hover:bg-white/25"
                    >
                        <ChevronRight class="h-6 w-6 text-gray-600" />
                    </button>

                    <!-- Slider Indicators -->
                    <div class="mt-6 flex justify-center space-x-2">
                        <button
                            v-for="(_, index) in Math.ceil(props.brands.length / getSlidesPerView())"
                            :key="index"
                            @click="currentSlide = index"
                            :class="[
                                'h-3 w-3 rounded-full transition-all duration-300',
                                currentSlide === index ? 'bg-gray-600' : 'bg-gray-400 hover:bg-gray-500',
                            ]"
                        ></button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer Partners -->
        <footer class="relative z-10 border-t border-white/10 bg-black/10 py-8 backdrop-blur-sm">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-wrap items-center justify-center gap-8 opacity-60">
                    <span class="text-xs font-medium text-gray-600">Copyright © 2025 Partner Bisnismu. CTO BCE With Love</span>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
@keyframes spin-slow {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.animate-spin-slow {
    animation: spin-slow 20s linear infinite;
}

/* Responsive grid adjustments */
@media (max-width: 768px) {
    .hero-title {
        font-size: 2.5rem;
        line-height: 1.1;
    }

    /* Center align all text on mobile */
    .text-left {
        text-align: center !important;
    }

    h1,
    h2,
    h3,
    h4,
    h5,
    h6,
    p,
    span,
    div {
        text-align: center !important;
    }

    /* Exception for flex items that should remain centered - exclude header */
    .flex:not(.justify-between) {
        justify-content: center !important;
    }
}

/* Footer font size */
footer span {
    font-size: 12px !important;
}

/* Smooth scrolling */
html {
    scroll-behavior: smooth;
}

/* Backdrop blur fallback */
@supports not (backdrop-filter: blur(12px)) {
    .backdrop-blur-sm {
        background-color: rgba(255, 255, 255, 0.1);
    }
    .backdrop-blur-md {
        background-color: rgba(255, 255, 255, 0.15);
    }
}

/* Line clamp utility */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Mobile responsive styles */
@media (max-width: 639px) {
    .mobile-hamburger {
        display: flex !important;
    }
    .desktop-buttons {
        display: none !important;
    }
    .mobile-logo-text {
        display: none !important;
    }
}

@media (min-width: 640px) {
    .mobile-hamburger {
        display: none !important;
    }
    .desktop-buttons {
        display: flex !important;
    }
    .mobile-logo-text {
        display: block !important;
    }
}

/* Header responsive layout - Logo mepet kiri, Menu mepet kanan */

/* Ensure proper flex layout for navigation */
.desktop-buttons {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.mobile-hamburger {
    display: none;
}
</style>
