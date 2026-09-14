import {beforeEach, describe, expect, it, vi} from 'vitest';
import {createPinia, setActivePinia} from 'pinia';
import axios from '../../resources/js/apps/main/axiosInstance.js';
import {useGeneralStore} from '../../resources/js/apps/main/stores/general.js';

vi.mock('../../resources/js/apps/main/axiosInstance.js', () => ({
    default: {get: vi.fn(), post: vi.fn()},
}));

const user = (overrides = {}) => ({
    id: 1,
    name: 'Test User',
    is_admin: true,
    frontend_user_settings: {},
    ...overrides,
});

const options = (currentUser = user()) => ({
    user: currentUser,
    server: {max_upload: 10485760},
    systemname: 'Materialpool Test',
});

describe('general Pinia store', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    it('coalesces options loading, caches the current user and exposes derived values', async () => {
        const response = options();
        axios.get.mockResolvedValue({data: response});
        const store = useGeneralStore();

        const first = store.options();
        const second = store.options();
        await expect(Promise.all([first, second])).resolves.toEqual([response, response]);

        expect(axios.get).toHaveBeenCalledOnce();
        expect(axios.get).toHaveBeenCalledWith('/api/v1/general/options');
        expect(store.getOptions()).toEqual(response);
        expect(store.getUserFromCache(1)).toEqual(response.user);
        await expect(store.maxUploadSize()).resolves.toBe(10485760);
        await expect(store.currentUser()).resolves.toEqual(response.user);
        await expect(store.currentUserId()).resolves.toBe(1);
        await expect(store.isAdmin()).resolves.toBe(true);
		await expect(store.hasPermission('materials.create')).resolves.toBe(true);
        await expect(store.systemName()).resolves.toBe('Materialpool Test');
    });

	it('evaluates effective permissions for non-admin users', async () => {
		const store = useGeneralStore();
		store.setOptions(options(user({is_admin: false, permissions: ['resources.create']})));

		await expect(store.hasPermission('resources.create')).resolves.toBe(true);
		await expect(store.hasPermission('system.shutdown')).resolves.toBe(false);
	});

    it('keeps the fulfilled options-error contract while allowing a retry', async () => {
        const store = useGeneralStore();
        axios.get
            .mockRejectedValueOnce({response: {data: {message: 'Optionen fehlgeschlagen'}}})
            .mockResolvedValueOnce({data: options()});

        await expect(store.options()).resolves.toBe('Optionen fehlgeschlagen');
        expect(store.getOptions()).toBeNull();
        await expect(store.options()).resolves.toEqual(options());
        expect(axios.get).toHaveBeenCalledTimes(2);
    });

    it('coalesces user loading and refreshes matching current-user options', async () => {
        const store = useGeneralStore();
        store.setOptions(options(user({name: 'Before'})));
        axios.get.mockResolvedValue({data: user({name: 'After'})});

        const first = store.getUser(1);
        const second = store.getUser(1);
        await expect(Promise.all([first, second])).resolves.toEqual([
            user({name: 'After'}),
            user({name: 'After'}),
        ]);

        expect(axios.get).toHaveBeenCalledOnce();
        expect(axios.get).toHaveBeenCalledWith('/api/v1/users/1');
        expect(store.getOptions().user.name).toBe('After');
    });

    it('keeps the fulfilled user-error contract while allowing a retry', async () => {
        const store = useGeneralStore();
        axios.get
            .mockRejectedValueOnce({response: {data: {error: 'Benutzer fehlgeschlagen'}}})
            .mockResolvedValueOnce({data: user({id: 2})});

        await expect(store.getUser(2)).resolves.toBe('Benutzer fehlgeschlagen');
        expect(store.getUserFromCache(2)).toBeNull();
        await expect(store.getUser(2)).resolves.toEqual(user({id: 2}));
        expect(axios.get).toHaveBeenCalledTimes(2);
    });

    it('reads nested current-user settings with the established default behavior', async () => {
        const store = useGeneralStore();
        store.setOptions(options(user({
            frontend_user_settings: {assign: {material: {defaulttemplate: 'Standard'}}},
        })));

        await expect(store.currentUserSetting({
            settingId: 'assign.material.defaulttemplate',
            defaultValue: null,
        })).resolves.toBe('Standard');
        await expect(store.currentUserSetting({
            settingId: 'assign.material.templates',
            defaultValue: {Existing: true},
        })).resolves.toEqual({Existing: true});
    });

    it('stores and removes nested settings while updating both user caches', async () => {
        const store = useGeneralStore();
        const initialUser = user({frontend_user_settings: {assign: {material: {templates: {Old: true}}}}});
        store.setOptions(options(initialUser));
        store.setUser(initialUser);
        axios.post.mockImplementation((_url, {data}) => Promise.resolve({
            data: user({frontend_user_settings: data}),
        }));

        await store.storeCurrentUserSetting({
            settingId: 'assign.material.templates.New',
            data: {title: 'Neue Vorlage'},
        });
        expect(axios.post).toHaveBeenLastCalledWith('/api/v1/users/1', {
            data: {
                assign: {material: {templates: {
                    Old: true,
                    New: {title: 'Neue Vorlage'},
                }}},
            },
        });
        expect(store.getOptions().user.frontend_user_settings.assign.material.templates.New)
            .toEqual({title: 'Neue Vorlage'});

        await store.removeCurrentUserSetting('assign.material.templates.Old');
        expect(axios.post).toHaveBeenCalledTimes(2);
        expect(store.getUserFromCache(1).frontend_user_settings.assign.material.templates)
            .toEqual({New: {title: 'Neue Vorlage'}});
    });

    it('preserves the fulfilled settings-write error value', async () => {
        const store = useGeneralStore();
        axios.post.mockRejectedValue({message: 'Speichern fehlgeschlagen'});

        await expect(store.storeCompleteUserSettings({userId: 3, allSettings: {}}))
            .resolves.toBe('Speichern fehlgeschlagen');
        expect(axios.post).toHaveBeenCalledWith('/api/v1/users/3', {data: {}});
    });
});
