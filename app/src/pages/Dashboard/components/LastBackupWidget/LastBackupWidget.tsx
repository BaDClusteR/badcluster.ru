import {ReactNode} from "react";
import {Anchor, Tooltip} from "@mantine/core";
import {IconArchive} from "@tabler/icons-react";
import Widget from "../Widget";
import usePolledQuery from "../usePolledQuery";
import {formatBytes} from "../SystemMetricsWidget/format";
import classes from "./LastBackupWidget.module.css";

type BackupStatusResponse = {
  lastBackupAt: number | null;
  success: boolean;
  sizeBytes: number;
  archiveName: string;
  url: string;
  error: string;
};

const POLL_INTERVAL_MS = 60_000;

function getWordForm(count: number, firstForm: string, secondForm: string, thirdForm: string): string {
  const countMod100 = count % 100;
  if (countMod100 >= 10 && countMod100 <= 20) {
    return thirdForm;
  }

  const countMod10 = count % 10;
  switch (countMod10) {
    case 1:
      return firstForm;
    case 2:
    case 3:
    case 4:
      return secondForm;
    default:
      return thirdForm;
  }
}

function formatAge(seconds: number): string {
  const days = Math.floor(seconds / 86_400);
  const hours = Math.floor(seconds / 3_600);
  const minutes = Math.floor(seconds / 60);
  let result = "";

  if (days > 0) {
    result = `${days === 1 ? "" : `${days} `}${getWordForm(days, "день", "дня", "дней")} назад`;
  }

  if (!result && hours > 0) {
    result = `${hours === 1 ? "" : `${hours} `}${getWordForm(hours, "час", "часа", "часов")} назад`;
  }

  if (!result && minutes > 0) {
    result = `${minutes === 1 ? "" : `${minutes} `}${getWordForm(hours, "минуту", "минуты", "минут")} назад`;
  }

  if (!result) {
    result = "только что";
  }

  return result.charAt(0).toUpperCase() + result.slice(1);
}

export default function LastBackupWidget() {
  const status = usePolledQuery<BackupStatusResponse>("backup_status", POLL_INTERVAL_MS);
  const lastBackupAt = status?.lastBackupAt ?? null;

  let tooltip: ReactNode = null;
  if (lastBackupAt !== null) {
    tooltip = <>{new Date(lastBackupAt * 1000).toLocaleString(undefined, {hourCycle: "h23"})}</>;

    if (status?.sizeBytes && status.sizeBytes > 0) {
      tooltip = <>
        {tooltip}
        <span style={{opacity: .7, fontSize: ".8em", paddingLeft: "1.5ch"}}>
          {formatBytes(status.sizeBytes)}
        </span>
      </>;
    }

    if (status?.success === false && status.error) {
      tooltip = <>{tooltip}<br/>{status.error}</>;
    }
  }

  return <Widget
    label="Последний бэкап"
    value={
      lastBackupAt !== null
        ? <>
          <Tooltip label={tooltip} target="#backup-info"/>
          <span id="backup-info">
            {
              status?.url
                ? <Anchor
                  href={status.url}
                  target="_blank"
                  rel="noopener"
                  classNames={{root: classes.name}}
                >
                  {formatAge(Date.now() / 1000 - lastBackupAt)}
                  {status?.success === false && " ⚠"}
                </Anchor>
                : <>
                  {formatAge(Date.now() / 1000 - lastBackupAt)}
                  {status?.success === false && " ⚠"}
                </>
            }
          </span>
        </>
        : "—"
    }
    color={!status || status.success || lastBackupAt === null ? "blue" : "red"}
    icon={<IconArchive/>}
  />;
}
