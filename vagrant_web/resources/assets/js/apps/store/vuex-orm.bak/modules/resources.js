import {api_v1_resources_show} from './../../../../components/serverRoutes'
import axios from 'axios'
import materialResource from './../models/materialResource';


const state = {};

const getters   = {
};
const mutations = {
    setResource(state, resource) {

        state[resource.id] = resource;
    }
};


const actions = {

    setResource: ({commit, dispatch}, resourceData) => {
        dispatch('create', {data: resourceData});

        if (resourceData.materials && resourceData.materials.length > 0) {

            resourceData.materials.forEach((mat) => {

                    if (mat.pivot) {
                        materialResource.update({
                            where: (record) => {
                                return record.material_id == mat.id && record.resource_id == mat.pivot.resource_id;
                            },
                            data: {limitation: mat.pivot.limitation}
                        }).then((entities) => {
                            console.log('updated: ', entities);
                        });
                    }

                }
            );
        }


    },

    getResourceById: ({getters, commit, dispatch}, id) => {
        return new Promise((resolve, reject) => {
            console.log('GETTING');

            let res = getters['query']().find(id);

            if (res) {
                resolve(res);
            } else {
                axios.get(api_v1_resources_show(id), {
                    params: {
                        relations: ['materials' /*, 'materials.keywords', 'materials.bibleverses' */]
                    }
                }).then((response) => {
                    dispatch('setResource', response.data);

                    resolve(getters['query']().with('materials').find(id));
                }).catch(() => {
                    reject('Could not find Resource');
                });
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