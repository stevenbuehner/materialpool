<template>
  <b-modal size="lg"
           lazy
           ref="myModal"
           @hide="_cancelPromise"
           @shown="_selectFocus"
  >

    <template #modal-header>
      <div class="row sb_materialcreator_header">
        <div class="col col-9 col-md-10 modal-title">
          <h5>
            {{ headline || $t('pool.Create-a-material') }}
          </h5>
        </div>

        <div class="col col-3 col-md-2 ">
          <b-button-group
              size="sm">
            <b-dropdown right size="sm" :text="$t('pool.templates')"
                        ref="templateDropdown"
                        @show="[preloadDropdown.loadFromMaterialFormActive = false, preloadDropdown.createNewTemplateFormActive = false]">

              <!-- Aktuellen Zustand als Preload-Template abspeichern-->
              <li v-if="!preloadDropdown.createNewTemplateFormActive">
                <b-button
                    role="menuitem"
                    type="button"
                    class="dropdown-item"
                    @click.prevent.stop="_btnCreatePreset">
                  {{ $t('pool.Create-new-template') }}
                </b-button>
              </li>
              <b-dropdown-form @submit.stop.prevent="_saveNewPreset"
                               v-if="preloadDropdown.createNewTemplateFormActive">
                <b-input-group size="sm">
                  <b-form-input
                      v-model="preloadDropdown.templateNameInput"
                      :placeholder="$t('pool.Template-name')"
                      ref="templateNameInput"
                  />
                  <b-button @click="_saveNewPreset"
                            v-if="!preloadDropdown.presetIsSaving">
                    {{ $t('pool.Save') }}
                  </b-button>
                  <b-button v-if="preloadDropdown.presetIsSaving"
                            variant="outline-secondary">
                    <materialpool-spinner/>
                  </b-button>
                </b-input-group>
              </b-dropdown-form>

              <b-dropdown-divider/>

              <b-dropdown-group :header="$t('pool.Existing-templates')">
                <b-dropdown-item-button v-for="(temp, i) in presets" :key="i.title"
                                        @click="_loadPreset(i)">
                  {{ i }}
                  <span class="sb_dropdown_icons_wrapper">
                                        <repeat-icon class="sb_dropdown-icon"
                                                     v-if="i !== defaultPresetId"
                                                     @click.stop="_saveDefaultPresetId(i)"
                                                     :title="$t('pool.Select-as-default')"/>
                                        <repeat-icon class="sb_dropdown-icon selected"
                                                     v-if="i === defaultPresetId"
                                                     @click.stop="_saveDefaultPresetId(null)"
                                                     :title="$t('pool.Remove-default')"/>
                                        <trash-icon class="sb_dropdown-icon"
                                                    @click.stop="_deletePreset(i)"
                                                    :title="$t('pool.Delete-Preset')"/>
                                    </span>
                </b-dropdown-item-button>
              </b-dropdown-group>

              <b-dropdown-divider/>


              <!-- Material anhand einer ID preloaden -->
              <li v-if="!preloadDropdown.loadFromMaterialFormActive">
                <b-button
                    role="menuitem"
                    type="button"
                    class="dropdown-item"
                    @click.prevent.stop="_btnLoadMaterialId">
                  {{ $t('pool.Load-from-material-id') }}
                </b-button>
              </li>
              <b-dropdown-form @submit.stop.prevent="_selectMaterialId(preloadMaterialId)"
                               v-if="preloadDropdown.loadFromMaterialFormActive">
                <b-input-group size="sm">
                  <b-form-input
                      type="number"
                      v-model="preloadMaterialId"
                      :placeholder="$t('pool.Material-ID')"
                      ref="preloadMaterialIdInput"
                  />
                  <b-button variant="primary"
                            @click="_selectMaterialId(preloadMaterialId)"
                            v-if="!preloadDropdown.materialIdIsLoading">
                    {{ $t('pool.Ok') }}
                  </b-button>
                  <b-button v-if="preloadDropdown.materialIdIsLoading"
                            variant="outline-secondary">
                    <materialpool-spinner/>
                  </b-button>
                </b-input-group>
              </b-dropdown-form>
            </b-dropdown>
          </b-button-group>
        </div>
      </div>
    </template>

    <template #modal-footer>

      <slot name="all-buttons">
        <slot name="extra-buttons"></slot>
        <button type="button" class="btn btn-secondary btn-sm" @click="hide" :disabled="buttonsDisabled">
          {{ $t('pool.Cancel') }}
        </button>
        <button type="button" class="btn btn-secondary btn-sm" @click="_onReset" :disabled="buttonsDisabled">
          {{ $t('pool.Reset') }}
        </button>
        <button type="button" class="btn btn-success btn-sm" @click="_onSubmit" :disabled="buttonsDisabled">
          {{ $t('pool.Save') }}
        </button>
      </slot>

    </template>

    <b-form
        @submit.prevent="onSubmit"
        @reset="_onReset">
      <b-form-group horizontal
                    breakpoint="md"
                    :label="$t('pool.Title')"
                    label-for="materialtitle"
                    :label-cols="labelCols"
      >
        <b-form-input id="materialtitle"
                      type="text"
                      v-model.lazy="form.title"
                      required
                      :placeholder="$t('pool.Material-title')"
                      :disabled="formDisabled"
                      ref="titleInput"/>
      </b-form-group>


      <b-form-group horizontal
                    breakpoint="md"
                    :label="$t('pool.Description')"
                    label-for="materialdescription"
                    :label-cols="labelCols"
      >
        <b-form-input id="materialdescription"
                      type="text"
                      v-model.lazy="form.description"
                      required
                      :placeholder="$t('pool.Add-description-here')"
                      :disabled="formDisabled"/>
      </b-form-group>


      <b-form-group horizontal
                    breakpoint="md"
                    :label="$t('pool.Author')"
                    label-for="materialauthor"
                    :label-cols="labelCols"
      >

        <keyword-toggle-text-select
            :keyword="form.author"
            @newKeywordSelection="form.author = $event"
            :disabled="formDisabled"
            :emptyPlaceholder="$t('pool.Name-of-material-author')"/>

      </b-form-group>


      <b-form-group horizontal
                    breakpoint="md"
                    :label="$t('pool.Rating')"
                    :label-cols="labelCols"
      >
        <star-rating
            :increment="1"
            :max-rating="20"
            inactive-color="lightgray"
            active-color="black"
            :star-size="15"
            :inline="true"
            text-class="starRatingText"
            v-model:rating="form.rating"
            :read-only="formDisabled"/>
      </b-form-group>


      <div class="row">
        <div class="col-6">
          <keyword-input
              :keywords="keywordInput"
              @updated="keywordInput = $event"
              :disabled="formDisabled"/>
        </div>
        <div class="col-6">
          <bibleverse-input
              :bibleverses="bibleverseInput"
              @updated="bibleverseInput = $event"
              :disabled="formDisabled"
              :external-suggestions="externalBibleverseSuggestions"/>
        </div>
      </div>


    </b-form>


    <b-alert v-for="(alert, index) in formErrors"
             :variant="'danger'"
             class="mt-1 mb-1"
             :show="3"
             fade
             dismissible
             @dismissed="formErrors.splice(index,1)"
             :key="alert">{{ alert }}
    </b-alert>

  </b-modal>
