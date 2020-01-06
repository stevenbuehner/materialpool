import {timeout_flashErrorMessage, timeout_flashSavingMessage} from "../apps/config";

export const savingDialogs = {

	methods: {

		flashActionStartedWaiting(message) {
			return this.flash((message), 'warning', {
				important: true,
				timeout: timeout_flashSavingMessage,
			});
		},

		flashActionSuccessfullyFinished(message, closeFlash) {
			if (closeFlash) {
				closeFlash.destroy();
			}

			return this.flash(message, 'success', {
				important: true,
				timeout: timeout_flashSavingMessage,
			})
		},

		flashActionFailed(message, closeFlash) {
			if (closeFlash) {
				closeFlash.destroy();
			}

			return this.flash(message, 'error', {
				important: false,
				timeout: timeout_flashErrorMessage,
			});
		},

		flashStartSaving(propertyName) {
			return this.flashActionStartedWaiting(this.$t('pool.saving-xy-now', {xy: propertyName[0].toUpperCase() + propertyName.substring(1).toLowerCase()}));
		},

		flashSaved(propertyName) {
			this.flashActionSuccessfullyFinished(this.$t('pool.xy-saved', {xy: propertyName[0].toUpperCase() + propertyName.substring(1).toLowerCase()}));
		},

		flashStartRemoving(propertyName) {
			return this.flashActionStartedWaiting(this.$t('pool.removing-xy-now', {xy: propertyName[0].toUpperCase() + propertyName.substring(1).toLowerCase()}));
		},

		flashRemoved(propertyName) {
			return this.flashActionSuccessfullyFinished(this.$t('pool.xy-removed', {xy: propertyName[0].toUpperCase() + propertyName.substring(1).toLowerCase()}));
		},

		flashError(propertyName, msg) {
			if (msg) {
				console.error(msg);
				msg = ' (' + msg + ')';
			}

			this.flashActionFailed('An error accured while while saving ' + propertyName.toLowerCase() + (msg || ''));
		},

		flashUpdateTagError({tag, msg}) {
			this.flashActionFailed(msg);
		},

	}

};