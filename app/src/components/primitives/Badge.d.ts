import React from "react";
export default function Badge({ className, type, children }: {
    className?: string;
    type: 'success' | 'info';
    children?: React.ReactNode;
}): React.JSX.Element;
export declare function BadgeGreen({ className, children }: {
    className?: string;
    children?: React.ReactNode;
}): React.JSX.Element;
export declare function BadgeGray({ className, children }: {
    className?: string;
    children?: React.ReactNode;
}): React.JSX.Element;