</template>

<script>

import {
  BAlert,
  BButton,
  BButtonGroup,
  BDropdown,
  BDropdownDivider,
  BDropdownForm,
  BDropdownGroup,
  BDropdownItemButton,
  BForm,
  BFormGroup,
  BFormInput,
  BInputGroup,
  BModal
}                              from '@/adapters/bootstrap';
import starRating              from '@/adapters/star-rating';
import KeywordInput            from "../../keyword/keywordInput.vue";
import BibleverseInput         from "../../bibleverse/bibleverseInput";
import _debounce               from 'lodash/debounce';
import KeywordToggleTextSelect from "../../keyword/keywordToggleTextSelect";
import {RELEVANCE_USER_AVG}    from "../../../apps/config";
import {savingDialogs}         from "../../../helper/flashMessages";
import MaterialpoolSpinner     from "../../spinner/materialpool-spinner";
import {useRecentMaterialsStore} from '../../../apps/main/stores/recentMaterials';
import {useKeywordsStore}        from '../../../apps/main/stores/keywords';
import {useMaterialsStore}       from '../../../apps/main/stores/materials';

// Icons
import trashIcon   from '@icons/vendor/svg-icon/svg/oct/trashcan.svg';
import repeatIcon  from '@icons/vendor/svg-icon/svg/typcn/arrow-repeat.svg';
import {cloneDeep} from "lodash";


