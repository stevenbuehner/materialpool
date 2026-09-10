export function setReactive(target, key, value) {
    target[key] = value;
    return value;
}

export function deleteReactive(target, key) {
    return delete target[key];
}
