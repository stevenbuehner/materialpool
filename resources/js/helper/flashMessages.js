import {timeout_flashErrorMessage, timeout_flashSavingMessage} from "../apps/config";

function propNameFormat(p) {
	const s = String(p);
	return s[0].toUpperCase() + s.substring(1).toLowerCase();
}

export const savingDialogs = {

	methods: {

		flashActionStartedWaiting(message, closeFlash) {
			this._flashCloseAndDestroy(closeFlash);

			return this.flash((message), 'warning', {
				important: true,
				timeout: timeout_flashSavingMessage,
			});
		},

		flashActionSuccessfullyFinished(message, closeFlash) {
			this._flashCloseAndDestroy(closeFlash);

			return this.flash(message, 'success', {
				important: true,
				timeout: timeout_flashSavingMessage,
			})
		},

		flashActionFailed(message, closeFlash) {
			this._flashCloseAndDestroy(closeFlash);

			return this.flash(message, 'error', {
				important: false,
				timeout: timeout_flashErrorMessage,
			});
		},

		flashStartSaving(propertyName) {
			return this.flashActionStartedWaiting(this.$t('pool.saving-xy-now', {xy: propNameFormat(propertyName)}));
		},

		flashSaved(propertyName, closeFlash) {
			this.flashActionSuccessfullyFinished(this.$t('pool.xy-saved', {xy: propNameFormat(propertyName)}), closeFlash);
		},

		flashStartCreating(propertyName) {
			return this.flashActionStartedWaiting(this.$t('pool.creating-xy-now', {xy: propNameFormat(propertyName)}));
		},

		flashCreated(propertyName, closeFlash) {
			return this.flashActionSuccessfullyFinished(this.$t('pool.xy-created', {xy: propNameFormat(propertyName)}), closeFlash);
		},

		flashStartRemoving(propertyName) {
			return this.flashActionStartedWaiting(this.$t('pool.removing-xy-now', {xy: propNameFormat(propertyName)}));
		},

		flashRemoved(propertyName, closeFlash) {
			return this.flashActionSuccessfullyFinished(this.$t('pool.xy-removed', {xy: propNameFormat(propertyName)}), closeFlash);
		},

		flashError(propertyName, msg, closeFlash) {
			if (msg) {
				console.error(msg);
				msg = ' (' + msg + ')';
			}

			this._flashCloseAndDestroy(closeFlash);

			this.flashActionFailed('An error accured while while saving ' + propertyName.toLowerCase() + (msg || ''));
		},

		flashUpdateTagError({msg}) {
			this.flashActionFailed(msg);
		},

		_flashCloseAndDestroy(flashObject) {
			if (flashObject && typeof flashObject.destroy === 'function') {
				flashObject.destroy();
			}
		}

	}

};
