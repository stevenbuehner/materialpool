const state = {
    materials: []
};

const getters = {
    getMaterialById: (state) => (id) => {
        return state.materials.find(mat => {
            return mat.id === id;
        });
    }
};

const mutations = {};

const actions = {
    getMaterialById: ({getters}, id) => {
        return new Promise((resolve, reject) => {
            let mat = getters.getMaterialById(id);

            if (mat) {
                resolve(mat);
            } else {
                // Todo load material from server
                reject('Could not find Material');
            }
        });
    }

};

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
};