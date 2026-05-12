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
  // The "original" slug from the backend — set once on initial load,
  // not updated on every keystroke.
  const savedSlug = useRef<string | undefined>(undefined);
  const initialized = useRef(false);

  const [currentSlug, setCurrentSlug] = useState<string>('');
  const [currentUrl, setCurrentUrl] = useState<string>('');
  const [validationError, setValidationError] = useState<string | null>(null);

  // Initialize/reset when form data arrives (form.initialize() updates defaultValue).
  // We only capture savedSlug on the FIRST non-empty value, or when it changes
  // from a previously saved value (e.g. navigating to a different entity).
  useEffect(() => {
    const slug = defaultValue ?? value ?? '';
    if (!slug) return;

    // First load, or navigated to a different entity
    if (!initialized.current || (savedSlug.current !== undefined && savedSlug.current !== slug && slug !== currentSlug)) {
      savedSlug.current = slug;
      initialized.current = true;
    }

    setCurrentSlug(slug);
    setCurrentUrl(url(slug));
    setValidationError(null);
  }, [defaultValue, value]);

  const isOriginal = currentSlug !== '' && currentSlug === savedSlug.current;

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
        setValidationError(
          slug && !VALID_SLUG.test(slug)
            ? 'Только латиница, цифры, дефис и подчёркивание'
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
