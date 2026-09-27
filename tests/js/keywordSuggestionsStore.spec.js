import {beforeEach, describe, expect, it, vi} from 'vitest';
import {createPinia, setActivePinia} from 'pinia';
import axios from '../../resources/js/apps/main/axiosInstance.js';
import {queue} from '../../resources/js/apps/main/store/networkQueue.js';
import {useKeywordsStore} from '../../resources/js/apps/main/stores/keywords.js';
import {useKeywordSuggestionsStore} from '../../resources/js/apps/main/stores/keywordSuggestions.js';

vi.mock('../../resources/js/apps/main/axiosInstance.js', () => ({
    default: {get: vi.fn()},
}));

vi.mock('../../resources/js/apps/main/store/networkQueue.js', () => ({
    queue: {add: vi.fn(task => task())},
}));

describe('keyword suggestions Pinia store', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
        queue.add.mockImplementation(task => task());
    });

    it('loads pages to the requested maximum and publishes keywords without relevance', async () => {
        const first = {id: 8, title: 'Beta', relevance: 9};
        const second = {id: 9, title: 'Gamma', relevance: 7};
        const third = {id: 10, title: 'Delta', relevance: 4};
        axios.get
            .mockResolvedValueOnce({data: {
                data: [first],
                next_page_url: '/api/v2/keywords/suggestions/7?page=2',
                total: 3,
            }})
            .mockResolvedValueOnce({data: {
                data: [second, third],
                next_page_url: null,
                total: 3,
            }});
        const store = useKeywordSuggestionsStore();

        await expect(store.get({id: 7, maximum: 2})).resolves.toEqual([first, second]);
        await expect(store.get({id: 7, maximum: 3})).resolves.toEqual([first, second, third]);

        expect(axios.get).toHaveBeenCalledTimes(2);
        expect(axios.get).toHaveBeenNthCalledWith(1, '/api/v2/keywords/suggestions/7');
        expect(axios.get).toHaveBeenNthCalledWith(2, '/api/v2/keywords/suggestions/7?page=2');
        expect(store.getKeywordSuggestionsCount(7)).toBe(3);
        expect(useKeywordsStore().getKeyword(8)).toEqual({id: 8, title: 'Beta'});
        expect(first).toHaveProperty('relevance', 9);
    });

    it('uses a complete cache and supports explicit invalidation', async () => {
        const store = useKeywordSuggestionsStore();
        const suggestions = [{id: 8}, {id: 9}];
        store.setKeywordSuggestions({id: 7, items: suggestions});
        store.setKeywordSuggestionsCount({id: 7, count: 2});

        await expect(store.get({id: 7})).resolves.toEqual(suggestions);
        expect(axios.get).not.toHaveBeenCalled();

        store.clearKeywordSuggestions(7);
        expect(store.getKeywordSuggestions(7)).toBe(false);
        expect(store.getKeywordSuggestionsCount(7)).toBe(false);
    });

    it('coalesces parallel count and list requests for the same keyword', async () => {
        let resolveRequest;
        axios.get.mockReturnValue(new Promise(resolve => {
            resolveRequest = resolve;
        }));
        const store = useKeywordSuggestionsStore();

        const countPromise = store.getCount(7);
        const suggestionsPromise = store.get({id: 7, maximum: 1});
        resolveRequest({data: {data: [{id: 8}], next_page_url: null, total: 1}});

        await expect(countPromise).resolves.toBe(1);
        await expect(suggestionsPromise).resolves.toEqual([{id: 8}]);
        expect(axios.get).toHaveBeenCalledOnce();
    });

    it('treats a zero total as a cached count', async () => {
        axios.get.mockResolvedValue({data: {data: [], next_page_url: null, total: 0}});
        const store = useKeywordSuggestionsStore();

        await expect(store.getCount(7)).resolves.toBe(0);
        await expect(store.getCount(7)).resolves.toBe(0);

        expect(axios.get).toHaveBeenCalledOnce();
    });

    it('returns a converted request error as a fulfilled action value', async () => {
        axios.get.mockRejectedValue({response: {data: {error: 'Vorschläge nicht erreichbar'}}});

        await expect(useKeywordSuggestionsStore().get({id: 7}))
            .resolves.toBe('Vorschläge nicht erreichbar');
    });

    it('queues multiple ids and preserves their result order', async () => {
        axios.get
            .mockResolvedValueOnce({data: {data: [{id: 8}], next_page_url: null, total: 1}})
            .mockResolvedValueOnce({data: {data: [{id: 10}], next_page_url: null, total: 1}});

        await expect(useKeywordSuggestionsStore().getMultiple([
            {id: 7, maximum: 1},
            {id: 9, maximum: 1},
        ])).resolves.toEqual([[{id: 8}], [{id: 10}]]);

        expect(queue.add).toHaveBeenCalledTimes(2);
        expect(axios.get).toHaveBeenNthCalledWith(1, '/api/v2/keywords/suggestions/7');
        expect(axios.get).toHaveBeenNthCalledWith(2, '/api/v2/keywords/suggestions/9');
    });
});
