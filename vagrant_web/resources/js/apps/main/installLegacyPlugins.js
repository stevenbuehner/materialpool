import VueLang from '@eli5/vue-lang-js';
import AsyncComputed from 'vue-async-computed';
import ShortKey from 'vue-shortkey';

import flashMessage from '@/adapters/flash-message';
import {
    FormInputPlugin,
    FormTextareaPlugin,
    TabsPlugin,
} from '@/adapters/bootstrap';
import queuedImagesLoader from '@/directives/queued-images-loader';
import { timeout_flashSavingMessage } from '../config';

export function installLegacyPlugins(VueApp, vueLangConfig) {
    VueApp.use(ShortKey);
    VueApp.use(AsyncComputed);
    VueApp.use(VueLang, vueLangConfig);
    VueApp.use(FormInputPlugin);
    VueApp.use(FormTextareaPlugin);
    VueApp.use(TabsPlugin);
    VueApp.use(flashMessage, {
        messageOptions: {
            timeout: timeout_flashSavingMessage,
            important: true,
            pauseOnInteract: true,
        },
    });
    VueApp.directive('image-queue', queuedImagesLoader);
}
