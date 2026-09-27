// See: https://ourcodeworld.com/articles/read/713/converting-bytes-to-human-readable-values-kb-mb-gb-tb-pb-eb-zb-yb-with-javascript

const sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

export function readableBytes(num) {
	const neg = num < 0;

	if (neg) {
		num = -num;
	}

	if (num < 1) {
		return (neg ? '-' : '') + num + ' B';
	}

	const exponent = Math.min(Math.floor(Math.log(num) / Math.log(1000)), sizes.length - 1);

	num = Number((num / Math.pow(1000, exponent)).toFixed(2));

	const unit = sizes[exponent];

	return (neg ? '-' : '') + num + ' ' + unit;
}

export function displayFilesize(filesize, notCalculatedLabel) {
	return filesize === null || filesize === undefined
		? notCalculatedLabel
		: readableBytes(filesize);
}

export default {
	methods: {
		readableBytes,
		displayFilesize,

		/**
		 * Converts a long string of bytes into a readable format e.g KB, MB, GB, TB, YB
		 * 1024 bytes based short version
		 *
		 * @param {Int} bytes The number of bytes.
		 */
		/*
		// Derzeit noch nicht in Verwendung => Traffic sparen
		readableBits(bytes) {
			const i = Math.floor(Math.log(bytes) / Math.log(1024));

			return (bytes / Math.pow(1024, i)).toFixed(2) * 1 + ' ' + sizes[i];
		},
		*/

		/**
		 * Converts a long string of bytes into a readable format e.g KB, MB, GB, TB, YB
		 * 1000 bytes based version
		 * The other option offers a conversion of bytes to a readable format but having in count that 1KB
		 * is equal to 1000 bytes, not 1024 like the first option. This increases decreases the margin of
		 * accuracy, but works with almost the same logic of our first method:
		 * @param {Int} num The number of bytes.
		 */
	}
};
