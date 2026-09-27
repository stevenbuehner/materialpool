import {beforeEach, describe, expect, it, vi} from 'vitest';
import {createPinia, setActivePinia} from 'pinia';
import axios from '../../resources/js/apps/main/axiosInstance.js';
import {queue} from '../../resources/js/apps/main/store/networkQueue.js';
import {getAllPages} from '../../resources/js/apps/main/store/helper/paginationHelperQueued.js';
import {useKeywordsStore} from '../../resources/js/apps/main/stores/keywords.js';

vi.mock('../../resources/js/apps/main/axiosInstance.js', () => ({
    default: {delete: vi.fn(), get: vi.fn(), post: vi.fn()},
}));

vi.mock('../../resources/js/apps/main/store/networkQueue.js', () => ({
    queue: {add: vi.fn(task => task())},
}));

vi.mock('../../resources/js/apps/main/store/helper/paginationHelperQueued.js', () => ({
    getAllPages: vi.fn(),
}));

describe('keywords Pinia store', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
        queue.add.mockImplementation(task => task());
    });

    it('stores a clone without material-specific pivot data', () => {
        const keyword = {id: 7, title: 'Alpha', pivot: {relevance: 200}};
        const store = useKeywordsStore();

        store.setKeyword(keyword);

        expect(store.getKeyword(7)).toEqual({id: 7, title: 'Alpha'});
        expect(keyword).toHaveProperty('pivot');
    });

    it('loads one keyword and reuses the id cache', async () => {
        const keyword = {id: 7, title: 'Alpha'};
        axios.get.mockResolvedValue({data: keyword});
        const store = useKeywordsStore();

        await expect(store.get(7)).resolves.toEqual(keyword);
        await expect(store.get(7)).resolves.toEqual(keyword);

        expect(axios.get).toHaveBeenCalledOnce();
        expect(axios.get).toHaveBeenCalledWith('/api/v1/keywords/7');
    });

    it('queues multiple keyword requests and caches the results', async () => {
        axios.get
            .mockResolvedValueOnce({data: {id: 7, title: 'Alpha'}})
            .mockResolvedValueOnce({data: {id: 8, title: 'Beta'}});
        const store = useKeywordsStore();

        await expect(store.getMultiple([7, 8])).resolves.toEqual([
            {id: 7, title: 'Alpha'},
            {id: 8, title: 'Beta'},
        ]);

        expect(queue.add).toHaveBeenCalledTimes(2);
        expect(store.getAllKeywords()).toHaveLength(2);
    });

    it('loads all pages once and supports an explicit force reload', async () => {
        getAllPages
            .mockResolvedValueOnce([{id: 7, title: 'Alpha'}])
            .mockResolvedValueOnce([{id: 8, title: 'Beta'}]);
        const store = useKeywordsStore();

        await expect(store.getAll()).resolves.toEqual([{id: 7, title: 'Alpha'}]);
        await expect(store.getAll()).resolves.toEqual([{id: 7, title: 'Alpha'}]);
        await expect(store.getAll(true)).resolves.toEqual([{id: 8, title: 'Beta'}]);

        expect(getAllPages).toHaveBeenCalledTimes(2);
        expect(getAllPages).toHaveBeenCalledWith('/api/v1/keywords/');
        expect(store.areAllKeywordsLoaded()).toBe(true);
        expect(store.getAllKeywords()).toHaveLength(2);
    });

    it('loads relation counts and throws converted request errors', async () => {
        axios.get.mockResolvedValueOnce({data: {materials_count: 3}});
        const store = useKeywordsStore();

        await expect(store.relationsCount(7)).resolves.toEqual({materials_count: 3});
        expect(axios.get).toHaveBeenCalledWith('/api/v1/keywords/7/relations_count');

        axios.get.mockRejectedValueOnce({response: {data: {message: 'Zählung fehlgeschlagen'}}});
        await expect(store.relationsCount(7)).rejects.toBe('Zählung fehlgeschlagen');
    });

    it('creates with the default type, caches the result and throws converted errors', async () => {
        const consoleInfo = vi.spyOn(console, 'info').mockImplementation(() => {});
        const keyword = {id: 7, title: 'Alpha', type: 'key'};
        axios.post.mockResolvedValueOnce({data: keyword});
        const store = useKeywordsStore();

        await expect(store.create({title: 'Alpha'})).resolves.toBe(keyword);
        expect(axios.post).toHaveBeenCalledWith('/api/v1/keywords', {title: 'Alpha', type: 'key'});
        expect(store.getKeyword(7)).toEqual(keyword);

        axios.post.mockRejectedValueOnce({response: {data: {error: 'Anlegen fehlgeschlagen'}}});
        await expect(store.create({title: 'Beta', type: 'person'})).rejects.toBe('Anlegen fehlgeschlagen');
        consoleInfo.mockRestore();
    });

    it('creates and assigns with the requested relevance', async () => {
        vi.spyOn(console, 'info').mockImplementation(() => {});
        axios.post
            .mockResolvedValueOnce({data: {id: 7, title: 'Alpha', type: 'key'}})
            .mockResolvedValueOnce({data: {id: 7, pivot: {relevance: 250}}});

        await expect(useKeywordsStore().createAndAssign({
            title: 'Alpha',
            type: 'key',
            materialId: 3,
            relevance: 250,
        })).resolves.toEqual({id: 7, pivot: {relevance: 250}});

        expect(axios.post).toHaveBeenNthCalledWith(2, '/api/v1/material/3/keyword/7', {
            _method: 'PUT',
            relevance: 250,
        });
    });

    it('updates in place and invalidates the full cache when the server merges ids', async () => {
        const store = useKeywordsStore();
        store.setKeyword({id: 7, title: 'Alpha'});
        store.setAllKeywordsLoaded(true);
        const updateData = {title: 'Beta'};
        axios.post.mockResolvedValue({data: {id: 8, title: 'Beta'}});

        await expect(store.update({id: 7, data: updateData})).resolves.toEqual({id: 8, title: 'Beta'});

        expect(updateData).toEqual({title: 'Beta', _method: 'PUT'});
        expect(store.getKeyword(7)).toBe(false);
        expect(store.getKeyword(8)).toEqual({id: 8, title: 'Beta'});
        expect(store.areAllKeywordsLoaded()).toBe(false);
    });

    it('keeps assignment payload, default relevance and error contracts', async () => {
        axios.post.mockResolvedValueOnce({data: {id: 7, pivot: {relevance: 200}}});
        const store = useKeywordsStore();

        await store.updateRelevance({materialId: 3, keywordId: 7, relevance: 0});
        expect(axios.post).toHaveBeenNthCalledWith(1, '/api/v1/material/3/keyword/7', {_method: 'PUT'});

        axios.post.mockRejectedValueOnce({response: {data: {error: 'Zuordnung fehlgeschlagen'}}});
        await expect(store.updateRelevance({materialId: 3, keywordId: 7, relevance: 250}))
            .rejects.toBe('Zuordnung fehlgeschlagen');
    });

    it('deletes single and multiple assignments with method spoofing and queue ordering', async () => {
        axios.post
            .mockResolvedValueOnce({data: 1})
            .mockResolvedValueOnce({data: 2});
        const store = useKeywordsStore();

        await expect(store.deleteMultipleAssignemts({keywordIds: [7, 8], materialId: 3}))
            .resolves.toEqual([1, 2]);

        expect(queue.add).toHaveBeenCalledTimes(2);
        expect(axios.post).toHaveBeenNthCalledWith(1, '/api/v1/material/3/keyword/7', {_method: 'DELETE'});
        expect(axios.post).toHaveBeenNthCalledWith(2, '/api/v1/material/3/keyword/8', {_method: 'DELETE'});
    });

    it('deletes a keyword and keeps useful failures instead of double-converting them', async () => {
        const store = useKeywordsStore();
        store.setKeyword({id: 7, title: 'Alpha'});
        axios.delete.mockResolvedValueOnce({data: {success: true}});

        await expect(store.delete(7)).resolves.toBe(true);
        expect(store.getKeyword(7)).toBe(false);

        axios.delete.mockResolvedValueOnce({data: {success: false}});
        await expect(store.delete(8)).rejects.toBe('Unknown error while deleting keyword with id 8');

        axios.delete.mockRejectedValueOnce({response: {data: {message: 'Löschen verboten'}}});
        await expect(store.delete(9)).rejects.toBe('Löschen verboten');
    });

    it('searches with pagination metadata and caches returned keywords', async () => {
        axios.get.mockResolvedValue({data: {
            data: [{id: 7, title: 'Alpha', pivot: {relevance: 100}}],
            from: 1,
            to: 1,
            per_page: 10,
            total: 2,
            current_page: 1,
            last_page: 2,
        }});
        const store = useKeywordsStore();

        await expect(store.search({searchText: 'Al', type: ['key'], limit: 10}))
            .resolves.toEqual({
                keywords: [{id: 7, title: 'Alpha', pivot: {relevance: 100}}],
                pagination: {from: 1, to: 1, limit: 10, total: 2, hasMore: true, current_page: 1},
            });

        expect(axios.get).toHaveBeenCalledWith('/pool/search/guess/keywords', {
            params: {q: 'Al', limit: 10, page: 1, t: ['key']},
        });
        expect(store.getKeyword(7)).toEqual({id: 7, title: 'Alpha'});
    });

    it('merges parallel search results and removes duplicate ids', async () => {
        axios.get
            .mockResolvedValueOnce({data: {
                data: [{id: 7}, {id: 8}], current_page: 1, last_page: 1,
            }})
            .mockResolvedValueOnce({data: {
                data: [{id: 8}, {id: 9}], current_page: 1, last_page: 1,
            }});

        await expect(useKeywordsStore().searchMultiple([
            {searchText: 'A', type: 'key', limit: 5},
            {searchText: 'B', type: 'key', limit: 5},
        ])).resolves.toEqual([{id: 7}, {id: 8}, {id: 9}]);

        expect(queue.add).toHaveBeenCalledTimes(2);
    });
});
