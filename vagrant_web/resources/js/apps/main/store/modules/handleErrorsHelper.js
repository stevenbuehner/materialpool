export function convertErrorResponseToMessage(result) {
	// console.error(result);

	if (Array.isArray(result?.response?.data?.errors?.content) && result.response.data.errors.content.length > 0) {
		return result.response.data.errors.content.join(', ');
	} else if (result?.response?.data?.message) {
		return result.response.data.message;
	} else if (result?.response?.data?.error) {
		return result.response.data.error;
	} else if (result?.response?.message) {
		return result.response.message
	} else if (result?.message) {
		return result.message;
	} else {
		return 'undefined error';
	}
}