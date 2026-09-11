import {beforeEach, describe, expect, it, vi} from 'vitest';
import {createPinia, setActivePinia} from 'pinia';
import axios from '../../resources/js/apps/main/axiosInstance.js';
import {queue} from '../../resources/js/apps/main/store/networkQueue.js';
import {useBibleverseCrossReferencesStore} from '../../resources/js/apps/main/stores/bibleverseCrossReferences.js';

vi.mock('../../resources/js/apps/main/axiosInstance.js', () => ({
    default: {get: vi.fn()},
}));

vi.mock('../../resources/js/apps/main/store/networkQueue.js', () => ({
    queue: {add: vi.fn(task => task())},
}));

describe('bibleverse cross references Pinia store', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
        queue.add.mockImplementation(task => task());
    });

    it('loads pages until the requested maximum and caches the collected items', async () => {
        const first = {target_from: 1001002, target_to: 0, relevance: 90};
        const second = {target_from: 1001003, target_to: 1001004, relevance: 80};
        const third = {target_from: 1001005, target_to: 0, relevance: 70};
        axios.get
            .mockResolvedValueOnce({data: {
                data: [first],
                next_page_url: '/api/v2/bibleverses/crossrefs/1001001-1001001?page=2',
                total: 3,
            }})
            .mockResolvedValueOnce({data: {
                data: [second, third],
                next_page_url: null,
                total: 3,
            }});
        const store = useBibleverseCrossReferencesStore();

        await expect(store.get({from: 1001001, maximum: 2})).resolves.toEqual([first, second]);
        await expect(store.get({from: 1001001, maximum: 3})).resolves.toEqual([first, second, third]);

        expect(axios.get).toHaveBeenCalledTimes(2);
        expect(axios.get).toHaveBeenNthCalledWith(1, '/api/v2/bibleverses/crossrefs/1001001-1001001');
        expect(axios.get).toHaveBeenNthCalledWith(
            2,
            '/api/v2/bibleverses/crossrefs/1001001-1001001?page=2'
        );
        expect(store.getCrossRefsCount('1001001-1001001')).toBe(3);
    });

    it('uses a complete cache without another request', async () => {
        const store = useBibleverseCrossReferencesStore();
        const refs = [{target_from: 1001002}, {target_from: 1001003}];
        store.setCrossRefs({rangeId: '1001001-1001001', refs});
        store.setCrossRefsCount({rangeId: '1001001-1001001', count: 2});

        await expect(store.get({from: 1001001, to: 1001001})).resolves.toEqual(refs);
        expect(axios.get).not.toHaveBeenCalled();
    });

    it('treats a zero total as a cached count', async () => {
        axios.get.mockResolvedValue({data: {data: [], next_page_url: null, total: 0}});
        const store = useBibleverseCrossReferencesStore();

        await expect(store.getCount({from: 1001001})).resolves.toBe(0);
        await expect(store.getCount({from: 1001001})).resolves.toBe(0);

        expect(axios.get).toHaveBeenCalledOnce();
    });

    it('returns the converted error message as a fulfilled action value', async () => {
        axios.get.mockRejectedValue({response: {data: {error: 'Querverweise nicht erreichbar'}}});

        await expect(useBibleverseCrossReferencesStore().get({from: 1001001}))
            .resolves.toBe('Querverweise nicht erreichbar');
    });

    it('queues multiple ranges and preserves their result order', async () => {
        axios.get
            .mockResolvedValueOnce({data: {
                data: [{target_from: 1001002}],
                next_page_url: null,
                total: 1,
            }})
            .mockResolvedValueOnce({data: {
                data: [{target_from: 1002002}],
                next_page_url: null,
                total: 1,
            }});

        await expect(useBibleverseCrossReferencesStore().getMultiple([
            {from: 1001001, maximum: 1},
            {from: 1002001, maximum: 1},
        ])).resolves.toEqual([
            [{target_from: 1001002}],
            [{target_from: 1002002}],
        ]);

        expect(queue.add).toHaveBeenCalledTimes(2);
    });
});
