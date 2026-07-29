import {Grid, Title} from "@mantine/core";
import clsx from "clsx";
import classes from "./Dashboard.module.css";
import SystemMetricsWidget from "./components/SystemMetricsWidget";
import UptimeWidget from "./components/UptimeWidget";
import LastBackupWidget from "./components/LastBackupWidget";
import DiskSpaceWidget from "./components/DiskSpaceWidget";
import AttentionWidget from "./components/AttentionWidget";

export function DashboardPage() {
  return (
    <>
      <Title order={2} mb="lg">
        Дашборд
      </Title>

      <Grid classNames={{root: classes.gridRoot, inner: classes.gridInner}}>
        <Grid.Col span={{base: 12}}>
          <DiskSpaceWidget/>
        </Grid.Col>
        <Grid.Col span={{base: 12}}>
          <AttentionWidget/>
        </Grid.Col>
        <Grid.Col key="load" span={{base: 12}} className={clsx(classes.colSpan2, classes.rowSpan2)}>
          <SystemMetricsWidget kind="load"/>
        </Grid.Col>
        <Grid.Col span={{base: 12}}>
          <UptimeWidget/>
        </Grid.Col>
        <Grid.Col span={{base: 12}}>
          <LastBackupWidget/>
        </Grid.Col>
        {(["disk", "net"] as const).map((kind) => (
          <Grid.Col key={kind} span={{base: 12}} className={clsx(classes.colSpan2, classes.rowSpan2)}>
            <SystemMetricsWidget kind={kind}/>
          </Grid.Col>
        ))}
      </Grid>
    </>
  );
}
