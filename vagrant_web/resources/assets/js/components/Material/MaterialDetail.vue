<template>
    <div>
        <div v-if="!material && !errorOnLoadingMessage">{{$t('pool.Material-is-loading')}}</div>
        <div class="alert alert-warning"
             v-if="!material && errorOnLoadingMessage">
            {{errorOnLoadingMessage}}
            <a href="javascript:history.go(-1)" class="btn btn-primary">{{$t('pool.go-back')}}</a>
        </div>

        <div v-if="material">

            <flash-message class="flashMessageHolder col-md-4 col-sm-6 col-lg-3 col-xs-12">This is some test
            </flash-message>

            <edditable-text
                    type="h1"
                    :value="material.title"
                    @value-changed="submitTitle"
                    classes="materialTitle"
                    placeholder="Please enter a title here ...">
            </edditable-text>


            <div class="meta row">
                <div class="col-lg-12">
                    <star-rating
                            :increment="1"
                            :max-rating="20"
                            inactive-color="lightgray"
                            active-color="black"
                            :star-size="15"
                            :inline="true"
                            @rating-selected="submitRating"
                            text-class="starRatingText"
                            :rating="material.rating">
                    </star-rating>

                    (
                    <span v-if="material.resources !== undefined">
                    {{$tc('pool.resource-count', material.resources.length, {name : material.resources.length}) }},
                </span>
                    {{$t('pool.eddited')}} {{material.updated_at}},
                    <span v-if="material.creator !== undefined && material.creator.name !== undefined">
                    {{$t('pool.by')}} {{material.creator.name}}
                </span>

                    <span v-if="material.author">{{$t('pool.resource-author-is', {name: material.author.title} )}}</span>
                    )

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
                             :editable="editable"
                             @saving="flashStartSaving('Keyword')"
                             @savingPivot="flashStartSaving('Keyword Piot')"
                             @savingError="flashUpdateTagError"
                             @savingPivotError="flashUpdateTagError"
                             :removeable="true"
                             @removed="removeKeyword(key)"
                    ></keyword>

                    <bibleverse v-for="(tag, key) in material.bibleverses" :key="'b' + tag.id"
                                v-model="material.bibleverses[key]"
                                :material-id="material.id"
                                :editable="editable"
                                @saving="flashStartSaving('Bibleverse')"
                                @savingPivot="flashStartSaving('Bibleverse Piot')"
                                @savingError="flashUpdateTagError"
                                @savingPivotError="flashUpdateTagError"
                                :removeable="true"
                                @removed="removeBibleverse(key)"
                    ></bibleverse>


                    <div class="btn btn-sm btn-primary" v-if="keywordsAndBibleveres.length === 0 && editable === true">
                        Tags
                        hinzufügen
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
                                    placeholder="Click here to insert description ..."></edditable-text>
                </div>
            </div>

            <div class="row" v-if="material.resources !== undefined && material.resources.length > 1">
                <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12" v-for="resource in material.resources">
                    <resource-preview :resource="resource">
                        <template slot="additional-buttons">
                            <button class="btn btn-outline-danger" @click.prevent="btnDetachResource(resource)">
                                {{$t('pool.detach')}}
                            </button>
                        </template>
                    </resource-preview>
                </div>
            </div>

            <div class="row" v-if="material.resources !== undefined && material.resources.length === 1">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <resource-detail :resource="material.resources[0]">
                        <template slot="additional-buttons">
                            <button class="btn btn-outline-danger"
                                    @click.prevent="btnDetachResource(material.resources[0])">
                                {{$t('pool.detach')}}
                            </button>
                        </template>
                    </resource-detail>
                </div>
            </div>

            <div class="row" v-if="material.resources !== undefined && material.resources.length === 0">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    Material ohne Resourcen
                    <button class="btn btn-sm btn-danger" @click="btnDeleteMaterial">{{$t('pool.delete')}}</button>
                </div>
            </div>

        </div>

        <resource-uploader v-if="material" @resource-created="addResourceToThisMaterial"></resource-uploader>

        <custom-dialog ref="myDialog"></custom-dialog>
    </div>
</template>

