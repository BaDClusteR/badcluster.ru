import classes from './Modal.module.css';
import React, {ReactNode} from "react";
import {Modal as MantineModal} from "@mantine/core";

export default function Modal(
  {
    opened,
    onClose,
    withCloseButton,
    title,
    children,
    ...props
  }: {
    opened: boolean,
    onClose: () => void,
    withCloseButton?: boolean,
    title?: ReactNode,
    children?: React.ReactNode
  }
): React.JSX.Element {
  return <MantineModal
    opened={opened}
    onClose={onClose}
    withCloseButton={withCloseButton}
    title={title}
    classNames={{
      header: classes.header,
      title: classes.title
    }}
    {...props}
  >
    {children}
  </MantineModal>
}
