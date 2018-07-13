<template>
    <div>
        <flash-message class="flashMessageHolder col-md-4 col-sm-6 col-lg-3 col-xs-12">This is some test</flash-message>

        <edditable-text type="h1"
                        :value="material.title"
                        @value-changed="submitTitle"
                        classes="materialTitle"
                        placeholder="Please enter a title here ..."></edditable-text>


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
                        :rating="material.rating"
                >
                </star-rating>

                Contains
                {{$tc('pool.resource-count', material.resources.length, {name : material.resources.length}) }},
                {{$t('pool.eddited')}} {{material.updated_at}},
                {{$t('pool.by')}} {{material.creator.name}}

                <span v-if="material.author">{{$t('pool.resource-author-is', {name: material.author.title} )}}</span>

            </div>

        </div>

        <div class="row">
            <div class="col-lg-12" id="allTags">

                <from-bot :from-bot="material.from_bot" @toggleRequest="submitFromBot(!material.from_bot)"></from-bot>

                <div v-for="tag in orderedKeywordsAndBibleversesByRelevance" :key="tag.is + tag.id">
                    <keyword
                            v-if="tag.is=='keyword'"
                            :keyword="tag"
                            :editable="editable"
                            :material-id="material.id"
                            @saving="flashStartSaving('Keyword')"
                            @saved="keywordUpdated"
                            @savingError="keywordUpdateError"
                            @savingPivot="flashStartSaving('Keyword Piot')"
                            @savedPivot="keywordUpdated"
                            @savingPivotError="keywordUpdated"
                    ></keyword>

                    <bibleverse v-if="tag.is =='bibleverse'" :bibleverse="tag"></bibleverse>
                </div>


            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <edditable-text type="div"
                                classes="card card-body"
                                :value="material.description"
                                @value-changed="submitDescription"
                                placeholder="Insert description ..."></edditable-text>
            </div>
        </div>

        <div class="row" v-if="material.resources.length >0">
            <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12" v-for="resource in material.resources">
                <resource :resource="resource"></resource>
            </div>
        </div>


    </div>
</template>

<script>
    import keyword from './../../components/keyword/keyword.vue';
    import bibleverse from './../../components/bibleverse/biblevers.vue';
    import resource from './../../components/resource/resource.vue';
    import edditableText from './../../components/edditable.vue';
    import fromBot from './../../components/fromBot.vue';
    // https://github.com/craigh411/vue-star-rating/#props
    import starRating from 'vue-star-rating';

    import axios from 'axios';

    export default {
        name: "MaterialApp",
        data() {
            return {
                material: materialpool.material,
                editable: true,
            };
        },

        computed: {
            materialApiUrl() {
                return '/api/v1/materials/' + this.material.id;
            },

            orderedKeywordsAndBibleversesByRelevance() {

                this.material.keywords.forEach((kw) => {
                    kw.is = 'keyword';
                });

                this.material.bibleverses.forEach((bv) => {
                    bv.is = 'bibleverse';
                });

                return this.material.keywords.concat(this.material.bibleverses.sort((k1, k2) => {
                    return k1.pivot.relevance - k2.pivot.relevance;
                }));
            }

        },


        methods: {
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

                data._method = 'PUT';

                const result = axios.post(this.materialApiUrl, data);

                if (propertyName) {
                    const startSavingMessage = this.flashStartSaving(propertyName);

                    result.then((response) => {
                        // On Success
                        this.flashSaved(propertyName);

                        if (response.data.from_bot !== undefined) {
                            this.material.from_bot = response.data.from_bot;
                        }

                    }).catch(() => {
                        // On Error
                        this.flashError(propertyName);
                    }).then(() => {
                        // Always
                        startSavingMessage.destroy();
                    });
                }


                return result;

            },

            keywordUpdated({newKeyword, oldKeyword}) {

                console.log(newKeyword, oldKeyword);

                // Success
                const index = this.material.keywords.findIndex((kw) => {
                    return kw.id === oldKeyword.id;
                });

                if (index !== -1) {
                    this.material.keywords.splice(index, 1, newKeyword); // https://vuejs.org/2016/02/06/common-gotchas/
                    this.flashSaved('Keyword "' + newKeyword.title + '"');
                } else {
                    console.error('Renamed keyword was not found in Array!');
                }

            },

            keywordUpdateError({keyword, msg}) {
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
            }


        },


        components: {
            keyword,
            bibleverse,
            resource,
            edditableText,
            starRating,
            fromBot
        },
    }
</script>

<style scoped>
    .meta {
        font-size: smaller;
    }

</style>

<style>
    .materialTitle {
        font-size: 2em;
    }

    .starRatingText {
        font-size: smaller;
    }

    .flashMessageHolder {
        position: fixed;
        top: 1em;
        right: 1em;
    }
</style>