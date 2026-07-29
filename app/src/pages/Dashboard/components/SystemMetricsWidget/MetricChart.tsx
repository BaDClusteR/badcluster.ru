import {PointerEvent, useMemo, useState} from "react";
import clsx from "clsx";
import classes from "./SystemMetricsWidget.module.css";
import {MetricSeriesDef, MetricSeriesKey, SystemMetricsPoint} from "./types";
import {niceCeil} from "./format";

// Сверху нужно место под подпись максимума над верхней гридлайной
const PAD_TOP = 16;
const PAD_BOTTOM = 20;
const PAD_RIGHT = 10;
const WINDOW_MS = 5 * 60_000;

// Разрыв в сэмплах больше этого порога (дашборд никто не смотрел) рисуем
// как дыру в линии, а не соединяем точки через весь пропуск
const GAP_MS = 5_000;

const GRID_FRACTIONS = [0, 0.25, 0.5, 0.75, 1];
const LABEL_FRACTIONS = [0.5, 1];

// Оценка ширины тултипа для флипа на другую сторону от курсора у правого края
const TOOLTIP_WIDTH = 210;

function buildSeriesPaths(
  points: SystemMetricsPoint[],
  key: MetricSeriesKey,
  toX: (t: number) => number,
  toY: (v: number) => number,
  baselineY: number
): { line: string, area: string } {
  let line = "";
  let area = "";
  let prev: SystemMetricsPoint | null = null;
  let prevX = 0;

  for (const point of points) {
    const x = toX(point.time);
    const y = toY(point[key]);

    if (!prev || point.time - prev.time > GAP_MS) {
      if (prev) {
        area += ` L${prevX} ${baselineY} Z`;
      }

      line += ` M${x} ${y}`;
      area += ` M${x} ${baselineY} L${x} ${y}`;
    } else {
      line += ` L${x} ${y}`;
      area += ` L${x} ${y}`;
    }

    prev = point;
    prevX = x;
  }

  if (prev) {
    area += ` L${prevX} ${baselineY} Z`;
  }

  return {line: line.trim(), area: area.trim()};
}

function formatTime(time: number, withSeconds = false): string {
  return new Date(time).toLocaleTimeString(undefined, {
    hour: "2-digit",
    minute: "2-digit",
    hourCycle: "h23",
    ...(withSeconds ? {second: "2-digit"} : {})
  });
}

