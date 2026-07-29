import {ReactNode} from "react";
import {Grid, Title} from "@mantine/core";
import {IconUsers, IconFiles, IconEye, IconActivity} from "@tabler/icons-react";
import clsx from "clsx";
import classes from "./Dashboard.module.css";
import Widget from "./components/Widget";
import SystemMetricsWidget from "./components/SystemMetricsWidget";

const stats: { label: string, value: string, icon: ReactNode, color: string }[] = [
  {label: "Users", value: "—", icon: <IconUsers/>, color: "indigo"},
  {label: "Pages", value: "—", icon: <IconFiles/>, color: "teal"},
  {label: "Views today", value: "—", icon: <IconEye/>, color: "orange"},
  {label: "Uptime", value: "—", icon: <IconActivity/>, color: "green"}
];

export function DashboardPage() {
  return (
    <>
      <Title order={2} mb="lg">
        Dashboard
      </Title>

      <Grid classNames={{root: classes.gridRoot, inner: classes.gridInner}}>
        {stats.map((s) => (
          <Grid.Col key={s.label} span={{base: 12}}>
            <Widget label={s.label} value={s.value} color={s.color} icon={s.icon}/>
          </Grid.Col>
        ))}
        {(["load", "disk", "net"] as const).map((kind) => (
          <Grid.Col key={kind} span={{base: 12}} className={clsx(classes.colSpan2, classes.rowSpan2)}>
            <SystemMetricsWidget kind={kind}/>
          </Grid.Col>
        ))}
      </Grid>
    </>
  );
}
