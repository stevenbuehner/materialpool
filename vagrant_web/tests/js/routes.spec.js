import {describe, expect, it} from 'vitest';

import {routes} from '../../resources/js/apps/main/routes.js';

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
            'system-shutdown',
        ]);
    });
});
