# vue-select 3.20.4 – CSS-Kompatibilitätsschicht

Dieser Ordner enthält ausschließlich das für die visuelle Materialpool-Parität benötigte Stylesheet aus `vue-select` `3.20.4`. Der Vue-2-Komponentencode wurde nicht übernommen; die Laufzeit verwendet `@vueform/multiselect` hinter `resources/js/adapters/vue-select.vue`.

- Quelle: https://registry.npmjs.org/vue-select/-/vue-select-3.20.4.tgz
- Repository: https://github.com/sagalbot/vue-select
- Tarball-Integrität: `sha512-pXIsDUnBR1075qHNEM7mKgX7YnHI3MfCO+7VmXSA1Hywplpqi52jOa0j6EHWQU6MnaW/mBZPuaQtgp3yvks2Kw==`
- Lizenz: MIT; vollständiger Text in `LICENSE.md`

Die ergänzenden Regeln am Ende des Stylesheets neutralisieren ausschließlich strukturelle Unterschiede des neuen Wrappers. Bei Änderungen am Select-Adapter müssen Dropdown, Single- und Multiple-Modus erneut visuell sowie per Tastatur geprüft werden.
