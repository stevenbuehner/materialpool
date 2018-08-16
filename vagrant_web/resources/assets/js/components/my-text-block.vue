<template>
    <component :is="component" v-html="myText"></component>
</template>

<script>
    import striptags from 'striptags';

    export default {

        props: {
            text: {
                required: true,
                type: String
            },

            component: {
                required: false,
                type: String,
                default: 'div'
            },

            htmlExceptions: {
                required: false,
                type: Array,
                default() {
                    return [];
                }
            }
        },

        computed: {
            myText() {
                return this.renderMyStuff(this.striphtmltags(this.text));
            }

        },

        methods: {
            striphtmltags(text) {
                return striptags(text, this.htmlExceptions);
            },

            renderMyStuff(text) {

                let rendered = this.renderLists(text);
                rendered     = this.renderNewLines(rendered);

                return rendered;

            },

            renderLists(text) {

                let lines      = text.split("\n");
                let resultText = '';

                var lastIsList = false;
                var listIsOpen = false;

                for (let i in lines) {
                    lines[i].replace(/^\s*\-\s*(.*)\s*$/, function (match, p1) {


                        if (listIsOpen === false) {
                            resultText += '<ul>';
                            listIsOpen = true;
                        }

                        lastIsList = true;

                        resultText += '<li>' + p1 + '</li>';

                        return '';
                    });

                    if (listIsOpen === true && !lastIsList) {
                        resultText += '</ul>';
                        listIsOpen = false;
                    }

                    if(!listIsOpen){
                        resultText += '<p>' + lines[i] +'</p>';
                    }

                    lastIsList = false;
                }

                return resultText;

            },

            renderNewLines(text) {
                return text;
            }
        }

    }
</script>

<style scoped>

</style>