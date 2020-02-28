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
				this._flashCloseAndDestroy(closeFlash);
			}

			return this.flash(message, 'success', {
				important: true,
				timeout: timeout_flashSavingMessage,
			})
		},

		flashActionFailed(message, closeFlash) {
			if (closeFlash) {
				this._flashCloseAndDestroy(closeFlash);
			}

			return this.flash(message, 'error', {
				important: false,
				timeout: timeout_flashErrorMessage,
			});
		},

		flashStartSaving(propertyName) {
			return this.flashActionStartedWaiting(this.$t('pool.saving-xy-now', {xy: propertyName[0].toUpperCase() + propertyName.substring(1).toLowerCase()}));
		},

		flashSaved(propertyName, closeFlash) {
			if (closeFlash) {
				this._flashCloseAndDestroy(closeFlash);
			}

			this.flashActionSuccessfullyFinished(this.$t('pool.xy-saved', {xy: propertyName[0].toUpperCase() + propertyName.substring(1).toLowerCase()}));
		},

		flashStartRemoving(propertyName) {
			return this.flashActionStartedWaiting(this.$t('pool.removing-xy-now', {xy: propertyName[0].toUpperCase() + propertyName.substring(1).toLowerCase()}));
		},

		flashRemoved(propertyName, closeFlash) {
			if (closeFlash) {
				this._flashCloseAndDestroy(closeFlash);
			}

			return this.flashActionSuccessfullyFinished(this.$t('pool.xy-removed', {xy: propertyName[0].toUpperCase() + propertyName.substring(1).toLowerCase()}));
		},

		flashError(propertyName, msg, closeFlash) {
			if (msg) {
				console.error(msg);
				msg = ' (' + msg + ')';
			}

			if (closeFlash) {
				this._flashCloseAndDestroy(closeFlash);
			}

			this.flashActionFailed('An error accured while while saving ' + propertyName.toLowerCase() + (msg || ''));
		},

		flashUpdateTagError({tag, msg}) {
			this.flashActionFailed(msg);
		},

		_flashCloseAndDestroy(flashObject) {
			if (typeof flashObject.destroy === 'function') {
				flashObject.destroy();
			} else {
				console.error('Given Flash-Object has no destroy-function!', flashObject);
			}
		}

	}

};