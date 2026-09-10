'use strict';

/* global module, require */

const {compileTemplate} = require('@vue/compiler-sfc');

/**
 * Compile an SVG into a Vue 3 component without modifying its markup.
 *
 * Keeping this small loader local avoids the silent error handling and the
 * obsolete SVGO configuration of the former third-party loaders. Compilation
 * errors are deliberately surfaced to Webpack.
 */
module.exports = function svgVueLoader(source) {
    this.cacheable?.();

    // Vue's HTML template parser does not accept XML processing instructions
    // or doctypes. They carry no rendered information in an inline SVG.
    const template = source
        .replace(/^\uFEFF/, '')
        .replace(/<\?xml[^?]*\?>/gi, '')
        .replace(/<!doctype[^>]*>/gi, '');

    const {code, errors} = compileTemplate({
        id: this.resourcePath,
        filename: this.resourcePath,
        source: template,
        transformAssetUrls: false,
    });

    if (errors.length > 0) {
        throw new Error(`Could not compile SVG ${this.resourcePath}: ${errors.join('\n')}`);
    }

    return `${code.replace('export function render', 'function render')}\nexport default { render };`;
};