const USER_SETTINGS_MATERIAL_TEMPLATE_ID         = 'assign.material.templates';
const USER_SETTINGS_MATERIAL_DEFAULT_TEMPLATE_ID = 'assign.material.defaulttemplate';

export default {
  name: "materialCreator",

  mixins: [savingDialogs],

  data() {
    return {
      form: {
        title: '',
        description: '',
        rating: null,
        from_bot: false,
        author: null,
      },

      keywordInput: [],
      bibleverseInput: [],

      labelCols: 2,

      preloadMaterialId: '',

      reject: null,
      resolve: null,

      formErrors: [],
      materialCreationRunning: false,

      presets: {},
      defaultPresetId: null,

      preloadDropdown: {
        loadFromMaterialFormActive: false,
        materialIdIsLoading: false,

        createNewTemplateFormActive: false,
        templateNameInput: '',
        presetIsSaving: false,
      }
    };
  },

  props: {
    headline: {
      type: String,
      false: true,
      default: ''
    },

    externalBibleverseSuggestions: {
      type: Array,
      required: false,
      default() {
        return [];
      }
    }

  },

  computed: {
    formDisabled() {
      return this.materialCreationRunning;
    },

    buttonsDisabled() {
      return this.materialCreationRunning;
    },

    formData() {

      const data = Object.assign({}, this.form);

      if (data.author) {
        data.author = {
          id: data.author.id,
          title: data.author.title,
          type: data.author.type
        };
      }

      data.keywords = this.keywordInput.map((kw) => {
        let response = {
          type: kw.type,
          title: kw.title,
          id: kw.id,
        };

        if (kw.pivot && kw.pivot.relevance) {
          response.relevance = kw.pivot.relevance;
        }

        return response;
      });

      data.bibleverses = this.bibleverseInput.map((bv) => {
        let response = {
          from: bv.from,
          to: bv.to,
          id: bv.id,
        };

        if (bv.pivot && bv.pivot.relevance) {
          response.relevance = bv.pivot.relevance;
        }

        return response;
      });

      return data;

    },

  },

  watch: {
    authorSearch: _debounce(function (searchValue) {
      this._getAuthorSuggestion(searchValue)
    }, 300)
  },

  created() {
    this._onReset();
  },

  methods: {

    showPromise() {

      this._onReset();

      return new Promise((resolve, reject) => {
        this.resolve = resolve;
        this.reject  = reject;

        this.$refs.myModal.show();
      });


    },

    _cancelPromise() {

      if (typeof this.reject === 'function') {
        this.reject('closed early');
        // this.$refs.myModal.close();
        this.resolve = null;
        this.reject  = null;
      }

    },

    createAndReturnMaterial() {

      if (typeof this.resolve === 'function') {

        this.materialCreationRunning = true;
        const flashSave              = this.flashStartSaving(this.$t('pool.Material'));

        useMaterialsStore().create(this.formData)
            .then((material) => {

              this.flashSaved(this.$t('pool.Material'), flashSave);

              this.resolve(material);
              this.resolve = null; // already done during hide()
              this.reject  = null; // already done during hide()

              this.$refs.myModal.hide();

              useRecentMaterialsStore().addRecentMaterialId(material.id);
            })
            .catch((message) => {
              this.flashError(this.$t('pool.Material'), message, flashSave);
            })
            .then(() => {
              // Always
              this.materialCreationRunning = false;
            });
      }
    },

    hide() {
      this.$refs.myModal.hide();
    },

    _onSubmit() {

      this.checkRequirements();

      if (this.formErrors.length === 0) {
        this.createAndReturnMaterial();
      }

    },

    checkRequirements() {

      this.formErrors = [];

      // Check title
      if (!this.form.title || this.form.title.trim().length < 3) {
        this.formErrors.push(this.$tc('pool.min-length', this.form.title.trim().length, {
          count: this.form.title.trim().length,
          required: 3,
          field: this.$t('pool.Title')
        }));
      }

      if (!this.form.rating) {
        this.formErrors.push(this.$tc('pool.rating-missing'));
      }

      if (this.keywordInput.length < 1) {
        this.formErrors.push(this.$tc('pool.min-3-keywords'));
      }

    },

    _onReset() {

      this.formErrors = [];

      this.form.title       = '';
      this.form.description = '';
      this.form.rating      = null;
      this.form.from_bot    = false;
      this.form.author      = null;
      this.keywordInput     = [];
      this.bibleverseInput  = [];

      // Lade Vorlagen gemeinsam, damit die Standardvorlage nie gegen einen
      // noch ausstehenden asynchronen Preset-Wert geprüft wird.
      return this._refreshPresetSettings().then(() => {
        if (this.defaultPresetId !== null && this.presets[this.defaultPresetId]) {
          this._loadPreset(this.defaultPresetId);
        }
      });

    },

    _refreshPresetSettings() {
      return Promise.all([
        this.$store.dispatch('general/currentUserSetting', {
          settingId: USER_SETTINGS_MATERIAL_TEMPLATE_ID,
          defaultValue: {}
        }),
        this.$store.dispatch('general/currentUserSetting', {
          settingId: USER_SETTINGS_MATERIAL_DEFAULT_TEMPLATE_ID,
          defaultValue: null
        })
      ]).then(([presets, defaultPresetId]) => {
        this.presets = presets || {};
        this.defaultPresetId = defaultPresetId ?? null;
      });

    },

    _refreshPresets() {
      return this.$store.dispatch('general/currentUserSetting', {
        settingId: USER_SETTINGS_MATERIAL_TEMPLATE_ID,
        defaultValue: {}
      }).then((presets) => {
        this.presets = presets || {};
        return this.presets;
      });

    },

    /**
     *
     * @param templateID {string|null}
     * @private
     */
    _saveDefaultPresetId(templateID) {

      const flashMessage = this.flashStartSaving(this.$t('pool.Default-Preset'));

      this.$store.dispatch('general/storeCurrentUserSetting', {
        settingId: USER_SETTINGS_MATERIAL_DEFAULT_TEMPLATE_ID,
        data: templateID
      }).then(() => {
        this.flashSaved(this.$t('pool.Default-Preset'), flashMessage)
        this.defaultPresetId = templateID;
      }).catch((msg) => {
        this.flashError(this.$t('pool.Default-Preset'), msg, flashMessage);
      });

      this._loadPreset(templateID);

    },

    _selectFocus() {

      const el = this.$refs.titleInput.$el;

      el.focus();

      // Move cursor to selection end
      // see: https://css-tricks.com/snippets/javascript/move-cursor-to-end-of-input/
      if (typeof el.selectionStart == "number") {
        el.selectionStart = el.selectionEnd = el.value.length;
      } else if (typeof el.createTextRange != "undefined") {
        const range = el.createTextRange();
        range.collapse(false);
        range.select();
      }

    },

    _btnCreatePreset() {

      this.preloadDropdown.loadFromMaterialFormActive  = false;
      this.preloadDropdown.createNewTemplateFormActive = true;

      this.$nextTick(() => {
        this.$nextTick(() => {
          this.$refs.templateNameInput.$el.focus();
        });
      })

    },


    _btnLoadMaterialId() {

      this.preloadDropdown.loadFromMaterialFormActive  = true;
      this.preloadDropdown.createNewTemplateFormActive = false;

      this.$nextTick(() => {
        this.$nextTick(() => {
          this.$refs.preloadMaterialIdInput.$el.focus();
        });
      })

    },

    _saveNewPreset() {

      const id                            = this._createTemplateId(this.preloadDropdown.templateNameInput);
      const flashSave                     = this.flashStartSaving(this.$t('pool.template'));
      this.preloadDropdown.presetIsSaving = true;

      this.$store.dispatch('general/storeCurrentUserSetting', {
        settingId: id,
        data: cloneDeep(this.formData) // JSON.parse(JSON.stringify(this.formData))
      })
          .then(() => {

            this.flashSaved(this.$t('pool.template'), flashSave);

            return this._refreshPresets().then(() => {
              this.preloadDropdown.presetIsSaving = false;

              // Close Dropdown on success
              this._hideTemplateDropdown();
            });

          })
          .catch((message) => {
            this.preloadDropdown.presetIsSaving = false;
            this.flashError(this.$t('pool.template'), message, flashSave);
          });

    },

    _deletePreset(templateId) {

      const settingId = this._createTemplateId(templateId);
      const flashSave = this.flashStartRemoving(this.$t('pool.template'));

      this.$store.dispatch('general/removeCurrentUserSetting', settingId)
          .then(() => {
            this.flashRemoved(this.$t('pool.template'), flashSave);
          })
          .catch((message) => {
            this.flashError(this.$t('pool.template'), message, flashSave);
          })
          .then(() => {
            // Always
            return this._refreshPresets();
          });

    },

    _loadPreset(templateId) {

      const data = this.presets[templateId] || {};

      this._initMaterialFormWithTemplateData(data);

    },

    _selectMaterialId(materialId) {

      this.preloadDropdown.materialIdIsLoading = true;

      useMaterialsStore().getMaterialById(materialId)
          .then((material) => {

            this._initMaterialFormWithTemplateData(material);

            // Close Dropdown on success
            this._hideTemplateDropdown();
          })
          .catch((message) => {
            this.flashActionFailed(message);
          })
          .then(() => {
            // Always
            this.preloadDropdown.materialIdIsLoading = false;
          });

    },

    _createTemplateId(titleOrTempateId) {

      let id = titleOrTempateId || 'no_title_default';
      id     = id.trim();
      id     = id.replace(/[+#,./\\!"§$%&\(\)=\?-]+/g, '_');
      id     = USER_SETTINGS_MATERIAL_TEMPLATE_ID + '.' + id;

      return id;
    },

    _hideTemplateDropdown() {
      const dropdown = this.$refs.templateDropdown;
      dropdown.hide();
      setTimeout(() => dropdown.focus(), 0);
    },

    _initMaterialFormWithTemplateData(materialTemplate) {

      this.form.title       = materialTemplate.title || '';
      this.form.description = materialTemplate.description || '';
      this.form.rating      = materialTemplate.rating || 10;

      if (materialTemplate.author) {
        this.form.author = materialTemplate.author;

        if (materialTemplate.author.id) {
          useKeywordsStore().get(materialTemplate.author.id)
              .then((keyword) => {
                this.form.author = keyword;
              })
              .catch((message) => {
                this.flashActionFailed(message);
                this.form.author = null;
              })
        }
      } else {
        this.form.author = null;
      }

      // const keywordInput    = materialTemplate.keywords ? JSON.parse(JSON.stringify(materialTemplate.keywords)) : [];
      const keywordInput    = materialTemplate.keywords ? cloneDeep(materialTemplate.keywords) : [];
      // const bibleverseInput = materialTemplate.bibleverses ? JSON.parse(JSON.stringify(materialTemplate.bibleverses)) : [];
      const bibleverseInput = materialTemplate.bibleverses ? cloneDeep(materialTemplate.bibleverses) : [];

      function mapRelevance(el) {
        if (el.relevance) {
          el.pivot = {
            relevance: el.relevance
          };
          delete el.relevance;
        } else {
          el.pivot = {
            relevance: RELEVANCE_USER_AVG
          }
        }

        return el;
      }

      this.keywordInput    = keywordInput.map(mapRelevance);
      this.bibleverseInput = bibleverseInput.map(mapRelevance);

    },

  },

  components: {
    MaterialpoolSpinner,
    KeywordToggleTextSelect,
    BibleverseInput,
    KeywordInput,
    BForm,
    BAlert,
    BDropdown, BDropdownItemButton, BDropdownDivider, BDropdownGroup, BDropdownForm,
    BFormGroup, BInputGroup,
    BFormInput,
    BModal,
    BButton, BButtonGroup,
    starRating,
    trashIcon, repeatIcon,
  }


}
</script>

<style lang="scss">
@import "resources/sass/theme";

.sb_materialcreator_header {

  width: 100%;

  .sb_dropdown_icons_wrapper {
    float: right;

    .sb_dropdown-icon {
      width: 1em;
      height: 1em;

      &.selected {
        fill: $success;

        &:hover {
          fill: $danger;
        }
      }
    }
  }


}

</style>
