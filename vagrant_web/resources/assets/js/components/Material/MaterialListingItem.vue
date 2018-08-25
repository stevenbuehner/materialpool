<template>
    <div class="material"
         v-if="material"
         @click.prevent="goToMaterial">
        <div class="head">
            <div class="title">{{material.title}}</div>
            <small class="meta-info">
                <div class="info" v-if="material.author">von {{material.author.title}} |</div>
                <div class="info" v-if="material.author">letztes Update am {{material.updated_at}}</div>
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

    </div>
</template>

<script>
    import Keyword from './../keyword/keyword.vue'
    import Biblevers from "../bibleverse/biblevers.vue";
    import materialDetail from './MaterialDetail.vue';
    import {api_v1_materials_update} from './../serverRoutes';


    export default {
        created() {
            this.getMaterial();
        },

        props: ['id'],

        data() {
            return {
                material: null,
            }
        },

        computed: {
            materialDetailLink() {
                return api_v1_materials_update(this.id);
            },

        },

        watch: {
            id(newValue) {
                this.material = null;
                this.getMaterial();
            }
        },

        methods: {
            getMaterial() {
                this.$store.dispatch('materials/getMaterial', this.id).then((material) => {
                    this.material = material;
                })
            },

            goToMaterial() {
                this.$router.push(
                    {
                        name: 'material-detail',
                        params: {id: this.material.id}
                    }
                );
            }
        },


        components: {
            Biblevers,
            Keyword,
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
