<template>
    <div class="container">
        <div v-if="!material && !errorOnLoadingMessage">{{$t('pool.Material-is-loading')}}</div>
        <div class="alert alert-warning"
             v-if="!material && errorOnLoadingMessage">
            {{errorOnLoadingMessage}}
            <a href='#' class="btn btn-primary" @click="$router.go(-1)">{{$t('pool.go-back')}}</a>
        </div>

        <div v-if="material">


            <div class="d-flex justify-content-start align-items-center pb-2">
                <flag class="pl-1 pr-1 mr-2" :flagKey="material.flag" @flag-updated="submitFlag"></flag>
                <edditable-text
                        type="h1"
                        :value="material.title"
                        @value-changed="submitTitle"
                        class="flex-grow-1"
                        classes="m-0 p-0"
                        placeholder="Please enter a title here ...">
                </edditable-text>
            </div>


            <div class="meta row">
                <div class="col-lg-12">
                    <material-rating
                            :increment="1"
                            :max-rating="20"
                            inactive-color="lightgray"
                            active-color="black"
                            :star-size="15"
                            :inline="true"
                            @rating-selected="submitRating"
                            @current-rating="currentRatingChanged"
                            :show-rating="true"
                            v-model="material.rating"
                            ref="rating">
                    </material-rating>

                </div>

            </div>

            <div class="row">
                <div class="col-lg-11 col-md-11 col-sm-11 col-11" id="allTags" v-if="!editTagsModeEnabled">

                    <from-bot :from-bot="material.from_bot"
                              @toggleRequest="submitFromBot(!material.from_bot)"></from-bot>

                    <keyword v-for="(tag, key) in material.keywords"
                             :key="'k' + tag.id"
                             v-model="material.keywords[key]"
                             :material-id="material.id"
                             :editable="true"
                             :removeable="false"
                             @saving="flashStartSaving('Keyword')"
                             @savingPivot="flashStartSaving('Keyword Piot')"
                             @savingError="flashUpdateTagError"
                             @savingPivotError="flashUpdateTagError"
                             @removed="removeKeyword(key)"
                    ></keyword>

                    <bibleverse v-for="(tag, key) in material.bibleverses" :key="'b' + tag.id"
                                v-model="material.bibleverses[key]"
                                :material-id="material.id"
                                :editable="true"
                                :removeable="false"
                                @saving="flashStartSaving('Bibleverse')"
                                @savingPivot="flashStartSaving('Bibleverse Piot')"
                                @savingError="flashUpdateTagError"
                                @savingPivotError="flashUpdateTagError"
                                @removed="removeBibleverse(key)"
                    ></bibleverse>


                    <div class="btn btn-sm btn-primary" v-if="keywordsAndBibleveres.length < 3 && editable === true"
                         @click="editTagsModeEnabled=true">
                        {{$tc('pool.Add-tags', Math.min(0,keywordsAndBibleveres.length-1))}}
                    </div>

                </div>

                <div class="col col-md-6 col-sm-6 col-12 mb-2" v-if="editTagsModeEnabled">
                    <keyword-input
                            v-model="material.keywords"
                            :material-id="material.id"
                            @updated="materialWasModified"
                    ></keyword-input>
                </div>
                <div class="col col-md-5 col-sm-6 col-12 mb-2" v-if="editTagsModeEnabled">
                    <bibleverse-input
                            v-model="material.bibleverses"
                            :material-id="material.id"
                            @updated="materialWasModified"
                    ></bibleverse-input>
                </div>
                <div class="col col-md-1 col-12 mb-2">
                <span class="icon editIcon"
                      v-if="editable && editTagsModeEnabled === false"
                      @click="editTagsModeEnabled=true"></span>
                    <span class="icon doneIcon"
                          v-if="editable && editTagsModeEnabled === true"
                          @click="editTagsModeEnabled=false"></span>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <edditable-text type="div"
                                    classes="card card-body"
                                    :value="material.description"
                                    @value-changed="submitDescription"
                                    :placeholder="$t('pool.Click-here-to-insert-description')"/>
                </div>
            </div>

            <div class="row">
                <div class="col">
                    <span v-if="material.resources !== undefined">
                        {{$tc('pool.resource-count', material.resources.length, {name : material.resources.length}) }},
                    </span>

                    {{$t('pool.edited')}} {{material.updated_at | moment("calendar")}},

                    <span v-if="material.creator !== undefined && material.creator.name !== undefined"
                          class="mr-0 pr-0">
                        {{$t('pool.by')}} {{material.creator.name}},
                    </span>

                    {{$t('pool.author-is')}}
                    <keyword-toggle-text-select
                            :keyword="material.author"
                            @newKeywordSelection="submitAuthor"
                            :emptyPlaceholder="$t('pool.unknown')"/>
                </div>
            </div>

            <div class="row" v-if="material.resources !== undefined && material.resources.length > 1">
                <div class="col-xl-3 col-lg-4 col-md-4 col-sm-6 col-xs-12" v-for="resource in material.resources">
                    <resource-preview :resource="resource">
                        <template slot="additional-buttons">
                            <button class="btn btn-outline-danger mb-1"
                                    @click.prevent="btnDetachResource(resource)"
                                    :title="$t('pool.Detach-resource')">
                                {{$t('pool.detach')}}
                            </button>
                        </template>
                    </resource-preview>
                </div>
            </div>

            <div class="row" v-if="material.resources !== undefined && material.resources.length === 1">
                <div class="col-xl-12 col-12">
                    <resource-detail :resource="material.resources[0]" :showDelete="false">
                        <template slot="additional-buttons">
                            <button class="btn btn-outline-danger mb-1"
                                    @click.prevent="btnDetachResource(material.resources[0])"
                                    :title="$t('pool.Detach-resource')">
                                {{$t('pool.detach')}}
                            </button>
                        </template>
                    </resource-detail>
                </div>
            </div>

            <div class="row" v-if="material.resources !== undefined && material.resources.length === 0">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    {{$t('pool.Material-without-resources')}}
                    <button class="btn btn-sm btn-danger"
                            @click="btnDeleteMaterial"
                            :title="$t('pool.Delete-resource')">{{$t('pool.delete')}}
                    </button>
                </div>
            </div>

        </div>

        <div class="row mb-4" v-if="material">
            <resource-uploader class="col-6"
                               @resource-created="uploadResourceToThisMaterial"></resource-uploader>
            <div class="col-6">
                <div class="d-flex align-items-center justify-content-center w-100 sbAssignResource">
                    <button class="btn btn-secondary"
                            @click="assignResourceToThisMaterial">{{$t('pool.Assign-resource')}}
                    </button>
                </div>
            </div>
        </div>

        <custom-dialog ref="customDialog"/>
        <resource-selector ref="resourceSelector"/>
    </div>
