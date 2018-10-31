<template>

    <div class="container-fluid">


        <div class="waitmessage d-flex flex-column justify-content-around " v-if="treeStillLoading">
            <span class="align-self-center d-flex flex-column justify-content-center">
                <hollow-dots-spinner :dot-size="10"
                                     :dots-num="3"
                                     :animation-duration="1500"
                                     color="grey"
                                     class="align-self-center"></hollow-dots-spinner>
                <span>Keywords are beeing refreshed from the server. Please wait.</span>
            </span>

        </div>

        <div class="row" v-if="!treeStillLoading">


            <div class="col-6">
                <div class="mb-4">
                    <b-input v-model="treeSearch" :placeholder="$t('pool.Search')"/>
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

    import {HollowDotsSpinner} from 'epic-spinners'
    import editIcon from 'svg-icon/dist/svg/ionic/edit.svg';
    import refreshIcon from 'svg-icon/dist/svg/awesome/refresh.svg';
    import Tree from "../../../components/keyword/tree/Tree";
    import bInput from 'bootstrap-vue/src/components/form-input/form-input';

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

                let ids      = {};
                let model    = [];
                let root     = {'title': 'root', draggable: false, isOpen: true, children: [], id: null};
                let laterRun = [];

                model.push(root);

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
                        root.children.push(k);
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

                // Detach from tree first
                const backKW       = this.getKeyword(sourceId);
                const backParentId = backKW.parent_id;

                this.detachKeyword(sourceId);

                backKW.temp = true;
                this.attachKeyword(backKW, targetId);

                this.$store.dispatch('keywords/update', {
                    id: sourceId,
                    data: {
                        parent_id: targetId
                    }
                }).then((keyword) => {

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

            getKeyword(keywordId) {
                if (!keywordId) {
                    // Return root
                    return this.treeModel[0];
                } else {
                    return this.treeModelIds[keywordId];
                }
            },

            detachKeyword(keywordId) {

                keywordId      = parseInt(keywordId);
                const oldModel = this.treeModelIds[keywordId];

                // Remove from Array
                const parent    = this.getKeyword(oldModel.parent_id);
                parent.children = parent.children.filter(kw => {
                    return kw.id !== keywordId;
                });

                oldModel.parent_id = null;
                // parent.children.splice(parent.indexOf(oldModel), 1);

                // Remove from Index-Object
                delete this.treeModelIds[keywordId];

            },

            attachKeyword(sourceKeyword, targetId) {

                const targetKeyword = this.getKeyword(targetId);

                if (!targetKeyword.children || !Array.isArray(targetKeyword.children)) {
                    targetKeyword.children = [];
                }

                targetKeyword.children.push(sourceKeyword);

                sourceKeyword.parent_id = targetId;
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
            Tree,
            HollowDotsSpinner,
            editIcon,
            refreshIcon,
            bInput
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