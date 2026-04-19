import {Button as MantineButton} from "@mantine/core";
import classes from "./Button.module.css";
import React from "react";
import clsx from "clsx";

export default function Button(
  {
    leftSection,
    children,
    rightSection,
    className,
    onClick,
    loading,
    fullWidth,
    variant,
    disabled,
    color,
    ...props
  }: {
    leftSection?: React.ReactNode,
    rightSection?: React.ReactNode,
    children?: React.ReactNode,
    className?: string,
    onClick?: React.MouseEventHandler<HTMLButtonElement>,
    loading?: boolean,
    fullWidth?: boolean,
    disabled?: boolean,
    variant?: "default" | "filled" | "subtle" | "outline" | "light" | "gradient" | "transparent" | "white",
    color?: string,
    props?: any
  }
) {
  return <MantineButton
    disabled={disabled}
    variant={variant}
    leftSection={leftSection}
    rightSection={rightSection}
    className={clsx(classes.button, color && classes[`color${color}`], variant && classes[`variant${variant}`], className)}
    onClick={onClick}
    loading={loading}
    fullWidth={fullWidth}
    color={color}
    {...props}
  >
    {children}
  </MantineButton>
}
