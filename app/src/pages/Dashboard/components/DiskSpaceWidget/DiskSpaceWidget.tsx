import {Progress, Text} from "@mantine/core";
import Widget from "../Widget";
import useSystemMetrics from "../SystemMetricsWidget/useSystemMetrics";
import {formatBytes} from "../SystemMetricsWidget/format";
import classes from "./DiskSpaceWidget.module.css";

function severityColor(percent: number): string {
  if (percent >= 90) {
    return "red";
  }

  if (percent >= 75) {
    return "yellow";
  }

  return "green";
}

export default function DiskSpaceWidget() {
  const metrics = useSystemMetrics();
  const total = metrics?.diskTotalBytes ?? 0;
  const used = metrics?.diskUsedBytes ?? 0;
  const percent = total > 0 ? 100 * used / total : 0;

  return <Widget label="Место на ЖД">
    <div
      className={classes.body}
      title={total > 0 ? `${percent.toFixed(1)}% used, ${formatBytes(total - used)} free` : undefined}
    >
      <Text component="span" classNames={{root: classes.value}}>
        {total > 0 ? `${formatBytes(used)} / ${formatBytes(total)}` : "—"}
      </Text>
      <Progress value={percent} color={severityColor(percent)} size="md" radius="xl"/>
    </div>
  </Widget>;
}
