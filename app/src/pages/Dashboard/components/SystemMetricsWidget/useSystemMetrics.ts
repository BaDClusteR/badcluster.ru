import usePolledQuery from "../usePolledQuery";
import {SystemMetricsResponse} from "./types";

const POLL_INTERVAL_MS = 1_500;

/**
 * Все виджеты метрик используют один endpoint, поэтому три графика
 * и аптайм на дашборде поллят его одним общим запросом.
 */
export default function useSystemMetrics(): SystemMetricsResponse | undefined {
    return usePolledQuery<SystemMetricsResponse>("system_metrics", POLL_INTERVAL_MS);
}
