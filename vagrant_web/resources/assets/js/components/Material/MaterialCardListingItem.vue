<template>
    <div class="card material"
         @click.prevent="goToMaterial"
         @dblclick.prevent="goToMaterial">
        <div class="head">
            <div class="title">{{material.title}}</div>
            <small class="meta-info">
                <span class="info" v-if="material.author">von {{material.author.title}}</span>
                <span class="ressourcen" v-if="material.resources !== undefined">
                    ({{$tc('pool.resource-count', material.resources.length, {COUNT : material.resources.length}) }})
                </span>
            </small>
        </div>
        <div class="tags">
            <keyword v-for="keyword in material.keywords"
                     :key="'k' + keyword.id"
                     :keyword="keyword"
                     :materialId="material.id"
                     :editable="false"
                     size="mini"></keyword>
            <biblevers
                    v-for="bibleverse in material.bibleverses"
                    :key="'b' + bibleverse.id"
                    :bibleverse="bibleverse"
                    :materialId="material.id"
                    :editable="false"
                    size="mini"></biblevers>
        </div>
        <small class="description">{{material.description}}</small>

        <b-modal ref="materialDetail" title="Material Detail">
            <material-detail :material="material" :editable="false"></material-detail>
        </b-modal>
    </div>
</template>

<script>
    import Keyword from './../keyword/keyword.vue'
    import Biblevers from "../bibleverse/biblevers.vue";
    import materialDetail from './MaterialDetail.vue';
    import bModal from 'bootstrap-vue/es/components/modal/modal';
    import {api_v1_materials_update} from './../serverRoutes';


    export default {
        mounted() {
        },
        props: ['id'],

        computed: {
            materialDetailLink() {
                return api_v1_materials_update(this.material.id);
            }
        },

        methods: {
            openMaterialModal() {
                this.$refs.materialDetail.show();
            },

            goToMaterial() {
                this.$router.push({
                    name: 'material-details',
                    props: {id: this.material.id}
                })
//                window.location.href = materialShowRoute(this.material.id);
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

    .meta-info {
        color: gray;
    }

    .tags {
        display: inline-block;
    }

</style>
