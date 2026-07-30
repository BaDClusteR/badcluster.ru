import {ReactNode} from "react";
import {Card, Group, Text, ThemeIcon} from "@mantine/core";
import classes from "./Widget.module.css";

export default function Widget(
  {
    label,
    value,
    color,
    icon,
    children,
    postfix
  }: {
    label: ReactNode,
    value?: ReactNode,
    color?: string,
    icon?: ReactNode,
    children?: ReactNode,
    postfix?: ReactNode
  }
) {
  return <Card classNames={{root: classes.cardRoot}}>
    {children
      ? <>
        <Text classNames={{root: classes.cardTitle}}>{label}</Text>
        <div className={classes.cardBody}>{children}</div>
      </>
      : <Group classNames={{root: classes.cardGroup}}>
        <div>
          <Text classNames={{root: classes.cardTitle}}>{label}</Text>
          <Text classNames={{root: classes.cardText}}>{value}</Text>
        </div>
        {
          icon &&
          <ThemeIcon
            classNames={{root: classes.cardIconWrapper}}
            style={{
              "--widget-color": `var(--mantine-color-${color}-light-color)`,
              "--widget-bg": `var(--mantine-color-${color}-light)`
            }}
          >
            {icon}
          </ThemeIcon>
        }
        {postfix}
      </Group>}
  </Card>;
}
