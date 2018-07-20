<template>
    <div class="material" @click.prevent="goToMaterial">
        <div class="head">
            <div class="title">{{material.title}}</div>
            <small class="meta-info">
                <div class="info" v-if="material.author">von {{material.author.title}} |</div>
                <div class="info" v-if="material.author">letztes Update am {{material.updated_at}}</div>
            </small>
        </div>
        <div class="tags">
            <keyword v-for="keyword in material.keywords"
                     :key="keyword.id"
                     :keyword="keyword"
                     :materialId="material.id"
                     :editable="false"
                     size="mini"></keyword>
            <biblevers
                    v-for="bibleverse in material.bibleverses"
                    :key="bibleverse.id"
                    :bibleverse="bibleverse"
                    :materialId="material.id"
                    :editable="false"
                    size="mini"></biblevers>
        </div>
        <small class="description">{{material.description}}</small>

        <b-modal ref="materialDetail" title="Material Detail">
            <material-detail :material="material"></material-detail>
        </b-modal>
    </div>
</template>

<script>
    import Keyword from './../keyword/keyword.vue'
    import Biblevers from "../bibleverse/biblevers.vue";
    import materialDetail from './MaterialDetail.vue';
    import bModal from 'bootstrap-vue/es/components/modal/modal';


    export default {
        mounted() {
        },
        props: ['material'],

        computed: {
            materialDetailLink() {
                return '/pool/material/' + this.material.id;
            }
        },

        methods: {
            goToMaterial() {
                this.$refs.materialDetail.show();
            }
        },

        components: {
            Biblevers,
            Keyword,
            bModal,
            materialDetail
        }
    }
</script>

<style scoped>
    .material {
        border-bottom: 0.1rem solid gray;
        padding: 0.5rem;
        cursor: pointer;
    }

    .head {
        margin-bottom: 0.5rem;
    }

    .title {
        font-weight: bold;
        margin-bottom: -0.5rem;
    }

    .meta-info .info {
        display: inline-block;
        color: gray;
    }

    .tags {
        display: inline-block;
    }

</style>
