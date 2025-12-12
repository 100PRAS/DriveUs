(function(){
    const THEME_KEY = 'driveus_theme';
    const root = document.documentElement;
    const body = document.body;

    const apply = (mode) => {
        const isDark = mode === 'dark';
        
        console.log('[Sombre.js] Applying theme:', mode);
        
        // SUPPRIMER toutes les classes d'abord
        root.classList.remove('dark');
        if (body) {
            body.classList.remove('dark');
        }
        
        // AJOUTER la classe dark si nécessaire
        if (isDark) {
            root.classList.add('dark');
            if (body) {
                body.classList.add('dark');
            }
        }
        
        // Sauvegarder la préférence
        try {
            localStorage.setItem(THEME_KEY, mode);
            console.log('[Sombre.js] Saved to localStorage:', mode);
        } catch(e) {
            console.error('[Sombre.js] localStorage error:', e);
        }
    };

    // Appliquer le thème sauvegardé dès le chargement du DOM
    const applyStoredTheme = () => {
        try {
            const stored = localStorage.getItem(THEME_KEY);
            console.log('[Sombre.js] Stored value:', stored);
            
            if (stored === 'dark') {
                apply('dark');
            } else {
                apply('light');
            }
        } catch(e) {
            console.error('[Sombre.js] Error loading theme:', e);
            apply('light');
        }
    };

    // Si le DOM est déjà chargé, appliquer immédiatement
    if (document.readyState === 'loading') {
        console.log('[Sombre.js] DOM loading, waiting for DOMContentLoaded');
        document.addEventListener('DOMContentLoaded', applyStoredTheme);
    } else {
        console.log('[Sombre.js] DOM ready, applying immediately');
        applyStoredTheme();
    }

    // Fonction globale utilisée par le bouton
    window.darkToggle = function() {
        const isDark = root.classList.contains('dark');
        const next = isDark ? 'light' : 'dark';
        console.log('[Sombre.js] Toggle:', isDark ? 'dark -> light' : 'light -> dark');
        apply(next);
    };

    console.log('[Sombre.js] Initialized');
})();