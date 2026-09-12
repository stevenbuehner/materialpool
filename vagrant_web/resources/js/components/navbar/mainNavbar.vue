<template>
  <b-navbar toggleable="md" type="light" variant="light" fixed="top">

    <div class="container">

      <b-navbar-toggle target="nav_collapse"/>

      <b-navbar-brand :to="{name:'landingpage'}">MatPool</b-navbar-brand>

      <b-collapse is-nav id="nav_collapse">

        <b-navbar-nav>

          <b-nav-item :to="{name: 'resource-create'}" :title="$t('pool.Upload')">
            <upload-icon class="sb-icon sb-navbar-icon"/>
          </b-nav-item>

          <b-nav-item :to="{name: 'resource-text-create'}" :title="$t('pool.New')">
            <new-text-icon class="sb-icon sb-navbar-icon"/>
          </b-nav-item>

          <b-nav-item-dropdown right :text="$t('pool.Edit')" v-if="isAdmin">
            <b-dropdown-item :to="{name: 'keyword-list'}" class="dropdown-hover" v-if="isAdmin">{{
                $t('pool.Keywords')
              }}
            </b-dropdown-item>
            <b-dropdown-item :to="{name: 'bundle-list'}" class="dropdown-hover" v-if="isAdmin">{{ $t('pool.Bundle') }}
            </b-dropdown-item>
            <b-dropdown-item :to="{name: 'resource-lonely'}" class="dropdown-hover" v-if="isAdmin">
              {{ $t('pool.Lonely-Resources') }}
            </b-dropdown-item>
            <b-dropdown-item :to="{name: 'resource-newest'}" class="dropdown-hover" v-if="isAdmin">
              {{ $t('pool.Newest-Resources') }}
            </b-dropdown-item>
            <!-- Todo: Newest Materials Seite -->
            <b-dropdown-item :to="{name: 'newest-materials'}" class="dropdown-hover" :disabled="true">
              {{ $t('pool.Newest-Materials') }}
            </b-dropdown-item>
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
            <b-dropdown-item href="/logout" class="dropdown-hover">{{ $t('pool.Logout') }}</b-dropdown-item>
            <b-dropdown-item disabled href="#" class="dropdown-hover">{{ $t('pool.Settings') }}
            </b-dropdown-item>
            <b-dropdown-item :to="{name: 'system-shutdown'}" class="dropdown-hover bg-danger"
                             v-if="isAdmin">
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

export default {
  name: "mainNavbar",

  mixins: [asyncIsAdminMixin, asyncUsernameMixin],

  data() {
    return {
      schnellsuche: ''
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

.dropdown-hover:hover {
  background-color: lightgrey;
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
