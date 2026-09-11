import {compileTemplate} from '@vue/compiler-sfc';

/**
 * Compile SVG imports into Vue 3 render components.
 *
 * This preserves the behavior of the former local Webpack loader without
 * introducing another package solely for the project's existing icon imports.
 */
export default function svgVuePlugin() {
    return {
        name: 'materialpool-svg-vue',
        enforce: 'pre',
        transform(source, id) {
            const filename = id.split('?', 1)[0];

            if (!filename.endsWith('.svg')) {
                return null;
            }

            const template = source
                .replace(/^\uFEFF/, '')
                .replace(/<\?xml[^?]*\?>/gi, '')
                .replace(/<!doctype[^>]*>/gi, '');
            const {code, errors, map} = compileTemplate({
                id: filename,
                filename,
                source: template,
                transformAssetUrls: false,
            });

            if (errors.length > 0) {
                throw new Error(`Could not compile SVG ${filename}: ${errors.join('\n')}`);
            }

            return {
                code: `${code.replace('export function render', 'function render')}\nexport default { render };`,
                map,
            };
        },
    };
}
