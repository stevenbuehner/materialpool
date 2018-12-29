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
    import bModal from 'bootstrap-vue/src/components/modal/modal'
    import KeywordEdit from "../../keyword/keywordEdit";
    import bButton from 'bootstrap-vue/src/components/button/button';


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
            bModal,
            bButton
        }
    }
</script>

<style scoped>

</style>