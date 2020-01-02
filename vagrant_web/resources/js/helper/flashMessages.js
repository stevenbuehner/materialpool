import {timeout_flashErrorMessage} from "../apps/config";

export const savingDialogs = {

	methods: {
		flashUpdateTagError({tag, msg}) {
			this.flash(msg, 'error', {})
		},


		flashStartSaving(propertyName) {
			return this.flash(this.$t('pool.saving-xy-now', {xy: propertyName[0].toUpperCase() + propertyName.substring(1).toLowerCase()}), 'warning', {
				important: true,
				timeout: 2500
			});
		},

		flashSaved(propertyName) {
			// console.debug('saved Flash: ', propertyName);
			return this.flash(this.$t('pool.xy-saved', {xy: propertyName[0].toUpperCase() + propertyName.substring(1).toLowerCase()}), 'success', {
				timeout: 1000,
				important: true
			})
		},

		flashStartRemoving(propertyName) {
			return this.flash(this.$t('pool.removing-xy-now', {xy: propertyName[0].toUpperCase() + propertyName.substring(1).toLowerCase()}), 'warning', {
				important: true,
				timeout: 2500
			});
		},

		flashRemoved(propertyName) {
			return this.flash(this.$t('pool.xy-removed', {xy: propertyName[0].toUpperCase() + propertyName.substring(1).toLowerCase()}), 'success', {
				important: true,
				timeout: 1000
			});
		},

		flashError(propertyName, msg) {
			//  console.debug('Error Flash: ', propertyName);

			if (msg) {
				console.error(msg);
				msg = ' (' + msg + ')';
			}

			return this.flash('An error accured while while saving ' + propertyName.toLowerCase() + (msg || ''), 'error', {
				important: false,
				timeout: timeout_flashErrorMessage,
			});
		},
	}

};