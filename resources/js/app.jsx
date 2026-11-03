import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import Utama from './Layouts/Utama';
import './gaya.css';

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.jsx', { eager: true });
        const halaman = pages[`./Pages/${name}.jsx`];
        if (!halaman) throw new Error(`Halaman tidak ditemukan: ${name}`);
        const komponen = halaman.default;
        komponen.layout = komponen.layout || ((page) => <Utama>{page}</Utama>);
        return komponen;
    },
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
});
