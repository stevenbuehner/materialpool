<template>
    <div>

        <b-navbar toggleable="sm" type="dark" variant="info" v-if="resource">

            <b-navbar-nav>
                <b-button size="sm"
                          class="my-1
                          my-md-0 mx-1"
                          :title="selectedPages.length > 0 ? $t('pool.create-material-selected-pages') : $t('pool.select-pages-first')"
                          :disabled="selectedPages.length === 0">{{$t('pool.new')}}
                </b-button>
                <b-button size="sm"
                          class="my-1
                          my-md-0
                          mx-1"
                          :title="selectedPages.length > 0 ? $t('pool.add-material-selected-pages') : $t('pool.select-pages-first')"
                          :disabled="selectedPages.length === 0"
                          @click="btnAddPageSelectionToMaterial">{{$t('pool.add')}}
                </b-button>
                <b-nav-item href="#">Link</b-nav-item>

                <b-nav-item-dropdown :text="$tc('pool.material-selected', selectionMaterials.length, {COUNT :
                    selectionMaterials.length})" left>
                    <b-dropdown-item v-for="mat in selectionMaterials" :key="'mat'+mat.id">
                        <b-button
                                variant="danger"
                                size="sm"
                                @click.prevent.default="btnRemoveSelectionFromMaterial(mat.id)"
                        >{{$t('pool.delete')}}
                        </b-button>
                        {{mat.title | trim(70) }}
                    </b-dropdown-item>
                </b-nav-item-dropdown>
            </b-navbar-nav>

        </b-navbar>

        <b-alert
                :show="!resource"
                fade
                :variant="loadingType"
        >{{loadingMsg}}
        </b-alert>

        <page-list class=""
                   v-if="resource"
                   ref="pagelist"
                   :resource="resource"
                   @page-selection-updated="pageSelectionUpdated"
        ></page-list>

        <div class="col-12 md-4 col-lg-2 col-xl-1"
             v-if="resource"
        ></div>

        <material-selector
                v-if="resource"
                ref="materialSelector"
                :last-materials="materialsNotInEverySelection"
        ></material-selector>
    </div>
</template>

<script>

    import PageList from './../../components/assignment/pdfpages/pageList.vue'
    import bAlert from 'bootstrap-vue/src/components/alert/alert';
    import bNavbar from 'bootstrap-vue/src/components/navbar/navbar';
    import bNavbarNav from 'bootstrap-vue/src/components/navbar/navbar-nav';
    import bNavItem from 'bootstrap-vue/src/components/nav/nav-item';
    import bNavItemDropdown from 'bootstrap-vue/src/components/nav/nav-item-dropdown';
    import bDropdownItem from 'bootstrap-vue/src/components/dropdown/dropdown-item';
    import bButton from 'bootstrap-vue/src/components/button/button';
    import bTooltip from 'bootstrap-vue/src/directives/tooltip/tooltip';
    import materialSelector from './../../components/selectors/materialSelector.vue';
    import truncate from './../../filters/truncate-filter.mixin'

    import {uniqueArray} from "../../helper/ArrayHelper";

    export default {

        name: 'AssignApp',

        props: {
            id: {
                type: Number,
                required: true
            }
        },

        watch: {
            '$route.params.id': (newVal, oldVal) => {
                this.updateResource(newVal);
            }
        },

        data() {
            return {
                resource: null,
                loadingMsg: '',
                loadingType: 'info',
                selectedPages: [],

                showMaterialSelector: false,
            }
        },

        computed: {

            selectionMaterials() {
                return this.resource.materials.filter((mat) => {

                    const matPages = this.resourceLimitationPages(mat.id);

                    // Material gehört zu ALLEN Seiten => keine Limitation
                    if (matPages.length === 0) {
                        return true;

                        // Material hat Limitation
                    } else {
                        return matPages.filter((matPage) => {
                            return this.selectedPages.includes(matPage);
                        }).length > 0;
                    }

                });
            },

            materialsNotInEverySelection() {

                return this.resource.materials.filter((mat) => {

                    const matPages = this.resourceLimitationPages(mat.id);

                    // Material gehört zu ALLEN Seiten => keine Limitation
                    if (matPages.length === 0) {
                        return false;

                        // Material hat Limitation
                    } else {

                        return this.selectedPages.filter((page) => {
                            return matPages.includes(page);
                        }).length < this.selectedPages.length;

                    }

                });
            },

        },

        methods: {

            updateResource(id) {

                this.resource    = null;
                this.loadingMsg  = this.$t('pool.Loading-resource');
                this.loadingType = 'info';

                this.$store.dispatch('resources/getResource', id)
                    .then((resource) => {
                        this.resource = resource;
                    }).catch(() => {
                    this.loadingMsg  = this.$t('pool.Resource-loading-failed');
                    this.loadingType = 'danger';
                });

            },


            resourceLimitationPages(matId) {

                let resMat = this.resource.materials.find((mat) => mat.id === matId);


                if (resMat && resMat.pivot && resMat.pivot.limitation && resMat.pivot.limitation.pages && Array.isArray(resMat.pivot.limitation.pages)) {
                    return resMat.pivot.limitation.pages;
                }

                return [];
            },


            btnAddPageSelectionToMaterial() {

                this.$refs.materialSelector.showPromise().then((material) => {

                    const currentLimitation = this.resourceLimitationPages(material.id);
                    const newPageLimitation = uniqueArray(currentLimitation.concat(this.selectedPages));

                    this.serverDoResourceMaterialAttachment(material.id, this.id, newPageLimitation);

                }).catch((error) => {
                    console.info('Error while setting new Limitation: ', error)
                })

            },


            btnRemoveSelectionFromMaterial(matId) {

                let currentLimitPages = this.resourceLimitationPages(matId);
                if (currentLimitPages.length === 0) {
                    for (let i = 1; i <= this.resource.page_count; i++) {
                        currentLimitPages.push(i);
                    }
                }
                const removeLimitPages = this.selectedPages;

                const newPageLimitation = currentLimitPages.filter((oldPage) => {
                    return !removeLimitPages.includes(oldPage);
                });


                if (newPageLimitation.length === 0) {

                    if (confirm('Wirklich Material komplett von dieser Datei lösen?') === true) {
                        this.$store.dispatch('materials/detachResource', {materialId: matId, resourceId: this.id})
                            .then(({resource}) => {
                                this.resource = resource;
                            });
                    }

                } else {
                    this.serverDoResourceMaterialAttachment(matId, this.id, newPageLimitation);
                }


            },


            pageSelectionUpdated: function (selection) {
                this.selectedPages = selection;
            },

            serverDoResourceMaterialAttachment(materialId, resourceId, pages) {

                let limitation = undefined;

                if (pages && Array.isArray(pages) && pages.length > 0 && pages.length < this.resource.page_count) {
                    limitation = {
                        type: 'page',
                        value: pages.join(',')
                    }
                }

                return this.$store.dispatch('materials/attachResource', {
                    materialId,
                    resourceId,
                    limitation
                }).then(({resource}) => {
                    this.resource = resource;
                });

            },


        },

        created() {
            this.updateResource(this.id);
        },


        components: {
            PageList,
            bAlert,
            bNavbar,
            bNavbarNav,
            bNavItem,
            bNavItemDropdown,
            bDropdownItem,
            bButton,
            materialSelector
        },

        directives: {
            bTooltip
        },

        mixins: [truncate]
    }
</script>

<style scoped>

</style>