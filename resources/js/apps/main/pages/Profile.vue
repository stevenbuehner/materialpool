<template>
  <main class="container pb-4">
    <h1>{{ $t('pool.Profile') }}</h1>
    <div v-if="loadError" class="alert alert-danger" role="alert">{{ loadError }}</div>
    <div v-else-if="loading" role="status">{{ $t('pool.Loading') }}</div>
    <template v-else>
      <div v-if="success" class="alert alert-success" role="status">{{ success }}</div>
      <section class="card mb-3" aria-labelledby="profile-name-heading">
        <div class="card-body">
          <h2 id="profile-name-heading" class="h5">{{ $t('pool.Name') }}</h2>
          <form @submit.prevent="saveName">
            <label class="form-label" for="profile-name">{{ $t('pool.Name') }}</label>
            <input id="profile-name" v-model="name" class="form-control" required maxlength="255" autocomplete="name" :aria-invalid="Boolean(nameError)">
            <div v-if="nameError" class="text-danger mt-1" role="alert">{{ nameError }}</div>
            <button class="btn btn-primary mt-3" type="submit" :disabled="busy || name.trim() === profile.name">{{ $t('pool.Save') }}</button>
          </form>
        </div>
      </section>
      <section class="card mb-3" aria-labelledby="profile-email-heading">
        <div class="card-body">
          <h2 id="profile-email-heading" class="h5">{{ $t('pool.Profile-email') }}</h2>
          <p class="text-muted">{{ $t('pool.Profile-email-hint') }}</p>
          <form @submit.prevent="saveEmail">
            <label class="form-label" for="profile-email">{{ $t('pool.Profile-email') }}</label>
            <input id="profile-email" v-model="email" class="form-control" type="email" required maxlength="255" autocomplete="email" :aria-invalid="Boolean(emailError)">
            <div v-if="emailError" class="text-danger mt-1" role="alert">{{ emailError }}</div>
            <label class="form-label mt-3" for="profile-email-password">{{ $t('pool.Profile-current-password') }}</label>
            <input id="profile-email-password" v-model="emailPassword" class="form-control" type="password" required autocomplete="current-password" :aria-invalid="Boolean(emailPasswordError)">
            <div v-if="emailPasswordError" class="text-danger mt-1" role="alert">{{ emailPasswordError }}</div>
            <button class="btn btn-primary mt-3" type="submit" :disabled="busy || email.trim().toLowerCase() === profile.email">{{ $t('pool.Save') }}</button>
          </form>
        </div>
      </section>
      <section class="card mb-3" aria-labelledby="profile-password-heading">
        <div class="card-body">
          <h2 id="profile-password-heading" class="h5">{{ $t('pool.Profile-password') }}</h2>
          <button class="btn btn-outline-primary" type="button" @click="openPasswordModal">{{ $t('pool.Profile-change-password') }}</button>
        </div>
      </section>
      <p class="text-muted small">{{ $t('pool.Profile-created-at') }}: {{ createdAt }}</p>
    </template>

    <b-modal ref="passwordModal" hide-footer :title="$t('pool.Profile-change-password')" @shown="$refs.passwordCurrentInput.focus()" @hidden="resetPasswordForm">
      <form @submit.prevent="savePassword">
        <p>{{ $t('pool.Profile-password-hint', {count: profile?.password_min_length ?? ''}) }}</p>
        <div v-if="passwordError" class="alert alert-danger" role="alert">{{ passwordError }}</div>
        <label class="form-label" for="profile-current-password">{{ $t('pool.Profile-current-password') }}</label>
        <input id="profile-current-password" ref="passwordCurrentInput" v-model="passwordForm.current_password" class="form-control" type="password" required autocomplete="current-password">
        <label class="form-label mt-3" for="profile-new-password">{{ $t('pool.Profile-new-password') }}</label>
        <input id="profile-new-password" v-model="passwordForm.password" class="form-control" type="password" required autocomplete="new-password" :minlength="profile?.password_min_length">
        <label class="form-label mt-3" for="profile-confirm-password">{{ $t('pool.Profile-confirm-password') }}</label>
        <input id="profile-confirm-password" v-model="passwordForm.password_confirmation" class="form-control" type="password" required autocomplete="new-password">
        <div class="d-flex gap-2 mt-3">
          <button class="btn btn-primary" type="submit" :disabled="busy">{{ $t('pool.Save') }}</button>
          <button class="btn btn-secondary" type="button" :disabled="busy" @click="$refs.passwordModal.hide()">{{ $t('pool.Cancel') }}</button>
        </div>
      </form>
    </b-modal>
  </main>
