import {beforeEach, describe, expect, it, vi} from 'vitest';
import {createPinia, setActivePinia} from 'pinia';
import axios from '../../resources/js/apps/main/axiosInstance.js';
import {queue} from '../../resources/js/apps/main/store/networkQueue.js';
import {useBibleContentsStore} from '../../resources/js/apps/main/stores/bibleContents.js';
import {useBiblesStore} from '../../resources/js/apps/main/stores/bibles.js';

vi.mock('../../resources/js/apps/main/axiosInstance.js', () => ({
    default: {get: vi.fn()},
}));

vi.mock('../../resources/js/apps/main/store/networkQueue.js', () => ({
    queue: {add: vi.fn(task => task())},
}));

describe('bible contents Pinia store', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
        queue.add.mockImplementation(task => task());
    });

    it('loads a default translation through the queue and caches both range keys', async () => {
        const bible = {uuid: 'basis', title: 'BasisBibel'};
        const verses = [{verse: 1001001, bibleUuid: 'basis', text: 'Am Anfang'}];
        axios.get.mockResolvedValue({data: {bible, verses}});
        const store = useBibleContentsStore();

        await expect(store.get({from: 1001001, to: 1001002})).resolves.toEqual(verses);
        await expect(store.get({from: 1001001, to: 1001002})).resolves.toEqual(verses);
        await expect(store.get({from: 1001001, to: 1001002, bibleUuid: 'basis'})).resolves.toEqual(verses);

        expect(queue.add).toHaveBeenCalledOnce();
        expect(axios.get).toHaveBeenCalledOnce();
        expect(axios.get).toHaveBeenCalledWith('/api/v1/biblecontents/1001001-1001002');
        expect(useBiblesStore().getBible('basis')).toEqual(bible);
    });

    it('coalesces concurrent requests for the same range', async () => {
        let resolveRequest;
        axios.get.mockReturnValue(new Promise(resolve => {
            resolveRequest = resolve;
        }));
        const store = useBibleContentsStore();

        const first = store.get({from: 1001001, to: 1001001, bibleUuid: 'lut'});
        const concurrent = store.get({from: 1001001, to: 1001001, bibleUuid: 'lut'});
        const response = {
            bible: {uuid: 'lut', title: 'Luther 2017'},
            verses: [{verse: 1001001, bibleUuid: 'lut', text: 'Im Anfang'}],
        };
        resolveRequest({data: response});

        await expect(Promise.all([first, concurrent])).resolves.toEqual([response.verses, response.verses]);
        expect(queue.add).toHaveBeenCalledOnce();
        expect(axios.get).toHaveBeenCalledOnce();
    });

    it('clears a rejected in-flight cache entry so a later call can retry', async () => {
        const failure = new Error('Nicht erreichbar');
        const success = {
            bible: {uuid: 'basis', title: 'BasisBibel'},
            verses: [{verse: 1001001, bibleUuid: 'basis', text: 'Am Anfang'}],
        };
        axios.get.mockRejectedValueOnce(failure).mockResolvedValueOnce({data: success});
        const store = useBibleContentsStore();

        await expect(store.get({from: 1001001, to: 1001001})).rejects.toBe(failure);
        await expect(store.get({from: 1001001, to: 1001001})).resolves.toEqual(success.verses);

        expect(axios.get).toHaveBeenCalledTimes(2);
    });

    it('loads multiple ranges with the same result ordering', async () => {
        axios.get
            .mockResolvedValueOnce({data: {
                bible: {uuid: 'basis'},
                verses: [{verse: 1001001, text: 'A'}],
            }})
            .mockResolvedValueOnce({data: {
                bible: {uuid: 'basis'},
                verses: [{verse: 1001002, text: 'B'}],
            }});

        await expect(useBibleContentsStore().getMultiple([
            {from: 1001001, to: 1001001},
            {from: 1001002, to: 1001002},
        ])).resolves.toEqual([
            [{verse: 1001001, text: 'A'}],
            [{verse: 1001002, text: 'B'}],
        ]);
    });

    it('searches with the existing route and caches a single resolved range under both keys', async () => {
        const data = {
            bible: {uuid: 'basis', title: 'BasisBibel'},
            bibleverses: [{from: 43003016, to: 43003016}],
            verses: [{verse: 43003016, bibleUuid: 'basis', text: 'Also hat Gott'}],
        };
        axios.get.mockResolvedValue({data});
        const store = useBibleContentsStore();

        await expect(store.searchAndGet({search: 'Johannes 3,16'})).resolves.toBe(data);

        expect(axios.get).toHaveBeenCalledWith('/api/v1/biblecontents/search', {
            params: {search: 'Johannes 3,16'},
        });
        expect(store.getBibleverse('43003016-43003016')).toEqual(data.verses);
        expect(store.getBibleverse('43003016-43003016basis')).toEqual(data.verses);
    });

    it('logs and throws the existing API error value from search', async () => {
        const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {});
        axios.get.mockResolvedValue({data: {error: 'Ungültige Bibelstelle'}});

        await expect(useBibleContentsStore().searchAndGet({search: 'ungültig'}))
            .rejects.toBe('Ungültige Bibelstelle');
        expect(consoleError).toHaveBeenCalledWith('Ungültige Bibelstelle');
    });
});
