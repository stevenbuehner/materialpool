import {beforeEach, describe, expect, it, vi} from 'vitest';
import {createPinia, setActivePinia} from 'pinia';
import axios from '../../resources/js/apps/main/axiosInstance.js';
import {useBundlesStore} from '../../resources/js/apps/main/stores/bundles.js';

vi.mock('../../resources/js/apps/main/axiosInstance.js', () => ({
    default: {get: vi.fn(), post: vi.fn()},
}));

describe('bundles Pinia store', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    it('shares the first index request and caches bundles plus infos', async () => {
        const bundles = [{id: 1, uuid: 'basis', name: 'Basis'}];
        const infos = [{uuid: 'basis', version: '2.0'}];
        axios.get.mockResolvedValue({data: {bundles, infos}});
        const store = useBundlesStore();

        const first = store.allBundles();
        const concurrent = store.allBundles();

        await expect(Promise.all([first, concurrent])).resolves.toEqual([bundles, bundles]);
        await expect(store.allInfos()).resolves.toEqual(infos);
        expect(axios.get).toHaveBeenCalledOnce();
        expect(axios.get).toHaveBeenCalledWith('/api/v1/bundles');
    });

    it('forces a fresh index request and replaces both caches', async () => {
        axios.get
            .mockResolvedValueOnce({data: {
                bundles: [{id: 1, uuid: 'old'}],
                infos: [{uuid: 'old'}],
            }})
            .mockResolvedValueOnce({data: {
                bundles: [{id: 2, uuid: 'new'}],
                infos: [{uuid: 'new'}],
            }});
        const store = useBundlesStore();

        await store.allBundles();
        await expect(store.allBundles(true)).resolves.toEqual([{id: 2, uuid: 'new'}]);
        await expect(store.allInfos()).resolves.toEqual([{uuid: 'new'}]);
        expect(axios.get).toHaveBeenCalledTimes(2);
    });

    it('resolves bundle and info by uuid and rejects missing uuids', async () => {
        axios.get.mockResolvedValue({data: {
            bundles: [{id: 1, uuid: 'basis', name: 'Basis'}],
            infos: [{uuid: 'basis', version: '2.0'}],
        }});
        const store = useBundlesStore();

        await expect(store.getBundle('basis')).resolves.toMatchObject({id: 1, name: 'Basis'});
        await expect(store.getBundleInfo('basis')).resolves.toMatchObject({version: '2.0'});
        await expect(store.getBundle('missing')).rejects.toThrow('No bundle with the uuid missing found');
        await expect(store.getBundleInfo('missing')).rejects.toThrow('No info with the uuid missing found');
    });

    it('initializes update and uninstall jobs with disabled request timeouts', async () => {
        axios.post
            .mockResolvedValueOnce({data: {openJobs: 4}})
            .mockResolvedValueOnce({data: {openJobs: 2}});
        const store = useBundlesStore();

        await expect(store.initUpdateJobs(7)).resolves.toEqual({openJobs: 4});
        await expect(store.initUninstallJobs(7)).resolves.toEqual({openJobs: 2});

        expect(axios.post).toHaveBeenNthCalledWith(1, '/api/v1/bundles/7/init-update', {}, {timeout: 0});
        expect(axios.post).toHaveBeenNthCalledWith(2, '/api/v1/bundles/7/init-uninstall', {}, {timeout: 0});
    });

    it('rejects initialization errors so components can display the server failure', async () => {
        axios.post.mockRejectedValue({response: {data: {error: 'Queue nicht erreichbar'}}});

        await expect(useBundlesStore().initUpdateJobs(7)).rejects.toMatchObject({response: {data: {error: 'Queue nicht erreichbar'}}});
    });

    it('loads a persisted bundle import run status', async () => {
        axios.get.mockResolvedValue({data: {id: 'run-uuid', status: 'running'}});

        await expect(useBundlesStore().getRunStatus(7, 'run-uuid')).resolves.toMatchObject({status: 'running'});
        expect(axios.get).toHaveBeenCalledWith('/api/v1/bundles/7/runs/run-uuid');
    });

    it('loads the active persisted bundle import run without a run id', async () => {
        axios.get.mockResolvedValue({data: {id: 'run-uuid', status: 'running'}});

        await expect(useBundlesStore().getActiveRunStatus(7)).resolves.toMatchObject({status: 'running'});
        expect(axios.get).toHaveBeenCalledWith('/api/v1/bundles/7/runs/active');
    });

    it('runs jobs and merges the completed server bundle into the existing cache object', async () => {
        const cached = {id: 7, uuid: 'basis', installed_version: '1.0', update_available: true};
        const store = useBundlesStore();
        store.setAllBundles([cached]);
        axios.post.mockResolvedValue({data: {
            done: 2,
            open: 0,
            bundle: {id: 7, uuid: 'basis', installed_version: '2.0', update_available: false},
        }});

        await expect(store.runJobs(7)).resolves.toMatchObject({done: 2, open: 0});

        expect(axios.post).toHaveBeenCalledWith('/api/v1/bundles/7/run-update', {}, {timeout: 0});
        expect(store.bundles[0]).toMatchObject({installed_version: '2.0', update_available: false});
    });

    it('loads each bundle icon once and preserves the fulfilled icon error contract', async () => {
        axios.get.mockResolvedValueOnce({data: '<svg />'});
        const store = useBundlesStore();

        await expect(store.getBundleIcon(7)).resolves.toBe('<svg />');
        await expect(store.getBundleIcon(7)).resolves.toBe('<svg />');
        expect(axios.get).toHaveBeenCalledOnce();

        axios.get.mockRejectedValueOnce({response: {data: {message: 'Icon fehlt'}}});
        await expect(store.getBundleIcon(8)).resolves.toBe('Icon fehlt');
    });

    it('resolves bundle names by numeric id and keeps the string rejection for a miss', async () => {
        axios.get.mockResolvedValue({data: {
            bundles: [{id: 7, uuid: 'basis', name: 'Basis-Paket'}],
            infos: [],
        }});
        const store = useBundlesStore();

        await expect(store.getBundleNameById(7)).resolves.toBe('Basis-Paket');
        await expect(store.getBundleNameById(8)).rejects.toBe('not found');
    });
});
