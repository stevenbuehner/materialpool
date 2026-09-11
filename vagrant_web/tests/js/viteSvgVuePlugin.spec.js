import {describe, expect, it} from 'vitest';
import svgVuePlugin, {compileSvg} from '../../scripts/vite-svg-vue-plugin.mjs';

describe('Vite SVG Vue plugin', () => {
    it('compiles SVG markup into a Vue render component', () => {
        const result = compileSvg(
            '<?xml version="1.0"?><svg viewBox="0 0 10 10"><path d="M0 0h10v10z"/></svg>',
            '/tmp/test-icon.svg',
        );

        expect(result.code).toContain('function render');
        expect(result.code).toContain('export default { render }');
        expect(result.code).not.toContain('<?xml');
        expect(result.map).toBeTruthy();
    });

    it('leaves non-SVG modules untouched', () => {
        expect(svgVuePlugin().load('/tmp/example.js')).toBeNull();
    });
});
