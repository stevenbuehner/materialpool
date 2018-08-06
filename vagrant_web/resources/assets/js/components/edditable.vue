<template>
    <div v-if="!editNow" @dblclick.prevent="startEdditing" class="displayArea">
        <component :is="type" :class="classes">
            {{text}}
            <div v-if="text === ''" class="missingTextPlaceholder">{{placeholder}}</div>
        </component>
    </div>

    <div v-else class="editArea">
        <div class="input-group">
            <input type="text"
                   class="form-control"
                   :placeholder="placeholder"
                   :aria-placeholder="placeholder"
                   aria-describedby="basic-addon2"
                   v-model="editText"
                   @keyup.enter.esc.tab="saveEdit"
                   autofocus
                   ref="textInput">
            <div class="input-group-append">
                <button class="btn btn-outline-secondary" type="button" @click="saveEdit" :disabled="!enableSave">save
                </button>
                <button class="btn btn-outline-secondary" type="button" @click="cancedlEdit">cancel</button>
            </div>
        </div>
    </div>
</template>

<script>

    export default {
        name: "edditable",

        props: {
            type: {
                type: String,
                required: true,
                default: 'h1'
            },
            value: {
                type: String,
                required: true
            },
            classes: {
                type: String,
                required: false,
                default: ''
            },
            placeholder: {
                type: String,
                required: false,
                default: 'Insert text here'
            }
        },
        data() {
            return {
                editNow: false,
                text: '',
                editText: '',
            };
        },

        computed: {
            enableSave() {
                return this.text !== this.editText;
            }
        },

        methods: {
            startEdditing() {
                this.editText = this.text;
                this.editNow  = true;

                this.$nextTick(() => this.$refs.textInput.focus())
            },


            cancedlEdit() {
                this.editNow = false;
            },

            saveEdit() {
                this.editNow = false;

                if (this.text !== this.editText) {
                    this.text = this.editText;
                    this.$emit('value-changed', this.text);
                }
            },


        },

        created() {
            this.text     = this.value;
            this.editText = this.value;
        },

        components: {}
    }
</script>

<style scoped>
    .editArea, .displayArea {
        margin-bottom: .5em;
    }

    .missingTextPlaceholder {
        color: gray;
    }

</style>