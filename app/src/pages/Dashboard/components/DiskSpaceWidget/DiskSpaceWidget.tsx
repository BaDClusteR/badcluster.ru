import {Progress, Text, Tooltip} from "@mantine/core";
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
  const tooltip = total > 0 ? `Использовано ${percent.toFixed(1)}%, свободно ${formatBytes(total - used)}` : undefined;

  return <Widget label="Место на ЖД">
    {tooltip && <Tooltip label={tooltip} target="#hd-space"/>}
    <div className={classes.body}>
      <Text component="span" classNames={{root: classes.value}}>
        {total > 0 ? `${formatBytes(used)} / ${formatBytes(total)}` : "—"}
      </Text>
      <Progress value={percent} color={severityColor(percent)} size="md" radius="xl" id="hd-space"/>
    </div>
  </Widget>;
}
