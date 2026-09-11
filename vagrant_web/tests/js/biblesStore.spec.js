import {beforeEach, describe, expect, it, vi} from 'vitest';
import {createPinia, setActivePinia} from 'pinia';
import axios from '../../resources/js/apps/main/axiosInstance.js';
import {getAllPages} from '../../resources/js/apps/main/store/helper/paginationHelperQueued.js';
import {useBiblesStore} from '../../resources/js/apps/main/stores/bibles.js';

vi.mock('../../resources/js/apps/main/axiosInstance.js', () => ({
    default: {get: vi.fn()},
}));

vi.mock('../../resources/js/apps/main/store/helper/paginationHelperQueued.js', () => ({
    getAllPages: vi.fn(),
}));

describe('bibles Pinia store', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    it('loads one translation once and then returns it from the UUID cache', async () => {
        const bible = {uuid: 'basis', title: 'BasisBibel'};
        axios.get.mockResolvedValue({data: bible});
        const store = useBiblesStore();

        await expect(store.get('basis')).resolves.toEqual(bible);
        await expect(store.get('basis')).resolves.toEqual(bible);

        expect(axios.get).toHaveBeenCalledOnce();
        expect(axios.get).toHaveBeenCalledWith('/api/v1/bibles/basis');
        expect(store.getBible('basis')).toEqual(bible);
    });

    it('returns an explicitly added translation without a request', async () => {
        const bible = {uuid: 'lut', title: 'Luther'};
        const store = useBiblesStore();
        store.addBible(bible);

        await expect(store.get('lut')).resolves.toEqual(bible);
        expect(axios.get).not.toHaveBeenCalled();
    });

    it('coalesces concurrent full-list loads and returns all cached translations', async () => {
        let resolveAll;
        const loading = new Promise(resolve => {
            resolveAll = resolve;
        });
        getAllPages.mockReturnValue(loading);
        const store = useBiblesStore();

        const first = store.getAll();
        const concurrent = store.getAll();

        const bibles = [
            {uuid: 'basis', title: 'BasisBibel'},
            {uuid: 'lut', title: 'Luther'},
        ];
        resolveAll(bibles);

        await expect(Promise.all([first, concurrent])).resolves.toEqual([bibles, bibles]);
        await expect(store.getAll()).resolves.toEqual(bibles);
        expect(getAllPages).toHaveBeenCalledOnce();
        expect(getAllPages).toHaveBeenCalledWith('/api/v1/bibles');
        expect(store.allBiblesLoaded).toBe(true);
    });
});
