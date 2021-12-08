<template>

  <div class="container-fluid">

    <div class="waitmessage d-flex flex-column justify-content-around " v-if="treeStillLoading">
            <span class="align-self-center d-flex flex-column justify-content-center">
                <materialpool-spinner class="align-self-center"/>
                <span>{{ $t('pool.Keywords-are-beeing-refreshed-from-server') }}</span>
            </span>
    </div>

    <div class="row" v-if="!treeStillLoading">


      <div class="col-6">
        <div class="mb-4">
          <b-form-input
              v-model="treeSearch"
              :placeholder="$t('pool.Search')"
              autocorrect="off"
          />
        </div>
        <div style="display: inline-block; width: 100%">
          <Tree :tree="this.treeModel"
                :move="moveKeyword"
                :search-phrase="treeSearch"
                ref="myTree"></Tree>
        </div>
      </div>

      <div class="col-6">
        <button class="btn btn-primary" @click="btnRefreshTree">
          <refresh-icon class="refreshIcon"></refresh-icon>
        </button>
      </div>
    </div>

  </div>

</template>

<script>

import editIcon            from 'svg-icon/dist/svg/ionic/edit.svg';
import refreshIcon         from 'svg-icon/dist/svg/awesome/refresh.svg';
import Tree                from "../../../components/keyword/tree/Tree";
import {BFormInput}        from 'bootstrap-vue';
import MaterialpoolSpinner from "../../../components/spinner/materialpool-spinner";

export default {
  name: "KeywordList",

  data() {
    return {
      treeModel: [
        {title: 'first Node', draggable: false, children: []}
      ],
      treeModelIds: {},
      treeStillLoading: true,

      treeSearch: '',

    };
  },

  computed: {},

  created() {
    this.getAllKeywords();
  },

  methods: {
    onTreeSelection(newSelection) {
      this.treeSelection = newSelection;
    },

    createModelFromKeywords(keywords) {

      let ids   = {};
      let model = [];

      const roots = {
        key: {
          title: this.$t('pool.Keywords'),
          type: 'key',
          draggable: false,
          isOpen: true,
          children: [],
          id: 'key'
        },
        person: {
          title: this.$t('pool.Persons'),
          type: 'person',
          draggable: false,
          isOpen: false,
          children: [],
          id: 'person'
        },
        place: {
          title: this.$t('pool.Places'),
          type: 'place',
          draggable: false,
          isOpen: false,
          children: [],
          id: 'place'
        },
        lang: {
          title: this.$t('pool.Languages'),
          type: 'lang',
          draggable: false,
          isOpen: false,
          children: [],
          id: 'lang'
        },
      };

      for (let i in roots) {
        model.push(roots[i]);
        ids[roots[i].id] = roots[i];
      }

      let laterRun = [];


      for (let i in keywords) {

        let k      = keywords[i];
        k.children = [];
        ids[k.id]  = k;

        if (k.parent_id) {

          if (!ids[k.parent_id]) {
            console.info('Missing Parrent ID for. Add later on', k)
            laterRun.push((k));
          } else {

            ids[k.parent_id].children.push(k);
          }

        } else {
          roots[k.type].children.push(k);
        }
      }

      for (let i in laterRun) {
        let k = laterRun[i];
        ids[k.parent_id].children.push(k);
      }

      this.treeModel    = model;
      this.treeModelIds = ids;

    },


    moveKeyword(sourceId, targetId, callback) {

      if (sourceId == targetId) {
        console.info('Dropped keyword on itself => do nothing');
        return;
      }

      // Detach from tree first
      const backKW       = this.getKeyword(sourceId);
      const backParentId = backKW.parent_id;

      this.detachKeyword(sourceId);

      backKW.temp = true;
      this.attachKeyword(backKW, targetId);

      this.$store.dispatch('keywords/update', {
        id: sourceId,
        data: {
          parent_id: Number.isInteger(targetId) ? targetId : null
        }
      }).then((keyword) => {

        // Add children to the keyword again
        keyword.children = backKW.children || [];

        // Attach Keyword in the DOM
        this.detachKeyword(keyword.id);
        this.attachKeyword(keyword, keyword.parent_id);
        this.flashSuccess(this.$t('pool.Keyword-saved'), {timeout: 3000});

      }).catch((response) => {
        // Reattach keyword at the end of the DOM
        delete backKw.temp;

        this.detachKeyword(backKw.id);
        this.attachKeyword(backKW, backParentId);
        this.flashError(this.$t('pool.Error-while-moving-keyword'));

      }).then(() => {
        callback();
      });


    },

    getKeyword(keywordId, backupType) {
      if (!keywordId) {
        // Return root
        return this.treeModelIds[backupType];
      } else {
        return this.treeModelIds[keywordId];
      }
    },

    detachKeyword(keywordId) {

      keywordId           = parseInt(keywordId);
      const sourceKeyword = this.getKeyword(keywordId);

      // Remove from Array
      const parent    = this.getKeyword(sourceKeyword.parent_id, sourceKeyword.type);
      parent.children = parent.children.filter(kw => {
        return kw.id !== keywordId;
      });

      sourceKeyword.parent_id = null;
      // parent.children.splice(parent.indexOf(oldModel), 1);

      // Remove from Index-Object
      delete this.treeModelIds[keywordId];

    },

    attachKeyword(sourceKeyword, targetId) {

      const targetKeyword = this.getKeyword(targetId, sourceKeyword.type);

      if (!targetKeyword) {
        console.error('Keyword was not found by getKeyword. ID: ', targetId);
      }

      if (!targetKeyword.children || !Array.isArray(targetKeyword.children)) {
        targetKeyword.children = [];
      }

      targetKeyword.children.push(sourceKeyword);

      sourceKeyword.parent_id             = targetId;
      this.treeModelIds[sourceKeyword.id] = sourceKeyword;

    },

    getAllKeywords(forceReload) {

      this.treeStillLoading = true;

      this.$store.dispatch('keywords/getAll', forceReload)
          .then((allKeywords) => {
            this.createModelFromKeywords(allKeywords);
            this.treeStillLoading = false;
          })
          .catch(() => {
            this.treeStillLoading = false;
          });
    },

    btnRefreshTree() {
      this.getAllKeywords(true);
    }

  },

  components: {
    MaterialpoolSpinner,
    Tree,
    editIcon,
    refreshIcon,
    BFormInput
  }
}
</script>

<style>
.keywordlisticon {
  position: relative;
  display: inline-block;
  background-size: contain;
  background-position: 0 0;
  height: 0.9rem;
  background-repeat: no-repeat;
  top: 0.1rem;
  width: 1rem;
  background-image: url(/img/icons/tag.svg);
  margin-right: 0.5rem;
  margin-left: 0;
}

</style>

<style scoped>
.waitmessage {
  min-height: 50vh;
}

.refreshIcon {
  width: 1.5rem;
}

.refreshIcon >>> path {
  fill: white;
}
</style>