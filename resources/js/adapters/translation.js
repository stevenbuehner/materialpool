import Lang from 'lang.js';

export function installTranslation(app, options) {
    const lang = new Lang({
        messages: options.messages || {},
        locale: options.locale || 'en',
        fallback: options.fallback || 'en',
    });
    const translate = (key, replacements) => lang.trans(key, replacements);
    const choice = (key, count, replacements) => lang.choice(key, count, replacements);
    const has = key => lang.has(key);

    Object.assign(app.config.globalProperties, {
        $lang: lang,
        $trans: translate,
        $t: translate,
        $choice: choice,
        $tc: choice,
        $has: has,
        $ifTrans: (key, fallback) => has(key) ? translate(key) : fallback,
        $it: (key, fallback) => has(key) ? translate(key) : fallback,
    });
}
