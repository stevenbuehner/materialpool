<template>
    <b-modal ref="imageZoomModal"
             centered
             hide-footer
             lazy
             :title="title"
             :hide-header-close="true"
             size="lg"
             class="zoomImageModal">
        <b-image :src="image"
                 fluid
                 @click="_hideZoom"></b-image>
        <span class="previous"
              @click.prevent="btnPrevious"
              v-show="hasPrevious"><</span>
        <span class="next"
              @click.prevent="btnNext"
              v-show="hasNext >= 0">></span>
    </b-modal>
</template>

<script>
    import bModal from 'bootstrap-vue/src/components/modal/modal';
    import bImage from 'bootstrap-vue/src/components/image/img'

    export default {
        name: "imageZoom",

        props: {
            data: {
                type: Array,
                required: true
            },

            endless: {
                type: Boolean,
                required: false,
                default: true
            },

            start: {
                type: Number,
                required: false,
                default: 0
            }
        },

        data() {
            return {
                currentIndex: Math.min(this.start, this.data.length),
            };
        },

        computed: {

            image() {
                return this.data[this.currentIndex].src;
            },

            title() {
                return this.data[this.currentIndex].title || '';
            },

            hasPrevious() {
                return (this.currentIndex > 0 || this.endless && this.data.length > 1);
            },

            hasNext() {
                return (this.currentIndex < this.data.length || this.endless && this.currentIndex === 0);
            }

        },

        methods: {

            btnNext() {

                const nextIndex = this.currentIndex < (this.data.length - 1) ? this.currentIndex + 1 : 0;
                this._showZoom(nextIndex);

            },

            btnPrevious() {

                let prevIndex = this.currentIndex;

                if (this.currentIndex > 0) {
                    prevIndex = this.currentIndex - 1;
                } else if (this.currentIndex === 0 && this.endless) {
                    prevIndex = this.data.length - 1;
                }

                this._showZoom(prevIndex);

            },

            _showZoom(arrayIndex) {

                this.currentIndex = arrayIndex;
                this.$refs.imageZoomModal.show();

            },

            _hideZoom() {
                this.$refs.imageZoomModal.hide();
            },

            show() {
                this._showZoom(this.start);
            },

            hide() {
                this._hideZoom();
            }

        },

        components: {
            bModal,
            bImage,
        }
    }
</script>

<style type="scss">

    .zoomImageModal {

        .previous, .next {
            position: absolute;
            color: black;
            font-size: 2em;
            height: 100%;
            top: 0;
            cursor: pointer;
            display: flex;
            justify-content: center;
            flex-direction: column;
        }

        .previous {
            left: 0;
            padding: 0 1em 0 .75em;
        }

        .next {
            right: 0;
            padding: 0 .75em 0 1em;
        }

        .previous:hover {
            background-image: linear-gradient(to right, rgb(184, 184, 184), rgba(210, 210, 210, 0.05));
        }

        .next:hover {
            background-image: linear-gradient(to left, rgb(184, 184, 184), rgba(210, 210, 210, 0.05));
        }

    }

</style>