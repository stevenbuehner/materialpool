<template>
    <b-nav-bar toggleable="md" type="light" variant="light" fixed="top">

        <div class="container">

            <b-navbar-toggle target="nav_collapse"></b-navbar-toggle>

            <b-navbar-brand href="#">MatPool</b-navbar-brand>

            <b-collapse is-nav id="nav_collapse">

                <b-navbar-nav>

                    <b-nav-item :to="{name: 'resource-create'}">{{$t('pool.New')}}</b-nav-item>
                    <b-nav-item :to="{name: 'search'}">{{$t('pool.Searchmask')}}</b-nav-item>
                </b-navbar-nav>

                <!-- Right aligned nav items -->
                <b-navbar-nav class="ml-auto">

                    <b-nav-form @submit="goForSearch">
                        <b-form-input size="sm" class="mr-sm-2" type="text" :placeholder="$t('pool.Speedsearch')" required
                                      v-model="schnellsuche"/>
                        <b-button size="sm" class="my-sm-0 btn-outline-dark" type="submit">{{$t('pool.Search')}}</b-button>
                    </b-nav-form>

                    <b-nav-item-dropdown right text="admin">
                        <b-dropdown-item href="/logout" class="dropdown-hover">{{$t('pool.Logout')}}</b-dropdown-item>
                        <b-dropdown-item href="#" class="dropdown-hover">{{$t('pool.Settings')}}</b-dropdown-item>
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
    import bButton from 'bootstrap-vue/src/components/button/button';

    export default {
        name: "mainNavbar",

        data() {
            return {
                schnellsuche: ''
            };
        },

        methods: {
            goForSearch() {
                alert('not working yet ... please try again ...');
                this.$router.push({name: 'search'});
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
        }
    }
</script>

<style scoped>
    .dropdown-hover:hover {
        background-color: lightgrey;
    }
</style>