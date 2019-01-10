<template>
    <div class="container">

        <h1>{{$t('pool.Create-new-Material-from-Textinput')}}</h1>

        <b-form-group
                :label="$t('pool.Metadata')"
                label-for="metaData"
                :description="$t('pool.metadata-exampes')"
                :state="metaInputValid"
                :invalid-feedback="invalidMetaFedback"
        >
            <b-form-textarea
                    id="metaData"
                    v-model="metaData"
                    :placeholder="$t('pool.Enter-metadata-here')"
                    :rows="3"
                    :disabled="!editingEnabled"
                    :state="metaInputValid"
            />
        </b-form-group>

        <div class="row">
            <div class="col-6">
                <b-form-group
                        :label="$t('pool.Textinformation')"
                        label-for="textInput"
                        :state="textInputValid"
                        :invalid-feedback="invalidTextFedback"
                >
                    <b-form-textarea
                            id="textInput"
                            v-model="textInput"
                            :placeholder="$t('pool.Enter-text')"
                            :rows="10"
                            :disabled="!editingEnabled"
                            :state="textInputValid"
                    />
                </b-form-group>
            </div>
            <div class="col-6">
                <label></label>
                <div class="markup" v-html="compiledMarkdown"/>
            </div>


            <button class="btn btn-success ml-3"
                    @click="btnCreate"
                    v-show="textInputValid"
                    :disabled="!editingEnabled"
            >
                {{$t('pool.Create-entry')}}
            </button>

        </div>

        <custom-dialog ref="myDialog"/>


    </div>
</template>

<script>

    import bFormGroup from 'bootstrap-vue/src/components/form-group/form-group';
    import bFormInput from 'bootstrap-vue/src/components/form-input/form-input';
    import bFormTextarea from 'bootstrap-vue/src/components/form-textarea/form-textarea';
    import marked from 'marked';
    import CustomDialog from "../../../components/modals/dialogs/customDialog";

    export default {
        name: "ResourceTextCreateWithMaterial",

        data() {
            return {
                metaData: '',
                textInput: '',

                editingEnabled: true,
            };
        },


        computed: {


            metaInputValid() {

                if (this.metaData.length > 0) {
                    // Mindestens ein Komma oder Semikolon muss dabei sein, damit die Tags richtig erkannt werden
                    return this.metaData.indexOf(',') !== -1 || this.metaData.indexOf(';') !== -1;
                } else {
                    return true;
                }

            },

            invalidMetaFedback() {
                return this.$t('pool.At-least-one-comma-or-semikolon-required');
            },

            textInputValid() {
                return this.textInput.length >= 10;
            },

            invalidTextFedback() {
                return this.$t('pool.Please-enter-more-text');
            },

            compiledMarkdown() {
                return marked(this.textInput, {sanitize: true, gfm: false, smartLists: true, smartypants: true})
            },
        },

        created() {
        },

        methods: {
            btnCreate() {
                this.editingEnabled = false;

                this.$refs.myDialog.show({
                    title: this.$t('pool.Creating-Resource'),
                    content: this.$t('pool.Please-wait'),
                    yesEnabled: false,
                    noEnabled: false,
                    allowBackdrop: false
                }).catch(() => {
                });

                this.$store.dispatch('resources/createTextResource', {
                    text: this.textInput
                })
                    .then((resource) => {
                        console.debug(resource);
                        this.createMaterialFromResource(resource);
                    })
                    .catch(() => {
                        this.$refs.myDialog.show({
                            title: this.$t('pool.Error'),
                            content: this.$t('pool.Error-while-creating-resource'),
                            yesText: this.$t('pool.Ok'),
                            yesEnabled: true,
                            noEnabled: false,
                            allowBackdrop: true
                        }).catch(() => {
                        });
                    })
                    .then(() => {
                        // Always
                        this.editingEnabled = true;
                    })
            },

            createMaterialFromResource(resource) {

                this.$refs.myDialog.show({
                    title: this.$t('pool.Creating-Material-from-Resource'),
                    content: this.$t('pool.Please-wait'),
                    yesEnabled: false,
                    noEnabled: false,
                    allowBackdrop: false
                }).catch(() => {
                });

                this.$store.dispatch('resources/autoCreateMaterial', {
                    resourceIds: [resource.id],
                    meta: this.metaData,
                    from_bot: false
                })
                    .then((material) => {

                        this.$router.push({
                            name: 'material-detail',
                            params: {
                                id: material.id
                            }
                        });

                    })
                    .catch(() => {
                        this.$refs.myDialog.show({
                            title: this.$t('pool.Error'),
                            content: this.$t('pool.Error-while-creating-material'),
                            yesText: this.$t('pool.Ok'),
                            yesEnabled: true,
                            noEnabled: false,
                            allowBackdrop: true
                        }).catch(() => {
                        });
                    });

            }


        },

        components: {
            CustomDialog,
            bFormGroup,
            bFormTextarea,
            bFormInput

        }


    }
</script>

<style scoped>

</style>