<template>
    <b-navbar toggleable="md" type="light" variant="light" fixed="top">

        <div class="container">

            <b-navbar-toggle target="nav_collapse"/>

            <b-navbar-brand :to="{name:'landingpage'}">MatPool</b-navbar-brand>

            <b-collapse is-nav id="nav_collapse">

                <b-navbar-nav>

                    <b-nav-item :to="{name: 'resource-create'}">{{$t('pool.Upload')}}</b-nav-item>

                    <b-nav-item :to="{name: 'resource-text-create'}">{{$t('pool.New')}}</b-nav-item>

                    <b-nav-item-dropdown right :text="$t('pool.Edit')">
                        <b-dropdown-item :to="{name: 'keyword-list'}" class="dropdown-hover">{{$t('pool.Keywords')}}
                        </b-dropdown-item>
                        <b-dropdown-item :to="{name: 'bundle-list'}" class="dropdown-hover">{{$t('pool.Bundle')}}
                        </b-dropdown-item>
                        <b-dropdown-item :to="{name: 'resource-lonely'}" class="dropdown-hover">
                            {{$t('pool.Lonely-Resources')}}
                        </b-dropdown-item>
                        <b-dropdown-item :to="{name: 'resource-newest'}" class="dropdown-hover">
                            {{$t('pool.Newest-Resources')}}
                        </b-dropdown-item>
                        <!-- Todo: Newest Materials Seite -->
                        <b-dropdown-item :to="{name: 'newest-materials'}" class="dropdown-hover" :disabled="true">
                            {{$t('pool.Newest-Materials')}}
                        </b-dropdown-item>
                    </b-nav-item-dropdown>

                    <b-nav-item :to="{name: 'readbible'}">{{$t('pool.Bible')}}</b-nav-item>

                    <b-nav-item :to="{name: 'search'}">{{$t('pool.Searchmask')}}</b-nav-item>
                </b-navbar-nav>

                <!-- Right aligned nav items -->
                <b-navbar-nav class="ml-auto">

                    <b-nav-form @submit="goForSearch">
                        <b-input-group>
                            <b-form-input size="sm" c
                                          lass="mr-sm-2"
                                          type="text"
                                          :placeholder="$t('pool.Speedsearch')"
                                          required
                                          v-model="schnellsuche"/>

                            <b-input-group-append>
                                <b-button size="sm" variant="outline-secondary" class="" type="submit">
                                    {{$t('pool.Search')}}
                                </b-button>
                            </b-input-group-append>
                        </b-input-group>
                    </b-nav-form>

                    <b-nav-item-dropdown right :text="username">
                        <b-dropdown-item href="/logout" class="dropdown-hover">{{$t('pool.Logout')}}</b-dropdown-item>
                        <b-dropdown-item disabled href="#" class="dropdown-hover">{{$t('pool.Settings')}}
                        </b-dropdown-item>
                        <b-dropdown-item :to="{name: 'system-shutdown'}" class="dropdown-hover bg-danger"
                                         v-if="isAdmin">
                            {{$t('pool.Shutdown')}}
                        </b-dropdown-item>
                    </b-nav-item-dropdown>
                </b-navbar-nav>

            </b-collapse>

        </div>
    </b-navbar>
</template>

<script>
	import {BNavbar}                         from 'bootstrap-vue';
	import {BNavbarToggle}                   from 'bootstrap-vue';
	import {BNavbarBrand}                    from 'bootstrap-vue';
	import {BNavbarNav}                      from 'bootstrap-vue';
	import {BNavItem}                        from 'bootstrap-vue';
	import {BNavItemDropdown}                from 'bootstrap-vue';
	import {BDropdownItem}                   from 'bootstrap-vue';
	import {BDropdownDivider}                from 'bootstrap-vue';
	import {BNavForm}                        from 'bootstrap-vue';
	import {BCollapse}                       from 'bootstrap-vue';
	import {BFormInput}                      from 'bootstrap-vue';
	import {BInputGroup}                     from 'bootstrap-vue';
	import {BInputGroupAppend}               from 'bootstrap-vue';
	import {BButton}                         from 'bootstrap-vue';
	import {searchArrayObjectsToSearchQuery} from "../search/searchHelper";

	export default {
		name: "mainNavbar",

		data() {
			return {
				schnellsuche: ''
			};
		},

		asyncComputed: {
			username: {
				get() {
					return this.$store.dispatch('general/currentUser')
					           .then((user) => {
						           return user.name;
					           });
				},
				default: 'User',
				/* watch() {
                    this.forceReload
                }*/
			},

			isAdmin: {
				get() {
					return this.$store.dispatch('general/isAdmin')
					           .then((isAdmin) => {
						           return isAdmin;
					           });
				},
				default: false,
				/* watch() {
                    this.forceReload
                }*/
			}
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
			BDropdownDivider,
			BDropdownItem,
			BCollapse,
			BFormInput,
			BButton,
			BInputGroup,
			BInputGroupAppend,
		}
	}
</script>

<style scoped>
    .dropdown-hover:hover {
        background-color: lightgrey;
    }
</style>