<template>
    <div class="container">
        <h1>{{$t('pool.create-new-resource')}}</h1>


        <div v-if="!materialCreationRunning">
            <resource-uploader
                    @resource-created="resourceCreated"
                    :styleObject="{minHeight: '50vh'}">
                {{$t('pool.drop-file-to-upload-resource')}}
            </resource-uploader>

            <div class="options">Zusatzoptionen:</div>
            <b-checkbox v-model="autocreateMaterial">{{$t('pool.auto-create-material')}}</b-checkbox>
        </div>

        <b-alert variant="info" :show="materialCreationRunning">{{$t('pool.material-is-beeing-generated')}}</b-alert>

    </div>
</template>

<script>

    import ResourceUploader from "../../../components/uploader/resourceUploader";
    import bCheckbox from 'bootstrap-vue/src/components/form-checkbox/form-checkbox';
    import bAlert from 'bootstrap-vue/src/components/alert/alert';

    export default {
        name: "resourceUpload",

        data() {
            return {
                autocreateMaterial: true,
                materialCreationRunning: false
            };
        },

        methods: {
            resourceCreated(resource) {

                if (this.autocreateMaterial === true) {

                    this.materialCreationRunning = true;

                    this.$store.dispatch('resources/autoCreateMaterial', resource.id)
                        .then(({material}) => {

                            this.$router.push({
                                name: 'material-detail',
                                params: {
                                    id: material.id
                                }
                            });

                        });

                } else {

                    this.$router.push({
                        name: 'resource-detail',
                        params: {
                            id: resource.id
                        }
                    });

                }

            }

        },
        components: {
            ResourceUploader,
            bCheckbox,
            bAlert
        }
    }
</script>

<style scoped>
    .options {
        font-weight: bold;
    }
</style>