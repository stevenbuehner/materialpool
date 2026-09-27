import axios       from "axios";
import {is_safari} from "../../helper/browserCheck";

const axiosInstance = axios.create({
	headers: {
		'X-CSRF-TOKEN': window.Laravel.csrfToken,
		'X-Requested-With': 'XMLHttpRequest',
	},
	timeout: 30000,
	// xsrfCookieName: 'XSRF-TOKEN', // default
});

if (is_safari) {
	// Beim Safari Browser, aufgrund eines Bugs, einen sich verändernden Hash-Wert setzen ... => Reload every time
	console.log("This is a Safari - I hate it ;-)");

	// Add a request interceptor
	axiosInstance.interceptors.request.use(function (config) {
		config.params             = config.params || {};
		config.params.safariNervt = Math.random();
		return config;
	});
}

export default axiosInstance;