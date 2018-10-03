<template>
    <div class="resource container">

        <div class="card">

            <div class="card-header">
                <h1><span v-if="resource">{{resource.type | upper}}</span>-Resource</h1>
            </div>

            <b-tabs card>
                <b-tab title="Vorschau">
                    <resource-detail :resource="resource" v-if="resource" :showOpen="false"></resource-detail>
                </b-tab>

                <b-tab title="Materialien" v-if="resource">
                    <b-list-group v-if="resource">
                        <b-list-group-item
                                class="d-flex justify-content-between align-items-center"
                                v-for="material in resource.materials"
                                :key="material.id">

                            <div class="materialData">
                                {{material.title}}
                                <div class="limitation" v-if="isLimitable">
                                    <component
                                            :is="limitationComponent"
                                            :limitation="material.pivot.limitation">
                                    </component>
                                </div>
                            </div>

                            <span class="materialNavi">
                                <router-link v-if="material.pivot.limitation && isLimitable"
                                             class="btn btn-warning btn-sm mb-1 mr-1"
                                             :to="routerEditLimitationObject(resource, material.pivot)">Limitierung bearbeiten</router-link>
                                <button v-if="!material.pivot.limitation && isLimitable"
                                        class="btn btn-success btn-sm mb-1 mr-1">Limitierung erstellen</button>
                                <button @click="btnDetachMaterialFromResource(material)"
                                        class="btn btn-outline-danger btn-sm mb-1 mr-1">{{$t('pool.remove')}}</button>
                                <router-link :to="{name: 'material-detail', params: {id: material.id}}"
                                             class="btn btn-primary btn-sm mb-1 mr-1">{{$t('pool.open')}}</router-link>
                            </span>

                        </b-list-group-item>
                    </b-list-group>

                    <div class="d-flex justify-content-center pt-2">
                        <button class="btn btn-outline-secondary m-1"
                                :title="$t('pool.auto-create-material')"
                                v-if="resource.materials.length === 0"
                                @click="btnCreateAutoMaterialFromResource">
                            <span class="icon autocreation"></span>
                        </button>
                        <button class="btn btn-outline-secondary m-1"
                                :title="$t('pool.create-and-assign-material')"
                                @click="btnCreateAndAssignMaterialManually">
                            <span class="icon manualcreation"></span>
                        </button>
                        <button class="btn btn-outline-secondary m-1"
                                :title="$t('pool.assign-material')"
                                @click="btnAddMaterialToResource">
                            <span class="icon assign"></span>
                        </button>
                    </div>
                </b-tab>

                <b-tab title="MetaInfo" v-if="resource">
                    <b-list-group>
                        <b-list-group-item
                                v-for="(value, index) in metaInfo"
                                :key="index"
                                v-if="index != 'materials'">

                            <b>{{index}}:</b>
                            <user v-if="index ==='creator'" :user="value"></user>
                            <span v-else>{{value}}</span>
                        </b-list-group-item>
                    </b-list-group>
                </b-tab>
            </b-tabs>
        </div>

        <span v-if="loading">Still loading</span>
        {{errorMsg}}

        <material-selector ref="materialSelector"></material-selector>

        <material-creator ref="materialCreator"></material-creator>

        <custom-dialog ref="myDialog"></custom-dialog>

    </div>
</template>

