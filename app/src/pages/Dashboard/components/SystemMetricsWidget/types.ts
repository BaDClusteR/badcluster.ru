export type SystemMetricsPoint = {
    time: number;
    cpu: number;
    ram: number;
    ioRead: number;
    ioWrite: number;
    netIn: number;
    netOut: number;
};

export type SystemMetricsResponse = {
    points: SystemMetricsPoint[];
    cpuCores: number;
    ramTotalBytes: number;
    source: "proc" | "fake";
};

export type MetricSeriesKey = keyof Omit<SystemMetricsPoint, "time">;

export type MetricSeriesDef = {
    key: MetricSeriesKey;
    name: string;
    /** Цветовой слот: a — синий, b — оранжевый */
    slot: "a" | "b";
};
