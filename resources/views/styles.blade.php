<style>
    .sp-hub-toolbar { display: flex; flex-wrap: wrap; gap: .75rem; margin: 1rem 0; }
    .sp-hub-search { flex: 1 1 16rem; }
    .sp-hub-maker { flex: 0 1 16rem; }
    .sp-hub-grid { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fill, minmax(17rem, 1fr)); }
    .sp-hub-card { display: flex; flex-direction: column; overflow: hidden; border-radius: .75rem; background: #fff; box-shadow: 0 0 0 1px rgb(0 0 0 / .08); }
    .dark .sp-hub-card { background: var(--gray-900); box-shadow: 0 0 0 1px rgb(255 255 255 / .1); }
    .sp-hub-shot { display: block; width: 100%; aspect-ratio: 16 / 9; object-fit: cover; object-position: top; background: var(--gray-100); }
    .dark .sp-hub-shot { background: var(--gray-800); }
    .sp-hub-body { display: flex; flex: 1; flex-direction: column; gap: .35rem; padding: .875rem 1rem 1rem; }
    .sp-hub-head { display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
    .sp-hub-title { font-size: 1rem; font-weight: 600; line-height: 1.3; }
    .sp-hub-by, .sp-hub-by-large { align-self: flex-start; font-weight: 600; color: var(--primary-600); text-align: left; }
    .dark .sp-hub-by, .dark .sp-hub-by-large { color: var(--primary-400); }
    .sp-hub-by:hover { text-decoration: underline; }
    .sp-hub-by-large { font-size: 1.05rem; }
    .sp-hub-summary { flex: 1; font-size: .875rem; color: var(--gray-600); }
    .dark .sp-hub-summary { color: var(--gray-300); }
    .sp-hub-meta { font-size: .8rem; color: var(--gray-500); }
    .sp-hub-meta a { text-decoration: underline; }
    .sp-hub-actions { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .5rem; }
    .sp-hub-empty { display: flex; align-items: center; justify-content: center; gap: .5rem; padding: 3rem 1rem; color: var(--gray-500); }
    .sp-hub-spinner { width: 1.25rem; height: 1.25rem; }
    .sp-hub-note { margin-top: 1rem; font-size: .8rem; color: var(--gray-500); }
    .sp-hub-details { display: flex; flex-direction: column; gap: .75rem; }
    .sp-hub-facts { display: grid; grid-template-columns: max-content 1fr; gap: .25rem 1rem; font-size: .875rem; }
    .sp-hub-facts dt { color: var(--gray-500); }
    .sp-hub-detail-shot { width: 100%; border-radius: .5rem; box-shadow: 0 0 0 1px rgb(0 0 0 / .08); }
    @media (max-width: 600px) {
        .sp-hub-maker { flex-basis: 100%; }
    }
</style>
