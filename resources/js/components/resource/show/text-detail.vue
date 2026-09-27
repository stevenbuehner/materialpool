<template>
  <div class="container-fluid pt-3 pb-2">
    <div class="row" v-if="!editModeEnabled" @dblclick="editModeEnabled=!editDisabled">
      <div class="col-12">
        <markdown :text="myTextContent"/>
        <button class="btn btn-sm btn-primary" @click.stop="editModeEnabled=true"
                v-if="almostNoContentToEditVisible">
          {{ $t('pool.Edit') }}
        </button>
      </div>
    </div>
    <div class="row" v-else>
      <div class="col-6 p-0 liveEditorWrapper" :class="{savingNeccessary}">
            <textarea
                class="liveEditor p-3"
                v-model="myTextContent"
                @keydown.meta.enter.exact="btnSave"
                @keyup.esc.exact="btnCancelIfNothingChanged"/>
      </div>
      <div class="col-6 p-0 livePreviewWrapper">
        <markdown class="p-3" :text="myTextContent"/>
      </div>
      <div class="col-12 p-3">
        <button
            class="btn btn-sm btn-success float-end m-1"
            @click="btnSave"
            v-show="savingNeccessary"
            title="CMD + ENTER"
        >{{ $t('pool.Save') }}
        </button>
        <button
            class="btn btn-sm btn-danger float-end m-1"
            @click="btnCancel"
            title="ESC"
        >{{ $t('pool.Cancel') }}
        </button>
      </div>
    </div>
  </div>

</template>

<script>

import Markdown            from "../../markdown/compiledMarkdown";
import {savingDialogs}     from "../../../helper/flashMessages";
import {BibleVerseService} from "../../../helper/BibleverseHelper";
import {useResourcesStore} from '../../../apps/main/stores/resources';

const regexp     = BibleVerseService.biblePattern;
window.bibletest = regexp;

export default {
  name: 'TextDetail',

  mixins: [savingDialogs],

  props: {
    resource: {
      type: Object,
      required: true,
    },
    editDisabled: {
      type: Boolean,
      required: false,
      default: false
    }
  },

  data() {
    return {
      editModeEnabled: false,
      isSaving: false,
      myTextContent: this.resource?.content || '',
    };
  },

  computed: {
    almostNoContentToEditVisible() {
      return this.myTextContent.length <= 5;
    },

    savingNeccessary() {
      return this.myTextContent !== this.resource.content;
    },
  },

  methods: {
    btnCancel() {
      this.editModeEnabled = false;
      this.myTextContent   = this.resource.content;
      this.flashInfo(this.$t('pool.Undo-changes'));
    },

    btnCancelIfNothingChanged() {
      if (!this.savingNeccessary) {
        this.btnCancel();
      }
    },

    btnSave() {

      this.editModeEnabled = false;
      this.isSaving        = true;

      const startFlash = this.flashActionStartedWaiting(this.$t('pool.Saving-content-changes'));

      useResourcesStore().update({
        id: this.resource.id,
        data: {
          content: this.myTextContent
        }
      }).then((resource) => {
        this.$emit('resource-updated', resource);
        this.flashActionSuccessfullyFinished(this.$t('pool.Content-saved'), startFlash);
      }).catch((msg) => {
        this.flashActionFailed(this.$t('pool.Content-not-saved') + ' - ' + msg, startFlash);
      }).then(() => {
        this.isSaving = false;
      });
    }
  },

  components: {
    Markdown,
  }

}
</script>

<style lang="scss">

@use "../../../../sass/theme" as *;

.liveEditor, .livePreview {
  display: inline-block;
  vertical-align: top;
  left: 0;
  height: 100%;
  width: 100%;
  overflow-y: scroll;
  // overflow-x: hidden;
}

.liveEditorWrapper {
  background-color: #f6f6f6;
  border: 1px solid #f6f6f6;
  border-right: 1px solid #ccc;
  position: relative;

  .liveEditor {
    border: none;
    resize: none;
    outline: none;
    font-size: 14px;
    font-family: 'Monaco', courier, monospace;
    padding: 0;
    background-color: transparent;
  }

  &.savingNeccessary {
    border-color: $red;
  }

}

.livePreviewWrapper {
  height: 80vh;
  overflow-y: scroll;
}


</style>
