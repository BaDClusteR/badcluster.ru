import {Button as MantineButton} from "@mantine/core";
import classes from "./Button.module.css";
import React from "react";
import clsx from "clsx";
import {StringKeyObject} from "@admin/types";

export default function Button(
  {
    leftSection,
    children,
    rightSection,
    className,
    classNames,
    onClick,
    loading,
    fullWidth,
    variant,
    disabled,
    color,
    type,
    ...props
  }: {
    leftSection?: React.ReactNode,
    rightSection?: React.ReactNode,
    children?: React.ReactNode,
    className?: string,
    classNames?: StringKeyObject,
    onClick?: React.MouseEventHandler<HTMLButtonElement>,
    loading?: boolean,
    fullWidth?: boolean,
    disabled?: boolean,
    variant?: "default" | "filled" | "subtle" | "outline" | "light" | "gradient" | "transparent" | "white",
    color?: string,
    props?: any,
    type?: "button" | "submit" | "reset",
  }
) {
  return <MantineButton
    disabled={disabled}
    variant={variant}
    leftSection={leftSection}
    rightSection={rightSection}
    className={clsx(classes.button, color && classes[`color${color}`], variant && classes[`variant${variant}`], className)}
    classNames={classNames}
    onClick={onClick}
    loading={loading}
    fullWidth={fullWidth}
    color={color}
    type={type}
    {...props}
  >
    {children}
  </MantineButton>;
}
