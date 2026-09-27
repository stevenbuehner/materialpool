/**
 *
 * Zerlegt einen ID-String ('material.default.test') in ein Array (['material', 'default', 'test']
 * @param id
 * @param splitWith
 * @returns {*}
 */
export function idExplode(id, splitWith) {

	if (!splitWith) {
		splitWith = '.';
	}

	if (!id) {
		return [];
	} else {
		return id.split(splitWith);
	}

}

/**
 *
 * @param id
 * @param data
 * @param defaultValue
 * @returns {*}
 */
export function extractValueById(id, data, defaultValue) {

	if (!defaultValue) {
		defaultValue = {};
	}

	const idExploded = idExplode(id);
	let result       = data;

	while (idExploded.length > 0) {

		const key = idExploded.shift();

		if (result[key] !== undefined) {
			result = result[key];
		} else {
			result = defaultValue;
			break;
		}

	}

	return result;

}

/**
 * Überschreibt im mitgegebenen Object/Array den Wert an der mit "id" spezifizierten Stelle
 * @param id
 * @param completeCurrentDataTree
 * @param newValue
 * @returns {*}
 */
export function overrideValueById(id, completeCurrentDataTree, newValue) {

	const idExploded = idExplode(id);
	let data         = completeCurrentDataTree;

	// Sichergehen, dass der ganze Array-Pfad angelegt ist / existiert
	while (idExploded.length > 0) {

		const key = idExploded.shift();

		if (data[key] === undefined) {
			data[key] = {};
		}

		if (idExploded.length > 0) {
			if (Array.isArray(data[key])) {
				data[key] = verifyStructure(data[key]);
			}
		} else if (idExploded.length === 0) {
			data[key] = newValue;
		}

		data = data[key];

	}

	return completeCurrentDataTree;

}

/**
 * Löscht im mitgegebenen Object/Array den Wert an der mit "id" spezifizierten Stelle
 * @param id
 * @param completeCurrentDataTree
 * @returns {*}
 */
export function removeValueById(id, completeCurrentDataTree) {

	const idExploded = idExplode(id);
	let data         = completeCurrentDataTree;

	// Sichergehen, dass der ganze Array-Pfad angelegt ist / existiert
	while (idExploded.length > 0) {

		const key = idExploded.shift();

		if (data[key] === undefined) {
			data[key] = {};
		}

		if (idExploded.length === 0) {
			delete data[key];
			break;
		}

		data = data[key];

	}

	return completeCurrentDataTree;

}


/** Sicherstellen, dass im Root-Element keine Daten in einem Array als Objekt-Daten abgelegt werden. Diese werden nämlich von axios nicht übertragen
 *
 */
export function verifyStructure(d) {

	if (!d) {
		return {};
	}

	if (Array.isArray(d) && Object.getOwnPropertyNames(d).length > 0) {

		let bereinigt = {};

		for (let i in Object.getOwnPropertyNames(d)) {
			if (d[i]) {
				// Keine undefined und null Werte hinzufügen
				bereinigt[i] = d[i];
			}
		}

		return bereinigt;

	} else {
		return d;
	}


}

