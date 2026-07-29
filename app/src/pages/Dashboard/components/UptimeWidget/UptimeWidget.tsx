import {IconActivity} from "@tabler/icons-react";
import Widget from "../Widget";
import useSystemMetrics from "../SystemMetricsWidget/useSystemMetrics";

function formatUptime(seconds: number): string {
  const days = Math.floor(seconds / 86_400);
  const hours = Math.floor(seconds % 86_400 / 3_600);
  const minutes = Math.floor(seconds % 3_600 / 60);

  if (days > 0) {
    return `${days}d ${hours}h`;
  }

  if (hours > 0) {
    return `${hours}h ${minutes}m`;
  }

  return `${minutes}m`;
}

export default function UptimeWidget() {
  const metrics = useSystemMetrics();

  return <Widget
    label="Аптайм"
    value={metrics ? formatUptime(metrics.uptimeSeconds) : "—"}
    color="green"
    icon={<IconActivity/>}
  />;
}
