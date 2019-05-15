
export const savingDialogs = {

    methods:{
        flashUpdateTagError({tag, msg}) {
            this.flash(msg, 'error', {})
        },


        flashStartSaving(propertyName) {
            return this.flash('Saving ' + propertyName[0].toUpperCase() + propertyName.substring(1).toLowerCase() + ' now ...', 'warning', {
                important: false,
                timeout: 2000
            });
        },

        flashSaved(propertyName) {
            // console.debug('saved Flash: ', propertyName);
            return this.flash(propertyName[0].toUpperCase() + propertyName.substring(1).toLowerCase() + ' saved', 'success', {
                timeout: 2000,
                important: false
            })
        },
        flashError(propertyName) {
            //  console.debug('Error Flash: ', propertyName);
            return this.flash('An error accured while while saving ' + propertyName.toLowerCase(), 'error', {
                important: true
            });
        },
    }

};