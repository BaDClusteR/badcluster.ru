import {Badge, Text} from "@mantine/core";
import {useElementSize} from "@mantine/hooks";
import clsx from "clsx";
import Widget from "../Widget";
import MetricChart from "./MetricChart";
import useSystemMetrics from "./useSystemMetrics";
import {formatBytes, niceCeilBytes} from "./format";
import classes from "./SystemMetricsWidget.module.css";
import {MetricSeriesDef, MetricSeriesKey, SystemMetricsPoint, SystemMetricsResponse} from "./types";

export type SystemMetricsWidgetKind = "load" | "disk" | "net";

const formatPercent = (v: number) => `${v.toFixed(1)}%`;
const formatPercentTick = (v: number) => `${Math.round(v)}%`;
const formatOps = (v: number) => `${v >= 100 ? Math.round(v) : v.toFixed(1)} op/s`;
const formatOpsTick = (v: number) => `${Math.round(v)} op/s`;
const formatRate = (v: number) => `${formatBytes(v)}/s`;

type WidgetConfig = {
  label: string;
  series: MetricSeriesDef[];
  maxValue?: number;
  autoFloor?: number;
  niceDomain?: (max: number) => number;
  formatValue: (value: number) => string;
  formatTick?: (value: number) => string;
  buildTooltipExtra?: (metrics: SystemMetricsResponse) =>
    (key: MetricSeriesKey, point: SystemMetricsPoint) => string | null;
};

export const WIDGET_CONFIGS: Record<SystemMetricsWidgetKind, WidgetConfig> = {
  load: {
    label: "Загрузка системы",
    series: [
      {key: "cpu", name: "CPU", slot: "a"},
      {key: "ram", name: "RAM", slot: "b"}
    ],
    maxValue: 100,
    formatValue: formatPercent,
    formatTick: formatPercentTick,
    buildTooltipExtra: (metrics) => (key, point) =>
      key === "ram" && metrics.ramTotalBytes > 0
        ? `${formatBytes(point.ram / 100 * metrics.ramTotalBytes)} / ${formatBytes(metrics.ramTotalBytes)}`
        : null
  },
  disk: {
    label: "Дисковая активность",
    series: [
      {key: "ioRead", name: "Read", slot: "a"},
      {key: "ioWrite", name: "Write", slot: "b"}
    ],
    autoFloor: 20,
    formatValue: formatOps,
    formatTick: formatOpsTick
  },
  net: {
    label: "Сетевая активность",
    series: [
      {key: "netIn", name: "In", slot: "a"},
      {key: "netOut", name: "Out", slot: "b"}
    ],
    autoFloor: 16 * 1024,
    niceDomain: niceCeilBytes,
    formatValue: formatRate
  }
};

export default function SystemMetricsWidget({kind}: { kind: SystemMetricsWidgetKind }) {
  const config = WIDGET_CONFIGS[kind];
  const {ref, width, height} = useElementSize<HTMLDivElement>();
  const metrics = useSystemMetrics();

  const points = metrics?.points ?? [];
  const last = points.length ? points[points.length - 1] : null;
  const tooltipExtra = metrics && config.buildTooltipExtra
    ? config.buildTooltipExtra(metrics)
    : undefined;

  return (
    <Widget label={config.label}>
      <div className={classes.root}>
        <div className={classes.legend}>
          {config.series.map(({key, name, slot}) => (
            <span key={key} className={classes.legendItem}>
              <span
                className={clsx(classes.legendKey, slot === "a" ? classes.legendKeyA : classes.legendKeyB)}
              />
              <Text component="span" classNames={{root: classes.legendLabel}}>{name}</Text>
              <Text component="span" classNames={{root: classes.legendValue}}>
                {last ? config.formatValue(last[key]) : "—"}
              </Text>
            </span>
          ))}
          {
            metrics?.source === "fake" &&
            <Badge size="xs" variant="light" color="gray">demo</Badge>
          }
        </div>
        <div ref={ref} className={classes.plot}>
          {points.length >= 2 && width > 0 && height > 0
            ? <MetricChart
              points={points}
              series={config.series}
              width={width}
              height={height}
              maxValue={config.maxValue}
              autoFloor={config.autoFloor}
              niceDomain={config.niceDomain}
              formatValue={config.formatValue}
              formatTick={config.formatTick}
              tooltipExtra={tooltipExtra}
            />
            : <Text classNames={{root: classes.placeholder}}>Collecting data…</Text>}
        </div>
      </div>
    </Widget>
  );
}
