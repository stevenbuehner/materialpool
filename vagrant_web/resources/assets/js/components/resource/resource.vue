<template>
    <div class="resource">

        <div class="card">

            <div class="card-header">
                <h1><span v-if="resource">{{resource.type | upper}}</span>-Resource</h1>
            </div>

            <b-tabs card>
                <b-tab title="Vorschau">
                    Preview
                </b-tab>

                <b-tab title="Materialien">
                    <b-list-group v-if="resource">
                        <b-list-group-item
                                class="d-flex justify-content-between align-items-center"
                                v-for="material in resource.materials"
                                :key="material.id">

                            <div class="materialData">
                                {{material.title}}
                                <div class="limitation">
                                    <component
                                            :is="limitationComponent"
                                            :limitation="material.pivot.limitation">
                                    </component>
                                </div>
                            </div>

                            <span class="materialNavi">
                                <button v-if="material.pivot.limitation"
                                        class="btn btn-warning">Limitierung bearbeiten</button>
                                <button v-if="!material.pivot.limitation"
                                        class="btn btn-success">Limitierung erstellen</button>
                                <router-link :to="{name: 'material-detail', params: {id: material.id}}"
                                             class="btn btn-primary">Öffnen</router-link>
                            </span>

                        </b-list-group-item>
                    </b-list-group>

                    <div class="d-flex justify-content-center pt-2">
                        <button class="btn btn-secondary"
                                title="Material zuordnen"
                                @click="assignMaterial">+
                        </button>
                    </div>
                </b-tab>

                <b-tab title="MetaInfo" v-if="resource">
                    <b-list-group>
                        <b-list-group-item
                                v-for="(value, index) in resource"
                                :key="index"
                                v-if="index != 'materials'">
                            <b>{{index}}:</b> {{value}}
                        </b-list-group-item>
                    </b-list-group>
                </b-tab>
            </b-tabs>
        </div>

        <span v-if="loading">Still loading</span>
        {{errorMsg}}

        <component
                v-if="resourceComponent !== null"
                :is="resourceComponent"
                v-model="resource"></component>


    </div>
</template>

<script>
    import resourceLinks from './resource-links.mixin';
    import pdfEdit from './edit/pdfEdit.vue';
    import bTabs from 'bootstrap-vue/src/components/tabs/tabs';
    import bTab from 'bootstrap-vue/src/components/tabs/tab';
    import bListGroup from 'bootstrap-vue/src/components/list-group/list-group';
    import bListGroupItem from 'bootstrap-vue/src/components/list-group/list-group-item';
    import pdfLimitation from './limitation/pdfLimitation.vue';


    import Vue from 'vue';
    import AsyncComputed from 'vue-async-computed';

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
            };
        },

        computed: {

            resourceComponent() {
                if (this.resource === null) {
                    return false
                } else {
                    return this.resource.type + 'Edit';
                }
            },

            limitationComponent() {
                if (this.resource === null) {
                    return false
                } else {
                    return this.resource.type + 'Limitation';
                }
            }
        },

        asyncComputed: {
            resource() {
                return this.$store.dispatch('resources/getResource', this.id);
            }
        },

        methods: {
            assignMaterial() {
                alert('Not implemented yet');
            }
        },

        filters: {
            upper(text) {
                return text.toUpperCase();
            }
        },

        components: {
            pdfEdit,
            bTabs,
            bTab,
            bListGroup,
            bListGroupItem,
            pdfLimitation
        }
    }
</script>

<style scoped>

    .limitation {
        font-size: smaller;
        color: grey;
    }

    .materialMain {

    }

    .materialNavi {
    }

</style>