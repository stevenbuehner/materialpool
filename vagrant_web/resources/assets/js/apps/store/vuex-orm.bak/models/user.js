import {Model} from '@vuex-orm/core'
import Resource from './resource';
import MaterialResource from './materialResource';

export default class User extends Model{
    static entity = 'users';

    static fields(){
        return {
            id: this.attr(null),
            name : this.string(''),
        }
    }
}