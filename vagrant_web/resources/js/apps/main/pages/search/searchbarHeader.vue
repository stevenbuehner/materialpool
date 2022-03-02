<template>
  <div>
    <div class="row" v-for="(sp, key, index) in searchParams" :key="sp.id">
      <div class="col-lg-11 col-lg-11 col-sm-11">
        <search-input v-model="sp.values" @updated="emitSearchUpdated($event, sp.id)"/>
      </div>
      <div class="col-lg-1 col-lg-1 col-sm-1">
        <div class="btn-group">
          <button class="btn btn-default"
                  v-if="sp.values.length>0"
                  @click="optimizeKeywordSearch(sp.values, sp.id)">
            <optimize-icon class="icon"/>
          </button>
          <button class="btn btn-default"
                  v-if="index > 0 || Object.keys(searchParams).length > 1"
                  :title="$t('pool.Remove-this-searchinput')"
                  @click="requestRemovingSarchInput(key)">-
          </button>
          <button class="btn btn-default"
                  v-if="Object.keys(searchParams).length -1 === index"
                  :title="$t('pool.Add-another-AND-searchinput')"
                  @click="requestAdditionalSearchInputAfter(key)">+
          </button>
        </div>
      </div>
    </div>

    <optimize-keywords ref="optimizeKeywords"/>

  </div>
</template>

<script>

import searchInput      from '../../../../components/search/searchInput.vue';
import OptimizeKeywords from "../../../../components/modals/selectors/optimizeKeywordSearch";
import optimizeIcon     from 'svg-icon/dist/svg/icomoon/zoom-in.svg';

function getNewSearchParam(id, values) {
  return {
    id,
    values: values || []
  }
}

export default {

  props: {
    searchObjects: {
      type: Object,
      default() {
        return {
          1: getNewSearchParam(1)
        };
      }
    }
  },

  data() {

    // Init search Params
    return {
      searchParams: this.fromPropsToData(this.searchObjects)
    };
  },

  computed: {},

  watch: {
    searchObjects: {
      handler(newValue, oldValue) {
        this.searchParams = this.fromPropsToData(newValue);
      },
      deep: true
    }
  },

  methods: {

    fromPropsToData(searchObjects) {
      let searchParams = {};

      if (Object.keys(searchObjects).length === 0) {
        searchParams[1] = getNewSearchParam(1);
      } else {
        for (let i in searchObjects) {
          searchParams[i] = getNewSearchParam(i, searchObjects[i]);
        }
      }

      return searchParams;
    },

    requestRemovingSarchInput(idParam) {

      if (this.searchParams.length <= 1) {
        alert('You have to leave at least one searchInput alive');
        return false;
      }

      const needsUpdateMaterials = this.searchParams[idParam] && this.searchParams[idParam].values && this.searchParams[idParam].values.length > 0;

      this.$delete(this.searchParams, idParam);

      if (needsUpdateMaterials) {
        this.emitSearchUpdated();
      }

    },

    requestAdditionalSearchInputAfter(idParam) {

      let nextCounter = 0;
      Object.keys(this.searchParams).forEach((key) => {
        nextCounter = Math.max(key, nextCounter);
      });
      nextCounter++;

      this.$set(this.searchParams, nextCounter, getNewSearchParam(nextCounter));

      // Don't emit. Because then the unneccessary lines will be removed again
      // this.emitSearchUpdated(undefined, nextCounter);
    },

    emitSearchUpdated(data, id) {

      let searchLineItems = [];

      Object.keys(this.searchParams).forEach((key) => {
        searchLineItems.push(
            this.searchParams[key].values.map((v) => v.item)
        );
      });

      this.$emit('searchUpdated', searchLineItems);

    },

    optimizeKeywordSearch(values, id) {

      this.$refs.optimizeKeywords.showPromise(values)
          .then((resultValues) => {
            this.searchParams[id].values = resultValues;
            this.emitSearchUpdated(resultValues, id);
          })
          .catch(() => {
          });

    }

  },

  created() {

    // Add one initial searchbar
    if (Object.keys(this.searchParams).length === 0) {
      this.requestAdditionalSearchInputAfter(0);
    }

  },


  components: {
    OptimizeKeywords,
    searchInput,
    optimizeIcon
  }
}
</script>

<style scoped>
.icon {
  height: 1em;
  width: 1em;
}

</style>