<template>
    <div class="container-fluid">

        <b-navbar toggleable="sm" type="dark" variant="info" v-if="resource" fixed="top" class="sub-menu">

            <b-navbar-brand :to="{name: 'resource-detail', params: {id: id}}">MatPool</b-navbar-brand>

            <b-navbar-nav>
                <b-button size="sm"
                          class="my-1 my-md-0 mx-1"
                          :title="selectedPages.length > 0 ? $t('pool.create-material-selected-pages') : $t('pool.select-pages-first')"
                          :disabled="selectedPages.length === 0"
                          @click="btnCreateNewMaterialFromSelection">{{$t('pool.new')}}
                </b-button>
                <b-button size="sm"
                          class="my-1 my-md-0 mx-1"
                          :title="selectedPages.length > 0 ? $t('pool.add-material-selected-pages') : $t('pool.select-pages-first')"
                          :disabled="selectedPages.length === 0"
                          @click="btnAddPageSelectionToMaterial">{{$t('pool.add')}}
                </b-button>

                <b-nav-item-dropdown :text="$tc('pool.material-selected', selectionMaterials.length, {COUNT :
                    selectionMaterials.length})" left>
                    <b-dropdown-item v-for="mat in selectionMaterials" :key="'mat'+mat.id">
                        <b-button
                                variant="danger"
                                size="sm"
                                @click.prevent.default="btnRemoveSelectionFromMaterial(mat.id)"
                        >{{$t('pool.delete')}}
                        </b-button>
                        <b-button
                                variant="primary"
                                size="sm"
                                :to="{name:'material-detail', params: {id:mat.id}}"
                        >{{$t('pool.open')}}
                        </b-button>
                        {{mat.title | trim(70) }}
                    </b-dropdown-item>
                </b-nav-item-dropdown>
            </b-navbar-nav>

            <b-navbar-nav class="ml-auto">
                <b-nav-item-dropdown :text="$t('pool.Display')" left>
                    <b-dropdown-item @click="previewSize='lg'" :disabled="previewSize ==='lg'">
                        {{$t('pool.large')}}
                    </b-dropdown-item>
                    <b-dropdown-item @click="previewSize='md'" :disabled="previewSize ==='md'">
                        {{$t('pool.medium')}}
                    </b-dropdown-item>
                    <b-dropdown-item @click="previewSize='sm'" :disabled="previewSize ==='sm'">
                        {{$t('pool.small')}}
                    </b-dropdown-item>
                </b-nav-item-dropdown>

                <b-button size="sm"
                          class="my-1 my-md-0 mx-1"
                          v-if="resource.page_count !== selectedPages.length"
                          @click="btnSelectAllPages">{{$t('pool.select-all')}}
                </b-button>
            </b-navbar-nav>

        </b-navbar>

        <div class="content">

            <b-alert
                    :show="!resource"
                    fade
                    :variant="loadingType"
            >{{loadingMsg}}
            </b-alert>

            <b-alert
                    :show="resource && !resource.page_count"
                    fade
                    variant="warning"
                    class="mt-4 mb-4"
            >Sorry, Page-Count is missing. I am unable to display PDF-Pages.
            </b-alert>

            <page-list class=""
                       v-if="resource"
                       ref="pagelist"
                       :preview-size="previewSize"
                       :resource="resource"
                       @page-selection-updated="pageSelectionUpdated"
            ></page-list>

        </div>


        <material-selector
                v-if="resource"
                ref="materialSelector"
                :last-materials="materialsNotInEverySelection"
        ></material-selector>

        <material-creator
                ref="materialCreator"
        ></material-creator>
    </div>
</template>

<script>

    import PageList from './../../components/assignment/pdfpages/pageList.vue'
    import bAlert from 'bootstrap-vue/src/components/alert/alert';
    import bNavbar from 'bootstrap-vue/src/components/navbar/navbar';
    import bNavbarBrand from 'bootstrap-vue/src/components/navbar/navbar-brand';
    import bNavbarNav from 'bootstrap-vue/src/components/navbar/navbar-nav';
    import bNavItem from 'bootstrap-vue/src/components/nav/nav-item';
    import bNavItemDropdown from 'bootstrap-vue/src/components/nav/nav-item-dropdown';
    import bDropdownItem from 'bootstrap-vue/src/components/dropdown/dropdown-item';
    import bButton from 'bootstrap-vue/src/components/button/button';
    import bTooltip from 'bootstrap-vue/src/directives/tooltip/tooltip';
    import materialSelector from '../../components/modals/selectors/materialSelector.vue';
    import materialCreator from '../../components/modals/creators/materialCreator.vue';
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
                previewSize: 'sm',
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

                    this.attachCurrentSelectionToMaterial(material.id);

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

            btnCreateNewMaterialFromSelection() {
                this.$refs.materialCreator
                    .showPromise()
                    .then((material) => {
                        return this.attachCurrentSelectionToMaterial(material.id);
                    }).catch((err) => {
                    console.info('Closed Material-Creation with reason:', err);
                })
            },

            btnSelectAllPages() {
                this.$refs.pagelist.selectAllPages();
            },


            pageSelectionUpdated: function (selection) {
                this.selectedPages = selection;
            },

            attachCurrentSelectionToMaterial(materialId) {

                const currentLimitation = this.resourceLimitationPages(materialId);
                const newPageLimitation = uniqueArray(currentLimitation.concat(this.selectedPages));

                return this.serverDoResourceMaterialAttachment(materialId, this.id, newPageLimitation);

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
            bNavbarBrand,
            bNavbarNav,
            bNavItem,
            bNavItemDropdown,
            bDropdownItem,
            bButton,
            materialSelector,
            materialCreator
        },

        directives: {
            bTooltip
        },

        mixins: [truncate]
    }
</script>

<style scoped>

</style>