<script>
    import keyword from './../keyword/keyword.vue';
    import keywordInput from './../keyword/keywordInput.vue';
    import bibleverse from './../bibleverse/biblevers.vue';
    import bibleverseInput from './../bibleverse/bibleverseInput.vue';
    import resourcePreview from './../resource/show/resource-preview.vue';
    import resourceDetail from './../resource/show/resource-detail.vue';
    import edditableText from './../edditable.vue';
    import fromBot from './../fromBot.vue';
    import starRating from 'vue-star-rating';
    import flashMessage from 'vue-flash-message';
    import Vue from 'vue';
    import AsyncComputed from 'vue-async-computed';
    import ResourceUploader from "../uploader/resourceUploader";
    import {resourceDownloadLink} from "../serverRoutes";
    import customDialog from './../modals/dialogs/customDialog';

    Vue.use(flashMessage);
    Vue.use(AsyncComputed);

    // https://github.com/craigh411/vue-star-rating/#props

    export default {
        name: "MaterialApp",

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

            submitTitle(newTitle) {
                this.submitMaterialUpdate({title: newTitle, from_bot: false}, 'Title');
            },

            submitRating(newRating) {
                this.submitMaterialUpdate({rating: newRating}, 'Rating');
            },

            submitDescription(newDescription) {
                this.submitMaterialUpdate({description: newDescription, from_bot: false}, 'Description');
            },

            submitFromBot(newValue) {
                this.submitMaterialUpdate({'from_bot': newValue}, 'From bot');
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

            addResourceToThisMaterial(resource) {
                this.$store.dispatch('materials/attachResource',
                    {materialId: this.id, resourceId: resource.id}
                ).then(({material}) => {
                    this.material = material;
                })
            },

            btnDetachResource(resource) {

                this.$store.dispatch('materials/detachResource',
                    {materialId: this.id, resourceId: resource.id}
                ).then(({material, resource}) => {
                    this.material = material;

                    if (resource.material && resource.material.length === 0) {
                        this.$refs.myDialog.show({
                            title: 'Rückfrage',
                            content: 'Diese Ressource ist jetzt keinem Material mehr zugeordnet.<br/>Soll ' + (resource.original_filename ? '"' + resource.original_filename + '"' : 'sie') + ' <b>jetzt komplett</b> gelöscht werden?',
                            yesText: 'Ja, löschen',
                            yesVariant: 'success',
                            noText: 'Nein, so lassen',
                            noVariant: 'warning',
                            allowBackdrop: false
                        }).then((answerPositive) => {

                            if (answerPositive === true) {
                                this.$refs.myDialog.show({
                                    title: 'Lösche Resource',
                                    content: 'Lösche ' + (resource.original_filename ? '"' + resource.original_filename + '"' : 'Ressource') + '...',
                                    yesEnabled: false,
                                    noEnabled: false,
                                    allowBackdrop: false
                                });

                                this.$store.dispatch('resources/deleteResource', resource.id)
                                    .then(() => {
                                        this.$refs.myDialog.show({
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

            btnDeleteMaterial() {

                this.$store.dispatch('materials/deleteMaterial', this.id)
                    .then(() => {
                        window.history.back();
                    });

            },

            removeKeyword(index) {
                this.material.keywords.splice(index, 1);
                this.materialWasModified();
            },

            removeBibleverse(index) {
                console.log("Removing Keyword with index: ", index);
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
                console.debug('saved Flash: ', propertyName);
                return this.flash(propertyName[0].toUpperCase() + propertyName.substring(1).toLowerCase() + ' saved', 'success', {
                    timeout: 2000,
                    important: false
                })
            },
            flashError(propertyName) {
                console.debug('Error Flash: ', propertyName);
                return this.flash('An error accured while while saving ' + propertyName.toLowerCase(), 'error', {
                    important: true
                });
            },

            downloadResource(resource) {
                window.location = resourceDownloadLink(resource);
            },

        },


        components: {
            ResourceUploader,
            keyword,
            keywordInput,
            bibleverse,
            bibleverseInput,
            resourcePreview,
            resourceDetail,
            edditableText,
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

    .flashMessageHolder {
        position: fixed;
        top: 1em;
        right: 1em;
        z-index: 99999;
    }


</style>

<style>
    .starRatingText {
        font-size: smaller;
    }
</style>