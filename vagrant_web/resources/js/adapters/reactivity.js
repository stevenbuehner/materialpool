import Vue from 'vue';

export function setReactive(target, key, value) {
    return Vue.set(target, key, value);
}

export function deleteReactive(target, key) {
    return Vue.delete(target, key);
}
