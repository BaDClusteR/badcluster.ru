import {Button as MantineButton} from "@mantine/core";
import classes from "./Button.module.css";
import React from "react";

export default function Button(
  {
    leftSection,
    children,
    rightSection,
    className,
    onClick,
    ...props
  }: {
    leftSection?: React.ReactNode,
    rightSection?: React.ReactNode,
    children?: React.ReactNode,
    className?: string,
    onClick?: React.MouseEventHandler<HTMLButtonElement>,
  }
) {
  return <MantineButton
    leftSection={leftSection}
    rightSection={rightSection}
    className={classes.button}
    onClick={onClick}
    {...props}
  >
    {children}
  </MantineButton>
}
