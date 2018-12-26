<template>
    <div class="materialListingItem row"
         v-if="material"
         @click.prevent="goToMaterial(material.id)">

        <div class="head col-12">
            <div class="row">
                <div v-html="bundleIcon" class="icon pl-3" v-if="bundleIcon">test</div>
                <div :class="{'col-10' : bundleIcon, 'col-12' : !bundleIcon}">
                    <div class="title">{{material.title}}</div>
                    <small class="meta-info">
                        <div class="info" v-if="material.author">von {{material.author.title}} |</div>
                        <div class="info" v-if="material.author">letztes Update am {{material.updated_at}}</div>
                    </small>
                </div>

            </div>
        </div>
        <div class="tags col-12">
            <keyword v-for="keyword in material.keywords"
                     :key="'k' + keyword.id"
                     :keyword="keyword"
                     :materialId="material.id"
                     :editable="false"
                     size="mini"></keyword>
            <biblevers
                    v-for="bibleverse in material.bibleverses"
                    :key="'b' + bibleverse.id"
                    :bibleverse="bibleverse"
                    :materialId="material.id"
                    :editable="false"
                    size="mini"></biblevers>
        </div>
        <small class="description col-12">{{material.description}}</small>

    </div>
</template>

<script>
    import Keyword from './../keyword/keyword.vue'
    import Biblevers from "../bibleverse/biblevers.vue";
    import materialDetail from '../../apps/main/pages/MaterialDetail.vue';
    import {api_v1_materials_update} from './../serverRoutes';
    import materialStoreMixin from './materialStore.mixin';


    export default {

        mixins: [
            materialStoreMixin
        ],

        created() {
            this.updateMaterial();
        },

        props: ['id'],

        data() {
            return {
                bundleIcon: null
            }
        },

        computed: {
            materialDetailLink() {
                return api_v1_materials_update(this.id)
            },
        },

        watch: {
            id(newValue) {
                this.updateMaterial();
            }
        },


        methods: {

            updateMaterial() {

                const response = this.updateMaterialData(this.id);

                response.then((material) => {
                    this.updateBundleIcon();
                });

                return response;

            },

            updateBundleIcon() {

                if (this.material && this.material.icon_of_bundle) {
                    this.$store.dispatch('bundles/getBundleIcon', this.material.icon_of_bundle)
                        .then(response => {
                            this.bundleIcon = response;
                        })
                        .catch(() => {
                            this.bundleIcon = null;
                        })
                } else {
                    this.bundleIcon = null;
                }

            }
        },

        components: {
            Biblevers,
            Keyword,
            materialDetail
        }
    }
</script>

<style type="scss">

    .materialListingItem {

        border-bottom: 0.1rem solid gray;
        cursor: pointer;
        padding: .5em 0;
        line-height: 1em;

        .icon {
            max-width: 10vw;
        }


        .title {
            font-weight: bold;
        }

        .head {
            padding-bottom: 0.25em;
        }

        .meta-info {
            .info {
                display: inline-block;
                color: gray;
            }
        }

        .tags {
            padding-bottom: 0.25em;
        }
    }


</style>
