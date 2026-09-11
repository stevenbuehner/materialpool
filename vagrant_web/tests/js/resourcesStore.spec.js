import {beforeEach, describe, expect, it, vi} from 'vitest';
import {createPinia, setActivePinia} from 'pinia';
import axios from '../../resources/js/apps/main/axiosInstance.js';
import {queue} from '../../resources/js/apps/main/store/networkQueue.js';
import {useMaterialsStore} from '../../resources/js/apps/main/stores/materials.js';
import {useResourcesStore} from '../../resources/js/apps/main/stores/resources.js';

vi.mock('../../resources/js/apps/main/axiosInstance.js', () => ({
    default: {delete: vi.fn(), get: vi.fn(), post: vi.fn(), put: vi.fn()},
}));

vi.mock('../../resources/js/apps/main/store/networkQueue.js', () => ({
    queue: {add: vi.fn(task => task())},
}));

describe('resources Pinia store', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
        queue.add.mockImplementation(task => task());
    });

    it('coalesces loads, caches resources and retries after a failure', async () => {
        axios.get.mockResolvedValueOnce({data: {id: 9, notes: 'Detail'}});
        const store = useResourcesStore();
        await expect(Promise.all([store.get(9), store.get(9)])).resolves.toEqual([
            {id: 9, notes: 'Detail'},
            {id: 9, notes: 'Detail'},
        ]);
        expect(axios.get).toHaveBeenCalledOnce();
        expect(axios.get).toHaveBeenCalledWith('/api/v1/resources/9', {
            params: {relations: ['materials', 'materials.keywords', 'materials.bibleverses', 'creator']},
        });

        axios.get.mockRejectedValueOnce({response: {data: {message: 'Laden fehlgeschlagen'}}});
        await expect(store.get(10)).rejects.toBe('Laden fehlgeschlagen');
        expect(store.getResourceLoadingPromise(10)).toBe(false);
    });

    it('queues multiple resource loads in result order', async () => {
        axios.get
            .mockResolvedValueOnce({data: {id: 9}})
            .mockResolvedValueOnce({data: {id: 10}});

        await expect(useResourcesStore().getMultiple([9, 10])).resolves.toEqual([{id: 9}, {id: 10}]);
        expect(queue.add).toHaveBeenCalledTimes(2);
    });

    it('creates, updates and deletes text resources with cache updates', async () => {
        const store = useResourcesStore();
        axios.post.mockResolvedValueOnce({data: {id: 9, content: 'Text'}});
        await store.createTextResource({text: 'Text'});
        expect(axios.post).toHaveBeenCalledWith('/api/v1/resources', {
            content: 'Text', notes: '', is_public: false,
        });

        axios.put.mockResolvedValueOnce({data: {id: 9, content: 'Neu'}});
        await store.update({id: 9, data: {content: 'Neu'}});
        expect(store.updateResource(9).content).toBe('Neu');

        axios.delete.mockResolvedValueOnce({data: {success: true}});
        await expect(store.deleteResource(9)).resolves.toBe(true);
        expect(store.updateResource(9)).toBeNull();
    });

    it('creates a material and invalidates its resource objects by id', async () => {
        const store = useResourcesStore();
        store.setResource({id: 9});
        axios.post.mockResolvedValue({data: {id: 7, resources: [{id: 9}]}});

        await store.autoCreateMaterial({resourceIds: [9]});

        expect(axios.post).toHaveBeenCalledWith('/api/v1/resources/create-material', {
            resourceIds: [9], from_bot: false, meta: '',
        });
        expect(store.updateResource(9)).toBeNull();
        expect(useMaterialsStore().getMaterial(7)).toEqual({id: 7, resources: [{id: 9}]});
    });

    it('builds find parameters, caches results and delegates lonely searches', async () => {
        const page = {data: [{id: 9}], current_page: 2};
        axios.get.mockResolvedValue({data: page});
        const store = useResourcesStore();

        await expect(store.find({is_public: false, missing_materials: true, page: 2}))
            .resolves.toBe(page);
        expect(axios.get).toHaveBeenNthCalledWith(1, '/api/v1/resources/find', {
            params: {is_public: false, missing_materials: true, page: 2},
        });
        expect(store.updateResource(9)).toEqual({id: 9});

        await store.lonely({page: 3});
        expect(axios.get).toHaveBeenNthCalledWith(2, '/api/v1/resources/find', {
            params: {missing_materials: true, page: 3},
        });
    });

    it('replaces a resource and invalidates affected material objects by id', async () => {
        const store = useResourcesStore();
        store.setResource({id: 9});
        store.setResource({id: 10});
        useMaterialsStore().setMaterial({id: 7});
        axios.post.mockResolvedValue({data: {id: 10, materials: [{id: 7}]}});

        await store.replaceResource({oldResourceId: 9, newResourceId: 10});

        expect(axios.post).toHaveBeenCalledWith('/api/v1/resources/replace/9/with/10');
        expect(store.updateResource(9)).toBeNull();
        expect(store.updateResource(10)).toEqual({id: 10, materials: [{id: 7}]});
        expect(useMaterialsStore().getMaterial(7)).toBeNull();
    });
});
