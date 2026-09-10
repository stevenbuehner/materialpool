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


export function dayjs(value, customFormat) {
	return (customFormat !== undefined) ? dayJS(value, customFormat) : dayJS(value);
}

export function format(dayjsValue, formatPattern) {
	return dayjsValue.format(formatPattern);
}

export function recentOrFormat(dayjsValue, hours, formatPattern) {

	hours         = hours || 24 * 7;
	formatPattern = formatPattern || 'LLL';

	if ((Math.abs(dayjsValue.clone().diff(dayJS(), 'hour'))) > hours) {
		return dayjsValue.format(formatPattern);
	}

	return dayjsValue.fromNow();
}

export function fromNow(dayjsValue) {
	return dayjsValue.fromNow();
}

export function toNow(dayjsValue) {
	return dayjsValue.toNow();
}

export const formatLocalizedDate = {
	methods: {
		dayjs,
		format,
		fromNow,
		recentOrFormat,
		toNow,
	}
};
