import axios from "../../axiosInstance";
import {queue} from "../networkQueue";

export const getPage = async (url, pageNo) => {

    // console.log('Start RUNNING (AXIOS) for page ' + pageNo);

    return axios.get(url,
        {
            params: {page: pageNo}
        })
        .then(({data}) => {
            return data
        })
        .catch((response) => {
            console.error(response);
            return [];
        });
};

export function getAllPages(url) {
    return getPage(url, 1)
        .then(data => {
            const last_page    = data.last_page;
            const current_page = data.current_page;

            if (last_page !== current_page) {

                let allPromises = [];

                allPromises.push(
                    queue.add(
                        () => {
                            return data.data;
                        }
                    ));

                for (let i = 2; i <= last_page; i++) {
                    allPromises.push(
                        queue.add(
                            () => {
                                return getPage(url, i).then(({data}) => data);
                            }
                        )
                    );
                }

                return Promise.all(allPromises).then((results) => {

                    return [].concat(...results);
                });
            } else {
                return data.data;
            }
        });
}

