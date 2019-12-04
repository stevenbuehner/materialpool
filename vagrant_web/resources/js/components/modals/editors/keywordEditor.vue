<template>
    <b-modal
            :title="$t('pool.Edit-Keyword')"
            :hide-footer="true"
            v-model="showEditor"
            v-if="showEditor"
    >
        <keyword-edit
                :id="id"
                @saved="onSaved"
                @saving="$emit('saving', $event)"
                @savingError="$emit('savingError', $event)"
                @deleted="$emit('deleted', $event)">

            <template slot="additional-buttons">
                <b-button variant="secondary"
                          @click.prevent="hide">{{$t('pool.cancel')}}
                </b-button>
            </template>

        </keyword-edit>

    </b-modal>
</template>

<script>
    import {BModal} from 'bootstrap-vue'
    import KeywordEdit from "../../keyword/keywordEdit";
    import {BButton} from 'bootstrap-vue';


    export default {
        name: "keywordEditor",

        props: {
            id: {
                type: Number,
                required: true
            },
        },

        data() {
            return {
                showEditor: false
            }
        },

        computed: {},

        methods: {
            onSaved(e) {
                this.hide();
                this.$emit('saved', e);
            },

            show() {
                this.showEditor = true;
            },
            hide() {
                this.showEditor = false;
            }
        },


        components: {
            KeywordEdit,
            BModal,
            BButton
        }
    }
</script>

<style scoped>

</style>