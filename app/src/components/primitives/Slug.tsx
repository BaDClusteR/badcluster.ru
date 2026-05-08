import React, {useEffect, useState} from "react";
import {TextInput} from "@mantine/core";
import classes from "./Slug.module.css";

export default function Slug(
  {
    defaultValue,
    value,
    url,
    onChange,
    ...rest
  }: {
    url: (slug: string) => string,
    onChange: (slug: string) => void,
    defaultValue?: string,
    value?: string,
    rest?: any
  }
): React.JSX.Element {
  const [currentUrl, setCurrentUrl] = useState<string>(url(value ?? ''));
  const [currentSlug, setCurrentSlug] = useState<string>(value ?? '');

  useEffect(() => {
    const slug = defaultValue ?? value ?? '';
    setCurrentUrl(url(slug));
    setCurrentSlug(slug);
  }, [defaultValue, value]);

  return <>
    <TextInput defaultValue={defaultValue} onChange={(e) => {
      const slug = e.target.value;
      onChange(slug);
      setCurrentUrl(url(slug));
      setCurrentSlug(slug);
    }} {...rest} />
    <span className={classes.url}>{currentSlug ? currentUrl : " "}</span>
  </>;
}
