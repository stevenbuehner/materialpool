<template>
    <b-nav-bar toggleable="md" type="light" variant="light" fixed="top">

        <div class="container">

            <b-navbar-toggle target="nav_collapse"></b-navbar-toggle>

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
                    </b-nav-item-dropdown>

                    <b-nav-item :to="{name: 'readbible'}">{{$t('pool.Read-bible')}}</b-nav-item>

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
                    </b-nav-item-dropdown>
                </b-navbar-nav>

            </b-collapse>

        </div>
    </b-nav-bar>
</template>

<script>
    import bNavBar from 'bootstrap-vue/src/components/navbar/navbar';
    import bNavbarToggle from 'bootstrap-vue/src/components/navbar/navbar-toggle';
    import bNavbarBrand from 'bootstrap-vue/src/components/navbar/navbar-brand';
    import bNavbarNav from 'bootstrap-vue/src/components/navbar/navbar-nav';
    import bNavItem from 'bootstrap-vue/src/components/nav/nav-item';
    import bNavItemDropdown from 'bootstrap-vue/src/components/nav/nav-item-dropdown';
    import bDropdownItem from 'bootstrap-vue/src/components/dropdown/dropdown-item';
    import bDropdownDivider from 'bootstrap-vue/src/components/dropdown/dropdown-divider';
    import bNavForm from 'bootstrap-vue/src/components/nav/nav-form';
    import bCollapse from 'bootstrap-vue/src/components/collapse/collapse';
    import bFormInput from 'bootstrap-vue/src/components/form-input/form-input';
    import bInputGroup from 'bootstrap-vue/src/components/input-group/input-group';
    import bInputGroupAppend from 'bootstrap-vue/src/components/input-group/input-group-append';
    import bButton from 'bootstrap-vue/src/components/button/button';
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
            }
        },

        components: {
            bNavBar,
            bNavbarToggle,
            bNavbarBrand,
            bNavbarNav,
            bNavItem,
            bNavForm,
            bNavItemDropdown,
            bDropdownDivider,
            bDropdownItem,
            bCollapse,
            bFormInput,
            bButton,
            bInputGroup,
            bInputGroupAppend,
        }
    }
</script>

<style scoped>
    .dropdown-hover:hover {
        background-color: lightgrey;
    }
</style>