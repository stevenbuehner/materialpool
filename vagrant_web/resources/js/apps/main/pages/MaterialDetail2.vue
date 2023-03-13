<template>
  <div class="container-fluid px-0 px-sm-1 px-md-2 px-lg-3 materialDetail2"
       :class="{materialEditLockActive}">

    <div class="row mx-0 errorMessages">

      <div v-if="!material && !errorOnLoadingMessage">{{ $t('pool.Material-is-loading') }}</div>
      <div class="alert alert-warning"
           v-if="!material && errorOnLoadingMessage">
        {{ errorOnLoadingMessage }}
        <a href='#' class="btn btn-primary" @click="$router.go(-1)">{{ $t('pool.go-back') }}</a>
      </div>

    </div>

    <div class="row mx-0 mx-sm-n1 mx-lg-n3" v-if="material">

      <div class="col-12 col-sm-7 col-md-8 col-lg-8 mb-3 px-0 px-sm-1 px-md-2 px-lg-3">

        <div class="contentSideWrapper">
          <div class="row no-gutters mx-0">
            <div class="col-12 contentMenu">
              <button class="btn btn-sm">
                <public-material-download :material-id="id"/>
              </button>

              <button class="btn btn-sm" :title="$t('pool.Delete-material')"
                      @click="btnDeleteMaterial"
                      :disabled="!material">
                <trash-icon class="trash-icon buttonIcon"/>
              </button>

              <button class="btn btn-sm" :title="$t('pool.duplicate-material')"
                      @click="duplicateAndOpenMaterial"
                      :disabled="!material">
                <clone-icon class="cloneIcon buttonIcon"/>
              </button>
              <div class="title">{{ material.title }}</div>
            </div>
          </div>


          <div class="contentContainer container-fluid">

            <!-- Auflistung bei mehr als einer Ressource -->
            <div class="row"
                 v-if="material.resources && material.resources.length > 1">
              <div class="col-xl-3 col-lg-4 col-md-4 col-sm-6 col-xs-1 p-2"
                   v-for="resource in material.resources">
                <resource-preview :resource="resource" :edit-disabled="material.from_bot">
                  <template v-slot:additional-buttons>
                    <button class="btn btn-sm btn-outline-danger mb-1"
                            @click.prevent="btnDetachResource(resource)"
                            v-if="!materialEditLockActive"
                            :title="$t('pool.Detach-resource')">
                      {{ $t('pool.detach') }}
                    </button>
                  </template>
                </resource-preview>
              </div>
            </div>

            <!-- Detaillierter bei nur einer Ressource -->
            <div class="row"
                 v-if="material.resources && material.resources.length === 1">
              <div class="col-xl-12 col-12 p-0">
                <resource-detail :resource="material.resources[0]" :showDelete="false"
                                 :edit-disabled="material.from_bot">
                  <template v-slot:additional-buttons>
                    <button class="btn btn-outline-danger mb-1"
                            @click.prevent="btnDetachResource(material.resources[0])"
                            v-if="!materialEditLockActive"
                            :title="$t('pool.Detach-resource')">
                      {{ $t('pool.detach') }}
                    </button>
                  </template>
                </resource-detail>
              </div>
            </div>


            <!-- Ohne eine Resource -->
            <div class="row" v-if="material.resources && material.resources.length === 0">
              <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 py-2">
                {{ $t('pool.Material-without-resources') }}
                <button class="btn btn-sm btn-danger btn-sm"
                        @click="btnDeleteMaterial"
                        :title="$t('pool.Delete-resource')">{{ $t('pool.delete') }}
                </button>
              </div>
            </div>

          </div>
        </div>

      </div>


      <div class="col-12 col-sm-5 col-md-4 col-lg-4 px-0 px-sm-1 px-md-2 px-lg-3" v-if="material">

        <b-tabs small
                nav-class="sideTab"
                content-class="sideTabContent"
                :value="tabIndex"
                @activate-tab="onTabSwitch">
          <b-tab :title="$tc('pool.material', 1)">

            <bundle-list
                v-if="bundleIds.length > 0"
                :disabled="true"
                :value="bundleIds"
                :name="$t('pool.Assigned-Bundles')"
            />

            <text-edit-sidebar-field
                :value="material.title"
                :name="$t('pool.Title')"
                :placeholder="$t('pool.enter-name')"
                :disabled="materialEditLockActive"
                @save-request="submitTitle"
            >
              <template v-slot:icon>
                <title-icon/>
              </template>
            </text-edit-sidebar-field>

            <text-edit-sidebar-field
                :value="material.created_at"
                :name="$t('pool.date')"
                :required="true"
                :disabled="materialEditLockActive"
                type="date"
                @save-request="submitDate"
            >
              <template v-slot:icon>
                <calendar-icon/>
              </template>
            </text-edit-sidebar-field>

            <single-tag-select
                :value="material.author"
                :name="$t('pool.Author')"
                :placeholder="$t('pool.Unknown')"
                :disabled="materialEditLockActive"
                typefilter="person"
                @input:associated="submitAuthor"
                @input:dissociated="submitAuthor"
            >
              <template v-slot:icon>
                <person-icon/>
              </template>
            </single-tag-select>


            <text-edit-sidebar-field
                :value="material.description"
                :name="$t('pool.description')"
                :placeholder="$t('pool.enter-description')"
                :disabled="materialEditLockActive"
                type="textarea"
                @save-request="submitDescription"
            />

            <bibleverse-edit-sidebar-field
                :value="material.bibleverses"
                :name="$t('pool.Bibleverses')"
                :placeholder="$t('pool.enter-bibleverse')"
                :disabled="materialEditLockActive"
                :batch-new-tags-enabled="true"
                :batch-edit-relevance-enabled="material.bibleverses.length > 2"
                @input:added="addBibleverse"
                @input:removed="removeBibleverse"
                @request-update-relevance="updateBibleverseRelevance($event.tag, $event.relevance)"
            >
              <template v-slot:icon>
                <bibleverse-icon/>
              </template>
            </bibleverse-edit-sidebar-field>


            <tag-edit-sidebar-field
                :value="material.keywords"
                :name="$t('pool.tags')"
                :placeholder="$t('pool.enter-tags')"
                :disabled="materialEditLockActive"
                typefilter="key"
                :batch-edit-relevance-enabled="material.keywords.length > 3"
                @input:added="addKeyword"
                @input:removed="removeKeyword"
                @request-update-relevance="updateKeywordRelevance($event.tag, $event.relevance)"
            />

            <tag-edit-sidebar-field
                :value="material.keywords"
                :name="$t('pool.Persons')"
                :placeholder="$t('pool.enter-tags')"
                :disabled="materialEditLockActive"
                typefilter="person"
                @input:added="addKeyword"
                @input:removed="removeKeyword"
                @request-update-relevance="updateKeywordRelevance($event.tag, $event.relevance)"
            >
              <template v-slot:icon>
                <person-icon/>
              </template>
            </tag-edit-sidebar-field>

            <tag-edit-sidebar-field
                :value="material.keywords"
                :name="$t('pool.Places')"
                :placeholder="$t('pool.enter-tags')"
                :disabled="materialEditLockActive"
                typefilter="place"
                @input:added="addKeyword"
                @input:removed="removeKeyword"
                @request-update-relevance="updateKeywordRelevance($event.tag, $event.relevance)"
            >
              <template v-slot:icon>
                <place-icon/>
              </template>
            </tag-edit-sidebar-field>


            <tag-edit-sidebar-field
                :value="material.keywords"
                :name="$t('pool.Languages')"
                :placeholder="$t('pool.enter-tags')"
                typefilter="lang"
                :min-input="0"
                :new-tags-enabled="false"
                :disabled="materialEditLockActive"
                @input:added="addKeyword"
                @input:removed="removeKeyword"
                @request-update-relevance="updateKeywordRelevance($event.tag, $event.relevance)"
            >
              <template v-slot:icon>
                <language-icon/>
              </template>
            </tag-edit-sidebar-field>


            <rating-edit
                :value="material.rating"
                :name="$t('pool.Rating')"
                @input="submitRating"/>

            <usage-edit
                :material-id="material.id"
                @input:add-usage=""
                @input:update-usage=""
                @input:remove-usage=""
            />

          </b-tab>
          <b-tab :title="$t('pool.assignments')">
            <div class="row">

              <div class="col-12 col-sm-6 col-mb-4 mb-2 mb-sm-0 py-2">
                <resource-uploader
                    v-if="!materialEditLockActive"
                    @resource-created="uploadResourceToThisMaterial"/>
              </div>

              <div class="col-12 col-sm-6 col-mb-4 py-2"
                   v-if="!materialEditLockActive">
                <div class="dashedBorder p-2 d-flex align-items-center justify-content-center">
                  <b-button @click="assignResourceToThisMaterial">
                    {{ $t('pool.Assign-resource') }}
                  </b-button>
                </div>
              </div>

              <div class="col-12 col-sm-6 col-mb-4 py-2"
                   v-if="!materialEditLockActive">
                <div class="dashedBorder p-2 d-flex align-items-center justify-content-center">
                  <b-button @click="createAndAttachTextResourceToThisMaterial">
                    {{ $t('pool.Create-text') }}
                  </b-button>
                </div>
              </div>

            </div>
          </b-tab>
          <b-tab :title="$t('pool.meta')">
            <text-edit-sidebar-field
                v-if="material.creator"
                :disabled="true"
                :value="material.creator.title"
                :name="$t('pool.Creator')">
            </text-edit-sidebar-field>

            <text-edit-sidebar-field
                :disabled="true"
                :value="material.id"
                :name="$t('pool.Material-ID')">
            </text-edit-sidebar-field>

            <text-edit-sidebar-field
                :disabled="true"
                :value="material.updated_at"
                type="date"
                :name="$t('pool.Updated-at')">
              <template v-slot:icon>
                <calendar-icon/>
              </template>
            </text-edit-sidebar-field>
          </b-tab>
        </b-tabs>

      </div>

    </div>

    <custom-dialog ref="customDialog"/>
    <material-deletor v-if="material"
                      ref="materialDeletor"
                      :material-id="material.id"/>
    <resource-selector v-if="material && material.resources"
                       ref="resourceSelector"
                       :excluded-resource-id="material.resources.map(({id})=> id)"/>
  </div>
