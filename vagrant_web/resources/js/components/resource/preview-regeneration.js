import {reactive} from 'vue';

const activePreviewRegenerations = reactive(new Set());

export function isPreviewRegenerationActive(resourceId) {
	return activePreviewRegenerations.has(resourceId);
}

export async function regeneratePreview(resourceId, regenerate) {
	if (activePreviewRegenerations.has(resourceId)) {
		return false;
	}

	activePreviewRegenerations.add(resourceId);

	try {
		await regenerate();

		return true;
	} finally {
		activePreviewRegenerations.delete(resourceId);
	}
}
