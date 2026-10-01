<template>
  <b-navbar toggleable="md" type="light" variant="light" fixed="top">

    <div class="container">

      <b-navbar-toggle target="nav_collapse"/>

      <b-navbar-brand :to="{name:'landingpage'}" class="matpool-brand" :aria-label="'MatPool – ' + $t('pool.home')">
        <img src="/img/brand/matpool-logo.svg" alt="" class="matpool-brand-logo" aria-hidden="true">
      </b-navbar-brand>

      <b-collapse is-nav id="nav_collapse">

        <b-navbar-nav>

          <b-nav-item v-if="canCreateResources" :to="{name: 'resource-create'}" :title="$t('pool.Upload')">
            <upload-icon class="sb-icon sb-navbar-icon"/>
          </b-nav-item>

          <b-nav-item v-if="canCreateResources" :to="{name: 'resource-text-create'}" :title="$t('pool.New')">
            <new-text-icon class="sb-icon sb-navbar-icon"/>
          </b-nav-item>

          <b-nav-item-dropdown right :text="$t('pool.Edit')" v-if="isAdmin || canManageKeywords">
            <b-dropdown-item :to="{name: 'keyword-list'}" class="dropdown-hover" v-if="canManageKeywords">{{
                $t('pool.Keywords')
              }}
            </b-dropdown-item>
            <b-dropdown-item :to="{name: 'resource-lonely'}" class="dropdown-hover" v-if="isAdmin">
              {{ $t('pool.Lonely-Resources') }}
            </b-dropdown-item>
            <b-dropdown-item :to="{name: 'resource-newest'}" class="dropdown-hover" v-if="isAdmin">
              {{ $t('pool.Newest-Resources') }}
            </b-dropdown-item>
            <b-dropdown-item :to="{name: 'material-newest'}" class="dropdown-hover" v-if="isAdmin">
              {{ $t('pool.Newest-Materials') }}
            </b-dropdown-item>
            <b-dropdown-item :to="{name: 'material-recently-updated'}" class="dropdown-hover" v-if="isAdmin">
              {{ $t('pool.Recently-Updated-Materials') }}
            </b-dropdown-item>
          </b-nav-item-dropdown>

          <b-nav-item-dropdown right :text="$t('pool.Admin')" v-if="isAdmin || canManageBundles">
            <b-dropdown-item :to="{name: 'admin-queues'}" class="dropdown-hover" v-if="isAdmin">
              {{ $t('pool.queue-overview-title') }}
            </b-dropdown-item>
            <b-dropdown-item :to="{name: 'bundle-list'}" class="dropdown-hover" v-if="canManageBundles">
              {{ $t('pool.Bundle') }}
            </b-dropdown-item>
            <li v-if="isAdmin" role="presentation">
              <button
                type="button"
                class="dropdown-item d-flex justify-content-between align-items-center dropdown-hover"
                :aria-expanded="calibrationMenuOpen"
                aria-controls="calibration-submenu"
                @click.stop="calibrationMenuOpen = !calibrationMenuOpen"
              >
                {{ $t('pool.calibration-menu') }}
                <span aria-hidden="true">{{ calibrationMenuOpen ? '▾' : '▸' }}</span>
              </button>
            </li>
            <ul v-if="isAdmin && calibrationMenuOpen" id="calibration-submenu" class="calibration-submenu list-unstyled mb-0" role="group">
              <b-dropdown-item :to="{name: 'context-search-ocr-calibration'}" class="dropdown-hover calibration-submenu-item">
                {{ $t('pool.ocr-calibration-title') }}
              </b-dropdown-item>
              <b-dropdown-item :to="{name: 'context-search-evaluation-datasets'}" class="dropdown-hover calibration-submenu-item">
                {{ $t('pool.ai-datasets') }}
              </b-dropdown-item>
            </ul>
          </b-nav-item-dropdown>

          <b-nav-item :to="{name: 'readbible'}">{{ $t('pool.Bible') }}</b-nav-item>

          <b-nav-item :to="{name: 'search'}">{{ $t('pool.Searchmask') }}</b-nav-item>
        </b-navbar-nav>

        <!-- Right aligned nav items -->
        <b-navbar-nav class="ms-auto">

          <b-nav-form @submit.prevent="goForSearch">
            <b-input-group class="px-2">
              <b-form-input size="sm"
                            type="text"
                            :placeholder="$t('pool.Speedsearch')"
                            required
                            v-model="schnellsuche"/>

              <b-button size="sm" variant="outline-secondary" class="" type="submit">
                {{ $t('pool.Search') }}
              </b-button>
            </b-input-group>
          </b-nav-form>

          <b-nav-item-dropdown right :text="username">
            <b-dropdown-item :to="{name: 'profile'}" class="dropdown-hover">
              {{ $t('pool.Profile') }}
            </b-dropdown-item>
            <b-dropdown-item :to="{name: 'admin-users'}" class="dropdown-hover" v-if="isAdmin">
              {{ $t('pool.User-management') }}
            </b-dropdown-item>
            <b-dropdown-item-button class="dropdown-hover" :disabled="isLoggingOut" @click="logout">
              {{ $t('pool.Logout') }}
            </b-dropdown-item-button>
            <b-dropdown-item disabled href="#" class="dropdown-hover">{{ $t('pool.Settings') }}
            </b-dropdown-item>
            <b-dropdown-item :to="{name: 'system-shutdown'}" class="dropdown-hover bg-danger"
                             v-if="canShutdown">
              {{ $t('pool.Shutdown') }}
            </b-dropdown-item>
          </b-nav-item-dropdown>
        </b-navbar-nav>

      </b-collapse>

    </div>
  </b-navbar>
