<template>
  <main class="container pb-4">
    <h1>{{ $t('pool.User-management') }}</h1>
    <b-alert :show="Boolean(store.error)" variant="danger">{{ store.error }}</b-alert>

    <section class="card mb-4" aria-labelledby="users-heading">
      <div class="card-body">
        <h2 id="users-heading" class="h4">{{ $t('pool.Users') }}</h2>
        <form class="row g-2 mb-3" @submit.prevent="loadUsers(1)">
          <div class="col-md-6">
            <label class="form-label" for="admin-user-search">{{ $t('pool.Search') }}</label>
            <input id="admin-user-search" v-model="filters.search" class="form-control" type="search">
          </div>
          <div class="col-md-3">
            <label class="form-label" for="admin-user-status">{{ $t('pool.Status') }}</label>
            <select id="admin-user-status" v-model="filters.status" class="form-select">
              <option value="">{{ $t('pool.All') }}</option>
              <option v-for="status in statuses" :key="status" :value="status">{{ statusLabel(status) }}</option>
            </select>
          </div>
          <div class="col-md-3 d-flex align-items-end">
            <b-button type="submit" variant="primary" :disabled="store.loading">{{ $t('pool.Apply-filter') }}</b-button>
          </div>
        </form>

        <form class="border rounded p-3 mb-3" @submit.prevent="saveUser">
          <h3 class="h5">{{ userForm.id ? $t('pool.Edit-user') : $t('pool.Invite-user') }}</h3>
          <div class="row g-3">
            <div class="col-md-4"><label class="form-label">{{ $t('pool.Name') }}<input v-model="userForm.name" class="form-control" required></label></div>
            <div class="col-md-4"><label class="form-label">E-Mail<input v-model="userForm.email" class="form-control" type="email" required></label></div>
            <div v-if="userForm.id" class="col-md-4"><label class="form-label">{{ $t('pool.Status') }}<select v-model="userForm.status" class="form-select"><option v-for="status in statuses" :key="status" :value="status">{{ statusLabel(status) }}</option></select></label></div>
            <div class="col-md-6">
              <label class="form-label">{{ $t('pool.Groups') }}</label>
              <select v-model="userForm.group_ids" class="form-select" multiple :size="Math.min(6, store.groups.length || 1)">
                <option v-for="group in store.groups" :key="group.id" :value="group.id">{{ group.name }}</option>
              </select>
            </div>
            <div class="col-md-6 d-flex flex-column justify-content-center">
              <b-form-checkbox v-model="userForm.is_admin">{{ $t('pool.Global-admin') }}</b-form-checkbox>
            </div>
          </div>
          <div class="mt-3 d-flex gap-2">
            <b-button type="submit" variant="primary" :disabled="store.loading">{{ $t('pool.Save') }}</b-button>
            <b-button v-if="userForm.id" type="button" variant="secondary" @click="resetUserForm">{{ $t('pool.Cancel') }}</b-button>
          </div>
        </form>

        <div class="table-responsive">
          <table class="table table-striped align-middle">
            <thead><tr><th>{{ $t('pool.Name') }}</th><th>E-Mail</th><th>{{ $t('pool.Status') }}</th><th>{{ $t('pool.Groups') }}</th><th :aria-label="$t('pool.Actions')"></th></tr></thead>
            <tbody>
              <tr v-for="user in store.users" :key="user.id">
                <td>{{ user.name }} <span v-if="user.is_admin" class="badge bg-danger">{{ $t('pool.Global-admin') }}</span></td>
                <td>{{ user.email }}</td>
                <td>{{ statusLabel(user.status) }}</td>
                <td>{{ user.groups.map(group => group.name).join(', ') || '–' }}</td>
                <td class="text-nowrap text-end">
                  <b-button size="sm" variant="outline-primary" @click="editUser(user)">{{ $t('pool.Edit') }}</b-button>
                  <b-button v-if="user.status === 'invited'" size="sm" variant="outline-secondary" class="ms-2" @click="resendInvitation(user)">{{ $t('pool.Resend-invitation') }}</b-button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <nav class="d-flex justify-content-between align-items-center" :aria-label="$t('pool.Pagination')">
          <span>{{ store.pagination.total }} {{ $t('pool.Users') }}</span>
          <div class="btn-group">
            <b-button variant="outline-secondary" :disabled="store.pagination.current_page <= 1" @click="loadUsers(store.pagination.current_page - 1)">‹</b-button>
            <b-button variant="outline-secondary" disabled>{{ store.pagination.current_page }} / {{ store.pagination.last_page }}</b-button>
            <b-button variant="outline-secondary" :disabled="store.pagination.current_page >= store.pagination.last_page" @click="loadUsers(store.pagination.current_page + 1)">›</b-button>
          </div>
        </nav>
      </div>
    </section>

    <section class="card" aria-labelledby="groups-heading">
      <div class="card-body">
        <h2 id="groups-heading" class="h4">{{ $t('pool.Groups') }}</h2>
        <form class="border rounded p-3 mb-3" @submit.prevent="saveGroup">
          <h3 class="h5">{{ groupForm.id ? $t('pool.Edit-group') : $t('pool.Create-group') }}</h3>
          <label class="form-label">{{ $t('pool.Name') }}<input v-model="groupForm.name" class="form-control" required></label>
          <fieldset v-for="(permissions, area) in staticPermissionsByArea" :key="area" class="mt-3">
            <legend class="h6 text-capitalize">{{ area }}</legend>
            <div class="row">
              <div v-for="permission in permissions" :key="permission.code" class="col-lg-6">
                <b-form-checkbox :model-value="groupForm.permissions.includes(permission.code)" @update:model-value="togglePermission(permission.code, $event)">{{ permission.code }}</b-form-checkbox>
              </div>
            </div>
          </fieldset>
          <fieldset v-if="bundleReadPermissions.length" class="mt-3">
            <legend class="h6">{{ $t('pool.Bundle-read-permissions') }}</legend>
            <div class="row">
              <div v-for="permission in bundleReadPermissions" :key="permission.code" class="col-lg-6">
                <b-form-checkbox :model-value="groupForm.permissions.includes(permission.code)" @update:model-value="togglePermission(permission.code, $event)">
                  {{ permission.bundle.name }} <span class="text-muted">({{ permission.bundle.is_installed ? $t('pool.Bundle-installed') : $t('pool.Bundle-uninstalled') }}<template v-if="permission.bundle.installed_version"> · {{ permission.bundle.installed_version }}</template>)</span>
                </b-form-checkbox>
              </div>
            </div>
          </fieldset>
          <div class="mt-3 d-flex gap-2"><b-button type="submit" variant="primary" :disabled="store.loading">{{ $t('pool.Save') }}</b-button><b-button v-if="groupForm.id" variant="secondary" @click="resetGroupForm">{{ $t('pool.Cancel') }}</b-button></div>
        </form>

        <div class="list-group">
          <div v-for="group in store.groups" :key="group.id" class="list-group-item d-flex flex-column flex-md-row justify-content-between gap-2">
            <div><strong>{{ group.name }}</strong><div class="text-muted">{{ group.users_count }} {{ $t('pool.Users') }} · {{ group.permissions.length }} {{ $t('pool.Permissions') }}</div></div>
            <div class="text-nowrap"><b-button size="sm" variant="outline-primary" @click="editGroup(group)">{{ $t('pool.Edit') }}</b-button><b-button size="sm" variant="outline-danger" class="ms-2" :disabled="group.users_count > 0 || group.name === 'Standardnutzer'" @click="deleteGroup(group)">{{ $t('pool.Delete') }}</b-button></div>
          </div>
        </div>
      </div>
    </section>
  </main>
