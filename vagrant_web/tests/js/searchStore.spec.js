import {beforeEach, describe, expect, it, vi} from 'vitest';
import {createPinia, setActivePinia} from 'pinia';
import axios from '../../resources/js/apps/main/axiosInstance.js';
import {useMaterialsStore} from '../../resources/js/apps/main/stores/materials.js';
import {useSearchStore} from '../../resources/js/apps/main/stores/search.js';

vi.mock('../../resources/js/apps/main/axiosInstance.js', () => ({
    default: {post: vi.fn()},
}));

const searchResponse = (materials = [{id: 7, title: 'Treffer'}]) => ({
    data: {
        data: materials,
        current_page: 1,
        from: 1,
        last_page: 2,
        next_page_url: '/pool/search/get?page=2',
        per_page: 30,
        prev_page_url: null,
        to: materials.length,
        total: 31,
    },
});

describe('search Pinia store', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    it('coalesces identical searches and publishes material previews', async () => {
        axios.post.mockResolvedValue(searchResponse());
        const store = useSearchStore();
        const query = [[{type: '*', text: 'Test'}]];

        const first = store.materials({query});
        const second = store.materials({query});

        await expect(Promise.all([first, second])).resolves.toEqual([
            {
                materials: [{id: 7, title: 'Treffer'}],
                paging: {
                    current_page: 1,
                    from: 1,
                    last_page: 2,
                    next_page_url: '/pool/search/get?page=2',
                    per_page: 30,
                    prev_page_url: null,
                    to: 1,
                    total: 31,
                },
            },
            expect.any(Object),
        ]);

        expect(axios.post).toHaveBeenCalledOnce();
        expect(axios.post).toHaveBeenCalledWith('/pool/search/get', {
            q: query,
            page: 1,
            per_page: 30,
        });
        expect(useMaterialsStore().getMaterial(7)).toEqual({id: 7, title: 'Treffer'});
        expect(useMaterialsStore().hasMaterialDetails(7)).toBe(false);
    });

    it('keeps the 20 most recently used cache entries', () => {
        const store = useSearchStore();

        for (let index = 0; index < 21; index++) {
            store.putSearchCache({query: {q: index}, promise: Promise.resolve(index)});
        }

        expect(store.searchCacheHistory).toHaveLength(20);
        expect(store.hasCacheEntry({q: 0})).toBe(false);
        expect(store.hasCacheEntry({q: 1})).toBe(true);

        store.getCacheEntry({q: 1});
        expect(store.searchCacheHistory.at(-1)).toBe(JSON.stringify({q: 1}));

        store.putSearchCache({query: {q: 2}, promise: Promise.resolve('replaced')});
        expect(store.searchCacheHistory).toHaveLength(20);
        expect(store.searchCacheHistory.filter(query => query === JSON.stringify({q: 2}))).toHaveLength(1);
    });

    it('removes a failed request from the cache so it can be retried', async () => {
        const store = useSearchStore();
        const query = [{type: '*', text: 'Retry'}];
        axios.post
            .mockRejectedValueOnce({message: 'Suche fehlgeschlagen'})
            .mockResolvedValueOnce(searchResponse([]));

        await expect(store.materials({query})).rejects.toBe('Suche fehlgeschlagen');
        expect(store.hasCacheEntry({q: query, page: 1, per_page: 30})).toBe(false);
        expect(store.searchCacheHistory).toEqual([]);

        await expect(store.materials({query})).resolves.toEqual(expect.objectContaining({materials: []}));
        expect(axios.post).toHaveBeenCalledTimes(2);
    });

    it('preserves the legacy title-query shape and selected values', async () => {
        const store = useSearchStore();
        const materials = vi.spyOn(store, 'materials').mockResolvedValue({materials: [], paging: {}});

        await store.materialsWithParams({material: {title: 'Titel'}, page: 3});
        expect(materials).toHaveBeenCalledWith({
            query: [[{type: '*', text: 'Titel'}]],
            page: 3,
        });

        store.setSelectedSearchValues({type: 'keyword'});
        expect(store.selectedSearchValues).toEqual({type: 'keyword'});
    });

    it('passes an explicitly requested material order through to the search endpoint', async () => {
        axios.post.mockResolvedValue(searchResponse([]));

        await useSearchStore().materials({query: [], orderBy: 'created_at'});

        expect(axios.post).toHaveBeenCalledWith('/pool/search/get', {
            q: [],
            page: 1,
            per_page: 30,
            order_by: 'created_at',
        });
    });
});
