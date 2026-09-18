import PQueue, {AbortError}              from 'p-queue';
import {MAX_SIMULTANEOUS_IMAGES_LOADING} from "../apps/config";

const DEFAULT_PRIORITY = 10;
const DATASET_SRC      = 'src';

const EVENT_ABORT   = 'q-abort';
const EVENT_QUEUED  = 'q-queued';
const EVENT_LOADING = 'q-loading';
const EVENT_LOADED  = 'q-loaded';
const EVENT_ERROR   = 'q-error';

const DEFAULT_IMG_SVG = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADAAAAAwCAYAAABXAvmHAAAAAXNSR0IArs4c6QAAAERlWElmTU0AKgAAAAgAAYdpAAQAAAABAAAAGgAAAAAAA6ABAAMAAAABAAEAAKACAAQAAAABAAAAMKADAAQAAAABAAAAMAAAAADbN2wMAAADTElEQVRoBe2YO4wOURTHP4Ql8QjBRvQUZFuVntraTqfRUCmESISQeK0GBVFI9KoNGhNRSERQKEUssUhsIdt5/n/JnOTsyex+M9/M/R7JnOSfcx/nnvO/d+49c2c6nVbaFWhXINUKTMnxXI7JVEFS+oX8vxxfUgZK5dvImy6Ks0ON08INYbzIYJBtRtx05LJaDW8F638VDQZdN2KmI58Tjjw2f4SxaDTIuhE37blsVWVesD70I28wDGVPjrKX26r4/t+qT3iDYSh7gn4Ce0Tul+D7b6YkvL5H50ul0ZlAnq3ElmpcNsvjM4GVeiJUfcSHNYb8Dw4JJgsq+NXnMEchFhPFNhO2C5XlmEb4QOzTW0Ld1cqc35cqk05N8E0MYvnY58ygip4KTswhj5xV84Gr+GU1LwsnhQ35QHzhE98Wx+szuV0ltUrW94S/gndm5Tdq501aV5gQvsxv1K/Vt61OkP0azFsyOqZ+vY7jfOzZJXzXfdKLqK1U7ajwNQSbXmTVW+V88NnUWStks1GtlwRS5ENhi1BXOLhPBd4Nj4UJoZWRXoGDYv9JKDq0tLF9JoWmhbTt394x/qz6D5QJimEcHOspvrCWI2/xP8YJkF2ilHlBkSkGIWW4dewOY7OOmlUo9SgrzrBb3Hinqui+NR+OFeAlMyMsCJnAPaaurJWDu8I74aLAy7JxafSaG9jFuxDXFa4tRQkmDO1eTXLNDWHvqB4TBXUukFwke5bk19ycGdvyh1A0Ca7yD4SetlV8tBZgXg79B03Vv2t7Nf6KcFwYE5Cdwn0BwhbH61NqryxlrrlsMf93LesSZZ36vwtGDtJe9qnyQrB+0xzuylLmmsuTsCDony6K3aneq8328u5gz4pbnw1docIR4bOAzw8C4xoXJsh28hMgxZrMqmB9TALhiRkx6+OwFmUcthfbjU/bJNLt75oRNG0kWF1rM03a7KuU+btm5EwbQbbIc8Ha0d+ETULfhE8/T4CtxJby4vspe+GwxoxzwRukLJNJYnAOc5TlJoAtGcjbZDT2S/xvlvh3zTh4cvEJYEPu9yn1qg3shx5XkPh3LcbtNgHsdwnXhNPCGmGoZE5sbBKk1JET+8JK9QU3cgvSEm5XIMUK/Ac8R4HWzRb6tQAAAABJRU5ErkJggg==";

const CLASS_QUEUED  = 'q-queued';
const CLASS_LOADING = 'q-loading';
const CLASS_LOADED  = 'q-loaded';
const CLASS_ERROR   = 'q-error';

const queue = new PQueue({concurrency: MAX_SIMULTANEOUS_IMAGES_LOADING});
const observers = new WeakMap();

export default {
	mounted(el, binding) {

		// Verify Parameter and setup Config

		if (el.nodeName !== 'IMG') {
			console.error('Directive qloader only works on img-elements');
			return;
		}

		const src = el.getAttribute('src') || false;

		// console.log('bind');

		// Setup is valid
		if (src) {

			const priority   = binding.value || DEFAULT_PRIORITY;
			const src        = el.getAttribute('src');
			const srcAlt     = el.getAttribute('src-alt') || DEFAULT_IMG_SVG;
			const hideImage  = binding.modifiers?.hide === true;
			const controller = new AbortController();

			el.dataset[DATASET_SRC] = src;
			el.setAttribute('src', srcAlt);
			el.addEventListener(EVENT_ABORT, () => {
				// console.log('Abort requested');
				controller.abort();
			});

			if (hideImage) {
				el.style.display = 'none';
			}

			// console.log(EVENT_QUEUED);
			el.dispatchEvent(new Event(EVENT_QUEUED));
			el.classList.add(CLASS_QUEUED);

			const queueImage = () => queue.add(async () => {
				// console.log('Queue: ' + src);
				// console.log(EVENT_LOADING);
				el.dispatchEvent(new Event(EVENT_LOADING));

				el.classList.add(CLASS_LOADING);
				el.classList.remove(CLASS_QUEUED);

				// await sleep(5000);

				const prom = new Promise((resolve, reject) => {
					el.onload  = function (e) {
						// console.log('Queue Loaded: ' + src);

						el.dispatchEvent(new Event(EVENT_LOADED));

						el.classList.add(CLASS_LOADED);
						el.classList.remove(CLASS_LOADING);
						resolve(e);
					};
					el.onerror = function (e) {
						// console.log('Queue Error: ' + src);

						el.classList.add(CLASS_ERROR);
						el.classList.remove(CLASS_LOADING);
						reject(e);
					};
				}).then(resp => {

					// Cleanup
					delete el.dataset[DATASET_SRC];

					if (hideImage) {
						el.style.removeProperty("display");
					}

					return resp;
				});

				el.setAttribute('src', src);

				return prom;

			}, {priority, signal: controller.signal})
			     .catch((error) => {
				     if (!(error instanceof AbortError)) {
					     el.dispatchEvent(new Event(EVENT_ERROR, error));
				     }
			     });

			if (typeof window !== 'undefined' && 'IntersectionObserver' in window) {
				const observer = new IntersectionObserver((entries) => {
					if (!entries.some((entry) => entry.isIntersecting)) {
						return;
					}

					observer.disconnect();
					observers.delete(el);
					queueImage();
				}, {rootMargin: '250px 0px'});

				observers.set(el, observer);
				observer.observe(el);
			} else {
				queueImage();
			}
		}

		// console.log("bind", src, el, binding);

	},

	unmounted(el) {
		// console.log("unbind", binding);

		observers.get(el)?.disconnect();
		observers.delete(el);
		el.dispatchEvent(new CustomEvent(EVENT_ABORT, {reason: 'unbind'}));
	}
}
