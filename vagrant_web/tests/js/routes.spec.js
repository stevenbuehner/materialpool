import {describe, expect, it} from 'vitest';

import {routes, scrollBehavior} from '../../resources/js/apps/main/routes.js';
import {
    material_preview_image,
    pdfPreviewImageForPage,
    pdfPreviewImageForPageLarge,
    previewImageFirstPage,
    previewImageLarge,
} from '../../resources/js/components/serverRoutes.js';

describe('main application routes', () => {
    it('keeps every page behind a lazy route boundary', () => {
        const pageRoutes = routes.filter(route => route.component);

        expect(pageRoutes).not.toHaveLength(0);
        expect(pageRoutes.every(route => typeof route.component === 'function')).toBe(true);
    });

    it('preserves the public route names', () => {
        expect(routes.map(route => route.name).filter(Boolean)).toEqual([
            'landingpage',
            'search',
            'material',
			'material-newest',
			'material-recently-updated',
            'material-detail',
            'resource-create',
            'resource-text-create',
            'resource-lonely',
            'resource-newest',
            'resource-detail',
            'resource-assign',
            'resource-page-assign',
            'resource-replace',
            'keyword-list',
            'keyword-detail',
            'bundle-list',
            'readbible',
			'admin-users',
			'context-search-evaluation-datasets',
            'system-shutdown',
        ]);
    });

    it('restores the browser scroll position when navigating back', () => {
        const savedPosition = {left: 0, top: 840};

        expect(scrollBehavior(null, null, savedPosition)).toBe(savedPosition);
        expect(scrollBehavior(null, null, null)).toEqual({left: 0, top: 0});
    });

    it('uses small preview URLs for cards and large URLs only for zoom views', () => {
        const resource = {id: 42};

        expect(previewImageFirstPage(resource)).toBe('/resource/42/image/640/640');
        expect(previewImageLarge(resource)).toBe('/resource/42/image/1536/1536');
        expect(pdfPreviewImageForPage(resource, 3)).toBe('/resource/42/image/page-3?width=640&height=640');
        expect(pdfPreviewImageForPageLarge(resource, 3)).toBe('/resource/42/image/page-3?width=1536&height=1536');
        expect(material_preview_image(8)).toBe('/material/8/preview?width=640&height=640');
    });
});
