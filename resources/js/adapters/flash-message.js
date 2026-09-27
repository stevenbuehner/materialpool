import { h, reactive, TransitionGroup } from 'vue';

const storage = reactive({});

class FlashMessage {
    constructor(content, type, options) {
        this.content = content;
        this.type = type;
        this.options = options;
        this.id = `${Date.now()}-${Math.random().toString(16).slice(2)}`;
        this.timer = null;
        storage[this.id] = this;
        this.startTimer();
    }

    startTimer() {
        if (this.options.timeout > 0) {
            this.timer = window.setTimeout(() => this.destroy(), this.options.timeout);
        }
    }

    stopTimer() {
        window.clearTimeout(this.timer);
        this.timer = null;
    }

    destroy() {
        this.stopTimer();
        this.options.beforeDestroy?.();
        delete storage[this.id];
    }

    onStartInteract() {
        if (this.options.pauseOnInteract) {
            this.stopTimer();
        }
        this.options.onStartInteract?.();
    }

    onCompleteInteract() {
        if (this.options.pauseOnInteract) {
            this.startTimer();
        }
        this.options.onCompleteInteract?.();
    }
}

const FlashMessageList = {
    name: 'FlashMessageList',
    props: {
        transitionName: {
            type: String,
            default: 'flash-transition',
        },
        outerClass: {
            type: String,
            default: 'flash__wrapper',
        },
    },
    render() {
        const messages = Object.values(storage).map(message => h('div', {
            class: [message.type, 'flash__message'],
            key: message.id,
            role: 'alert',
            'aria-live': 'polite',
            'aria-atomic': 'true',
            onMouseover: () => message.onStartInteract(),
            onMouseleave: () => message.onCompleteInteract(),
        }, [
            h('div', {
                class: 'flash__message-content',
                innerHTML: message.content,
            }),
            message.options.important ? null : h('button', {
                type: 'button',
                class: 'flash__close-button',
                'aria-label': 'alertClose',
                onClick: event => {
                    event.preventDefault();
                    event.stopPropagation();
                    message.destroy();
                },
            }, '×'),
        ]));

        return h('div', {}, [
            h(TransitionGroup, {
                name: this.transitionName,
                tag: 'div',
                class: this.outerClass,
            }, {default: () => messages}),
        ]);
    },
};

export default {
    install(app, config = {}) {
        const defaults = {
            important: true,
            pauseOnInteract: true,
            timeout: 0,
            ...(config.messageOptions || {}),
        };
        const flash = (content, type = 'info', options = {}) => (
            new FlashMessage(content, type, {...defaults, ...options})
        );

        Object.assign(app.config.globalProperties, {
            flash,
            flashInfo: (content, options) => flash(content, 'info', options),
            flashError: (content, options) => flash(content, 'error', options),
            flashWarning: (content, options) => flash(content, 'warning', options),
            flashSuccess: (content, options) => flash(content, 'success', options),
            $flashStorage: storage,
        });
        app.component('flash-message', FlashMessageList);
    },
};
