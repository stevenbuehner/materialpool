import axiosInstance                   from "../apps/main/axiosInstance";
import {keepAliveRoute}                from "../components/serverRoutes";
import {convertErrorResponseToMessage} from "../apps/main/store/modules/handleErrorsHelper";

export async function sessionKeepAlive() {
	return await axiosInstance
		.get(keepAliveRoute)
		.catch((response) => throw convertErrorResponseToMessage(response));
}