import React from "react";
import {Badge as MantineBadge} from "@mantine/core";
import clsx from "clsx";
import classes from "./Badge.module.css";

export default function Badge(
  {
    className = '',
    type,
    children
  }: {
    className?: string;
    type: 'success' | 'info',
    children?: React.ReactNode
  }
): React.JSX.Element {
  return <MantineBadge
    className={clsx(className, classes.badge)}
    color={type === 'success' ? 'teal' : 'gray'}
    variant="light"
  >
    {children}
  </MantineBadge>
}

export function BadgeGreen(
  {
    className = '',
    children
  }: {
    className?: string,
    children?: React.ReactNode
  }
): React.JSX.Element {
  return <Badge type="success" className={className}>
    {children}
  </Badge>
}

export function BadgeGray(
  {
    className = '',
    children
  }: {
    className?: string,
    children?: React.ReactNode
  }
): React.JSX.Element {
  return <Badge type="info" className={className}>
    {children}
  </Badge>
}
