import React, {useEffect, useRef, useState} from "react";
import {Anchor, TextInput} from "@mantine/core";
import classes from "./Slug.module.css";

const VALID_SLUG = /^[a-z0-9_-]*$/;

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
  const savedSlug = useRef<string | undefined>(undefined);
  const initialized = useRef(false);
  const userEdited = useRef(false);

  const [currentSlug, setCurrentSlug] = useState<string>("");
  const [currentUrl, setCurrentUrl] = useState<string>("");
  const [validationError, setValidationError] = useState<string | null>(null);

  // Initialize/reset when form data arrives or after save.
  useEffect(() => {
    const slug = defaultValue ?? value ?? "";
    if (!slug) return;

    if (!initialized.current || (savedSlug.current !== undefined && savedSlug.current !== slug && slug !== currentSlug)) {
      savedSlug.current = slug;
      userEdited.current = false;
      initialized.current = true;
    }

    setCurrentSlug(slug);
    setCurrentUrl(url(slug));
    setValidationError(null);
  }, [defaultValue, value]);

  // Recalculate URL when context/values change.
  useEffect(() => {
    if (!currentSlug) return;
    const newUrl = url(currentSlug);
    if (newUrl !== currentUrl) {
      setCurrentUrl(newUrl);
    }
  });

  // Show link when user hasn't edited the slug (it's still the backend value).
  const isOriginal = currentSlug !== "" && !userEdited.current;

  return <>
    <TextInput
      defaultValue={defaultValue}
      {...rest}
      error={validationError || (rest as any).error}
      onChange={(e) => {
        const slug = e.target.value;
        onChange(slug);
        setCurrentSlug(slug);
        setCurrentUrl(url(slug));
        userEdited.current = slug !== savedSlug.current;
        setValidationError(
          slug && !VALID_SLUG.test(slug)
            ? "Только латиница, цифры, дефис и подчёркивание"
            : null
        );
      }}
    />
    {!validationError && currentSlug ? (
      isOriginal
        ? <Anchor href={currentUrl} target="_blank" className={classes.url}>{currentUrl}</Anchor>
        : <span className={classes.url}>{currentUrl}</span>
    ) : (
      <span className={classes.url}>{" "}</span>
    )}
  </>;
}