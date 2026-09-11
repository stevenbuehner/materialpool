import {beforeEach, describe, expect, it, vi} from 'vitest';
import {createPinia, setActivePinia} from 'pinia';
import axios from '../../resources/js/apps/main/axiosInstance.js';
import {queue} from '../../resources/js/apps/main/store/networkQueue.js';
import {useBibleversesStore} from '../../resources/js/apps/main/stores/bibleverses.js';

vi.mock('../../resources/js/apps/main/axiosInstance.js', () => ({
    default: {get: vi.fn(), post: vi.fn()},
}));

vi.mock('../../resources/js/apps/main/store/networkQueue.js', () => ({
    queue: {add: vi.fn(task => task())},
}));

describe('bibleverses Pinia store', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
        queue.add.mockImplementation(task => task());
    });

    it('loads a verse by id and reuses the id cache', async () => {
        const bibleverse = {id: 7, from: 1001001, to: 1001002, label: '1Mo 1,1f'};
        axios.get.mockResolvedValue({data: bibleverse});
        const store = useBibleversesStore();

        await expect(store.get(7)).resolves.toEqual(bibleverse);
        await expect(store.get(7)).resolves.toEqual(bibleverse);

        expect(axios.get).toHaveBeenCalledOnce();
        expect(axios.get).toHaveBeenCalledWith('/api/v1/bibleverses/7');
    });

    it('returns the converted get error as a fulfilled value', async () => {
        axios.get.mockRejectedValue({response: {data: {message: 'Bibelstelle fehlt'}}});

        await expect(useBibleversesStore().get(404)).resolves.toBe('Bibelstelle fehlt');
    });

    it('queues multiple ids and preserves their result order', async () => {
        axios.get
            .mockResolvedValueOnce({data: {id: 1}})
            .mockResolvedValueOnce({data: {id: 2}});

        await expect(useBibleversesStore().getMultiple([1, 2])).resolves.toEqual([{id: 1}, {id: 2}]);
        expect(queue.add).toHaveBeenCalledTimes(2);
    });

    it('creates a verse and assigns it with the requested relevance', async () => {
        const created = {id: 9, from: 1001001, to: 1001001};
        const assigned = {...created, pivot: {relevance: 250}};
        axios.post
            .mockResolvedValueOnce({data: created})
            .mockResolvedValueOnce({data: assigned});

        await expect(useBibleversesStore().createAndAssign({
            from: 1001001,
            to: 1001001,
            materialId: 3,
            relevance: 250,
        })).resolves.toEqual(assigned);

        expect(axios.post).toHaveBeenNthCalledWith(1, '/api/v1/bibleverses', {
            from: 1001001,
            to: 1001001,
        });
        expect(axios.post).toHaveBeenNthCalledWith(2, '/api/v1/material/3/bibleverse/9', {
            _method: 'PUT',
            relevance: 250,
        });
    });

    it('omits a falsy relevance to preserve the server default contract', async () => {
        axios.post.mockResolvedValue({data: {id: 9, pivot: {relevance: 200}}});

        await useBibleversesStore().updateRelevance({materialId: 3, bibleverseId: 9, relevance: 0});

        expect(axios.post).toHaveBeenCalledWith('/api/v1/material/3/bibleverse/9', {_method: 'PUT'});
    });

    it('deletes one assignment with method spoofing', async () => {
        axios.post.mockResolvedValue({data: 1});

        await expect(useBibleversesStore().deleteAssignment({materialId: 3, bibleverseId: 9}))
            .resolves.toBe(1);
        expect(axios.post).toHaveBeenCalledWith('/api/v1/material/3/bibleverse/9', {_method: 'DELETE'});
    });

    it('queues deletion of multiple assignments in input order', async () => {
        axios.post
            .mockResolvedValueOnce({data: 1})
            .mockResolvedValueOnce({data: 2});

        await expect(useBibleversesStore().deleteMultipleAssignemts({
            bibleverseIds: [9, 10],
            materialId: 3,
        })).resolves.toEqual([1, 2]);

        expect(queue.add).toHaveBeenCalledTimes(2);
        expect(axios.post).toHaveBeenNthCalledWith(1, '/api/v1/material/3/bibleverse/9', {_method: 'DELETE'});
        expect(axios.post).toHaveBeenNthCalledWith(2, '/api/v1/material/3/bibleverse/10', {_method: 'DELETE'});
    });

    it('searches with the established POST payload and throws the established error value', async () => {
        const result = [{id: 9, label: '1Mo 1,1'}];
        axios.post.mockResolvedValueOnce({data: result});
        const store = useBibleversesStore();

        await expect(store.search('1. Mose 1,1')).resolves.toBe(result);
        expect(axios.post).toHaveBeenCalledWith('/pool/search/guess/bibleverses', {q: '1. Mose 1,1'});

        axios.post.mockRejectedValueOnce({data: 'Ungültige Bibelstelle'});
        await expect(store.search('ungültig')).rejects.toBe('Ungültige Bibelstelle');
    });
});
