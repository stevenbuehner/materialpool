import Vue from 'vue';
import VueX from 'vuex';

import VuexORM from '@vuex-orm/core';
import materialModel from './models/material';
import resourceModel from './models/resource';
import materialResourceModel from './models/materialResource';
import userModel from './models/user';
import materialModule from './modules/materials';
import resourceModule from './modules/resources';
import materialResourceModule from './modules/materialResource';
import userModule from './modules/users';

Vue.use(VueX);

// Create new instance of Database.
const database = new VuexORM.Database();

// Register Model and Module. The First argument is the Model, and
// second is the Module.
database.register(materialModel, materialModule);
database.register(resourceModel, resourceModule);
database.register(materialResourceModel, materialResourceModule);
database.register(userModel, userModule);

export const store = new VueX.Store({

    plugins: [
        VuexORM.install(database)
    ]

});