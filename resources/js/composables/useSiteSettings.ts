import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

interface SiteSettings {
    site_title?: string;
    site_description?: string;
    site_logo?: string;
    site_favicon?: string;
}

export function useSiteSettings() {
    const page = usePage();
    
    // Get settings from page props if available
    const settings = ref<SiteSettings>(page.props.siteSettings || {});
    
    // Computed values with defaults
    const siteTitle = computed(() => settings.value.site_title || 'Laravel Starter Kit');
    const siteDescription = computed(() => settings.value.site_description || 'Marketing Database Management System');
    const siteLogo = computed(() => settings.value.site_logo || null);
    const siteFavicon = computed(() => settings.value.site_favicon || null);
    
    // Update settings
    const updateSettings = (newSettings: Partial<SiteSettings>) => {
        settings.value = { ...settings.value, ...newSettings };
    };
    
    return {
        settings,
        siteTitle,
        siteDescription,
        siteLogo,
        siteFavicon,
        updateSettings,
    };
}
