import {IconArchive} from "@tabler/icons-react";
import Widget from "../Widget";
import usePolledQuery from "../usePolledQuery";

type BackupStatusResponse = {
  lastBackupAt: number | null;
  success: boolean;
  sizeBytes: number;
  error: string;
};

const POLL_INTERVAL_MS = 60_000;

function formatAge(seconds: number): string {
  const days = Math.floor(seconds / 86_400);
  const hours = Math.floor(seconds / 3_600);
  const minutes = Math.floor(seconds / 60);

  if (days > 0) {
    return `${days}d ago`;
  }

  if (hours > 0) {
    return `${hours}h ago`;
  }

  if (minutes > 0) {
    return `${minutes}m ago`;
  }

  return "just now";
}

export default function LastBackupWidget() {
  const status = usePolledQuery<BackupStatusResponse>("backup_status", POLL_INTERVAL_MS);
  const lastBackupAt = status?.lastBackupAt ?? null;

  const title = lastBackupAt !== null
    ? new Date(lastBackupAt * 1000).toLocaleString(undefined, {hourCycle: "h23"})
    + (status?.success === false && status.error ? `\n${status.error}` : "")
    : undefined;

  return <Widget
    label="Последний бэкап"
    value={
      lastBackupAt !== null
        ? <span title={title}>
          {formatAge(Date.now() / 1000 - lastBackupAt)}
          {status?.success === false && " ⚠"}
        </span>
        : "—"
    }
    color={!status || status.success || lastBackupAt === null ? "blue" : "red"}
    icon={<IconArchive/>}
  />;
}
