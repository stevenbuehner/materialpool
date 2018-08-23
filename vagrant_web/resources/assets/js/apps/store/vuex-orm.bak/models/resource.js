import {Model} from '@vuex-orm/core';
import Material from './material';
import MaterialResource from './materialResource';

export default class Resource extends Model {
    static entity = 'resources';

    static fields() {
        return {
            id: this.attr(null),
            content_hash: this.string(''),
            is_public: this.boolean(false),
            notes: this.string(''),
            remote_path: this.attr(null),
            type: this.string('res'),

            materials: this.belongsToMany(Material, MaterialResource, 'resource_id', 'material_id')

        }
    }
}