</template>

<script>
import {
  BButton,
  BCollapse,
  BDropdownItem,
  BDropdownItemButton,
  BFormInput,
  BInputGroup,
  BNavbar,
  BNavbarBrand,
  BNavbarNav,
  BNavbarToggle,
  BNavForm,
  BNavItem,
  BNavItemDropdown
}                                        from '@/adapters/bootstrap';
import {searchArrayObjectsToSearchQuery} from "../search/searchHelper";
import uploadIcon                        from '@icons/vendor/svg-icon/svg/icomoon/cloud-upload.svg';
import newTextIcon                       from '@icons/vendor/svg-icon/svg/zero/custom-text.svg';
import asyncIsAdminMixin                 from "../general/async-isAdmin-mixin";
import asyncUsernameMixin                from "../general/async-username-mixin";
import {useGeneralStore}                 from '../../apps/main/stores/general';
import axios                              from '../../apps/main/axiosInstance';

export default {
  name: "mainNavbar",

  mixins: [asyncIsAdminMixin, asyncUsernameMixin],

  asyncComputed: {
    canCreateResources: {get: () => useGeneralStore().hasPermission('resources.create'), default: false},
    canManageKeywords: {get: () => useGeneralStore().hasPermission('keywords.manage'), default: false},
    canManageBundles: {get: () => useGeneralStore().hasPermission('bundles.manage'), default: false},
    canShutdown: {get: () => useGeneralStore().hasPermission('system.shutdown'), default: false},
  },

  data() {
    return {
      schnellsuche: '',
      isLoggingOut: false,
      calibrationMenuOpen: false,
    };
  },

  methods: {
    goForSearch() {
      this.$router.push({
        name: 'search',
        params: {
          search: searchArrayObjectsToSearchQuery([[this.schnellsuche]])
        }
      });
    },

    async logout() {
      this.isLoggingOut = true;

      await axios.post('/logout');

      window.location.assign('/');
    },


  },

  components: {
    BNavbar,
    BNavbarToggle,
    BNavbarBrand,
    BNavbarNav,
    BNavItem,
    BNavForm,
    BNavItemDropdown,
    BDropdownItem,
    BDropdownItemButton,
    BCollapse,
    BFormInput,
    BButton,
    BInputGroup,
    uploadIcon, newTextIcon
  }
}
</script>

<style lang="scss" scoped>
@use "resources/sass/theme" as *;

.matpool-brand {
  padding-block: .125rem;
}

.matpool-brand-logo {
  display: block;
  width: auto;
  height: 2.25rem;
}

.dropdown-hover:hover {
  background-color: lightgrey;
}

.calibration-submenu-item :deep(.dropdown-item) {
  padding-left: 2rem;
}

.calibration-submenu {
  border-left: 1px solid var(--bs-border-color);
  margin-left: .75rem;
}

.sb-navbar-icon {
  height: 1.5em;
  width: 1.5em;

  path {
    fill: $navbar-light-color;
  }

  &:hover path {
    fill: $navbar-light-hover-color
  }
}

.router-link-active {
  .sb-navbar-icon path {
    fill: $navbar-light-active-color;
  }
}
</style>
