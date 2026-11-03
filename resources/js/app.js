import Alpine from 'alpinejs';
import { initAll } from 'govuk-frontend';

/**
 * Address autocomplete for the new building signup form. Fills a hidden
 * input with the provider's address ID; the server re-fetches the details.
 */
Alpine.data('addressSearch', (searchUrl, initial = { id: '', label: '' }) => ({
    query: initial.label,
    selectedId: initial.id,
    results: [],
    open: false,
    loading: false,
    timer: null,

    search() {
        this.selectedId = '';
        clearTimeout(this.timer);

        if (this.query.trim().length < 3) {
            this.results = [];
            this.open = false;
            return;
        }

        this.timer = setTimeout(async () => {
            this.loading = true;
            try {
                const response = await fetch(`${searchUrl}?q=${encodeURIComponent(this.query)}`, {
                    headers: { Accept: 'application/json' },
                });
                this.results = response.ok ? await response.json() : [];
                this.open = true;
            } finally {
                this.loading = false;
            }
        }, 300);
    },

    choose(result) {
        this.selectedId = result.id;
        this.query = result.label;
        this.open = false;
    },
}));

window.Alpine = Alpine;
Alpine.start();

// GOV.UK Frontend's components (error summary, service navigation menu, notification banners).
initAll();

if (document.querySelector('textarea[data-rich-text]')) {
    import('./rich-editor.js').then(({ initRichEditors }) => initRichEditors());
}