</template>

<script>
import Keyword                 from '../../../components/keyword/keyword.vue';
import keywordInput            from '../../../components/keyword/keywordInput.vue';
import bibleverse              from '../../../components/bibleverse/biblevers.vue';
import bibleverseInput         from '../../../components/bibleverse/bibleverseInput.vue';
import resourcePreview         from '../../../components/resource/show/resource-preview.vue';
import resourceDetail          from '../../../components/resource/show/resource-detail.vue';
import editableText            from '../../../components/general/edditable.vue';
import fromBot                 from '../../../components/fromBot.vue';
import starRating              from 'vue-star-rating/src/star-rating';
import ResourceUploader        from "../../../components/uploader/resourceUploader";
import customDialog            from '../../../components/modals/dialogs/customDialog';
import MaterialRating          from "../../../components/Material/MaterialRating";
import Flag                    from "../../../components/flags/Flag";
import {flagColors}            from "../../../components/flags/flagOptions";
import KeywordToggleTextSelect from "../../../components/keyword/keywordToggleTextSelect";
import {savingDialogs}         from "../../../helper/flashMessages";
import PublicMaterialDownload  from "../../../components/download/public-material-download";
import {formatLocalizedDate}   from '../../../helper/datetime.mixin'
import cloneIcon               from 'svg-icon/dist/svg/awesome/clone.svg';
import usageEdit               from "../../../components/sidebar-fields/usageEdit";


