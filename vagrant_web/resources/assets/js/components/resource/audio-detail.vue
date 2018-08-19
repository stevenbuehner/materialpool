<template>
    <div ref="myAudioContainer">
        <audio-visual-line
                :audio-src="resourceDownloadUrl"
                line-color="grey"
                :canv-width="audioCanvasWidth">
        </audio-visual-line>
    </div>
</template>

<script>
    import AudioVisualLine from 'vue-audio-visual/src/components/AvLine';
    import resourceLinks from './resource-links.mixin';

    export default {
        mixins: [resourceLinks],

        props: {
            resource: {
                required: true,
                type: Object
            }
        },

        computed: {

            audioCanvasWidth() {
                return 300;
            }

        },

        components: {
            AudioVisualLine
        }
    }
</script>

<style scoped>

</style>