import {useQuery} from "@tanstack/react-query";
import apiCall from "@/utils/apiCall";
import {HttpError} from "@/utils/errors";

const ERROR_POLL_INTERVAL_MS = 15_000;

/**
 * Поллинг admin API для дашборда: одинаковый endpoint = один общий запрос
 * (react-query дедуплицирует по queryKey), при 401 поллинг останавливается,
 * при прочих ошибках отходит на паузу, сетевые сбои не спамят тостами.
 */
export default function usePolledQuery<T>(endpoint: string, intervalMs: number): T | undefined {
    const {data} = useQuery({
        queryKey: [endpoint],
        retry: false,
        refetchInterval: (query) => {
            const error = query.state.error;

            // Авторизация протухла — прекращаем поллинг, иначе предупреждение
            // о логине будет всплывать бесконечно
            if (error instanceof HttpError && error.status === 401) {
                return false;
            }

            return error ? Math.max(intervalMs, ERROR_POLL_INTERVAL_MS) : intervalMs;
        },
        queryFn: async ({signal}) => {
            try {
                return await apiCall("GET", endpoint, {}, {signal});
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

    return data as T | undefined;
}