import trashIcon       from 'svg-icon/dist/svg/oct/trashcan.svg';
import titleIcon       from 'svg-icon/dist/svg/material/title.svg';
import calendarIcon    from 'svg-icon/dist/svg/material/today.svg';
import descriptionIcon from 'svg-icon/dist/svg/material/description.svg';
import placeIcon       from 'svg-icon/dist/svg/material/place.svg';
import authorIcon      from 'svg-icon/dist/svg/material/person.svg';
import personIcon      from 'svg-icon/dist/svg/material/person.svg';
import languageIcon    from 'svg-icon/dist/svg/material/language.svg';
import bibleverseIcon  from '../../../../icons/bibleverse/bible.svg'
import Vue             from 'vue';


import {BButton, TabsPlugin}      from 'bootstrap-vue';
import TextEditSidebarField       from "../../../components/sidebar-fields/textEdit";
import BibleverseEditSidebarField from "../../../components/sidebar-fields/bibleverseEdit";
import dayjs                      from 'dayjs';
import TagEditSidebarField                          from "../../../components/sidebar-fields/tagEdit";
import {RELEVANCE_USER_MAX, server_datetime_format} from "../../config";
import RatingEdit                                   from "../../../components/sidebar-fields/ratingEdit";
import bundleList                 from '../../../components/sidebar-fields/bundleList';
import SingleTagSelect            from "../../../components/sidebar-fields/singleTagSelect";
import ResourceSelector           from "../../../components/modals/selectors/resourceSelector";
import MaterialDeletor            from "../../../components/modals/deletors/materialDeletor";
import Bundle                     from "../../../components/bundles/bundle";
import RelevanceSelector          from "../../../components/modals/dialogs/relevanceSeletor.vue";

