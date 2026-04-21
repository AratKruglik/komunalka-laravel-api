import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { ThemeProvider } from '@/Components/theme/ThemeProvider';

createInertiaApp({
    resolve: async (name) => {
        const pages = import.meta.glob('./Pages/**/*.tsx');
        const importPage = pages[`./Pages/${name}.tsx`];
        if (!importPage) {
            throw new Error(`Page not found: ${name}`);
        }
        const module = (await importPage()) as { default: React.ComponentType };
        return module.default;
    },
    setup({ el, App, props }) {
        createRoot(el).render(
            <ThemeProvider>
                <App {...props} />
            </ThemeProvider>
        );
    },
});
