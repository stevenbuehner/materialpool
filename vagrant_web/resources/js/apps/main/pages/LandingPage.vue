<template>
    <div class="container homeContainer">

        <div class="flex-center position-ref full-height">
            <div class="top-right links">
                <router-link :to="{name: 'landingpage'}" v-if="currentUser">{{$t('pool.home')}}</router-link>
                <a href='/login' v-if="!currentUser">{{$t('pool.login')}}</a>
                <a href='/register' v-if="!currentUser">{{$t('pool.logout')}}</a>
            </div>

            <div class="content">
                <div class="title m-b-md">
                    {{systemName}}
                </div>

                <div class="links">
                    <router-link :to="{name: 'search'}">{{$t('pool.Search')}}</router-link>

                    <router-link :to="{name: 'resource-create'}" v-if="currentUser">{{$t('pool.Upload')}}
                    </router-link>

                    <router-link :to="{name: 'resource-text-create'}" v-if="currentUser">{{$t('pool.Create-text')}}
                    </router-link>

                    <router-link :to="{name: 'readbible'}" v-if="currentUser">{{$t('pool.Read-bible')}}
                    </router-link>

                    <router-link :to="{name: 'material'}" v-if="currentUser">{{$t('pool.Materiallist')}}
                    </router-link>

                </div>
            </div>
        </div>

    </div>
</template>

<script>

    export default {
        name: "LandingPage",

        data() {
            return {}
        },


        asyncComputed: {

            currentUser: {
                get() {
                    return this.$store.dispatch('general/currentUser');
                },

            },
            systemName: {
                get() {
                    return this.$store.dispatch('general/systemName');
                }
            }
        },

        methods: {},

        components: {}
    }
</script>

<style type="scss">

    .homeContainer {
        background-color: #fff;
        color: #636b6f;
        font-family: 'Raleway', sans-serif;
        font-weight: 100;
        height: 100vh;
        margin: 0;

        .full-height {
            height: 100vh;
        }

        .flex-center {
            align-items: center;
            display: flex;
            justify-content: center;
        }

        .position-ref {
            position: relative;
        }

        .top-right {
            position: absolute;
            right: 10px;
            top: 18px;
        }

        .content {
            text-align: center;
        }

        .title {
            font-size: 84px;
        }

        .links > a {
            color: #636b6f;
            padding: 0 25px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: .1rem;
            text-decoration: none;
            text-transform: uppercase;
        }

        .m-b-md {
            margin-bottom: 30px;
        }
    }


</style>