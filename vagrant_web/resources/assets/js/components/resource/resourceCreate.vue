<template>
    <div class="container">
        <h1>{{$t('pool.create-new-resource')}}</h1>
        <resource-uploader
                @resource-created="resourceCreated"
                :styleObject="{minHeight: '50vh'}">
            {{$t('pool.drop-file-to-upload-resource')}}
        </resource-uploader>
    </div>
</template>

<script>

    import ResourceUploader from "../uploader/resourceUploader";

    export default {
        name: "resourceUpload",
        methods: {
            resourceCreated(resource) {

                this.$router.push({
                    name: 'resource-detail',
                    params: {
                        id: resource.id
                    }
                });
            }

        },
        components: {ResourceUploader}
    }
</script>

<style scoped>

</style>