export function isResourceTypeLimitable(type) {

    let isLimitable = false;

    switch (type) {
        case 'audio':
        case 'video':
        case 'pdf':
            isLimitable = true;
            break;
        case 'image':
        case 'text':
        case 'doc':
        case 'res':
            isLimitable = false;
            break;
        default:
            console.log('Unknown Resource-Type: ' + type);
            isLimitable = false;
    }

    return isLimitable;

}