export default function MetricChart(
  {
    points,
    series,
    width,
    height,
    maxValue,
    autoFloor = 1,
    niceDomain = niceCeil,
    formatValue,
    formatTick,
    tooltipExtra
  }: {
    points: SystemMetricsPoint[],
    series: MetricSeriesDef[],
    width: number,
    height: number,
    /** Фиксированный потолок оси Y (например, 100 для процентов); без него — автоскейл */
    maxValue?: number,
    /** Минимальный потолок при автоскейле, чтобы фоновый шум не растягивался на весь график */
    autoFloor?: number,
    niceDomain?: (max: number) => number,
    formatValue: (value: number) => string,
    formatTick?: (value: number) => string,
    tooltipExtra?: (key: MetricSeriesKey, point: SystemMetricsPoint) => string | null
  }
) {
  const [hoverIndex, setHoverIndex] = useState<number | null>(null);

  const tEnd = points[points.length - 1].time;
  const tStart = tEnd - WINDOW_MS;

  const visible = useMemo(
    () => points.filter((p) => p.time >= tStart),
    [points, tStart]
  );

  const domainMax = maxValue ?? niceDomain(
    Math.max(autoFloor, ...visible.flatMap((p) => series.map(({key}) => p[key])))
  );

  const plotWidth = width - PAD_RIGHT;
  const baselineY = height - PAD_BOTTOM;
  const plotHeight = baselineY - PAD_TOP;

  const toX = (t: number) => Math.round((plotWidth * (t - tStart)) / WINDOW_MS * 10) / 10;
  const toY = (v: number) => Math.round((PAD_TOP + plotHeight * (1 - Math.min(v, domainMax) / domainMax)) * 10) / 10;

  const paths = useMemo(
    () => Object.fromEntries(
      series.map(({key}) => [key, buildSeriesPaths(visible, key, toX, toY, baselineY)])
    ),
    [visible, series, width, height, domainMax]
  );

  const minuteTicks = useMemo(() => {
    const ticks: { t: number, x: number }[] = [];

    for (let t = Math.ceil(tStart / 60_000) * 60_000; t <= tEnd; t += 60_000) {
      const x = toX(t);

      if (x >= 18 && x <= plotWidth - 18) {
        ticks.push({t, x});
      }
    }

    return ticks;
  }, [tStart, tEnd, width]);

  const handlePointerMove = (e: PointerEvent<SVGSVGElement>) => {
    const rect = e.currentTarget.getBoundingClientRect();
    const t = tStart + ((e.clientX - rect.left) / plotWidth) * WINDOW_MS;
    let nearest = 0;

    for (let i = 1; i < visible.length; i++) {
      if (Math.abs(visible[i].time - t) < Math.abs(visible[nearest].time - t)) {
        nearest = i;
      }
    }

    setHoverIndex(nearest);
  };

  const lastPoint = visible[visible.length - 1];
  const hovered = hoverIndex !== null ? visible[hoverIndex] : null;
  const hoveredX = hovered ? toX(hovered.time) : 0;
  const tooltipLeft = hovered
    ? (hoveredX + 12 + TOOLTIP_WIDTH > width ? hoveredX - TOOLTIP_WIDTH - 12 : hoveredX + 12)
    : 0;

  const seriesNames = series.map(({name}) => name).join(" and ");

  return <>
    <svg
      className={classes.svg}
      width={width}
      height={height}
      role="img"
      aria-label={`${seriesNames} over the last 5 minutes`}
      tabIndex={0}
      onPointerMove={handlePointerMove}
      onPointerLeave={() => setHoverIndex(null)}
      onFocus={() => setHoverIndex(visible.length - 1)}
      onBlur={() => setHoverIndex(null)}
    >
      {GRID_FRACTIONS.map((fraction) => (
        <line
          key={fraction}
          className={clsx(classes.gridline, fraction === 0 && classes.baseline)}
          x1={0}
          x2={plotWidth}
          y1={toY(domainMax * fraction)}
          y2={toY(domainMax * fraction)}
        />
      ))}
      {LABEL_FRACTIONS.map((fraction) => (
        <text
          key={fraction}
          className={classes.axisLabel}
          x={2}
          y={toY(domainMax * fraction) - 4}
        >
          {(formatTick ?? formatValue)(domainMax * fraction)}
        </text>
      ))}
      {minuteTicks.map(({t, x}) => (
        <text
          key={t}
          className={classes.axisLabel}
          x={x}
          y={height - 5}
          textAnchor="middle"
        >
          {formatTime(t)}
        </text>
      ))}
      {/* Сначала обе заливки, потом обе линии — иначе заливка второй серии приглушает линию первой */}
      {series.map(({key, slot}) => (
        <path
          key={key}
          className={clsx(classes.area, slot === "a" ? classes.areaA : classes.areaB)}
          d={paths[key].area}
        />
      ))}
      {series.map(({key, slot}) => (
        <path
          key={key}
          className={clsx(classes.line, slot === "a" ? classes.lineA : classes.lineB)}
          d={paths[key].line}
        />
      ))}
      {hovered && hovered !== lastPoint && (
        <line
          className={classes.crosshair}
          x1={hoveredX}
          x2={hoveredX}
          y1={PAD_TOP}
          y2={baselineY}
        />
      )}
      {series.map(({key, slot}) => (
        <circle
          key={key}
          className={clsx(classes.marker, slot === "a" ? classes.markerA : classes.markerB)}
          cx={toX(lastPoint.time)}
          cy={toY(lastPoint[key])}
          r={4}
        />
      ))}
      {hovered && hovered !== lastPoint && series.map(({key, slot}) => (
        <circle
          key={key}
          className={clsx(classes.marker, slot === "a" ? classes.markerA : classes.markerB)}
          cx={hoveredX}
          cy={toY(hovered[key])}
          r={3.5}
        />
      ))}
    </svg>
    {hovered && (
      <div className={classes.tooltip} style={{left: tooltipLeft, top: PAD_TOP}}>
        <div className={classes.tooltipTime}>{formatTime(hovered.time, true)}</div>
        {series.map(({key, name, slot}) => (
          <div key={key} className={classes.tooltipRow}>
            <span
              className={clsx(classes.legendKey, slot === "a" ? classes.legendKeyA : classes.legendKeyB)}
            />
            <span className={classes.tooltipValue}>{formatValue(hovered[key])}</span>
            <span className={classes.tooltipLabel}>{name}</span>
            {
              tooltipExtra?.(key, hovered) &&
              <span className={classes.tooltipBytes}>{tooltipExtra(key, hovered)}</span>
            }
          </div>
        ))}
      </div>
    )}
  </>;
}
