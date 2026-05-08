import React, { ReactNode } from "react";
export default function Modal({ opened, onClose, withCloseButton, title, children, ...props }: {
    opened: boolean;
    onClose: () => void;
    withCloseButton?: boolean;
    title?: ReactNode;
    children?: React.ReactNode;
}): React.JSX.Element;
