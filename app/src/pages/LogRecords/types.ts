import type {EntityRow} from "@admin/types";

export type LogLevel = "WARNING" | "ERROR" | "CRITICAL" | "EMERGENCY";

export interface LogRecordRow extends EntityRow {
  level: LogLevel,
  message: string,
  date: string,
  occurrences: number
}
