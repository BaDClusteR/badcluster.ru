import {useQuery} from "@tanstack/react-query";
import apiCall from "@/utils/apiCall";
import {HttpError} from "@/utils/errors";
import {SystemMetricsResponse} from "./types";

const POLL_INTERVAL_MS = 1_500;
const ERROR_POLL_INTERVAL_MS = 15_000;

/**
 * Один queryKey на все виджеты метрик: react-query дедуплицирует запросы,
 * так что три графика на дашборде поллят эндпоинт одним запросом.
 */
export default function useSystemMetrics(): SystemMetricsResponse | undefined {
    const {data} = useQuery({
        queryKey: ["system_metrics"],
        retry: false,
        refetchInterval: (query) => {
            const error = query.state.error;

            // Авторизация протухла — прекращаем поллинг, иначе предупреждение
            // о логине будет всплывать бесконечно
            if (error instanceof HttpError && error.status === 401) {
                return false;
            }

            return error ? ERROR_POLL_INTERVAL_MS : POLL_INTERVAL_MS;
        },
        queryFn: async ({signal}) => {
            try {
                return await apiCall("GET", "system_metrics", {}, {signal});
            } catch (e) {
                if (e instanceof HttpError) {
                    throw e;
                }

                // Сетевые сбои заворачиваем в HttpError: глобальный обработчик их
                // игнорирует, и каждый неудачный поллинг не превращается в тост
                throw new HttpError(0, null, e instanceof Error ? e.message : String(e));
            }
        }
    });

    return data as SystemMetricsResponse | undefined;
}
