import React from "react";
import {Badge as MantineBadge} from "@mantine/core";
import classes from "./Badge.module.css";
import clsx from "clsx";

export default function Badge(
  {
    className = '',
    type,
    children
  }: {
    className?: string;
    type: 'success' | 'info' | 'warning' | 'danger',
    children?: React.ReactNode
  }
): React.JSX.Element {
  let color;

  switch (type) {
    case "success":
      color = 'teal';
      break;
    case 'warning':
      color = 'yellow';
      break;
    case 'danger':
      color = 'red';
      break;
    default:
      color = 'gray';
  }

  return <MantineBadge
    className={clsx(className, classes.badgeContainer, classes[`color${color}`])}
    color={color}
    variant="light"
    classNames={{label: classes.badge}}
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

export function BadgeRed(
  {
    className = '',
    children
  }: {
    className?: string,
    children?: React.ReactNode
  }
): React.JSX.Element {
  return <Badge type="danger" className={className}>
    {children}
  </Badge>
}

export function BadgeYellow(
  {
    className = '',
    children
  }: {
    className?: string,
    children?: React.ReactNode
  }
): React.JSX.Element {
  return <Badge type="warning" className={className}>
    {children}
  </Badge>
}
