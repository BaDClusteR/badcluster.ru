import React from "react";
export default function Slug({ defaultValue, value, url, onChange, ...rest }: {
    url: (slug: string) => string;
    onChange: (slug: string) => void;
    defaultValue?: string;
    value?: string;
    rest?: any;
}): React.JSX.Element;