</template>

<script>
import {BModal} from '@/adapters/bootstrap';
import axios from '../axiosInstance';
import {useGeneralStore} from '../stores/general';

const emptyPasswordForm = () => ({current_password: '', password: '', password_confirmation: ''});

export default {
  name: 'ProfilePage',
  components: {BModal},
  data: () => ({profile: null, name: '', email: '', emailPassword: '', passwordForm: emptyPasswordForm(), loading: true, busy: false, loadError: '', nameError: '', emailError: '', emailPasswordError: '', passwordError: '', success: ''}),
  computed: {
    createdAt() { return this.profile?.created_at ? new Intl.DateTimeFormat(undefined, {dateStyle: 'long'}).format(new Date(this.profile.created_at)) : '–'; },
  },
  mounted() { this.load(); },
  methods: {
    async load() {
      try {
        const {data} = await axios.get('/api/v2/profile');
        this.applyProfile(data);
      } catch (error) {
        this.loadError = this.errorMessage(error);
      } finally {
        this.loading = false;
      }
    },
    applyProfile(profile) {
      this.profile = profile;
      this.name = profile.name;
      this.email = profile.email;
      useGeneralStore().updateCurrentUserProfile(profile);
    },
    errorMessage(error) { return error.response?.data?.message || this.$t('pool.Profile-save-error'); },
    fieldError(error, field) { return error.response?.data?.errors?.[field]?.[0] || this.errorMessage(error); },
    async saveName() {
      this.busy = true; this.nameError = ''; this.success = '';
      try {
        const {data} = await axios.patch('/api/v2/profile/name', {name: this.name});
        this.applyProfile(data);
        this.success = this.$t('pool.Profile-name-saved');
      } catch (error) { this.nameError = this.fieldError(error, 'name'); }
      finally { this.busy = false; }
    },
    async saveEmail() {
      this.busy = true; this.emailError = ''; this.emailPasswordError = ''; this.success = '';
      try {
        const {data} = await axios.patch('/api/v2/profile/email', {email: this.email, current_password: this.emailPassword});
        this.applyProfile(data);
        this.emailPassword = '';
        this.success = this.$t('pool.Profile-email-saved');
      } catch (error) {
        const field = error.response?.data?.errors?.current_password ? 'current_password' : 'email';
        this[field === 'email' ? 'emailError' : 'emailPasswordError'] = this.fieldError(error, field);
      } finally { this.busy = false; }
    },
    openPasswordModal() { this.resetPasswordForm(); this.$refs.passwordModal.show(); },
    resetPasswordForm() { this.passwordForm = emptyPasswordForm(); this.passwordError = ''; },
    async savePassword() {
      this.busy = true; this.passwordError = '';
      try {
        if (this.passwordForm.password !== this.passwordForm.password_confirmation) {
          this.passwordError = this.$t('pool.Profile-password-mismatch');
          return;
        }
        await axios.put('/api/v2/profile/password', this.passwordForm);
        this.$refs.passwordModal.hide();
        window.location.assign('/login');
      } catch (error) {
        const field = Object.keys(error.response?.data?.errors || {})[0] || 'password';
        this.passwordError = this.fieldError(error, field);
        this.busy = false;
        return;
      }
    },
  },
};
</script>
