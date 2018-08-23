import {Model} from '@vuex-orm/core'
import Resource from './resource';
import MaterialResource from './materialResource';
import User from './user';

export default class Material extends Model{
    static entity = 'materials';

    static fields(){
        return {
            id: this.attr(null),
            created_at : this.attr(null),
            updated_at : this.attr(null),
            description : this.string(''),
            from_bot : this.boolean(false),
            rating: this.attr(null),
            title: this.string(''),

            created_by: this.attr(null),

            resources: this.belongsToMany(Resource, MaterialResource, 'material_id', 'resource_id' ),
            creator: this.belongsTo(User, 'created_by'),

        }
    }
}