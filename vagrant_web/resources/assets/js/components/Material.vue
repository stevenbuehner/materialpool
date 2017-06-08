<template>
    <div class="material">
        <div class="head">
            <div class="title">{{material.title}}</div>
            <small class="meta-info">
                <div class="info" v-if="material.author">von {{material.author.title}} |</div>
                <div class="info" v-if="material.author">letztes Update am {{material.updated_at}}</div>
            </small>
        </div>
        <div class="tags">
            <div class="btn btn-sm btn-secondary keyword" v-for="keyword in material.keywords" :key="keyword.id">{{keyword.title}}</div>
            <div class="btn btn-sm btn-secondary bibleverse" v-for="bibleverse in material.bibleverses" :key="bibleverse.id">
                {{bibleverse.label}}
            </div>
        </div>
        <small class="description">{{material.description}}</small>
    </div>
</template>

<script>
    export default {
        mounted() {
            console.log('Material component mounted.')
        },
        props: ['material']
    }
</script>

<style lang="css">
    .material {
        border-bottom: 0.1rem solid gray;
        padding: 0.5rem;
    }

    .material .head {
        margin-bottom: 0.5rem;
    }

    .material .head .title {
        font-weight: bold;
        margin-bottom: -0.5rem;
    }

    .material .meta-info .info {
        display: inline-block;
        color: gray;
    }

    .material .bibleverse, .material .keyword {
        margin-right: 0.25rem;
        margin-bottom: 0.25rem;
    }
</style>