</template>

<script>
import {mapStores} from 'pinia';
import {BAlert, BButton, BFormCheckbox} from '@/adapters/bootstrap';
import {useAdminStore} from '../stores/admin';

const blankGroup = () => ({id: null, name: '', permissions: []});
const blankUser = () => ({id: null, name: '', email: '', status: 'invited', is_admin: false, group_ids: []});

export default {
	name: 'AdminUsers',
	components: {BAlert, BButton, BFormCheckbox},
	data: () => ({filters: {search: '', status: ''}, statuses: ['invited', 'active', 'suspended'], userForm: blankUser(), groupForm: blankGroup()}),
	computed: {
		...mapStores(useAdminStore),
		store() { return this.adminStore; },
		staticPermissionsByArea() { return this.store.permissions.filter(permission => permission.area !== 'bundle-read').reduce((groups, permission) => ({...groups, [permission.area]: [...(groups[permission.area] || []), permission]}), {}); },
		bundleReadPermissions() { return this.store.permissions.filter(permission => permission.area === 'bundle-read'); },
	},
	async mounted() {
		await this.store.loadReferenceData();
		this.resetUserForm();
		await this.loadUsers(1);
	},
	methods: {
		statusLabel(status) { return this.$t(`pool.User-status-${status}`); },
		async loadUsers(page) { await this.store.loadUsers({...this.filters, page}); },
		resetUserForm() {
			const defaultGroup = this.store.groups.find(group => group.name === 'Standardnutzer');
			this.userForm = {...blankUser(), group_ids: defaultGroup ? [defaultGroup.id] : []};
		},
		editUser(user) { this.userForm = {...user, group_ids: user.groups.map(group => group.id)}; this.$nextTick(() => document.querySelector('#users-heading')?.scrollIntoView()); },
		async saveUser() {
			try {
				if (this.userForm.id) await this.store.updateUser(this.userForm); else await this.store.createUser(this.userForm);
				this.resetUserForm(); await this.loadUsers(this.store.pagination.current_page); await this.store.loadReferenceData();
			} catch (_) { /* Store zeigt die lokalisierbare Servermeldung an. */ }
		},
		async resendInvitation(user) { try { await this.store.resendInvitation(user.id); } catch (_) { /* siehe Store */ } },
		resetGroupForm() { this.groupForm = blankGroup(); },
		editGroup(group) { this.groupForm = {...group, permissions: [...group.permissions]}; },
		togglePermission(code, enabled) { this.groupForm.permissions = enabled ? [...new Set([...this.groupForm.permissions, code])] : this.groupForm.permissions.filter(permission => permission !== code); },
		async saveGroup() { try { if (this.groupForm.id) await this.store.updateGroup(this.groupForm); else await this.store.createGroup(this.groupForm); this.resetGroupForm(); await this.store.loadReferenceData(); await this.loadUsers(this.store.pagination.current_page); } catch (_) { /* siehe Store */ } },
		async deleteGroup(group) { if (!window.confirm(this.$t('pool.Confirm-delete-group'))) return; try { await this.store.deleteGroup(group.id); await this.store.loadReferenceData(); } catch (_) { /* siehe Store */ } },
	},
};
</script>
