import {Checkbox as MantineCheckbox} from "@mantine/core";
import React, {ChangeEvent} from "react";
import classes from "./Checkbox.module.css";

export default function Checkbox(
    {
        checked,
        onChange,
    }: {
        checked: boolean,
        onChange: (checked: boolean, event: ChangeEvent<HTMLInputElement>) => void,
    }
): React.JSX.Element {
   return <MantineCheckbox
     classNames={{
       input: classes.checkbox,
       inner: classes.inner
     }}
     checked={checked}
     onChange={(e) => {onChange?.(e.currentTarget.checked, e)}}
   />;
}