</template>

<script>
    import keyword from '../../../components/keyword/keyword.vue';
    import keywordInput from '../../../components/keyword/keywordInput.vue';
    import bibleverse from '../../../components/bibleverse/biblevers.vue';
    import bibleverseInput from '../../../components/bibleverse/bibleverseInput.vue';
    import resourcePreview from '../../../components/resource/show/resource-preview.vue';
    import resourceDetail from '../../../components/resource/show/resource-detail.vue';
    import editableText from '../../../components/general/edditable.vue';
    import fromBot from '../../../components/fromBot.vue';
    import starRating from 'vue-star-rating/src/star-rating';
    import Vue from 'vue';
    import AsyncComputed from 'vue-async-computed';
    import ResourceUploader from "../../../components/uploader/resourceUploader";
    import {resourceDownloadLink} from "../../../components/serverRoutes";
    import customDialog from '../../../components/modals/dialogs/customDialog';
    import MaterialRating from "../../../components/Material/MaterialRating";
    import Flag from "../../../components/flags/Flag";
    import {flagColors} from "../../../components/flags/flagOptions";
    import ResourceSelector from "../../../components/modals/selectors/resourceSelector";
    import KeywordToggleTextSelect from "../../../components/keyword/keywordToggleTextSelect";

    Vue.use(AsyncComputed);

    // https://github.com/craigh411/vue-star-rating/#props

    export default {

        name: 'MaterialDetail',

        props: {
            id: {
                required: true,
                type: Number
            },
            editable: {
                required: false,
                type: Boolean,
                default: true
            },


        },

        data() {
            return {
                material: null,
                editTagsModeEnabled: false,
                errorOnLoadingMessage: null,

                currentRating: null,
            };
        },

        computed: {

            keywordsAndBibleveres() {

                this.material.keywords.forEach((kw) => {
                    kw.is = 'keyword';
                });

                this.material.bibleverses.forEach((bv) => {
                    bv.is = 'bibleverse';
                });

                return this.material.keywords.concat(this.material.bibleverses);

                /*.sort((k1, k2) => {
                    return k1.pivot.relevance - k2.pivot.relevance;
                }));
                */
            },

            flagColor() {

                if (this.material.flag && this.material.flag <= flagColors.length) {
                    return flagColors[this.material.flag - 1];
                }

                return false;
            }

        },

        asyncComputed: {},

        watch: {
            id(newValue) {
                this.material = null;
                this.getMaterial();
            }
        },

        created() {
            this.getMaterial();
        },


        methods: {

            getMaterial() {
                this.errorOnLoadingMessage = null;

                this.$store.dispatch('materials/getMaterial', this.id).then((material) => {
                    this.material              = material;
                    this.errorOnLoadingMessage = null;
                }).catch((response) => {
                    this.errorOnLoadingMessage = response;
                });
            },

            submitFlag(newFlag) {
                this.submitMaterialUpdate({flag: newFlag, from_bot: false}, 'Flag');
            },

            submitTitle(newTitle) {
                this.submitMaterialUpdate({title: newTitle, from_bot: false}, 'Title');
            },

            submitRating(newRating) {
                this.currentRatingChanged(newRating);
                this.submitMaterialUpdate({rating: newRating}, 'Rating');
            },

            currentRatingChanged(value) {
                this.currentRating = value;
            },

            submitDescription(newDescription) {
                this.submitMaterialUpdate({description: newDescription, from_bot: false}, 'Description');
            },

            submitFromBot(newValue) {
                this.submitMaterialUpdate({'from_bot': newValue}, 'From bot');
            },

            submitAuthor(newKeyword) {
                this.submitMaterialUpdate({'author': newKeyword}, 'Authors');
            },

            submitMaterialUpdate(data, propertyName) {

                const result = this.$store.dispatch('materials/updateMaterial', {id: this.material.id, data});

                if (propertyName) {
                    const startSavingMessage = this.flashStartSaving(propertyName);

                    result.then((response) => {
                        // On Success
                        this.flashSaved(propertyName);
                    }).catch(() => {
                        // On Error
                        this.flashError(propertyName);
                    }).then((data) => {
                        // Always
                        startSavingMessage.destroy();
                        return data;
                    });
                }

                result.then((data) => {
                    this.getMaterial();
                    return data;
                });

                return result;
            },

            uploadResourceToThisMaterial(resource) {
                this.$store.dispatch('materials/attachResource',
                    {materialId: this.id, resourceId: resource.id}
                ).then(({material}) => {
                    this.material = material;
                }).catch(() => {
                });
            },

            btnDetachResource(resource) {

                this.$store.dispatch('materials/detachResource',
                    {materialId: this.id, resourceId: resource.id}
                ).then(({material, resource}) => {
                    this.material = material;

                    if (resource.materials && resource.materials.length === 0) {
                        this.$refs.customDialog.show({
                            title: 'Rückfrage',
                            content: 'Diese Ressource ist jetzt keinem Material mehr zugeordnet.<br/>Soll ' + (resource.original_filename ? '"' + resource.original_filename + '"' : 'sie') + ' <b>jetzt komplett</b> gelöscht werden?',
                            yesText: 'Ja, löschen',
                            yesVariant: 'success',
                            noText: 'Nein, so lassen',
                            noVariant: 'warning',
                            allowBackdrop: false
                        }).then((answerPositive) => {

                            if (answerPositive === true) {
                                this.$refs.customDialog.show({
                                    title: 'Lösche Resource',
                                    content: 'Lösche ' + (resource.original_filename ? '"' + resource.original_filename + '"' : 'Ressource') + '...',
                                    yesEnabled: false,
                                    noEnabled: false,
                                    allowBackdrop: false
                                }).catch(() => {
                                });

                                this.$store.dispatch('resources/deleteResource', resource.id)
                                    .then(() => {
                                        this.$refs.customDialog.show({
                                            title: 'Resource gelöscht',
                                            content: 'Resource erfolgreich gelöscht!',
                                            yesText: 'ok',
                                            yesVariant: 'primary',
                                            yesEnabled: true,
                                            noEnabled: false,
                                            allowBackdrop: true,
                                        });
                                    });
                            }

                        }).catch(({message}) => {
                            this.$refs.customDialog.show({
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

            assignResourceToThisMaterial() {
                this.$refs.resourceSelector.showPromise()
                    .then((resource) => {

                        if (this.material.resources.find(mr => mr.id == resource.id)) {
                            alert('This resource exists already in this material');
                        } else {
                            this.$store.dispatch('materials/attachResource', {
                                materialId: this.id,
                                resourceId: resource.id
                            }).then(({material}) => {
                                this.material = material;
                            })
                        }
                    })
            },

            btnDeleteMaterial() {

                this.$store.dispatch('materials/deleteMaterial', this.id)
                    .then(() => {
                        this.$router.go(-1);
                    });

            },

            removeKeyword(index) {
                this.material.keywords.splice(index, 1);
                this.materialWasModified();
            },

            removeBibleverse(index) {
                this.material.bibleverses.splice(index, 1);
                this.materialWasModified();
            },

            materialWasModified() {
                this.material.from_bot = false;
            },

            bibleverseUpdated({oldBibleverse, newBibleverse}) {

                // Success
                const index = this.material.bibleverses.findIndex((bv) => {
                    return bv.id === oldBibleverse.id;
                });

                if (index !== -1) {
                    this.material.bibleverses.splice(index, 1, newBibleverse); // https://vuejs.org/2016/02/06/common-gotchas/
                    this.flashSaved('Keyword "' + newBibleverse.label + '"');
                    this.materialWasModified();
                } else {
                    console.error('Changed bibleverse was not found in Array!');
                }

            },

            flashUpdateTagError({tag, msg}) {
                this.flash(msg, 'error', {})
            },


            flashStartSaving(propertyName) {
                return this.flash('Saving ' + propertyName[0].toUpperCase() + propertyName.substring(1).toLowerCase() + ' now ...', 'warning', {
                    important: false,
                    timeout: 2000
                });
            },

            flashSaved(propertyName) {
                // console.debug('saved Flash: ', propertyName);
                return this.flash(propertyName[0].toUpperCase() + propertyName.substring(1).toLowerCase() + ' saved', 'success', {
                    timeout: 2000,
                    important: false
                })
            },
            flashError(propertyName) {
                //  console.debug('Error Flash: ', propertyName);
                return this.flash('An error accured while while saving ' + propertyName.toLowerCase(), 'error', {
                    important: true
                });
            },

            downloadResourceLink(resource) {
                window.location = resourceDownloadLink(resource);
            },

        },


        components: {
            KeywordToggleTextSelect,
            ResourceSelector,
            Flag,
            MaterialRating,
            ResourceUploader,
            keyword,
            keywordInput,
            bibleverse,
            bibleverseInput,
            resourcePreview,
            resourceDetail,
            edditableText: editableText,
            starRating,
            fromBot,
            customDialog,
        },

    }
</script>

<style scoped>
    .meta {
        font-size: smaller;
    }

    .icon {
        background-repeat: no-repeat;
        background-size: 0.8em;
        display: inline-block;
        width: 1em;
        height: 1em;
        position: relative;
        top: 0.25em;
        cursor: pointer;
    }

    .editIcon {
        background-image: url("/img/icons/entypo-plus/lock.svg");
    }

    .doneIcon {
        background-image: url("/img/icons/entypo-plus/lock-open.svg");
    }

    .sbAssignResource {
        width: 100%;
        border-radius: 0.3rem;
        border: 1px dashed #bdbdbd;
        background-color: #e9ecef;
        min-height: 5rem;
        display: flex;
    }


</style>

<style>
</style>