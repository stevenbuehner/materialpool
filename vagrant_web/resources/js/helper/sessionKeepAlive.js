import axiosInstance    from "../apps/main/axiosInstance";
import {keepAliveRoute} from "../components/serverRoutes";

export async function sessionKeepAlive() {
	const response = await axiosInstance.get(keepAliveRoute);
	return response.data && response.data.ok === true;
}