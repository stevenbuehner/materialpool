<template>
    <vue-select class="searchInputSelect"
                :multiple="true"
                :selectOnTab="true"
                :options="options"
                :placeholder="$t('pool.Insert-search-phrase-here')"
                :filterable="false"
                :value="lineValues"
                language="de-DE"
                label="text"
                @input="$emit('updated', $event)"
                @search="onSearch"
    >

        <template v-slot:no-options>
            {{$t('pool.Insert-search-phrase')}}
        </template>

        <template v-slot:option="option">
            <div class="d-center">
                <span class="icon" :style="{backgroundImage: 'url('+ option.icon+')'}"></span>
                {{ option.text }}
                <span class="descendants" v-if="option.descendants && option.descendants > 0">({{$tc('pool.XY-subtopics', option.descendants, {XY:option.descendants})}})</span>
            </div>
        </template>

        <template v-slot:selected-option-container="{option, disabled, multiple, deselect}">
            <div class="vs__selected d-center">
                <span class="icon" :style="{backgroundImage: 'url('+ option.icon+')'}"></span>
                {{ option.text }}

                <button v-if="!disabled" @click="deselect(option)"
                        type="button"
                        class="vs__deselect"
                        aria-label="Remove option">

                    <span v-if="showDescendants && option.descendants && option.descendants.length > 0"
                          class="descendants">({{option.descendants.map(kw => kw.title).join(', ')}})</span>

                    <span aria-hidden="true"><slot name="label">&times;</slot></span>
                </button>
            </div>
        </template>

    </vue-select>
</template>

<script>
	import vueSelect from 'vue-select';
	import _debounce from 'lodash/debounce';

	export default {

		props: {
			lineValues: {
				type: Array,
				required: true,
			},

			showDescendants: {
				type: Boolean,
				required: false,
				default: true
			}
		},

		data() {
			return {
				options: [],
			};
		},


		model: {
			prop: 'lineValues',
			event: 'updated'
		},


		watch: {},


		methods: {
			onSearch(search, loading) {
				loading(true);

				this.search(loading, search, this);
			},

			// _.debounce is a function provided by lodash to limit how
			// often a particularly expensive operation can be run.
			// To learn
			// more about the _.debounce function (and its cousin
			// _.throttle), visit: https://lodash.com/docs#debounce
			search: _debounce((loading, search, vm) => {

				vm.$store.dispatch('tagsearch/searchTags', search)
				  .then((data) => {
					  vm.options = data.data;
					  loading(false);
				  });

			}, 250),

		},

		created() {
		},

		components: {
			vueSelect
		},
	}
</script>


<style lang="scss">
    @import "~vue-select/dist/vue-select.css";

    .searchInputSelect {

        .vs__selected-options {
            .selected .close {
                margin-left: 0.25rem;
                top: -.15rem;
                position: relative;
            }

            .descendants {
                font-size: 0.8em;
                padding: 0.2em 0.2em 0 0.2em;
            }
        }

        .icon {
            position: relative;
            display: inline-block;
            background-size: contain;
            background-position: 0 0;
            height: 1rem;
            background-repeat: no-repeat;
            width: 1rem;
            margin-right: 0.25rem;
            margin-left: 0;
        }

        img {
            height: auto;
            max-width: 2.5rem;
            margin-right: 1rem;
        }

        .d-center {
            align-items: center;
            display: inline-flex;
        }


        .vs__dropdown-menu {

            .descendants {
                font-size: 0.8em;
                padding: 0.2em 0 0 0.5em;
            }

            li {
                border-bottom: 1px solid rgba(112, 128, 144, 0.1);
            }

            li:last-child {
                border-bottom: none;
            }

            li a {
                padding: 10px 20px;
                width: 100%;
                font-size: 1.25em;
                color: #3c3c3c;
            }

            .active > a {
                color: green;
            }

        }

    }
</style>

