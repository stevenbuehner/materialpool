import {beforeEach, describe, expect, it, vi} from 'vitest';
import {createPinia, setActivePinia} from 'pinia';
import axios from '../../resources/js/apps/main/axiosInstance.js';
import {useMaterialPagesStore} from '../../resources/js/apps/main/stores/materialPages.js';
import {useMaterialsStore} from '../../resources/js/apps/main/stores/materials.js';

vi.mock('../../resources/js/apps/main/axiosInstance.js', () => ({
    default: {get: vi.fn()},
}));

describe('material pages Pinia store', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    it('loads a page once and publishes its previews to the materials store', async () => {
        const page = {data: [{id: 7}, {id: 8}], current_page: 2, last_page: 3};
        axios.get.mockResolvedValue({data: page});
        const store = useMaterialPagesStore();

        await expect(store.getMaterialPage(2)).resolves.toBe(page);
        await expect(store.getMaterialPage(2)).resolves.toEqual(page);

        expect(axios.get).toHaveBeenCalledOnce();
        expect(axios.get).toHaveBeenCalledWith('/api/v1/materials', {params: {page: 2}});
        expect(useMaterialsStore().getMaterial(7)).toEqual({id: 7});
        expect(useMaterialsStore().hasMaterialDetails(7)).toBe(false);
    });

    it('distinguishes an explicitly cached falsy page from a missing key', async () => {
        const store = useMaterialPagesStore();
        store.setMaterialPage({page: 0, data: null});

        await expect(store.getMaterialPage(0)).resolves.toBeNull();
        expect(axios.get).not.toHaveBeenCalled();
    });
});
