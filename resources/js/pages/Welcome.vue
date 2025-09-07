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
let autoPlayInterval: NodeJS.Timeout | null = null;

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
    <Head title="MarkeTeam - Unlock Top Marketing Talent">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    
    <!-- Main Container with Full Gradient Background -->
    <div class="min-h-screen bg-gradient-to-br from-blue-600 via-purple-600 to-orange-500 relative overflow-hidden">
        <!-- Decorative Elements -->
        <div class="absolute top-0 left-0 w-full h-full">
            <!-- Floating circles for decoration -->
            <div class="absolute top-20 left-10 w-32 h-32 bg-white/10 rounded-full blur-xl"></div>
            <div class="absolute top-40 right-20 w-24 h-24 bg-white/15 rounded-full blur-lg"></div>
            <div class="absolute bottom-32 left-1/4 w-40 h-40 bg-white/5 rounded-full blur-2xl"></div>
            <div class="absolute bottom-20 right-1/3 w-28 h-28 bg-white/10 rounded-full blur-xl"></div>
        </div>

        <!-- Header Navigation -->
        <header class="relative z-10 bg-black/10 backdrop-blur-md border-b border-white/20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <!-- Logo -->
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-white/20 backdrop-blur-md rounded-lg flex items-center justify-center border border-white/30">
                            <Building2 class="w-6 h-6 text-white" />
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-white">MarkeTeam</h1>
                        </div>
                    </div>
                    
                    <!-- Navigation Menu -->
                    <nav class="hidden md:flex items-center space-x-8">
                        <a href="#" class="text-white/90 hover:text-white transition-colors">Your Team</a>
                        <a href="#" class="text-white/90 hover:text-white transition-colors">Solutions</a>
                        <a href="#" class="text-white/90 hover:text-white transition-colors">Blog</a>
                        <a href="#" class="text-white/90 hover:text-white transition-colors">Pricing</a>
                    </nav>
                    
                    <!-- Auth Buttons -->
                    <div class="flex items-center space-x-4">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="dashboard()"
                            class="bg-white/20 backdrop-blur-md hover:bg-white/30 text-white px-6 py-2 rounded-full font-medium transition-all duration-300 border border-white/30"
                        >
                            Dashboard
                        </Link>
                        <template v-else>
                            <Link
                                :href="login()"
                                class="text-white/90 hover:text-white transition-colors font-medium hidden sm:block"
                            >
                                Log In
                            </Link>
                            <Link
                                :href="register()"
                                class="bg-white/20 backdrop-blur-md hover:bg-white/30 text-white px-6 py-2 rounded-full font-medium transition-all duration-300 border border-white/30"
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
                <div class="text-left space-y-8">
                    <!-- David Badge -->
                    <div class="inline-flex items-center space-x-2 bg-purple-500/20 backdrop-blur-md border border-purple-400/30 rounded-full px-4 py-2">
                        <div class="w-6 h-6 bg-purple-500 rounded-full flex items-center justify-center">
                            <span class="text-white text-xs font-medium">D</span>
                        </div>
                        <span class="text-white text-sm font-medium">David</span>
                    </div>

                    <div class="space-y-6">
                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white leading-tight">
                            Unlock Top<br>
                            Marketing Talent<br>
                            You Thought Was<br>
                            Out of Reach –<br>
                            <span class="text-orange-300">Now Just One<br>
                            Click Away!</span>
                        </h1>
                        
                        <div class="flex items-center space-x-4 pt-4">
                            <button class="bg-black/30 backdrop-blur-md hover:bg-black/40 text-white px-8 py-3 rounded-full font-medium transition-all duration-300 border border-white/30 flex items-center space-x-2">
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
                        <div class="w-80 h-80 sm:w-96 sm:h-96 rounded-full border-2 border-white/30 relative animate-spin-slow">
                            <!-- Avatar positions around the circle -->
                            <div class="absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                                <div class="w-12 h-12 rounded-full bg-white/20 backdrop-blur-md border-2 border-white/40 flex items-center justify-center">
                                    <Users class="w-6 h-6 text-white" />
                                </div>
                            </div>
                            <div class="absolute top-1/4 right-0 transform translate-x-1/2 -translate-y-1/2">
                                <div class="w-12 h-12 rounded-full bg-white/20 backdrop-blur-md border-2 border-white/40 flex items-center justify-center">
                                    <TrendingUp class="w-6 h-6 text-white" />
                                </div>
                            </div>
                            <div class="absolute bottom-1/4 right-0 transform translate-x-1/2 translate-y-1/2">
                                <div class="w-12 h-12 rounded-full bg-white/20 backdrop-blur-md border-2 border-white/40 flex items-center justify-center">
                                    <Briefcase class="w-6 h-6 text-white" />
                                </div>
                            </div>
                            <div class="absolute bottom-0 left-1/2 transform -translate-x-1/2 translate-y-1/2">
                                <div class="w-12 h-12 rounded-full bg-white/20 backdrop-blur-md border-2 border-white/40 flex items-center justify-center">
                                    <Building2 class="w-6 h-6 text-white" />
                                </div>
                            </div>
                            <div class="absolute bottom-1/4 left-0 transform -translate-x-1/2 translate-y-1/2">
                                <div class="w-12 h-12 rounded-full bg-white/20 backdrop-blur-md border-2 border-white/40 flex items-center justify-center">
                                    <Users class="w-6 h-6 text-white" />
                                </div>
                            </div>
                            <div class="absolute top-1/4 left-0 transform -translate-x-1/2 -translate-y-1/2">
                                <div class="w-12 h-12 rounded-full bg-white/20 backdrop-blur-md border-2 border-white/40 flex items-center justify-center">
                                    <TrendingUp class="w-6 h-6 text-white" />
                                </div>
                            </div>
                        </div>
                        
                        <!-- Center content -->
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="text-center">
                                <div class="text-6xl font-bold text-white mb-2">20k+</div>
                                <div class="text-white/80 text-lg">Specialists</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Brand Partners Section -->
        <section class="relative z-10 bg-black/10 backdrop-blur-md border-t border-white/20 py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-bold text-white mb-2">Trusted by Leading Brands</h2>
                    <p class="text-white/80">Our partners who trust our marketing solutions</p>
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
                                     class="bg-white/10 backdrop-blur-md rounded-xl p-6 border border-white/20 hover:bg-white/20 transition-all duration-300 flex items-center justify-center">
                                    
                                    <div v-if="brand.logo_url" class="w-full h-16 flex items-center justify-center">
                                        <img :src="brand.logo_url" 
                                             :alt="brand.nama"
                                             class="max-w-full max-h-full object-contain filter brightness-0 invert opacity-80 hover:opacity-100 transition-opacity">
                                    </div>
                                    <div v-else class="w-full h-16 flex items-center justify-center">
                                        <span class="text-white/80 font-medium text-center">{{ brand.nama }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Navigation Arrows -->
                    <button @click="prevSlide" 
                            class="absolute left-0 top-1/2 transform -translate-y-1/2 -translate-x-4 bg-white/20 backdrop-blur-md hover:bg-white/30 rounded-full p-3 border border-white/30 transition-all duration-300">
                        <ChevronLeft class="w-6 h-6 text-white" />
                    </button>
                    
                    <button @click="nextSlide" 
                            class="absolute right-0 top-1/2 transform -translate-y-1/2 translate-x-4 bg-white/20 backdrop-blur-md hover:bg-white/30 rounded-full p-3 border border-white/30 transition-all duration-300">
                        <ChevronRight class="w-6 h-6 text-white" />
                    </button>
                    
                    <!-- Slider Indicators -->
                    <div class="flex justify-center space-x-2 mt-6">
                        <button v-for="(_, index) in Math.ceil(props.brands.length / getSlidesPerView())" 
                                :key="index"
                                @click="currentSlide = index"
                                :class="[
                                    'w-3 h-3 rounded-full transition-all duration-300',
                                    currentSlide === index 
                                        ? 'bg-white' 
                                        : 'bg-white/40 hover:bg-white/60'
                                ]">
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer Partners -->
        <footer class="relative z-10 bg-black/20 backdrop-blur-md border-t border-white/20 py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-wrap justify-center items-center gap-8 opacity-60">
                    <span class="text-white font-medium">Dreamure</span>
                    <span class="text-white font-medium">SWITCH.WIN</span>
                    <span class="text-white font-medium">Sphere</span>
                    <span class="text-white font-medium">PinSpace</span>
                    <span class="text-white font-medium">Visionix</span>
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
}

/* Smooth scrolling */
html {
    scroll-behavior: smooth;
}

/* Backdrop blur fallback */
@supports not (backdrop-filter: blur(12px)) {
    .backdrop-blur-md {
        background-color: rgba(0, 0, 0, 0.3);
    }
}
</style>
