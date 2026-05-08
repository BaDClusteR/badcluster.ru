import React, { ChangeEvent } from "react";
export default function Checkbox({ checked, onChange, }: {
    checked: boolean;
    onChange: (checked: boolean, event: ChangeEvent<HTMLInputElement>) => void;
}): React.JSX.Element;
