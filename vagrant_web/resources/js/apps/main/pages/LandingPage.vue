<template>
  <div class="container homeContainer d-flex justify-content-center">

    <div class="top-right links" v-show="false">
      <router-link :to="{name: 'landingpage'}" v-if="currentUser">{{ $t('pool.home') }}</router-link>

      <a href='/login' v-if="!currentUser">{{ $t('pool.login') }}</a>
      <a href='/register' v-if="!currentUser">{{ $t('pool.logout') }}</a>
    </div>

    <div class="row align-items-center">

      <div class="col col-12">
        <div class="row">
          <div class="col col-12 title text-center">
            {{ systemName }}
          </div>

          <div class="col col-12 links text-center">
            <router-link :to="{name: 'search'}">{{ $t('pool.Search') }}</router-link>

            <router-link :to="{name: 'resource-create'}" v-if="currentUser">{{ $t('pool.Upload') }}
            </router-link>

            <router-link :to="{name: 'resource-text-create'}" v-if="currentUser">{{ $t('pool.Create-text') }}
            </router-link>

            <router-link :to="{name: 'readbible'}" v-if="currentUser">{{ $t('pool.Bible') }}
            </router-link>

            <router-link :to="{name: 'material'}" v-if="currentUser">{{ $t('pool.Materiallist') }}
            </router-link>

          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
import {useGeneralStore} from '../stores/general';

export default {
  name: "LandingPage",

  data() {
    return {}
  },


  asyncComputed: {

    currentUser: {
      get() {
        return useGeneralStore().currentUser();
      },

    },
    systemName: {
      get() {
        return useGeneralStore().systemName();
      }
    }
  },

  methods: {},

  components: {}
}
</script>

<style lang="scss">

@use "resources/sass/theme" as *;

.homeContainer {
  background-color: #fff;
  color: $gray-600;
  font-family: 'Raleway', sans-serif;
  font-weight: 100;
  height: 100vh;
  margin: 0;

  .position-ref {
    position: relative;
  }

  .top-right {
    position: absolute;
    right: 10px;
    top: 18px;
  }

  .title {
    font-size: 84px;
  }

  .links > a {
    color: $gray-600;
    padding: 0 25px;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: .1rem;
    text-decoration: none;
    text-transform: uppercase;
  }

}


</style>
