<script>
	import tagIcon                 from 'svg-icon/dist/svg/material/style.svg';
	import {debounce as _debounce} from 'lodash';
	import tagEdit                 from "./tagEdit";

	export default {
		name: "bibleverseEdit",
		extends: tagEdit,

		data() {
			return {
				clearOnSelect: true, // Override
			}
		},

		computed: {
			validTypesValues() {
				return this.value;
			},

			invalidTypesValues() {
				return [];
			}


		},

		methods: {

			getTagLabelFromObject(value) {
				if (typeof value === 'object') {
					if (!value.hasOwnProperty('label')) {
						return console.warn(
							`[vue-select warn]: Label key "option.label" does not` +
							` exist in options object ${JSON.stringify(value)}.\n` +
							'http://sagalbot.github.io/vue-select/#ex-labels'
						)
					} else {
						return value.label;
					}

				} else {
					return value;
				}
			},

			filterSuggestionsBy(object) {
				return this.value.find((el) => el.from === object.from && el.to === object.to) === undefined;
			},

			// _.debounce is a function provided by lodash to limit how
			// often a particularly expensive operation can be run.
			// To learn
			// more about the _.debounce function (and its cousin
			// _.throttle), visit: https://lodash.com/docs#debounce
			search: _debounce((loading, search, vm) => {

				vm.$store.dispatch('bibleverses/search', search)
				  .then((bibleverses) => {
					  vm.suggestedFilteredTags = bibleverses.filter(vm.filterSuggestionsBy.bind(vm));
				  })
				  .catch((data) => {
					  alert(data);
				  })
				  .then(() => {
					  // Always
					  loading(false);
				  });

			}, 250),


		},

		components: {
			tagIcon
		}
	}
</script>