<script>
    import resourceLinks from './resource-links.mixin';
    import bTabs from 'bootstrap-vue/src/components/tabs/tabs';
    import bTab from 'bootstrap-vue/src/components/tabs/tab';
    import bListGroup from 'bootstrap-vue/src/components/list-group/list-group';
    import bListGroupItem from 'bootstrap-vue/src/components/list-group/list-group-item';
    import pdfLimitation from './limitation/pdfLimitation.vue';
    import {isResourceTypeLimitable} from "./limitation/limitable";
    import resourceDetail from './show/resource-detail'

    import user from './../user/user-name';


    import Vue from 'vue';
    import AsyncComputed from 'vue-async-computed';
    import MaterialSelector from "../modals/selectors/materialSelector";
    import CustomDialog from "../modals/dialogs/customDialog";
    import MaterialCreator from "../modals/creators/materialCreator";

    Vue.use(AsyncComputed);


    export default {

        mixins: [resourceLinks],

        props: {
            id: {
                required: true,
                type: Number
            }
        },

        data() {
            return {
                loading: false,
                errorMsg: null,

                forceReload: 0,
            };
        },

        computed: {
            limitationComponent() {
                return this.resource ? this.resource.type + 'Limitation' : false;
            },

            isLimitable() {
                return isResourceTypeLimitable(this.resource.type);
            },

            metaInfo() {

                let info = {};

                for (let i in this.resource) {
                    if (i !== 'created_by' && this.resource[i] !== '' && i !== 'type') {
                        info[i] = this.resource[i];
                    }
                }

                return info;
            }
        },

        asyncComputed: {
            resource: {
                get() {
                    return this.$store.dispatch('resources/getResource', this.id);
                },
                default: null,
                watch() {
                    this.forceReload
                }
            }
        },

        methods: {
            btnAddMaterialToResource() {

                this.$refs.materialSelector.showPromise().then((material) => {

                    return this.$store.dispatch('materials/attachResource', {
                        materialId: material.id,
                        resourceId: this.id
                    }).then(({resource}) => {
                        this.resource = resource;
                    }).catch((error) => {
                        alert(error);
                    });
                }).catch(() => {
                });

            },

            btnDetachMaterialFromResource(material) {

                this.$store.dispatch('materials/detachResource',
                    {materialId: material.id, resourceId: this.id}
                ).then(({material, resource}) => {
                    this.resource = resource;

                    if (material.resources && material.resources.length === 0) {
                        this.$refs.myDialog.show({
                            title: 'Rückfrage',
                            content: 'Diesem Material ist jetzt keine Ressource mehr zugeordet<br/>Soll ' + (material.title ? '"' + material.title + '"' : 'es') + ' <b>jetzt komplett</b> gelöscht werden?',
                            yesText: 'Ja, löschen',
                            yesVariant: 'success',
                            noText: 'Nein, so lassen',
                            noVariant: 'warning',
                            allowBackdrop: false
                        }).then((answerPositive) => {

                            if (answerPositive === true) {
                                this.$refs.myDialog.show({
                                    title: 'Lösche Material',
                                    content: 'Lösche ' + (material.title ? '"' + material.title + '"' : 'Material') + '...',
                                    yesEnabled: false,
                                    noEnabled: false,
                                    allowBackdrop: false
                                }).catch(() => {
                                });

                                this.$store.dispatch('materials/deleteMaterial', material.id)
                                    .then(() => {
                                        this.$refs.myDialog.show({
                                            title: 'Material gelöscht',
                                            content: 'Material erfolgreich gelöscht!',
                                            yesText: 'ok',
                                            yesVariant: 'primary',
                                            yesEnabled: true,
                                            noEnabled: false,
                                            allowBackdrop: true,
                                        });
                                    });
                            }

                        }).catch(({message}) => {
                            this.$refs.myDialog.show({
                                title: 'Warnung',
                                content: message,
                                yesText: 'ok',
                                yesVariant: 'primary',
                                yesEnabled: true,
                                noEnabled: false,
                                allowBackdrop: true,
                            });
                        });
                    }

                });
            },

            btnCreateAutoMaterialFromResource() {

                this.$store.dispatch('resources/autoCreateMaterial', this.id)
                    .then(({material}) => {

                        this.$router.push({
                            name: 'material-detail',
                            params: {
                                id: material.id
                            }
                        });
                    });

            },

            btnCreateAndAssignMaterialManually() {
                this.$refs.materialCreator.showPromise()
                    .then((material) => {
                        return this.$store.dispatch('materials/attachResource', {
                            materialId: material.id,
                            resourceId: this.id,
                        })
                    })
                    .then(({material, resource}) => {
                        this.forceReload++;
                    });
            }
        },

        filters: {
            upper(text) {
                return text.toUpperCase();
            }
        },

        components: {
            MaterialCreator,
            CustomDialog,
            MaterialSelector,
            resourceDetail,
            bTabs,
            bTab,
            bListGroup,
            bListGroupItem,
            pdfLimitation,
            user
        }
    }
</script>

<style scoped>

    .limitation {
        font-size: smaller;
        color: grey;
    }

    .materialNavi {

    }

    .icon {
        position: relative;
        display: inline-block;
        background-size: contain;
        background-position: 0 0;
        height: 1rem;
        background-repeat: no-repeat;
        top: 0.1rem;
        width: 1rem;
    }

    .autocreation {
        background-image: url(/img/icons/entypo-plus/rocket.svg);
    }

    .manualcreation {
        background-image: url(/img/icons/entypo-plus/new-message.svg);
    }

    .assign {
        background-image: url(/img/icons/entypo-plus/flow-tree.svg);
    }

</style>