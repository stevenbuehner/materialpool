<template>
    <div class="container-fluid border rounded p-3">

        <div class="beforeLoaded d-flex flex-column justify-content-around " v-if="!keyword">
            <span class="align-self-center d-flex flex-column justify-content-center">
                <hollow-dots-spinner :dot-size="10"
                                     :dots-num="3"
                                     :animation-duration="1500"
                                     color="grey"
                                     class="align-self-center"></hollow-dots-spinner>
            {{$t('pool.Keyword-is-beeing-loaded')}}
            </span>

        </div>

        <div class="form" v-if="keyword">
            <div class="row">
                <div class="col-sm-3">
                    <label>{{$t('pool.Title')}}:</label>
                </div>
                <div class="col-sm-9">
                    <b-form-input
                            name="keywordText"
                            id="keywordText"
                            type="text"
                            v-model="keyword.title"
                            :disabled="disableForm"
                            autofocus
                    ></b-form-input>
                    <div class="meta_data">ID: {{keyword.id}}, Key: {{keyword.lc_title}}</div>
                </div>
            </div>

            <div class="row mt-2">
                <div class="col-sm-3">
                    <label>{{$t('pool.Select-Type')}}:</label>
                </div>
                <div class="col-sm-9">
                    <b-form-select
                            name="keywordType"
                            id="keywordType"
                            v-model="keyword.type"
                            :disabled="disableForm"
                    >
                        <option value="key">Keyword</option>
                        <option value="person">Person</option>
                        <option value="place">Place</option>
                        <option value="lang">Language</option>
                    </b-form-select>
                </div>
            </div>

            <div class="row mt-2">
                <div class="col-sm-3">
                    <label>{{$t('pool.Select-Icon')}}:</label>
                </div>
                <div class="col-sm-9">
                    {{$t('pool.Coming-soon')}}
                </div>
            </div>

            <div class="row mt-2">
                <div class="col-sm-3">
                    <label>{{$t('pool.Direct-Parent')}}:</label>
                </div>
                <div class="col-sm-9" v-if="keyword && keyword.parent_id && !parent">
                    {{$t('pool.Loading-parent-keyword')}}
                </div>
                <div class="col-sm-9" v-else-if="parent">
                    <router-link
                            :to="{name:'keyword-detail', params:{id: keyword.parent_id}}">
                        <keyword
                                :keyword="parent"
                                :editable="false"
                                :removeable="false"
                        ></keyword>
                    </router-link>
                </div>
                <div class="col-sm-9" v-else>
                    {{$t('pool.none')}}
                </div>
            </div>

            <div class="row mt-2">
                <slot name="menu">
                    <div class="col-sm-3">
                    </div>
                    <div class="col-sm-9 menu">
                        <slot name="all-buttons">
                            <b-button variant="primary"
                                      :disabled="!keywordWasModified || disableForm"
                                      @click.prevent="btnSave">{{$t('pool.save')}}
                            </b-button>
                            <slot name="additional-buttons"/>
                        </slot>
                    </div>
                </slot>
            </div>
        </div>
    </div>
</template>

<script>
    import bFormInput from 'bootstrap-vue/src/components/form-input/form-input';
    import bFormSelect from 'bootstrap-vue/src/components/form-select/form-select';
    import bButton from 'bootstrap-vue/src/components/button/button';
    import {HollowDotsSpinner} from 'epic-spinners'
    import Vue from 'vue';
    import AsyncComputed from 'vue-async-computed';
    import Keyword from "./keyword";


    Vue.use(AsyncComputed);

    export default {

        name: "keywordEdit",

        props: {
            id: {
                type: Number,
                required: true
            }
        },

        data() {
            return {
                keyword: null,

                backupJsonKeyword: null,
                errorOnLoadingMessage: null,
                keywordWasModified: false,

                disableForm: false,
            };
        },


        asyncComputed: {
            parent: {
                get() {
                    if (this.keyword && this.keyword.parent_id) {
                        return this.$store.dispatch('keywords/get', this.keyword.parent_id);
                    } else {
                        return null;
                    }
                },
                default: null,
                /* watch() {
                    this.forceReload
                }*/
            }
        },

        watch: {
            id(newValue) {
                this.keyword = null;
                this.getKeyword();
            },
            keyword: {
                handler: function (newVal, oldVal) {
                    this.updateKeywordModified();
                },
                deep: true
            }
        },

        created() {
            this.getKeyword();
        },


        methods: {

            getKeyword() {
                this.errorOnLoadingMessage = null;

                this.$store.dispatch('keywords/get', this.id).then((keyword) => {
                    this.setKeyword(keyword);
                    this.errorOnLoadingMessage = null;
                }).catch((response) => {
                    this.errorOnLoadingMessage = response;
                });
            },

            setKeyword(keyword) {
                this.keyword           = keyword;
                this.backupJsonKeyword = JSON.stringify(keyword);
            },

            updateKeywordModified() {
                this.keywordWasModified = JSON.stringify(this.keyword) !== this.backupJsonKeyword;
            },


            btnSave() {

                // Ignore if nothing was changed
                if (this.keywordWasModified === false) {
                    return;
                }

                this.disableForm = true;
                const originalK  = JSON.parse(this.backupJsonKeyword);
                let modifiedData = {};

                const mod = ['title', 'type', 'custom_icon'].filter((p) => {
                    return originalK[p] !== this.keyword[p]
                }).forEach((p) => {
                    modifiedData[p] = this.keyword[p];
                });

                this.updateKeywordData(modifiedData)
                    .then(() => {
                        // Always
                        this.disableForm = false;
                    });
            },


            updateKeywordData(properties) {

                this.$emit('saving', properties);

                const promise = this.$store.dispatch('keywords/update', {id: this.keyword.id, data: properties});

                promise.then((keyword) => {

                    this.setKeyword(keyword);
                    this.$emit('saved', keyword);

                }).catch((response) => {

                    // on failure
                    this.$emit('savingError', {
                        tag: this.keyword, // "Tag" is used for bibleverses and keywords
                        msg: this.parseResponseErrors(response.response)
                    });
                });

                return promise;

            },

            parseResponseErrors(response) {
                let msg = 'Error! ';

                if (response.data && response.data.errors) {
                    for (let i in response.data.errors) {
                        msg += i + ': ' + response.data.errors[i] + '. ';
                    }
                }

                return msg;
            },
        },

        components: {
            Keyword,
            bFormInput,
            bFormSelect,
            HollowDotsSpinner,
            bButton
        }
    }
</script>

<style scoped>
    .beforeLoaded {
        min-height: 50vh;
    }

    .meta_data {
        font-size: smaller;
        color: #CCC;
        margin-left: 1em;
        margin-top: .25em;
    }
</style>