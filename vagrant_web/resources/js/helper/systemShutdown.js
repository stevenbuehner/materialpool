import axiosInstance                   from "../apps/main/axiosInstance";
import {convertErrorResponseToMessage} from "../apps/main/store/modules/handleErrorsHelper";
import {api_v2_system_shutdown}        from "../components/serverRoutes";

export async function systemShutdown() {
	return await axiosInstance.get(api_v2_system_shutdown)
	                          .then(({data}) => data.done)
	                          .catch((response) => {
		                          throw convertErrorResponseToMessage(response);
	                          });
}
