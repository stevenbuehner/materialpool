import AsyncComputed from 'vue-async-computed';

import flashMessage from '@/adapters/flash-message';
import { installTranslation } from '@/adapters/translation';
import queuedImagesLoader from '@/directives/queued-images-loader';
import ShortKey from '@/directives/shortkey';
import { timeout_flashSavingMessage } from '../config';

export function installLegacyPlugins(VueApp, vueLangConfig) {
    VueApp.use(ShortKey);
    VueApp.use(AsyncComputed);
    installTranslation(VueApp, vueLangConfig);
    VueApp.use(flashMessage, {
        messageOptions: {
            timeout: timeout_flashSavingMessage,
            important: true,
            pauseOnInteract: true,
        },
    });
    VueApp.directive('image-queue', queuedImagesLoader);
}
