// https://github.com/you-dont-need/You-Dont-Need-Momentjs
// https://github.com/iamkun/dayjs

import dayJS from 'dayjs';

import relativeTime    from 'dayjs/plugin/relativeTime';
import localeData      from 'dayjs/plugin/localeData'
import localizedFormat from 'dayjs/plugin/localizedFormat'

import {lang} from "../apps/main/localisation";
import 'dayjs/locale/de';

dayJS.extend(localeData);
dayJS.extend(localizedFormat);
dayJS.extend(relativeTime);
dayJS.locale(lang);


export const formatLocalizedDate = {
	filters: {

		/**
		 * Parse String to dayJs Object
		 *
		 * @param  value String {string}
		 * @param customFormat {string}
		 * @return {dayJS}
		 */
		dayjs(value, customFormat) {
			return (customFormat !== undefined) ? dayJS(value, customFormat) : dayJS(value);
		},

		/**
		 *
		 * @param dayjs {dayJS}
		 * @param format {string}
		 * @return {string}
		 */
		format(dayjs, format) {
			return dayjs.format(format);
		},

		recentOrFormat(dayjs, hours, format) {

			hours = hours || 24 * 7;
			format = format || 'LLL';

			if ((Math.abs(dayjs.clone().diff(dayJS(), 'hour'))) > hours) {
				return dayjs.format(format);
			} else {
				return dayjs.fromNow();
			}

		},

		/**
		 *
		 * @param dayjs {dayJS}
		 * @return {string}
		 */
		fromNow(dayjs) {
			return dayjs.fromNow();
		},

		/**
		 *
		 * @param dayjs {dayJS}
		 * @return {string}
		 */
		toNow(dayjs) {
			return dayjs.toNow();
		}
	}
};