export function convertErrorResponseToMessage(result) {
    if (result.response && result.response.data && result.response.data.message) {
        return result.response.data.message;
    } else if (result.response && result.response.message) {
        return result.response.message
    } else if (result.message) {
        return result.message;
    } else {
        return 'undefined error';
    }
}