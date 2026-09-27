import {beforeEach, describe, expect, it} from 'vitest';
import {createPinia, setActivePinia} from 'pinia';
import {useRecentMaterialsStore} from '../../resources/js/apps/main/stores/recentMaterials.js';

describe('recent materials Pinia store', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
    });

    it('keeps the first occurrence when an id is added repeatedly', () => {
        const store = useRecentMaterialsStore();

        store.addRecentMaterialId(10);
        store.addRecentMaterialId(20);
        store.addRecentMaterialId(10);

        expect(store.getRecentMaterialIds).toEqual([10, 20]);
    });

    it('keeps at most five ids and removes the oldest one', () => {
        const store = useRecentMaterialsStore();

        [10, 20, 30, 40, 50, 60].forEach(id => store.addRecentMaterialId(id));

        expect(store.getRecentMaterialIds).toEqual([20, 30, 40, 50, 60]);
    });
});
