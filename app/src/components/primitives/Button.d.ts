import React from "react";
export default function Button({ leftSection, children, rightSection, className, onClick, loading, fullWidth, variant, disabled, color, type, ...props }: {
    leftSection?: React.ReactNode;
    rightSection?: React.ReactNode;
    children?: React.ReactNode;
    className?: string;
    onClick?: React.MouseEventHandler<HTMLButtonElement>;
    loading?: boolean;
    fullWidth?: boolean;
    disabled?: boolean;
    variant?: "default" | "filled" | "subtle" | "outline" | "light" | "gradient" | "transparent" | "white";
    color?: string;
    props?: any;
    type?: "button" | "submit" | "reset";
}): import("react/jsx-runtime").JSX.Element;
