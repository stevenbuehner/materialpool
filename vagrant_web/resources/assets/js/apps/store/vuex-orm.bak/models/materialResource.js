import {Model} from '@vuex-orm/core';

export default class MaterialResource extends Model {
    static entity = 'materialResource';

    static primaryKey = ['material_id', 'resource_id'];

    constructor(record) {
        return super(record);
    }

    static fields() {
        return {
            resource_id: this.attr(null),
            material_id: this.attr(null),
            limitation: this.attr(null),
        }
    }
}