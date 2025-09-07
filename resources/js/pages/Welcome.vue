<script setup lang="ts">
import { dashboard, login, register } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { ChevronLeft, ChevronRight, Play, Users, Briefcase, TrendingUp, Building2 } from 'lucide-vue-next';

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

// Brand slider state
const currentSlide = ref(0);
const isAutoPlay = ref(true);
let autoPlayInterval: number | null = null;

// Slider functionality
const nextSlide = () => {
    if (props.brands.length > 0) {
        currentSlide.value = (currentSlide.value + 1) % Math.ceil(props.brands.length / getSlidesPerView());
    }
};

const prevSlide = () => {
    if (props.brands.length > 0) {
        currentSlide.value = currentSlide.value === 0 
            ? Math.ceil(props.brands.length / getSlidesPerView()) - 1 
            : currentSlide.value - 1;
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

onMounted(() => {
    startAutoPlay();
});

onUnmounted(() => {
    stopAutoPlay();
});
</script>

<template>
    <Head title="Partner Bisnismu - Sistem Pencatatan Database">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    
    <!-- Main Container with Soft Blue-Orange Gradient Background -->
    <div class="min-h-screen bg-gradient-to-br from-blue-300 via-blue-200 to-orange-300 relative overflow-hidden">
        <!-- Decorative Elements -->
        <div class="absolute top-0 left-0 w-full h-full">
            <!-- Floating circles for decoration with more transparency -->
            <div class="absolute top-20 left-10 w-32 h-32 bg-white/5 rounded-full blur-xl"></div>
            <div class="absolute top-40 right-20 w-24 h-24 bg-white/8 rounded-full blur-lg"></div>
            <div class="absolute bottom-32 left-1/4 w-40 h-40 bg-white/3 rounded-full blur-2xl"></div>
            <div class="absolute bottom-20 right-1/3 w-28 h-28 bg-white/5 rounded-full blur-xl"></div>
        </div>

        <!-- Header Navigation -->
        <header class="relative z-10 bg-black/5 backdrop-blur-sm border-b border-white/10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <!-- Logo -->
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-white/15 backdrop-blur-sm rounded-lg flex items-center justify-center border border-white/20 p-1">
                            <img src="/images/partner-bisnismu-logo.png" alt="Partner Bisnismu" class="w-full h-full object-contain" />
                        </div>
                        <div class="hidden sm:block">
                            <h1 class="text-xl font-bold text-gray-700">Partner Bisnismu</h1>
                        </div>
                    </div>
                    
                    <!-- Auth Buttons -->
                    <div class="flex items-center space-x-4">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="dashboard()"
                            class="bg-white/15 backdrop-blur-sm hover:bg-white/25 text-gray-700 px-6 py-2 rounded-full font-medium transition-all duration-300 border border-white/20"
                        >
                            Dashboard
                        </Link>
                        <template v-else>
                            <Link
                                :href="login()"
                                class="text-gray-600 hover:text-gray-800 transition-colors font-medium hidden sm:block"
                            >
                                Log In
                            </Link>
                            <Link
                                :href="register()"
                                class="bg-white/15 backdrop-blur-sm hover:bg-white/25 text-gray-700 px-6 py-2 rounded-full font-medium transition-all duration-300 border border-white/20"
                            >
                                Join Now
                            </Link>
                        </template>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Hero Section -->
        <main class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-20">
            <div class="grid lg:grid-cols-2 gap-12 items-center min-h-[70vh]">
                <!-- Left Content -->
                <div class="text-left md:text-left text-center space-y-8">
                    <!-- David Badge -->
                    <div class="inline-flex items-center space-x-2 bg-blue-400/20 backdrop-blur-sm border border-blue-300/30 rounded-full px-4 py-2">
                        <div class="w-6 h-6 bg-blue-400 rounded-full flex items-center justify-center">
                            <span class="text-white text-xs font-medium">H</span>
                        </div>
                        <span class="text-gray-700 text-sm font-medium">Hidayat</span>
                    </div>

                    <div class="space-y-6">
                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-gray-800 leading-tight">
                            Sistem<br>
                            Pencatatan<br>
                            Database<br>
                            <span class="text-orange-400">CV Partner<br>
                            Bisnismu</span>
                        </h1>
                        
                        <div class="flex items-center justify-center md:justify-start space-x-4 pt-4">
                            <button class="bg-black/20 backdrop-blur-sm hover:bg-black/30 text-gray-700 px-8 py-3 rounded-full font-medium transition-all duration-300 border border-white/20 flex items-center space-x-2">
                                <Play class="w-5 h-5" />
                                <span>Start Project</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Right Content - Animated Circle with Avatars -->
                <div class="relative flex justify-center items-center">
                    <!-- Central Circle -->
                    <div class="relative">
                        <!-- Outer rotating ring -->
                        <div class="w-80 h-80 sm:w-96 sm:h-96 rounded-full border-2 border-white/20 relative animate-spin-slow">
                            <!-- Avatar positions around the circle -->
                            <div class="absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                                <div class="w-12 h-12 rounded-full bg-white/15 backdrop-blur-sm border-2 border-white/30 flex items-center justify-center">
                                    <Users class="w-6 h-6 text-gray-600" />
                                </div>
                            </div>
                            <div class="absolute top-1/4 right-0 transform translate-x-1/2 -translate-y-1/2">
                                <div class="w-12 h-12 rounded-full bg-white/15 backdrop-blur-sm border-2 border-white/30 flex items-center justify-center">
                                    <TrendingUp class="w-6 h-6 text-gray-600" />
                                </div>
                            </div>
                            <div class="absolute bottom-1/4 right-0 transform translate-x-1/2 translate-y-1/2">
                                <div class="w-12 h-12 rounded-full bg-white/15 backdrop-blur-sm border-2 border-white/30 flex items-center justify-center">
                                    <Briefcase class="w-6 h-6 text-gray-600" />
                                </div>
                            </div>
                            <div class="absolute bottom-0 left-1/2 transform -translate-x-1/2 translate-y-1/2">
                                <div class="w-12 h-12 rounded-full bg-white/15 backdrop-blur-sm border-2 border-white/30 flex items-center justify-center">
                                    <Building2 class="w-6 h-6 text-gray-600" />
                                </div>
                            </div>
                            <div class="absolute bottom-1/4 left-0 transform -translate-x-1/2 translate-y-1/2">
                                <div class="w-12 h-12 rounded-full bg-white/15 backdrop-blur-sm border-2 border-white/30 flex items-center justify-center">
                                    <Users class="w-6 h-6 text-gray-600" />
                                </div>
                            </div>
                            <div class="absolute top-1/4 left-0 transform -translate-x-1/2 -translate-y-1/2">
                                <div class="w-12 h-12 rounded-full bg-white/15 backdrop-blur-sm border-2 border-white/30 flex items-center justify-center">
                                    <TrendingUp class="w-6 h-6 text-gray-600" />
                                </div>
                            </div>
                        </div>
                        
                        <!-- Center content -->
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="text-center">
                                <div class="text-6xl font-bold text-gray-700 mb-2">20k+</div>
                                <div class="text-gray-600 text-lg">Database Mitra</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Brand Partners Section -->
        <section class="relative z-10 bg-black/5 backdrop-blur-sm border-t border-white/10 py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-bold text-gray-700 mb-2">Brand Kami</h2>
                    <p class="text-gray-600">Mitra yang mempercayai solusi pemasaran kami</p>
                </div>
                
                <!-- Brand Slider -->
                <div class="relative" 
                     @mouseenter="isAutoPlay = false; stopAutoPlay()" 
                     @mouseleave="isAutoPlay = true; startAutoPlay()">
                    
                    <!-- Slider Container -->
                    <div class="overflow-hidden rounded-2xl">
                        <div class="flex transition-transform duration-500 ease-in-out"
                             :style="`transform: translateX(-${currentSlide * 100}%)`">
                            
                            <div v-for="(brandGroup, groupIndex) in chunkedBrands" 
                                 :key="groupIndex"
                                 class="w-full flex-shrink-0 grid grid-cols-2 md:grid-cols-4 gap-6 px-4">
                                
                                <div v-for="brand in brandGroup" 
                                     :key="brand.id"
                                     class="bg-white/15 backdrop-blur-sm rounded-xl p-4 border border-white/15 hover:bg-white/25 transition-all duration-300 flex flex-col items-center justify-center space-y-3">
                                    
                                    <!-- Logo Section -->
                                    <div class="w-full h-12 flex items-center justify-center">
                                        <img v-if="brand.logo_url" 
                                             :src="brand.logo_url" 
                                             :alt="brand.nama"
                                             class="max-w-full max-h-full object-contain hover:scale-110 transition-transform duration-300">
                                        <div v-else class="w-8 h-8 bg-gray-400/50 rounded-lg flex items-center justify-center">
                                            <span class="text-gray-600 font-bold text-sm">{{ brand.nama.charAt(0).toUpperCase() }}</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Brand Name -->
                                    <div class="w-full text-center">
                                        <span class="text-gray-600 font-medium text-sm text-center line-clamp-2">{{ brand.nama }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Navigation Arrows -->
                    <button @click="prevSlide" 
                            class="absolute left-0 top-1/2 transform -translate-y-1/2 -translate-x-4 bg-white/15 backdrop-blur-sm hover:bg-white/25 rounded-full p-3 border border-white/20 transition-all duration-300">
                        <ChevronLeft class="w-6 h-6 text-gray-600" />
                    </button>
                    
                    <button @click="nextSlide" 
                            class="absolute right-0 top-1/2 transform -translate-y-1/2 translate-x-4 bg-white/15 backdrop-blur-sm hover:bg-white/25 rounded-full p-3 border border-white/20 transition-all duration-300">
                        <ChevronRight class="w-6 h-6 text-gray-600" />
                    </button>
                    
                    <!-- Slider Indicators -->
                    <div class="flex justify-center space-x-2 mt-6">
                        <button v-for="(_, index) in Math.ceil(props.brands.length / getSlidesPerView())" 
                                :key="index"
                                @click="currentSlide = index"
                                :class="[
                                    'w-3 h-3 rounded-full transition-all duration-300',
                                    currentSlide === index 
                                        ? 'bg-gray-600' 
                                        : 'bg-gray-400 hover:bg-gray-500'
                                ]">
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer Partners -->
        <footer class="relative z-10 bg-black/10 backdrop-blur-sm border-t border-white/10 py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-wrap justify-center items-center gap-8 opacity-60">
                    <span class="text-gray-600 font-medium text-xs">Copyright © 2025 Partner Bisnismu. CTO BCE With Love</span>
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
    
    h1, h2, h3, h4, h5, h6, p, span, div {
        text-align: center !important;
    }
    
    /* Exception for flex items that should remain centered */
    .flex {
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
</style>