Vue.use(TabsPlugin);


// https://github.com/craigh411/vue-star-rating/#props
export default {

  name: 'MaterialDetail2',

  mixins: [savingDialogs, formatLocalizedDate],

  props: {
    id: {
      required: true,
      type: Number
    },
    tabIndex: {
      required: false,
      type: Number,
      default: 0
    },
  },

  data() {
    return {
      material: null,
      materialDetailsLoaded: false,
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

    flagColor() {

      if (this.material.flag && this.material.flag <= flagColors.length) {
        return flagColors[this.material.flag - 1];
      }

      return false;
    },

    materialEditLockActive() {
      return this.material && this.material.from_bot === true;
    },

    bundleIds() {
      if (this.material && this.material.foreign_ids && Array.isArray(this.material.foreign_ids) && this.material.foreign_ids.length > 0) {
        return this.material.foreign_ids.map((forId) => forId.bundle_id);
      } else {
        return [];
      }
    }
  },

  asyncComputed: {
    material: {
      get() {
        this.errorOnLoadingMessage = null;
        this.materialDetailsLoaded = false;

        const materialDetailPromise = this.$store.dispatch('materials/getMaterialDetailed', this.id)
                                          .then((material) => {
                                            this.errorOnLoadingMessage = null;
                                            this.materialDetailsLoaded = true;
                                            // console.log('Material angekommen: ', material);

                                            return material;
                                          })
                                          .catch((message) => {
                                            this.errorOnLoadingMessage = message;
                                          });

        // Wenn die MaterialDetails noch nicht im Cache geladen sind, aber Preview-Daten schon da sind
        // ... dann zeige die schon mal an, bis der Rest geladen wurde
        if (!this.$store.getters['materials/hasMaterialDetails'](this.id) && this.$store.getters['materials/hasMaterialPreview'](this.id)) {

          // Stelle sicher, dass Material aus dem VueX-Store aktualisiert wird, sobald es geladen wurde
          materialDetailPromise.then(() => {
            // console.log("Detailliertes Material wurde nachgeladen");
            this.$asyncComputed.material.update();
          });

          // console.log("Zeige erst mal Preview-Material an und beginne mit dem Nachladen der Details");
          return this.$store.getters['materials/getMaterial'](this.id);
        }

        // Wenn weder Material-Preview noch Material-Detail zur Verfügung stehen, dann hilft alles nichts
        // Gib das Material-Detail-Promise zurück und warte, bis alles geladen ist.
        return materialDetailPromise;


      },
      default: null
    },
  },

  watch: {},

  created() {
  },


  methods: {

    submitFlag(newFlag) {
      this.submitMaterialUpdate({flag: newFlag, from_bot: false}, 'Flag');
    },

    submitTitle(newTitle) {
      this.submitMaterialUpdate({title: newTitle, from_bot: false}, this.$t('pool.Title'));
    },

    submitDate(newDate) {
      this.submitMaterialUpdate({
        created_at: dayjs(newDate).format(server_datetime_format),
        from_bot: false
      }, this.$t('pool.Creation-date'));
    },

    submitRating(newRating) {
      this.submitMaterialUpdate({rating: newRating}, this.$t('pool.Rating'));
    },

    submitDescription(newDescription) {
      this.submitMaterialUpdate({description: newDescription, from_bot: false}, this.$t('pool.Description'));
    },

    submitFromBot(newValue) {
      this.submitMaterialUpdate({'from_bot': newValue}, 'From bot');
    },

    submitAuthor(newKeyword) {
      this.submitMaterialUpdate({'author': newKeyword}, this.$t('pool.Author'));
    },

    submitMaterialUpdate(data, propertyName) {

      const result = this.$store.dispatch('materials/updateMaterial', {id: this.material.id, data});

      if (propertyName) {
        const startSavingMessage = this.flashStartSaving(propertyName);

        result.then(() => {
          // On Success
          this.flashSaved(propertyName);
        }).catch(({response}) => {
          // On Error

          // Wenn spezifische Fehler angegeben wurden
          if (response.data.errors && Object.keys(response.data.errors).length > 0) {
            const errors = response.data.errors;

            for (const keyName in errors) {
              // Spezifische Fehlermeldung
              const message = Array.isArray(errors[keyName]) ? errors[keyName].join(', ') : undefined;
              this.flashError(keyName, message);
            }
          } else {
            // Fehlermeldung ganz allgemein halten und auf das bearbeitete Feld beziehen
            this.flashError(propertyName);
          }
        }).then((data) => {
          // Always
          startSavingMessage.destroy();
          return data;
        });
      }

      result.then((data) => {
        this.$asyncComputed.material.update();
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
          });
    },

    createAndAttachTextResourceToThisMaterial() {

      const flashMessage = this.flashActionStartedWaiting(this.$t('pool.Create-text'));

      this.$store.dispatch('resources/createTextResource', {text: 'Lorem ipsum'})
          .then((resource) => {
            return this.$store.dispatch('materials/attachResource',
                {materialId: this.id, resourceId: resource.id})
                       .then(() => {
                         this.flashActionSuccessfullyFinished(this.$t('pool.Text-created-and-assigned'), flashMessage);
                         this.$asyncComputed.material.update();
                       })
          })
          .catch((message) => {
            this.flashActionFailed(message, flashMessage);
          })
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


    btnDeleteMaterial() {

      this.$refs.materialDeletor.showPromise()
          .then((message) => {
            // Material was deleted - jump somewhere
            this.$router.go(-1);
          })
          .catch(() => {
            // Material was not deleted - show message
          })
          .then(() => {
            this.$asyncComputed.material.update();
          });

    },

    addKeyword(keywordObject) {

      if (keywordObject && (!keywordObject.pivot || !keywordObject.pivot.relevance)) {
        keywordObject.pivot = {
          relevance: RELEVANCE_USER_MAX,
        }
      }

      const prm = new Promise((resolve, reject) => {

        if (keywordObject.isNew === true) {

          const startFlash = this.flashStartSaving(this.$t('pool.keyword'));

          resolve(this.$store.dispatch('keywords/create', {
            title: keywordObject.title,
            type: keywordObject.type
          }).then((keyword) => {
            startFlash.destroy();
            return keyword;
          }));

        } else {
          resolve(keywordObject);
        }
      }).then((keyword) => {
        return this.updateKeywordRelevance(keyword, keywordObject.pivot.relevance);
      });

    },

    removeKeyword(keywordObject) {

      const startFlash = this.flashStartRemoving(this.$t('pool.keyword') + ' ' + keywordObject.title);

      // Remove Element from array
      const i = this.material.keywords.findIndex(el => el.id === keywordObject.id);
      if (i !== -1) {
        this.material.keywords.splice(i, 1);
      }

      this.$store.dispatch('keywords/deleteAssignment', {
        materialId: this.id,
        keywordId: keywordObject.id
      }).then((response) => {
        this.materialWasModified();
        this.flashRemoved(this.$t('pool.keyword') + ' ' + keywordObject.title, startFlash);
      }).catch((response) => {
        this.flashActionFailed(this.$t('pool.Error-while-deleting-tag') + ' ' + keywordObject.title, startFlash);

        // Re-Insert element to array on error at last index (not tested yet)
        this.material.keywords.splice(Math.min(i, this.material.keywords.length - 1), 0, keywordObject);
      });

    },

    updateKeywordRelevance(keywordObject, newRelevance) {
      const startFlash = this.flashStartSaving(this.$t('pool.keyword'));

      return this.$store.dispatch('keywords/updateRelevance', {
        materialId: this.id,
        keywordId: keywordObject.id,
        relevance: newRelevance
      }).then((data) => {
        this.materialWasModified();

        const index = this.material.keywords.findIndex((el) => el.id === data.id);
        if (index === -1) {
          this.material.keywords.push(data);
        } else {
          this.$set(this.material.keywords, index, data);
        }

        startFlash.destroy();
        this.flashSaved(this.$t('pool.keyword'));

        return data;
      }).catch((message) => {
        this.flashActionFailed(this.$t('pool.Error-while-moving-keyword') + ': ' + message, startFlash);
      });

    },

    addBibleverse(bibleverseObject) {
      const relevance = bibleverseObject && (!bibleverseObject.pivot || !bibleverseObject.pivot.relevance) ? RELEVANCE_USER_MAX : bibleverseObject.pivot.relevance;
      this.updateBibleverseRelevance(bibleverseObject, relevance);
    },

    removeBibleverse(bibleVerseObject) {
      const startFlash = this.flashStartRemoving(this.$t('pool.Bibleverse') + ' ' + bibleVerseObject.label);

      // Remove element from array
      const i = this.material.bibleverses.findIndex((el) => el.id === bibleVerseObject.id);
      if (i !== -1) {
        this.material.bibleverses.splice(i, 1);
      }

      this.$store.dispatch('bibleverses/deleteAssignment', {
        materialId: this.id,
        bibleverseId: bibleVerseObject.id
      }).then((response) => {
        this.materialWasModified();
        startFlash.destroy();
        this.flashRemoved(this.$t('pool.Bibleverse') + ' ' + bibleVerseObject.label);
      }).catch((response) => {
        this.flashActionFailed(this.$t('pool.Error-while-deleting-tag') + ' ' + bibleVerseObject.label, startFlash);

        // Re-Insert element to array on error at last index (not tested yet)
        this.material.bibleverses.splice(Math.min(i, this.material.bibleverses.length - 1), 0, bibleVerseObject);
      });

      this.materialWasModified();
    },

    updateBibleverseRelevance(bibleverseObject, newRelevance) {

      const startFlash = this.flashStartSaving(this.$t('pool.Bibleverse'));

      const promise = bibleverseObject.id === undefined ?
                      this.$store.dispatch('bibleverses/createAndAssign', {
                        from: bibleverseObject.from,
                        to: bibleverseObject.to,
                        materialId: this.id,
                        relevance: newRelevance
                      }) :
                      this.$store.dispatch('bibleverses/updateRelevance', {
                        materialId: this.id,
                        bibleverseId: bibleverseObject.id,
                        relevance: newRelevance
                      });

      promise.then((data) => {
        this.materialWasModified();

        const index = this.material.bibleverses.findIndex((el) => el.id === data.id);
        if (index === -1) {
          this.material.bibleverses.push(data);
        } else {
          this.$set(this.material.bibleverses, index, data);
        }

        startFlash.destroy();
        this.flashSaved(this.$t('pool.Bibleverse'));

        return data;
      }).catch((message) => {
        this.flashActionFailed(this.$t('pool.Error-while-moving-keyword') + ': ' + message, startFlash);

        alert(message);
      });

    },

    materialWasModified() {
      this.material.from_bot = false;
    },

    onTabSwitch(index) {

      if (index === this.tabIndex)
        return;

      // Replace statt push
      this.$router.replace({
        name: this.$route.name,
        params: this.$route.params,
        query: {
          tabIndex: index
        }
      });
      return false;
    },


    duplicateAndOpenMaterial() {

      const startFlash = this.flashActionStartedWaiting(this.$t('pool.Copying-material'));

      this.$store.dispatch('materials/copyMaterial', this.material.id)
          .then((material) => {
            this.flashActionSuccessfullyFinished(this.$t('pool.Material-successfully-copied'), startFlash);

            this.$router.push({
              name: this.$route.name,
              params: {...this.$route.params, id: material.id}
            });
          })
          .catch((message) => {
            this.flashActionFailed(this.$t('pool.Copying-material', message), startFlash);
          });
    }
  },


  components: {
    RelevanceSelector,
    Bundle,
    MaterialDeletor,
    ResourceSelector,
    BButton,
    SingleTagSelect,
    RatingEdit,
    TagEditSidebarField,
    BibleverseEditSidebarField,
    TextEditSidebarField,
    PublicMaterialDownload,
    KeywordToggleTextSelect,
    Flag,
    MaterialRating,
    ResourceUploader,
    Keyword,
    keywordInput,
    bibleverse,
    bibleverseInput,
    resourcePreview,
    resourceDetail,
    edditableText: editableText,
    bundleList,
    starRating,
    usageEdit,
    fromBot,
    customDialog,
    trashIcon,
    cloneIcon,
    calendarIcon, titleIcon, descriptionIcon, placeIcon, authorIcon, personIcon, languageIcon, bibleverseIcon,
  },

}
</script>

<style lang="scss">
@import "resources/sass/theme.scss";

.materialDetail2 {

  .buttonIcon {
    width: 1.5em;
    height: 1.5em;
  }

  .contentSideWrapper {
    border: 1px solid $gray-400;
    border-radius: $card-border-radius;

    .contentMenu {
      border-bottom: 1px solid $gray-400;

      .title {
        display: inline-block;
        padding: 0.25em 0.5em;
        font-weight: bold;
        text-overflow: ellipsis;
        overflow: hidden;
        white-space: nowrap;
        max-width: 100%;

        @media(min-width: map-get($grid-breakpoints, "sm")) {
          float: right;
          padding-left: 1em;
        }
      }
    }

    .contentContainer {
      background-color: $jumbotron-bg;
    }
  }

  // Tab Navigation
  .sideTab {
    border-bottom-color: $gray-400;

    li a {
      padding: .25rem .5rem;
      color: $black;

      &.active {
        background: $gray-200;
        border-top-color: $gray-400;
        border-right-color: $gray-400;
        border-bottom-color: $gray-200;
        border-left-color: $gray-400;

      }
    }
  }

  // Tab Content
  .sideTabContent {
    background: $gray-200;
    border: 1px solid $gray-400;
    border-top: none;
    border-right-color: $gray-400;
    border-bottom-color: $gray-400;
    border-left-color: $gray-400;
    padding: 0.5em 0.5em 1em 0.5em;
    border-bottom-left-radius: $card-border-radius;
    border-bottom-right-radius: $card-border-radius;

    .dashedBorder {
      width: 100%;
      min-height: 5rem;
      border: 1px dashed $gray-500;
      border-radius: 0.3rem;
    }
  }

  &.materialEditLockActive {
    .contentContainer {
      background-color: mix($jumbotron-bg, $red, 70%);
    }
  }
}
</style>