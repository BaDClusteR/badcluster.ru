import {Text} from "@mantine/core";
import {IconCircleCheck} from "@tabler/icons-react";
import {Link} from "react-router";
import clsx from "clsx";
import Widget from "../Widget";
import usePolledQuery from "../usePolledQuery";
import {buildAdminUrl} from "@/utils/buildAdminUrl";
import classes from "./AttentionWidget.module.css";

type AttentionItem = {
  message: string;
  severity: "info" | "warning" | "error";
  adminPath: string;
};

type AttentionResponse = {
  items: AttentionItem[];
};

const POLL_INTERVAL_MS = 60_000;

const DOT_CLASSES: Record<AttentionItem["severity"], string> = {
  error: classes.dotError,
  warning: classes.dotWarning,
  info: classes.dotInfo
};

export default function AttentionWidget() {
  const data = usePolledQuery<AttentionResponse>("attention", POLL_INTERVAL_MS);
  const items = data?.items ?? [];

  return <Widget label="Требует внимания">
    <div className={classes.body}>
      {!data && <Text classNames={{root: classes.empty}}>—</Text>}
      {
        data && items.length === 0 &&
        <div className={classes.calm}>
          <IconCircleCheck size={18}/>
          <Text component="span" classNames={{root: classes.calmText}}>Всё спокойно</Text>
        </div>
      }
      {items.map((item) => {
        const content = <>
          <span className={clsx(classes.dot, DOT_CLASSES[item.severity])}/>
          {item.message}
        </>;

        return item.adminPath
          ? <Link key={item.message} to={buildAdminUrl(item.adminPath)} className={classes.row}>
            {content}
          </Link>
          : <div key={item.message} className={classes.row}>
            {content}
          </div>;
      })}
    </div>
  </Widget>;
}
