import {beforeEach, describe, expect, it, vi} from 'vitest';
import {createPinia, setActivePinia} from 'pinia';
import axios from '../../resources/js/apps/main/axiosInstance.js';
import {useUsersStore} from '../../resources/js/apps/main/stores/users.js';

vi.mock('../../resources/js/apps/main/axiosInstance.js', () => ({
    default: {
        get: vi.fn(),
    },
}));

describe('users Pinia store', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    it('searches with the existing parameters and indexes returned users by id', async () => {
        const users = [{id: 7, name: 'Ada'}, {id: 9, name: 'Grace'}];
        axios.get.mockResolvedValue({data: users});
        const store = useUsersStore();

        await expect(store.search({search: 'a', limit: 2})).resolves.toBe(users);
        expect(axios.get).toHaveBeenCalledWith('/api/v2/users/find', {
            params: {s: 'a', limit: 2},
        });
        expect(store.hasUser(7)).toBe(true);
        expect(store.getUser(7)).toEqual(users[0]);
        expect(store.getUser(8)).toBeUndefined();
    });

    it('omits a falsy limit and preserves the converted rejection', async () => {
        axios.get.mockRejectedValue({response: {data: {message: 'Nicht erreichbar'}}});

        await expect(useUsersStore().search({search: 'Ada', limit: 0}))
            .rejects.toBe('Nicht erreichbar');
        expect(axios.get).toHaveBeenCalledWith('/api/v2/users/find', {
            params: {s: 'Ada'},
        });
    });

    it('can clear a cached user without affecting other entries', () => {
        const store = useUsersStore();
        store.setUsers([{id: 1}, {id: 2}]);

        store.clearUser(1);

        expect(store.users).toEqual({2: {id: 2}});
    });
});
