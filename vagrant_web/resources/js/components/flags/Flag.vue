<template>
    <div class="sb-flag-wrapper" :class="[{noFlag: flagKey === null}]">

        <flag-item :color="flagColor(flagKey)" v-if="flagKey !== null" :title="$t('pool.remove-this-flag')"
                   class="single-flag"
                   @flag-selected="onFlagRemoved"></flag-item>

        <flag-item color="gray" v-if="flagKey === null"
                   v-once
                   class="single-flag unset"></flag-item>

        <div class="flag-list justify-content-center" :title="$t('pool.select-a-flag')"
             v-if="flagKey === null">
            <flag-item v-for="color in allFlags"
                       :color="color"
                       :key="color"
                       v-once
                       @flag-selected="onFlagSelected"></flag-item>
        </div>
    </div>
</template>

<script>
	import {flagColors} from './flagOptions.js';
	import FlagItem     from "./FlagItem";

	export default {
		name: "Flag",

		props: {
			flagKey: {
				required: true,
				validator(value) {
					return value === null || (value <= 0 && value > flagColors.length);
				}
			},
		},


		data() {
			return {
				allFlags: flagColors.filter((c) => c !== 'gray'),
				isHover: false
			};
		},

		methods: {

			flagColor(index) {
				return flagColors[index];
			},

			onFlagSelected(flagColor) {

				const index = flagColors.indexOf(flagColor);

				if (index !== -1) {
					this._emitFlagUpdate(index);
				}
			},

			onFlagRemoved() {
				this._emitFlagUpdate(null);
			},

			_emitFlagUpdate(value) {
				this.$emit('flag-updated', value);
			}

		},

		components: {
			FlagItem,
		}

	}
</script>

<style type="scss">

    .sb-flag-wrapper {

        .single-flag {
            font-size: 4rem;
        }

        .flag-list {
            font-size: calc(4rem / 3);
            width: 4rem;
        }

        &.noFlag {

            .flag-list {
                display: none;
            }

            &:hover {
                .single-flag {
                    display: none;
                }

                .flag-list {
                    display: flex;
                    flex-wrap: wrap;
                }
            }
        }

    }


</style>