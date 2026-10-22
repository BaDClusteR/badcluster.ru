import {LogLevel} from "@/pages/LogRecords/types";

export interface LogRecord {
  id: number,
  level: LogLevel,
  message: string,
  context: string,
  trace: string,
  url: string,
  ip: string,
  requestId: string,
  occurrences: number,
  firstSeen: string,
  lastSeen: string
}
